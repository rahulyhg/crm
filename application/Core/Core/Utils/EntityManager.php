<?php


namespace Core\Core\Utils;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\Conflict;
use \Core\Core\Utils\Json;
use \Core\Core\Container;

class EntityManager
{
    private $metadata;

    private $language;

    private $fileManager;

    private $config;

    private $metadataHelper;

    private $container;

    private $reservedWordList = ['__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'common'];

    public function __construct(Metadata $metadata, Language $language, File\Manager $fileManager, Config $config, Container $container = null)
    {
        $this->metadata = $metadata;
        $this->language = $language;
        $this->fileManager = $fileManager;
        $this->config = $config;

        $this->metadataHelper = new \Core\Core\Utils\Metadata\Helper($this->metadata);

        $this->container = $container;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getEntityManager()
    {
        if (!$this->container) return;

        return $this->container->get('entityManager');
    }

    protected function getLanguage()
    {
        return $this->language;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getMetadataHelper()
    {
        return $this->metadataHelper;
    }

    protected function getServiceFactory()
    {
        if (!$this->container) return;

        return $this->container->get('serviceFactory');
    }

    protected function checkControllerExists($name)
    {
        $controllerClassName = '\\Core\\Custom\\Controllers\\' . Util::normilizeClassName($name);
        if (class_exists($controllerClassName)) {
            return true;
        } else {
            foreach ($this->getMetadata()->getModuleList() as $moduleName) {
                $controllerClassName = '\\Core\\Modules\\' . $moduleName . '\\Controllers\\' . Util::normilizeClassName($name);
                if (class_exists($controllerClassName)) {
                    return true;
                }
            }
            $controllerClassName = '\\Core\\Controllers\\' . Util::normilizeClassName($name);
            if (class_exists($controllerClassName)) {
                return true;
            }
        }
        return false;
    }

    public function create($name, $type, $params = array())
    {
        $name = ucfirst($name);
        $name = trim($name);

        if ($this->getMetadata()->get('scopes.' . $name)) {
            throw new Conflict('Entity \''.$name.'\' already exists.');
        }
        if (empty($name) || empty($type)) {
            throw new Error();
        }

        if ($this->checkControllerExists($name)) {
            throw new Conflict('Entity name \''.$name.'\' is not allowed.');
        }

        $serviceFactory = $this->getServiceFactory();
        if ($serviceFactory && $serviceFactory->checKExists($name)) {
            throw new Conflict('Entity name \''.$name.'\' is not allowed.');
        }

        if (in_array(strtolower($name), $this->reservedWordList)) {
            throw new Conflict('Entity name \''.$name.'\' is not allowed.');
        }

        $normalizedName = Util::normilizeClassName($name);

        $contents = "<" . "?" . "php\n\n".
            "namespace Core\Custom\Entities;\n\n".
            "class {$normalizedName} extends \Core\Core\Templates\Entities\\{$type}\n".
            "{\n".
            "    protected \$entityType = \"$name\";\n".
            "}\n";

        $filePath = "custom/Core/Custom/Entities/{$normalizedName}.php";
        $this->getFileManager()->putContents($filePath, $contents);

        $contents = "<" . "?" . "php\n\n".
            "namespace Core\Custom\Controllers;\n\n".
            "class {$normalizedName} extends \Core\Core\Templates\Controllers\\{$type}\n".
            "{\n".
            "}\n";
        $filePath = "custom/Core/Custom/Controllers/{$normalizedName}.php";
        $this->getFileManager()->putContents($filePath, $contents);

        $contents = "<" . "?" . "php\n\n".
            "namespace Core\Custom\Services;\n\n".
            "class {$normalizedName} extends \Core\Core\Templates\Services\\{$type}\n".
            "{\n".
            "}\n";
        $filePath = "custom/Core/Custom/Services/{$normalizedName}.php";
        $this->getFileManager()->putContents($filePath, $contents);

        $contents = "<" . "?" . "php\n\n".
            "namespace Core\Custom\Repositories;\n\n".
            "class {$normalizedName} extends \Core\Core\Templates\Repositories\\{$type}\n".
            "{\n".
            "}\n";

        $filePath = "custom/Core/Custom/Repositories/{$normalizedName}.php";
        $this->getFileManager()->putContents($filePath, $contents);

        if (file_exists('application/Core/Core/Templates/SelectManagers/' . $type . '.php')) {
            $contents = "<" . "?" . "php\n\n".
                "namespace Core\Custom\SelectManagers;\n\n".
                "class {$normalizedName} extends \Core\Core\Templates\SelectManagers\\{$type}\n".
                "{\n".
                "}\n";

            $filePath = "custom/Core/Custom/SelectManagers/{$normalizedName}.php";
            $this->getFileManager()->putContents($filePath, $contents);
        }

        $stream = false;
        if (!empty($params['stream'])) {
            $stream = $params['stream'];
        }
        $disabled = false;
        if (!empty($params['disabled'])) {
            $disabled = $params['disabled'];
        }
        $labelSingular = $name;
        if (!empty($params['labelSingular'])) {
            $labelSingular = $params['labelSingular'];
        }
        $labelPlural = $name;
        if (!empty($params['labelPlural'])) {
            $labelPlural = $params['labelPlural'];
        }

        $languageList = $this->getConfig()->get('languageList', []);
        foreach ($languageList as $language) {
            $filePath = 'application/Core/Core/Templates/i18n/' . $language . '/' . $type . '.json';
            if (!file_exists($filePath)) continue;
            $languageContents = $this->getFileManager()->getContents($filePath);
            $languageContents = str_replace('{entityType}', $name, $languageContents);
            $languageContents = str_replace('{entityTypeTranslated}', $labelSingular, $languageContents);

            $destinationFilePath = 'custom/Core/Custom/Resources/i18n/' . $language . '/' . $name . '.json';
            $this->getFileManager()->putContents($destinationFilePath, $languageContents);
        }

        $filePath = "application/Core/Core/Templates/Metadata/{$type}/scopes.json";
        $scopesDataContents = $this->getFileManager()->getContents($filePath);
        $scopesDataContents = str_replace('{entityType}', $name, $scopesDataContents);
        $scopesData = Json::decode($scopesDataContents, true);

        $scopesData['stream'] = $stream;
        $scopesData['disabled'] = $disabled;
        $scopesData['type'] = $type;
        $scopesData['module'] = 'Custom';
        $scopesData['object'] = true;
        $scopesData['isCustom'] = true;

        $this->getMetadata()->set('scopes', $name, $scopesData);

        $filePath = "application/Core/Core/Templates/Metadata/{$type}/entityDefs.json";
        $entityDefsDataContents = $this->getFileManager()->getContents($filePath);
        $entityDefsDataContents = str_replace('{entityType}', $name, $entityDefsDataContents);
        $entityDefsDataContents = str_replace('{tableName}', $this->getEntityManager()->getQuery()->toDb($name), $entityDefsDataContents);
        $entityDefsData = Json::decode($entityDefsDataContents, true);
        $this->getMetadata()->set('entityDefs', $name, $entityDefsData);

        $filePath = "application/Core/Core/Templates/Metadata/{$type}/clientDefs.json";
        $clientDefsContents = $this->getFileManager()->getContents($filePath);
        $clientDefsContents = str_replace('{entityType}', $name, $clientDefsContents);
        $clientDefsData = Json::decode($clientDefsContents, true);
        $this->getMetadata()->set('clientDefs', $name, $clientDefsData);

        $this->getLanguage()->set('Global', 'scopeNames', $name, $labelSingular);
        $this->getLanguage()->set('Global', 'scopeNamesPlural', $name, $labelPlural);

        $this->getMetadata()->save();
        $this->getLanguage()->save();

        $layoutsPath = "application/Core/Core/Templates/Layouts/{$type}";
        if ($this->getFileManager()->isDir($layoutsPath)) {
            $this->getFileManager()->copy($layoutsPath, 'custom/Core/Custom/Resources/layouts/' . $name);
        }

        $this->processHook('afterCreate', $type, $name, $params);

        return true;
    }

    public function update($name, $data)
    {
        if (!$this->getMetadata()->get('scopes.' . $name)) {
            throw new Error('Entity ['.$name.'] does not exist.');
        }

        if (isset($data['stream']) || isset($data['disabled'])) {
            $scopeData = array();
            if (isset($data['stream'])) {
                $scopeData['stream'] = true == $data['stream'];
            }
            if (isset($data['disabled'])) {
                $scopeData['disabled'] = true == $data['disabled'];
            }
            $this->getMetadata()->set('scopes', $name, $scopeData);
        }

        if (array_key_exists('statusField', $data)) {
            $scopeData['statusField'] = $data['statusField'];
            $this->getMetadata()->set('scopes', $name, $scopeData);
        }

        if (!empty($data['labelSingular'])) {
            $labelSingular = $data['labelSingular'];
            $this->getLanguage()->set('Global', 'scopeNames', $name, $labelSingular);
            $labelCreate = $this->getLanguage()->translate('Create') . ' ' . $labelSingular;
            $this->getLanguage()->set($name, 'labels', 'Create ' . $name, $labelCreate);
        }

        if (!empty($data['labelPlural'])) {
            $labelPlural = $data['labelPlural'];
            $this->getLanguage()->set('Global', 'scopeNamesPlural', $name, $labelPlural);
        }

        if (isset($data['sortBy'])) {
            $entityDefsData = array(
                'collection' => array(
                    'sortBy' => $data['sortBy'],
                    'asc' => !empty($data['asc'])
                )
            );
            $this->getMetadata()->set('entityDefs', $name, $entityDefsData);
        }

        if (isset($data['textFilterFields'])) {
            $entityDefsData = array(
                'collection' => array(
                    'textFilterFields' => $data['textFilterFields']
                )
            );
            $this->getMetadata()->set('entityDefs', $name, $entityDefsData);
        }

        $this->getMetadata()->save();
        $this->getLanguage()->save();

        return true;
    }

    public function delete($name)
    {
        if (!$this->isCustom($name)) {
            throw new Forbidden;
        }

        $normalizedName = Util::normilizeClassName($name);

        $type = $this->getMetadata()->get(['scopes', $name, 'type']);

        $unsets = array(
            'entityDefs',
            'clientDefs',
            'scopes'
        );
        $res = $this->getMetadata()->delete('entityDefs', $name);
        $res = $this->getMetadata()->delete('clientDefs', $name);
        $res = $this->getMetadata()->delete('scopes', $name);

        $this->getFileManager()->removeFile("custom/Core/Custom/Resources/metadata/entityDefs/{$name}.json");
        $this->getFileManager()->removeFile("custom/Core/Custom/Resources/metadata/clientDefs/{$name}.json");
        $this->getFileManager()->removeFile("custom/Core/Custom/Resources/metadata/scopes/{$name}.json");

        $this->getFileManager()->removeFile("custom/Core/Custom/Entities/{$normalizedName}.php");
        $this->getFileManager()->removeFile("custom/Core/Custom/Services/{$normalizedName}.php");
        $this->getFileManager()->removeFile("custom/Core/Custom/Controllers/{$normalizedName}.php");
        $this->getFileManager()->removeFile("custom/Core/Custom/Repositories/{$normalizedName}.php");

        if (file_exists("custom/Core/Custom/SelectManagers/{$normalizedName}.php")) {
            $this->getFileManager()->removeFile("custom/Core/Custom/SelectManagers/{$normalizedName}.php");
        }

        $this->getFileManager()->removeInDir("custom/Core/Custom/Resources/layouts/{$normalizedName}");
        $this->getFileManager()->removeDir("custom/Core/Custom/Resources/layouts/{$normalizedName}");

        $languageList = $this->getConfig()->get('languageList', []);
        foreach ($languageList as $language) {
            $filePath = 'custom/Core/Custom/Resources/i18n/' . $language . '/' . $normalizedName . '.json' ;
            if (!file_exists($filePath)) continue;
            $this->getFileManager()->removeFile($filePath);
        }

        try {
            $this->getLanguage()->delete('Global', 'scopeNames', $name);
            $this->getLanguage()->delete('Global', 'scopeNamesPlural', $name);
        } catch (\Exception $e) {}

        $this->getMetadata()->save();
        $this->getLanguage()->save();

        if ($type) {
            $this->processHook('afterRemove', $type, $name);
        }

        return true;
    }

    protected function isCustom($name)
    {
        return $this->getMetadata()->get('scopes.' . $name . '.isCustom');
    }

    public function createLink(array $params)
    {
        $linkType = $params['linkType'];

        $entity = $params['entity'];
        $link = trim($params['link']);
        $entityForeign = $params['entityForeign'];
        $linkForeign = trim($params['linkForeign']);

        $label = $params['label'];
        $labelForeign = $params['labelForeign'];

        if ($linkType === 'manyToMany') {
            if (!empty($params['relationName'])) {
                $relationName = $params['relationName'];
            } else {
                $relationName = lcfirst($entity) . $entityForeign;
            }
        }

        $linkMultipleField = false;
        if (!empty($params['linkMultipleField'])) {
            $linkMultipleField = true;
        }

        $linkMultipleFieldForeign = false;
        if (!empty($params['linkMultipleFieldForeign'])) {
            $linkMultipleFieldForeign = true;
        }


        $audited = false;
        if (!empty($params['audited'])) {
            $audited = true;
        }

        $auditedForeign = false;
        if (!empty($params['auditedForeign'])) {
            $auditedForeign = true;
        }

        if (empty($linkType)) {
            throw new Error();
        }
        if (empty($entity) || empty($entityForeign)) {
            throw new Error();
        }
        if (empty($entityForeign) || empty($linkForeign)) {
            throw new Error();
        }

        if ($this->getMetadata()->get('entityDefs.' . $entity . '.links.' . $link)) {
            throw new Conflict('Link ['.$entity.'::'.$link.'] already exists.');
        }
        if ($this->getMetadata()->get('entityDefs.' . $entityForeign . '.links.' . $linkForeign)) {
            throw new Conflict('Link ['.$entityForeign.'::'.$linkForeign.'] already exists.');
        }

        if ($entity === $entityForeign) {
            if ($link === ucfirst($entity) || $linkForeign === ucfirst($entity)) {
                throw new Conflict();
            }
        }

        switch ($linkType) {
            case 'oneToMany':
                if ($this->getMetadata()->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign)) {
                    throw new Conflict('Field ['.$entityForeign.'::'.$linkForeign.'] already exists.');
                }
                if ($this->getMetadata()->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign . 'Id')) {
                    throw new Conflict('Field ['.$entityForeign.'::'.$linkForeign.'Id] already exists.');
                }
                if ($this->getMetadata()->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign . 'Name')) {
                    throw new Conflict('Field ['.$entityForeign.'::'.$linkForeign.'Name] already exists.');
                }
                $dataLeft = array(
                    'fields' => array(
                        $link => array(
                            "type" => "linkMultiple",
                            "layoutDetailDisabled"  => !$linkMultipleField,
                            "layoutListDisabled"  => true,
                            "layoutMassUpdateDisabled"  => !$linkMultipleField,
                            "noLoad"  => !$linkMultipleField,
                            "importDisabled" => !$linkMultipleField,
                            'isCustom' => true
                        )
                    ),
                    'links' => array(
                        $link => array(
                            'type' => 'hasMany',
                            'foreign' => $linkForeign,
                            'entity' => $entityForeign,
                            'audited' => $auditedForeign,
                            'isCustom' => true
                        )
                    )
                );
                $dataRight = array(
                    'fields' => array(
                        $linkForeign => array(
                            'type' => 'link'
                        )
                    ),
                    'links' => array(
                        $linkForeign => array(
                            'type' => 'belongsTo',
                            'foreign' => $link,
                            'entity' => $entity,
                            'audited' => $audited,
                            'isCustom' => true
                        )
                    )
                );
                break;
            case 'manyToOne':
                if ($this->getMetadata()->get('entityDefs.' . $entity . '.fields.' . $link)) {
                    throw new Conflict('Field ['.$entity.'::'.$link.'] already exists.');
                }
                if ($this->getMetadata()->get('entityDefs.' . $entity . '.fields.' . $link . 'Id')) {
                    throw new Conflict('Field ['.$entity.'::'.$link.'Id] already exists.');
                }
                if ($this->getMetadata()->get('entityDefs.' . $entity . '.fields.' . $link . 'Name')) {
                    throw new Conflict('Field ['.$entity.'::'.$link.'Name] already exists.');
                }
                $dataLeft = array(
                    'fields' => array(
                        $link => array(
                            'type' => 'link'
                        )
                    ),
                    'links' => array(
                        $link => array(
                            'type' => 'belongsTo',
                            'foreign' => $linkForeign,
                            'entity' => $entityForeign,
                            'audited' => $auditedForeign,
                            'isCustom' => true
                        )
                    )
                );
                $dataRight = array(
                    'fields' => array(
                        $linkForeign => array(
                            "type" => "linkMultiple",
                            "layoutDetailDisabled"  => !$linkMultipleFieldForeign,
                            "layoutListDisabled"  => true,
                            "layoutMassUpdateDisabled"  => !$linkMultipleFieldForeign,
                            "noLoad"  => !$linkMultipleFieldForeign,
                            "importDisabled" => !$linkMultipleFieldForeign,
                            'isCustom' => true
                        )
                    ),
                    'links' => array(
                        $linkForeign => array(
                            'type' => 'hasMany',
                            'foreign' => $link,
                            'entity' => $entity,
                            'audited' => $audited,
                            'isCustom' => true
                        )
                    )
                );
                break;
            case 'manyToMany':
                $dataLeft = array(
                    'fields' => array(
                        $link => array(
                            "type" => "linkMultiple",
                            "layoutDetailDisabled"  => !$linkMultipleField,
                            "layoutListDisabled"  => true,
                            "layoutMassUpdateDisabled"  => !$linkMultipleField,
                            "importDisabled" => !$linkMultipleField,
                            "noLoad"  => !$linkMultipleField,
                            'isCustom' => true
                        )
                    ),
                    'links' => array(
                        $link => array(
                            'type' => 'hasMany',
                            'relationName' => $relationName,
                            'foreign' => $linkForeign,
                            'entity' => $entityForeign,
                            'audited' => $auditedForeign,
                            'isCustom' => true
                        )
                    )
                );
                $dataRight = array(
                    'fields' => array(
                        $linkForeign => array(
                            "type" => "linkMultiple",
                            "layoutDetailDisabled"  => !$linkMultipleFieldForeign,
                            "layoutListDisabled"  => true,
                            "layoutMassUpdateDisabled"  => !$linkMultipleFieldForeign,
                            "importDisabled" => !$linkMultipleFieldForeign,
                            "noLoad"  => !$linkMultipleFieldForeign,
                            'isCustom' => true
                        )
                    ),
                    'links' => array(
                        $linkForeign => array(
                            'type' => 'hasMany',
                            'relationName' => $relationName,
                            'foreign' => $link,
                            'entity' => $entity,
                            'audited' => $audited,
                            'isCustom' => true
                        )
                    )
                );
                if ($entityForeign == $entity) {
                    $dataLeft['links'][$link]['midKeys'] = ['leftId', 'rightId'];
                    $dataRight['links'][$linkForeign]['midKeys'] = ['rightId', 'leftId'];
                }
                break;
        }

        $this->getMetadata()->set('entityDefs', $entity, $dataLeft);
        $this->getMetadata()->set('entityDefs', $entityForeign, $dataRight);
        $this->getMetadata()->save();

        $this->getLanguage()->set($entity, 'fields', $link, $label);
        $this->getLanguage()->set($entity, 'links', $link, $label);
        $this->getLanguage()->set($entityForeign, 'fields', $linkForeign, $labelForeign);
        $this->getLanguage()->set($entityForeign, 'links', $linkForeign, $labelForeign);

        $this->getLanguage()->save();

        return true;
    }

    public function updateLink(array $params)
    {
        $entity = $params['entity'];
        $link = $params['link'];
        $entityForeign = $params['entityForeign'];
        $linkForeign = $params['linkForeign'];

        $label = $params['label'];
        $labelForeign = $params['labelForeign'];

        if (empty($entity) || empty($entityForeign)) {
            throw new Error();
        }
        if (empty($entityForeign) || empty($linkForeign)) {
            throw new Error();
        }

        if (
            $this->getMetadata()->get("entityDefs.{$entity}.links.{$link}.type") == 'hasMany'
            &&
            $this->getMetadata()->get("entityDefs.{$entity}.links.{$link}.isCustom")
        ) {
            if (array_key_exists('linkMultipleField', $params)) {
                $linkMultipleField = $params['linkMultipleField'];
                $dataLeft = array(
                    'fields' => array(
                        $link => array(
                            "type" => "linkMultiple",
                            "layoutDetailDisabled"  => !$linkMultipleField,
                            "layoutListDisabled"  => true,
                            "layoutMassUpdateDisabled"  => !$linkMultipleField,
                            "noLoad"  => !$linkMultipleField,
                            "importDisabled" => !$linkMultipleField,
                            'isCustom' => true
                        )
                    )
                );
                $this->getMetadata()->set('entityDefs', $entity, $dataLeft);
                $this->getMetadata()->save();
            }
        }

        if (
            $this->getMetadata()->get("entityDefs.{$entityForeign}.links.{$linkForeign}.type") == 'hasMany'
            &&
            $this->getMetadata()->get("entityDefs.{$entityForeign}.links.{$linkForeign}.isCustom")
        ) {
            if (array_key_exists('linkMultipleFieldForeign', $params)) {
                $linkMultipleFieldForeign = $params['linkMultipleFieldForeign'];
                $dataRight = array(
                    'fields' => array(
                        $linkForeign => array(
                            "type" => "linkMultiple",
                            "layoutDetailDisabled"  => !$linkMultipleFieldForeign,
                            "layoutListDisabled"  => true,
                            "layoutMassUpdateDisabled"  => !$linkMultipleFieldForeign,
                            "noLoad"  => !$linkMultipleFieldForeign,
                            "importDisabled" => !$linkMultipleFieldForeign,
                            'isCustom' => true
                        )
                    )
                );
                $this->getMetadata()->set('entityDefs', $entityForeign, $dataRight);
                $this->getMetadata()->save();
            }
        }

        if (
            in_array($this->getMetadata()->get("entityDefs.{$entity}.links.{$link}.type"), ['hasMany', 'hasChildren'])
        ) {
            if (array_key_exists('audited', $params)) {
                $audited = $params['audited'];
                $dataLeft = array(
                    'links' => array(
                        $link => array(
                            "audited" => $audited
                        )
                    )
                );
                $this->getMetadata()->set('entityDefs', $entity, $dataLeft);
                $this->getMetadata()->save();
            }
        }

        if (
            in_array($this->getMetadata()->get("entityDefs.{$entityForeign}.links.{$linkForeign}.type"), ['hasMany', 'hasChildren'])
        ) {
            if (array_key_exists('auditedForeign', $params)) {
                $auditedForeign = $params['auditedForeign'];
                $dataRight = array(
                    'links' => array(
                        $linkForeign => array(
                            "audited" => $auditedForeign
                        )
                    )
                );
                $this->getMetadata()->set('entityDefs', $entityForeign, $dataRight);
                $this->getMetadata()->save();
            }
        }

        $this->getLanguage()->set($entity, 'fields', $link, $label);
        $this->getLanguage()->set($entity, 'links', $link, $label);
        $this->getLanguage()->set($entityForeign, 'fields', $linkForeign, $labelForeign);
        $this->getLanguage()->set($entityForeign, 'links', $linkForeign, $labelForeign);

        $this->getLanguage()->save();

        return true;
    }

    public function deleteLink(array $params)
    {
        $entity = $params['entity'];
        $link = $params['link'];

        if (!$this->getMetadata()->get("entityDefs.{$entity}.links.{$link}.isCustom")) {
            throw new Error();
        }

        $entityForeign = $this->getMetadata()->get("entityDefs.{$entity}.links.{$link}.entity");
        $linkForeign = $this->getMetadata()->get("entityDefs.{$entity}.links.{$link}.foreign");

        if (empty($entity) || empty($entityForeign)) {
            throw new Error();
        }
        if (empty($entityForeign) || empty($linkForeign)) {
            throw new Error();
        }

        $this->getMetadata()->delete('entityDefs', $entity, array(
            'fields.' . $link,
            'links.' . $link
        ));
        $this->getMetadata()->delete('entityDefs', $entityForeign, array(
            'fields.' . $linkForeign,
            'links.' . $linkForeign
        ));
        $this->getMetadata()->save();

        return true;
    }

    public function setFormulaData($scope, $data)
    {
        $this->getMetadata()->set('formula', $scope, $data);
        $this->getMetadata()->save();
    }

    protected function processHook($methodName, $type, $name, &$params = null)
    {
        $hook = $this->getHook($type);
        if (!$hook) return;

        if (!method_exists($hook, $methodName)) return;

        $hook->$methodName($name, $params);
    }

    protected function getHook($type)
    {
        $className = '\\Core\\Core\\Utils\\EntityManager\\Hooks\\' . $type . 'Type';
        $className = $this->getMetadata()->get(['entityTemplates', $type, 'hookClassName'], $className);

        if (class_exists($className)) {
            $hook = new $className();
            foreach ($hook->getDependencyList() as $name) {
                $hook->inject($name, $this->container->get($name));
            }
            return $hook;
        }
        return;
    }
}
