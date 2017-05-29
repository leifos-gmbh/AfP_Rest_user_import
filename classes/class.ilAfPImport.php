<?php

/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 *
 * @author Jesus Lopez <lopez@leifos.com>
 */
class ilAfPImport
{
	protected static $instance = null;
	protected $users_imported = array();
	protected $main_tag = "Users";
	protected $import_dir;
	protected $xml_file = "importFile.xml";

	protected $training_period_to_import = array();

	/**
	 * Get import instance
	 *
	 * @return ilAfPImport
	 */
	public static function getInstance()
	{
		if (self::$instance) {
			return self::$instance;
		}
		return self::$instance = new self();
	}

	public function getImportDir()
	{
		return $this->import_dir;
	}

	public function getXmlFile()
	{
		return $this->import_dir."/".$this->xml_file;
	}

	public function getXMLFileName()
	{
		return $this->xml_file;
	}

	function import()
	{
		// Checking for import lock
		if (ilAfPSettings::getInstance()->isLocked()) {
			throw new ilException(ilAfPImportPlugin::getInstance()->txt('err_import_locked'));
		}

		$this->setLock();

		try
		{
			$this->initImportDirectory();

			$reader = new ilAfPImportRestReader();

			//get users from rest api
			$users_data = $reader->getRestUsers();

			//parse users to custom array
			$user_data = $this->parseUserData($users_data);

			//create XML
			$xml = new ilAfPImportXmlWriter();

			$xml->setMainTag($this->main_tag);

			$xml->fillData($user_data);

			$xml->createXMLFile();

			require_once("./Services/User/classes/class.ilUserImportParser.php");
			$xml_parser = new ilUserImportParser($this->getXmlFile());

			$xml_parser->startParsing();



//LOOP XML FILE AND CHECK:
//if the ausbildung... has one of the 3 valid types then... ( in the array $this->$training_period_to_import)
//in obj_data table import_id column
//import users with ilUserImportParser

			$this->releaseLock();

		}
		catch (ilException $e) {
			ilAfPLogger::getLogger()->write("import() exception => " . $e->getMessage());
			$this->releaseLock();
			throw $e;
		}
	}

	/**
	 * @param $a_users array
	 * @returns $users array
	 */
	protected function parseUserData($a_users)
	{

		$users_data = array();

		foreach ($a_users as $user)
		{
			//TODO filter users by ausbildung, we have only 3 valid types
			$users_data[$user['cid']] = array(
				"user_id" => $user['cid'],
				"title" => $user['user38'],
				"name" => $user['user39'],
				"lastname" => $user['user40'],
				"email" => $user['email'],
				"street" => $user['street'],
				"city" => $user['city'],
				"postcode" => $user['postcode'],
				"company" => $user['company']
			);
		}

		return $users_data;
	}


	/**
	 * Release lock
	 */
	protected function releaseLock()
	{
		// Settings import lock
		ilAfPLogger::getLogger()->write("Release import lock");

		ilAfPSettings::getInstance()->enableLock(false);
		ilAfPSettings::getInstance()->save();
	}

	/**
	 * Set import lock
	 */
	protected function setLock()
	{
		// Settings import lock
		ilAfPLogger::getLogger()->write('Setting import lock');
		ilAfPSettings::getInstance()->enableLock(true);
		ilAfPSettings::getInstance()->save();
	}


	protected function initImportDirectory()
	{
		$dirname = ilAfPSettings::getInstance()->getBackupDir();

		$this->import_dir = $dirname.'/import_'.date('Y-m-d_H:i');

		// Create new import directory
		ilUtil::makeDir($this->import_dir);
	}

}
