<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 *
 * @author Jesus Lopez <lopez@leifos.com>
 */
class ilAfPImport
{
	protected static $instance = null;
	protected $users_id_login = array();
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

			//TODO filter only users which field "xxxx" in array of hardcoded course codes.
			$users_data = $this->filterByProgram($users_data);

			//parse users to custom array
			$user_data = $this->parseUserData($users_data);

			//create XML
			$xml = new ilAfPImportXmlWriter();
			$xml->setMainTag($this->main_tag);
			$xml->fillData($user_data);
			$xml->createXMLFile();

			//Save the users
			require_once("./Services/User/classes/class.ilUserImportParser.php");
			$xml_parser = new ilUserImportParser($this->getXmlFile());
			$xml_parser->startParsing();

			//Save import_id in object_data table
			$this->insertImportIds();

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
			$error = null;
			if($user['user39'] == "" and $user['user40'] == "")
			{
				$error = "ERROR - User with id:".$user['cid']." doesn't have name and lastname";
			}
			else if($user['email'] == "")
			{
				$error = "ERROR - User with id:".$user['cid']." doesn't have email";
			}
			else
			{

				//login must be name.lastname or name.lastname.number
				if($user['user39'] == "" || $user['user40'] == "") {
					$login = $user['user39'].$user['user40'];
				}
				else{
					$login = $user['user39'].".".$user['user40'];
				}
				//login can't contain white spaces
				$login = str_replace(" ", "_",$login);

				$count = 1;
				while(in_array($login, $this->users_id_login))
				{
					$login = $login.$count;
					$count ++;
				}

				$this->users_id_login[$user['cid']] = $login;

				//gender forced as 'f' or 'm'
				if(strtolower($user['gender']) == 'frau') {
					$gender = 'f';
				} else {
					$gender = 'm';
				}

				$users_data[$user['cid']] = array(
					"user_id" => $user['cid'],
					"title" => $user['user38'],
					"login" => $login,
					"gender" => $gender,
					"name" => $user['user39'],
					"lastname" => $user['user40'],
					"email" => $user['email'],
					"phone" => $user['phone'],
					"street" => $user['street'],
					"city" => $user['city'],
					"postcode" => $user['postcode'],
					"company" => $user['company']
				);
			}

			if($error){
				ilAfPLogger::getLogger()->write($error);
			}
		}

		return $users_data;
	}

	function filterByProgram($a_user_data)
	{
		//return only the members of specific courses.
		//TODO
		return $a_user_data;
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


	protected function insertImportIds()
	{
		global $ilDB;

		foreach($this->users_id_login as $import_id => $login)
		{
			//check if the if exists if not insert
			$query   = 'SELECT usr_id FROM usr_data WHERE login = %s';
			$qres    = $ilDB->queryF($query, array('text'), array($login));
			$userRow = $ilDB->fetchAssoc($qres);

			if(is_array($userRow) && $userRow['usr_id'])
			{
				$usr_id = $userRow['usr_id'];

				$update = "UPDATE object_data SET ".
					"import_id = $import_id ".
					"WHERE obj_id = $usr_id";

				$res = $ilDB->manipulate($update);
			}

		}
	}


	function lookupObjId($a_id)
	{
		global $ilDB;

		$query = 'SELECT obj_id FROM object_data '.
			'WHERE import_id = '.$ilDB->quote($a_id,'text').' ';

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->obj_id;
		}
		return null;
	}
}
