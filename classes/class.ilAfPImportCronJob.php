<?php
/* Copyright (c) 1998-20017 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Cron/classes/class.ilCronJob.php";

/**
 * afP import plugin
 *
 * @author Jesus Lopez <lopez@leifos.com>
 *
 */
class ilAfPImportCronJob extends ilCronJob
{
	protected $plugin;

	function getId()
	{
		return ilAfPImportPlugin::getInstance()->getId();
	}

	public function getTitle()
	{
		return ilAfPImportPlugin::PNAME;
	}

	public function getDescription()
	{
		return ilAfPImportPlugin::getInstance()->txt("cron_job_info");
	}

	function getDefaultScheduleValue()
	{
		return ilAfPSettings::getInstance()->getCronInterval();
	}

	function getDefaultScheduleType()
	{
		return self::SCHEDULE_TYPE_DAILY;
	}

	function hasAutoActivation()
	{
		return false;
	}

	function hasFlexibleSchedule()
	{
		return false;
	}

	public function hasCustomSettings()
	{
		return false;
	}

	/**
	 * @return ilCronJobResult
	 */
	function run()
	{
		$result = new ilCronJobResult();

		$importer = ilAfPImport::getInstance();

		try
		{
			$importer->import();
			ilAfPSettings::getInstance()->updateLastCronExecution();
			$result->setStatus(ilCronJobResult::STATUS_OK);
		}
		catch(Exception $e)
		{
			$result->setStatus(ilCronJobResult::STATUS_CRASHED);
			$result->setMessage($e->getMessage());

			ilAfPLogger::getLogger()->write("Cron update failed with message: " . $e->getMessage());
		}

		return $result;
	}
	public function getPlugin()
	{
		return ilAfPImportPlugin::getInstance();
	}
}