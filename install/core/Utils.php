<?php


class Utils
{
	static public $actionPath = 'install/core/actions';

	static public function checkActionExists($actionName)
	{
		return in_array($actionName, [
			'applySett',
			'buildDatabase',
			'checkPermission',
			'createUser',
			'errors',
			'finish',
			'main',
			'setEmailSett',
			'setPreferences',
			'settingsTest',
			'setupConfirmation',
			'step1',
			'step2',
			'step3',
			'step4',
			'step5'
		]);


		return false;
	}
}