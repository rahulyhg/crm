<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\BadRequest;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\NotFound;

class Email extends \Core\Core\Controllers\Record
{
    public function postActionGetCopiedAttachments($params, $data, $request)
    {
        if (empty($data['id'])) {
            throw new BadRequest();
        }
        $id = $data['id'];

        return $this->getRecordService()->getCopiedAttachments($id);
    }

    public function actionSendTestEmail($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if (!$this->getAcl()->checkScope('Email')) {
            throw new Forbidden();
        }

        if (is_null($data['password'])) {
            if ($data['type'] == 'preferences') {
                if (!$this->getUser()->isAdmin() && $data['id'] !== $this->getUser()->id) {
                    throw new Forbidden();
                }
                $preferences = $this->getEntityManager()->getEntity('Preferences', $data['id']);
                if (!$preferences) {
                    throw new NotFound();
                }

                if (is_null($data['password'])) {
                    $data['password'] = $this->getContainer()->get('crypt')->decrypt($preferences->get('smtpPassword'));
                }
            } else if ($data['type'] == 'emailAccount') {
                if (!$this->getAcl()->checkScope('EmailAccount')) {
                    throw new Forbidden();
                }
                if (!empty($data['id'])) {
                    $emailAccount = $this->getEntityManager()->getEntity('EmailAccount', $data['id']);
                    if (!$emailAccount) {
                        throw new NotFound();
                    }
                    if (!$this->getUser()->isAdmin()) {
                        if ($emailAccount->get('assigniedUserId') !== $this->getUser()->id) {
                            throw new Forbidden();
                        }
                    }
                    if (is_null($data['password'])) {
                        $data['password'] = $this->getContainer()->get('crypt')->decrypt($emailAccount->get('smtpPassword'));
                    }
                }
            } else {
                if (!$this->getUser()->isAdmin()) {
                    throw new Forbidden();
                }
                if (is_null($data['password'])) {
                    $data['password'] = $this->getConfig()->get('smtpPassword');
                }
            }
        }

        return $this->getRecordService()->sendTestEmail($data);
    }

    public function postActionMarkAsRead($params, $data, $request)
    {
        if (!empty($data['ids'])) {
            $ids = $data['ids'];
        } else {
            if (!empty($data['id'])) {
                $ids = [$data['id']];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->markAsReadByIdList($ids);
    }

    public function postActionMarkAsNotRead($params, $data, $request)
    {
        if (!empty($data['ids'])) {
            $ids = $data['ids'];
        } else {
            if (!empty($data['id'])) {
                $ids = [$data['id']];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->markAsNotReadByIdList($ids);
    }

    public function postActionMarkAllAsRead($params, $data, $request)
    {
        return $this->getRecordService()->markAllAsRead();
    }

    public function postActionMarkAsImportant($params, $data, $request)
    {
        if (!empty($data['ids'])) {
            $ids = $data['ids'];
        } else {
            if (!empty($data['id'])) {
                $ids = [$data['id']];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->markAsImportantByIdList($ids);
    }

    public function postActionMarkAsNotImportant($params, $data, $request)
    {
        if (!empty($data['ids'])) {
            $ids = $data['ids'];
        } else {
            if (!empty($data['id'])) {
                $ids = [$data['id']];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->markAsNotImportantByIdList($ids);
    }

    public function postActionMoveToTrash($params, $data)
    {
        if (!empty($data['ids'])) {
            $ids = $data['ids'];
        } else {
            if (!empty($data['id'])) {
                $ids = [$data['id']];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->moveToTrashByIdList($ids);
    }

    public function postActionRetrieveFromTrash($params, $data)
    {
        if (!empty($data['ids'])) {
            $ids = $data['ids'];
        } else {
            if (!empty($data['id'])) {
                $ids = [$data['id']];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->retrieveFromTrashByIdList($ids);
    }

    public function getActionGetFoldersNotReadCounts(&$params, $request, $data)
    {
        return $this->getRecordService()->getFoldersNotReadCounts();
    }

    protected function fetchListParamsFromRequest(&$params, $request, $data)
    {
        parent::fetchListParamsFromRequest($params, $request, $data);

        $folderId = $request->get('folderId');
        if ($folderId) {
            $params['folderId'] = $request->get('folderId');
        }
    }

    public function postActionMoveToFolder($params, $data)
    {
        if (!empty($data['ids'])) {
            $ids = $data['ids'];
        } else {
            if (!empty($data['id'])) {
                $ids = [$data['id']];
            } else {
                throw new BadRequest();
            }
        }

        if (empty($data['folderId'])) {
            throw new BadRequest();
        }
        return $this->getRecordService()->moveToFolderByIdList($ids, $data['folderId']);
    }
}

