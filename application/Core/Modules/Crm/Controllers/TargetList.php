<?php


namespace Core\Modules\Crm\Controllers;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class TargetList extends \Core\Core\Controllers\Record
{
	public function actionUnlinkAll($params, $data, $request)
	{
		if (!$request->isPost()) {
			throw new BadRequest();
		}

		if (empty($data['id'])) {
			throw new BadRequest();
		}

		if (empty($data['link'])) {
			throw new BadRequest();
		}

		return $this->getRecordService()->unlinkAll($data['id'], $data['link']);
	}

	public function postActionOptOut($params, $data)
	{
		if (empty($data['id'])) {
			throw new BadRequest();
		}
		if (empty($data['targetType'])) {
			throw new BadRequest();
		}
		if (empty($data['targetId'])) {
			throw new BadRequest();
		}
		return $this->getRecordService()->optOut($data['id'], $data['targetType'], $data['targetId']);
	}

	public function postActionCancelOptOut($params, $data)
	{
		if (empty($data['id'])) {
			throw new BadRequest();
		}
		if (empty($data['targetType'])) {
			throw new BadRequest();
		}
		if (empty($data['targetId'])) {
			throw new BadRequest();
		}
		return $this->getRecordService()->cancelOptOut($data['id'], $data['targetType'], $data['targetId']);
	}

}
