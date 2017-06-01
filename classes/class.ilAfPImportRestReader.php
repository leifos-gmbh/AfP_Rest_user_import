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

	function getBaseUrl()
	{
		return $this->rest_base_url;
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
	function getRestUsers($a_users_limit, $a_offset, $a_cron_last_execution)
	{
		/** GET CONTACTS WITH LIMITS */
		$target = $this->rest_base_url."contacts?method=crmgetChangedContactsLimit&response_type=JSON&session_id=".$this->session_id."&timestamp=".$a_cron_last_execution."&count=".$a_users_limit."&offset=".$a_offset;
		$response = file_get_contents($target);
		$items = json_decode($response, true);

		return $items;

	}

	function getCountTotalUsers()
	{
		try
		{
			$target = $this->rest_base_url."contacts?method=crmcountContacts&response_type=JSON&session_id=".$this->session_id;
			$response = file_get_contents($target);

			return json_decode($response, true);
		}
		catch (Exception $e)
		{
			ilAfPLogger::getLogger()->write("Cant coun't the Contacts. ".$e->getMessage());
		}


	}

}