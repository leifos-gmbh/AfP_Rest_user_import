<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */
//include_once 'class.crm_rest_api.php';


/**
 * REST reader.
 * @author Jesus Lopez <lopez@leifos.com>
 */
class ilAfPImportRestReader
{
	protected $session_id;
	protected $rest_base_url;
	protected $last_execution;

	function __construct()
	{
		$this->rest_base_url = ilAfPSettings::getInstance()->getRestUrl();
		$this->connection();
	}

	protected function connection()
	{
		try
		{
			$target = $this->rest_base_url."logon?method=crmLogin&response_type=JSON&username=".ilAfPSettings::getInstance()->getRestUser()."&password=".ilAfPSettings::getInstance()->getRestPassword();

			$response = file_get_contents($target);

			$this->session_id = json_decode($response);

			ilAfPLogger::getLogger()->write("Connection successful session id = ".$this->session_id);

		}
		catch (Exception $e)
		{
			ilAfPLogger::getLogger()->write("Connection Exception: ".$e->getMessage());
		}


	}

	// calls REST crmgetChangedContacts
	function getRestUsers()
	{

		//get last cron job execution
		foreach(ilCronManager::getPluginJobs() as $item)
		{
			$job = $item[1];
			if($job['job_id'] == "afpui") {
				$last_execution = $job["job_result_ts"];
				break;
			}
		}

		ilAfPLogger::getLogger()->write("Last execution = ".$last_execution);

		/** GET CONTACTS - RETURNS ERROR 500 */
		/*
		$target = $this->rest_base_url."contacts?method=crmgetChangedContacts&response_type=JSON&session_id=".$this->session_id."&timestamp=1464510140";
		*/

		/** GET CONTACTS WITH LIMITS */
		$target = $this->rest_base_url."contacts?method=crmgetChangedContactsLimit&response_type=JSON&session_id=".$this->session_id."&timestamp=".$last_execution."&count=20&offset=0";
		ilAfPLogger::getLogger()->write("target changed contacts LIMIT = ".$target);
		$response = file_get_contents($target);
		$items = json_decode($response, true);
		
		return $items;

	}

}