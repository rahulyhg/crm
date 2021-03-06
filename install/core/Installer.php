<?php


use Core\Core\Utils\Util;
use Core\Core\Utils\File\Manager as FileManager;
use Core\Core\Utils\Config;

class Installer
{
	protected $app = null;
	protected $language = null;

	protected $systemHelper = null;

	protected $isAuth = false;

	protected $permissionMap;

	protected $permissionError;

	private $passwordHash;

	protected $settingList = array(
		'dateFormat',
		'timeFormat',
		'timeZone',
		'weekStart',
		'defaultCurrency' => array(
			'currencyList', 'defaultCurrency',
		),
		'smtpSecurity',
		'language',
	);


	public function __construct()
	{
		$this->initialize();

		$this->app = new \Core\Core\Application();

		$user = $this->getEntityManager()->getEntity('User');
		$this->app->getContainer()->setUser($user);

		require_once('install/core/SystemHelper.php');
		$this->systemHelper = new SystemHelper();

		$configPath = $this->getConfig()->getConfigPath();
		$this->permissionMap = $this->getConfig()->get('permissionMap');
		$this->permissionMap['writable'][] = $configPath;

		if (!file_exists($configPath)) {
			$configData = $this->getConfig()->getDefaults();
			$this->getConfig()->set($configData);
			$this->getConfig()->save();
		}
	}

	protected function initialize()
	{
		$fileManager = new FileManager();
		$config = new Config($fileManager);
		$configPath = $config->getConfigPath();

		if (!file_exists($configPath)) {
			$fileManager->putPhpContents($configPath, array());

			$configData = $config->getDefaults();
			$config->set($configData);
			$config->save();
		}
	}

	protected function getContainer()
	{
		return $this->app->getContainer();
	}

	protected function getEntityManager()
	{
		return $this->getContainer()->get('entityManager');
	}

	protected function getConfig()
	{
		return $this->app->getContainer()->get('config');
	}

	protected function getSystemHelper()
	{
		return $this->systemHelper;
	}

	protected function getFileManager()
	{
		return $this->app->getContainer()->get('fileManager');
	}

	protected function getPasswordHash()
	{
		if (!isset($this->passwordHash)) {
			$config = $this->getConfig();
			$this->passwordHash = new \Core\Core\Utils\PasswordHash($config);
		}

		return $this->passwordHash;
	}

	public function getVersion()
	{
		return $this->getConfig()->get('version');
	}

	protected function auth()
	{
		if (!$this->isAuth) {
			$auth = new \Core\Core\Utils\Auth($this->app->getContainer());
			$auth->useNoAuth();

			$this->isAuth = true;
		}

		return $this->isAuth;
	}

	public function isInstalled()
	{
		return $this->app->isInstalled();
	}

	protected function getLanguage()
	{
		if (!isset($this->language)) {
			$this->language = $this->app->getContainer()->get('language');
		}

		return $this->language;
	}

	public function getLanguageList()
	{
		$config = $this->app->getContainer()->get('config');

		$languageList = $config->get('languageList');

		$translated = $this->getLanguage()->translate('language', 'options', 'Global', $languageList);

		return $translated;
	}

	/**
	 * Save data
	 *
	 * @param  array $database
	 * array (
	 *   'driver' => 'pdo_mysql',
	 *   'host' => 'localhost',
	 *   'dbname' => 'espocrm_test',
	 *   'user' => 'root',
	 *   'password' => '',
	 * ),
	 * @param  string $language
	 * @return bool
	 */
	public function saveData($database, $language)
	{
		$initData = include('install/core/afterInstall/config.php');

		$siteUrl = $this->getSystemHelper()->getBaseUrl();

		$data = array(
			'database' => $database,
			'language' => $language,
			'siteUrl' => $siteUrl,
			'passwordSalt' => $this->getPasswordHash()->generateSalt(),
			'cryptKey' => $this->getContainer()->get('crypt')->generateKey()
		);

		$owner = $this->getFileManager()->getPermissionUtils()->getDefaultOwner(true);
		$group = $this->getFileManager()->getPermissionUtils()->getDefaultGroup(true);

		if (!empty($owner)) {
			$data['defaultPermissions']['user'] = $owner;
		}
		if (!empty($group)) {
			$data['defaultPermissions']['group'] = $group;
		}

		$data = array_merge($data, $initData);
		$result = $this->saveConfig($data);

		return $result;
	}

	public function saveConfig($data)
	{
		$config = $this->app->getContainer()->get('config');

		$config->set($data);
		$result = $config->save();

		return $result;
	}

	public function buildDatabase()
	{
		$result = false;

		try {
			$result = $this->app->getContainer()->get('schema')->rebuild();
		} catch (\Exception $e) {
			$this->auth();
			$result = $this->app->getContainer()->get('schema')->rebuild();
		}

		return $result;
	}

	public function setPreferences($preferences)
	{
		$currencyList = $this->getConfig()->get('currencyList', array());
		if (isset($preferences['defaultCurrency']) && !in_array($preferences['defaultCurrency'], $currencyList)) {

			$preferences['currencyList'] = array($preferences['defaultCurrency']);
			$preferences['baseCurrency'] = $preferences['defaultCurrency'];
		}

		$res = $this->saveConfig($preferences);

		/*save these settings for admin*/
		$this->setAdminPreferences($preferences);

		return $res;
	}


	protected function createRecords()
	{
		$records = include('install/core/afterInstall/records.php');

		$result = true;
		foreach ($records as $entityName => $recordList) {
			foreach ($recordList as $data) {
				$result &= $this->createRecord($entityName, $data);
			}
		}

		return $result;
	}

	protected function createRecord($entityName, $data)
	{
		if (isset($data['id'])) {

			$entity = $this->getEntityManager()->getEntity($entityName, $data['id']);

			if (!isset($entity)) {
				$pdo = $this->getEntityManager()->getPDO();

				$sql = "SELECT id FROM `".Util::toUnderScore($entityName)."` WHERE `id` = '".$data['id']."'";
				$sth = $pdo->prepare($sql);
				$sth->execute();

				$deletedEntity = $sth->fetch(\PDO::FETCH_ASSOC);

				if ($deletedEntity) {
					$sql = "UPDATE `".Util::toUnderScore($entityName)."` SET deleted = '0' WHERE `id` = '".$data['id']."'";
					$pdo->prepare($sql)->execute();

					$entity = $this->getEntityManager()->getEntity($entityName, $data['id']);
				}
			}
		}

		if (!isset($entity)) {
			$entity = $this->getEntityManager()->getEntity($entityName);
		}

		$entity->set($data);

		$id = $this->getEntityManager()->saveEntity($entity);

		return is_string($id);
	}

	public function createUser($userName, $password)
	{
		$this->auth();

		$user = array(
			'id' => '1',
			'userName' => $userName,
			'password' => $this->getPasswordHash()->hash($password),
			'lastName' => 'Admin',
			'isAdmin' => '1',
		);

		$result = $this->createRecord('User', $user);

		return $result;
	}

	protected function setAdminPreferences($preferences)
	{
		$allowedPreferences = array(
			'dateFormat',
			'timeFormat',
			'timeZone',
			'weekStart',
			'defaultCurrency',
			'thousandSeparator',
			'decimalMark',
			'language',
		);

		$data = array_intersect_key($preferences, array_flip($allowedPreferences));
		if (empty($data)) {
			return true;
		}

		$entity = $this->getEntityManager()->getEntity('Preferences', '1');
		if ($entity) {
			$entity->set($data);
			return $this->getEntityManager()->saveEntity($entity);
		}

		return false;
	}

	public function checkPermission()
	{
		return $this->getFileManager()->getPermissionUtils()->setMapPermission();
	}

	public function getLastPermissionError()
	{
		return $this->getFileManager()->getPermissionUtils()->getLastErrorRules();
	}

	public function setSuccess()
	{
		$this->auth();

		/** afterInstall scripts */
		$result = $this->createRecords();
		$result &= $this->executeQueries();
		/** END: afterInstall scripts */

		$config = $this->app->getContainer()->get('config');
		$config->set('isInstalled', true);
		$result &= $config->save();

		return $result;
	}

	public function getSettingDefaults()
	{
		$defaults = array();

		$settingDefs = $this->app->getMetadata()->get('entityDefs.Settings.fields');

		foreach ($this->settingList as $fieldName => $field) {

			if (is_array($field)) {
				$fieldDefaults = array();
				foreach ($field as $subField) {
					if (isset($settingDefs[$subField])) {
						$fieldDefaults = array_merge($fieldDefaults, $this->translateSetting($subField, $settingDefs[$subField]));
					}
				}
				$defaults[$fieldName] = $fieldDefaults;

			} else if (isset($settingDefs[$field])) {

				$defaults[$field] = $this->translateSetting($field, $settingDefs[$field]);
			}
		}

		if (isset($defaults['language'])) {
			$defaults['language']['options'] = $this->getLanguageList();
		}

		return $defaults;
	}

	protected function translateSetting($name, array $settingDefs)
	{
		if (isset($settingDefs['options'])) {
			$optionLabel = $this->getLanguage()->translate($name, 'options', 'Settings', $settingDefs['options']);

			if ($optionLabel == $name) {
				$optionLabel = $this->getLanguage()->translate($name, 'options', 'Global', $settingDefs['options']);
			}

			if ($optionLabel == $name) {
				$optionLabel = array();
				foreach ($settingDefs['options'] as $key => $value) {
					$optionLabel[$value] = $value;
				}
			}

			$settingDefs['options'] = $optionLabel;
		}

		return $settingDefs;
	}

	public function getCronMessage()
	{
		return $this->getContainer()->get('scheduledJob')->getSetupMessage();
	}

	protected function executeQueries()
	{
		$queries = include('install/core/afterInstall/queries.php');

		$pdo = $this->getEntityManager()->getPDO();

		$result = true;

		foreach ($queries as $query) {
			$sth = $pdo->prepare($query);
			$result =& $sth->execute();
		}

		return $result;
	}


}
