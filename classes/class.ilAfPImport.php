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
	protected $users_target = array();
	protected $main_tag = "Users";
	protected $import_dir;
	protected $xml_file = "importFile.xml";
	protected $users_rest_limit = 500; // Query limit. Don't increase this value without increase also the memory limit of the servers.

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

			$total_users = (int)$reader->getCountTotalUsers();

			//get last cron job execution
			/*
			foreach(ilCronManager::getPluginJobs() as $item)
			{
				$job = $item[1];
				if($job['job_id'] == "afpui") {
					$last_execution = $job["job_result_ts"];
				}
				else{
					$last_execution = 0;
				}
				break;
			}
			*/
			//They need to import all users every night to be able to update users in the webservice.
			$last_execution = 0;

			$users_parsed = array();
			for($offset = 0; $offset <= $total_users; $offset = $offset + $this->users_rest_limit)
			{
				//get users from rest api
				$users_data = $reader->getRestUsers($this->users_rest_limit, $offset, $last_execution);
				$parsed = $this->parseUserData($users_data);
				$users_parsed = array_merge($users_parsed,$parsed);
			}

			//create XML
			$xml = new ilAfPImportXmlWriter();
			$xml->setMainTag($this->main_tag);
			$xml->fillData($users_parsed);
			$xml->createXMLFile();

			//Save the users
			require_once("./Services/User/classes/class.ilUserImportParser.php");
			$xml_parser = new ilUserImportParser($this->getXmlFile());
			$xml_parser->startParsing();

			//Save import_id in object_data table
			$this->insertImportIds();

			$this->addUsersToObjects();

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
		$programs_migrate = ilObjAfPProgram::getProgramsToMigrate();

		$users_data = array();
		$send_failure = false;
		foreach ($a_users as $user)
		{
			$error = null;
			if(array_key_exists($user['user45'], $programs_migrate))
			{
				if ($user['user39'] == "" and $user['user40'] == "") {
					$error = "ERROR - User with id:" . $user['cid'] . " doesn't have name and lastname";

				} else if ($user['email'] == "") {
					$error = "ERROR - User with id:" . $user['cid'] . " doesn't have email";
				} else {

					$login = $this->generateLogin($user['user39'], $user['user40'], $user['cid']);

					//gender forced as 'f' or 'm'
					if (strtolower($user['user36']) == 'frau') {
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

					$this->users_target[$user['cid']] = array($user['user45'],$user['user50'],$user['user52'],$user['user48']);
				}

				if ($error) {
					ilAfPLogger::getLogger()->write($error);
					$send_failure = true;
				}
			}
		}
		if($send_failure)
		{
			ilUtil::sendFailure(ilAfPImportPlugin::getInstance()->txt('name_conflicts_check_log'),true);
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

	function generateLogin($a_name, $a_lastname, $a_user_id)
	{
		//login must be name.lastname or name.lastname.number
		if ($a_name == "" || $a_lastname == "") {
			$login = $a_name.$a_lastname;
		} else {
			$login = $a_name.".".$a_lastname;
		}

		//login can't contain white spaces nor umlauts
		$login = str_replace(" ", "_", $login);
		$login = $this->umlauts($login);


		$count = 1;
		while (in_array($login, $this->users_id_login)) {
			$login = $login . $count;
			$count++;
		}

		$this->users_id_login[$a_user_id] = $login;

		return $login;
	}

	/**
	 * @param string $a_string
	 * @return string
	 */
	protected function umlauts($a_string)
	{
		//option 1 doesn't work in all systems.
		//setlocale(LC_CTYPE, 'de_DE');
		//return iconv("utf-8","ASCII//TRANSLIT",$a_string);

		return strtolower(strtr($a_string, array(
			"ä" => "ae",
			"ö" => "oe",
			"ü" => "ue",
			"Ä" => "ae",
			"Ö" => "oe",
			"Ü" => "ue",
			"ß" => "ss",
			"à" => "a",
			"á" => "a",
			"è" => "e",
			"é" => "e",
			"ì" => "i",
			"í" => "i",
			"ò" => "o",
			"ó" => "o",
			"ù" => "u",
			"ú" => "u"
		)));
	}


	function addUsersToObjects()
	{
		global $rbacadmin;

		require_once "./Services/Object/classes/class.ilObject2.php";
		require_once "./Services/Membership/classes/class.ilParticipants.php";

		foreach ($this->users_target as $user => $targets)
		{
			$user_ilias_id = $this->lookupObjId($user);
			//Force all the new users to have the role: User.
			$rbacadmin->assignUser(4,$user_ilias_id);

			foreach($targets as $target)
			{
				$program = new ilObjAfPProgram();
				$program_data = $program->getDataFromAfPId($target);
				$ilias_ref = $program_data['ilias_ref_id'];

				$obj_id = ilObject2::_lookupObjectId($ilias_ref);
				$obj_type = ilObject2::_lookupType($obj_id);

				switch ($obj_type)
				{
					case "crs":
						$members = ilParticipants::getInstanceByObjId($obj_id);
						if(!$members->isMember($user_ilias_id))
						{
							$members->add($user_ilias_id,IL_CRS_MEMBER);
							ilAfPLogger::getLogger()->write("user: ".$user_ilias_id." added to course:".$obj_id);
						}
						else
						{
							ilAfPLogger::getLogger()->write("user: ".$user_ilias_id." NOT added, is already in this course:".$obj_id);

						}
						break;
					case "prg":
						ilAfPLogger::getLogger()->write('Assigning to sprg: ' .$user.' to '. $ilias_ref);
						require_once("Modules/StudyProgramme/classes/class.ilObjStudyProgramme.php");
						$prg = ilObjStudyProgramme::getInstanceByRefId($ilias_ref);
						if(!$prg->hasAssignmentOf($user_ilias_id))
						{
							$prg->assignUser($user_ilias_id);
							ilAfPLogger::getLogger()->write("user: ".$user_ilias_id." added to study program ref:".$ilias_ref);
						}
						else
						{
							ilAfPLogger::getLogger()->write("user: ".$user_ilias_id." NOT added, is already in this study program ref:".$ilias_ref);
						}
						break;
				}

			}
		}

	}
}
