<?php


namespace Core\Services;

use \Core\ORM\Entity;
use \Core\Core\Entities\Person;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\NotFound;


class EmailTemplate extends Record
{

    protected function init()
    {
        parent::init();

        $this->addDependency('fileStorageManager');
        $this->addDependency('dateTime');
        $this->addDependency('language');
    }

    protected function getFileStorageManager()
    {
        return $this->injections['fileStorageManager'];
    }

    protected function getDateTime()
    {
        return $this->injections['dateTime'];
    }

    protected function getLanguage()
    {
        return $this->getInjection('language');
    }

    public function parseTemplate(Entity $emailTemplate, array $params = array(), $copyAttachments = false)
    {
        $entityHash = array();
        if (!empty($params['entityHash']) && is_array($params['entityHash'])) {
            $entityHash = $params['entityHash'];
        }

        if (!isset($entityHash['User'])) {
            $entityHash['User'] = $this->getUser();
        }

        if (!empty($params['emailAddress'])) {
            $emailAddress = $this->getEntityManager()->getRepository('EmailAddress')->where(array(
                'lower' => $params['emailAddress']
            ))->findOne();

            $entity = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($params['emailAddress']);

            if ($entity) {
                if ($entity instanceof Person) {
                    $entityHash['Person'] = $entity;
                }
                if (empty($entityHash[$entity->getEntityType()])) {
                    $entityHash[$entity->getEntityType()] = $entity;
                }
            }
        }

        if (empty($params['parent'])) {
            if (!empty($params['parentId']) && !empty($params['parentType'])) {
                $parent = $this->getEntityManager()->getEntity($params['parentType'], $params['parentId']);
                if ($parent) {
                    $params['parent'] = $parent;
                }
            }
        }

        if (!empty($params['parent'])) {
            $parent = $params['parent'];
            $entityHash[$parent->getEntityType()] = $parent;
            $entityHash['Parent'] = $parent;

            if (empty($entityHash['Person']) && ($parent instanceof Person)) {
                $entityHash['Person'] = $parent;
            }
        }

        if (!empty($params['relatedId']) && !empty($params['relatedType'])) {
            $related = $this->getEntityManager()->getEntity($params['relatedType'], $params['relatedId']);
            if ($related) {
                $entityHash[$related->getEntityType()] = $related;
            }
        }

        $subject = $emailTemplate->get('subject');
        $body = $emailTemplate->get('body');

        foreach ($entityHash as $type => $entity) {
            $subject = $this->parseText($type, $entity, $subject);
        }
        foreach ($entityHash as $type => $entity) {
            $body = $this->parseText($type, $entity, $body);
        }

        $attachmentsIds = array();
        $attachmentsNames = new \StdClass();

        if ($copyAttachments) {
            $attachmentList = $emailTemplate->get('attachments');
            if (!empty($attachmentList)) {
                foreach ($attachmentList as $attachment) {
                    $clone = $this->getEntityManager()->getEntity('Attachment');
                    $data = $attachment->toArray();
                    unset($data['parentType']);
                    unset($data['parentId']);
                    unset($data['id']);
                    $clone->set($data);
                    $clone->set('sourceId', $attachment->getSourceId());
                    $clone->set('storage', $attachment->get('storage'));

                    if (!$this->getFileStorageManager()->isFile($attachment)) {
                        continue;
                    }
                    $this->getEntityManager()->saveEntity($clone);

                    $attachmentsIds[] = $id = $clone->id;
                    $attachmentsNames->$id = $clone->get('name');
                }
            }
        }

        return array(
            'subject' => $subject,
            'body' => $body,
            'attachmentsIds' => $attachmentsIds,
            'attachmentsNames' => $attachmentsNames,
            'isHtml' => $emailTemplate->get('isHtml')
        );
    }

    public function parse($id, array $params = array(), $copyAttachments = false)
    {
        $emailTemplate = $this->getEntity($id);
        if (empty($emailTemplate)) {
            throw new NotFound();
        }

        return $this->parseTemplate($emailTemplate, $params, $copyAttachments);
    }

    protected function parseText($type, Entity $entity, $text, $skipLinks = false, $prefixLink = null)
    {
        $fieldList = array_keys($entity->getAttributes());

        $forbidenAttributeList = $this->getAcl()->getScopeForbiddenAttributeList($entity->getEntityType(), 'read');

        foreach ($fieldList as $field) {
            if (in_array($field, $forbidenAttributeList)) continue;

            $value = $entity->get($field);
            if (is_object($value)) {
                continue;
            }

            $fieldType = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() .'.fields.' . $field . '.type');

            if ($fieldType === 'enum') {
                $value = $this->getLanguage()->translateOption($value, $field, $entity->getEntityType());
            } else if ($fieldType === 'array' || $fieldType === 'multiEnum') {
                $valueList = [];
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $valueList[] = $this->getLanguage()->translateOption($v, $field, $entity->getEntityType());
                    }
                }
                $value = implode(', ', $valueList);
                $value = $this->getLanguage()->translateOption($value, $field, $entity->getEntityType());
            } else {
                if (!isset($entity->fields[$field]['type'])) continue;
                $attributeType = $entity->fields[$field]['type'];

                if ($attributeType == 'date') {
                    $value = $this->getDateTime()->convertSystemDate($value);
                } else if ($attributeType == 'datetime') {
                    $value = $this->getDateTime()->convertSystemDateTime($value);
                } else if ($attributeType == 'text') {
                    if (!is_string($value)) {
                        $value = '';
                    }
                    $value = nl2br($value);
                }
            }
            if (is_string($value) || $value === null || is_scalar($value) || is_callable([$value, '__toString'])) {
                $variableName = $field;
                if (!is_null($prefixLink)) {
                    $variableName = $prefixLink . '.' . $field;
                }
                $text = str_replace('{' . $type . '.' . $variableName . '}', $value, $text);
            }
        }

        if (!$skipLinks) {
            $relationDefs = $entity->getRelations();
            foreach ($entity->getRelationList() as $relation) {
                if (
                    !empty($relationDefs[$relation]['type'])
                    &&
                    ($entity->getRelationType($relation) === 'belongsTo' || $entity->getRelationType($relation) === 'belongsToParent')
                ) {
                    $relatedEntity = $entity->get($relation);
                    if (!$relatedEntity) continue;
                    if ($this->getAcl()) {
                        if (!$this->getAcl()->check($relatedEntity, 'read')) continue;
                    }

                    $text = $this->parseText($type, $relatedEntity, $text, true, $relation);
                }
            }
        }


        return $text;
    }
}

