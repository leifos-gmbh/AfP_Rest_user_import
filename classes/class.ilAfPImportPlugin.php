<?php
include_once("./Services/Cron/classes/class.ilCronHookPlugin.php");

/**
 * AfP import plugin base class
 * @author Jesus Lopez <lopez@leifos.de>
 */
class ilAfPImportPlugin extends ilCronHookPlugin
{
	private static $instance = null;

	const PNAME = 'AfPImport';
	const SLOT_ID= 'crnhk';
	const CNAME = 'Cron';
	const CTYPE = 'Services';

	function getPluginName()
	{
		return self::PNAME;
	}

	/**
	 * Get singelton instance
	 * @global ilPluginAdmin $ilPluginAdmin
	 * @return ilAfPImportPlugin
	 */
	public static function getInstance()
	{
		global $ilPluginAdmin;

		if(self::$instance)
		{
			return self::$instance;
		}
		include_once './Services/Component/classes/class.ilPluginAdmin.php';
		return self::$instance = ilPluginAdmin::getPluginObject(
			self::CTYPE,
			self::CNAME,
			self::SLOT_ID,
			self::PNAME
		);
	}


	//has to return an array with instances of all cron jobs of the plugin
	function getCronJobInstances()
	{
		$job = new ilAfPImportCronJob();

		ilAfPLogger::getLogger()->write("getinstance new job-> ".$job->getId());
		return array($job);
	}

	//has to return a single instance of the cron job with the given id
	function getCronJobInstance($a_job_id)
	{
		$job = new ilAfPImportCronJob();

		ilAfPLogger::getLogger()->write("getinstance new job-> ".$job->getId());

		return $job;
	}

	/**
	 * Init auto load
	 */
	protected function init()
	{
		$this->initAutoLoad();
	}

	/**
	 * Init auto loader
	 * @return void
	 */
	protected function initAutoLoad()
	{
		spl_autoload_register(
			array($this,'autoLoad')
		);
	}

	/**
	 * Auto load implementation
	 *
	 * @param string class name
	 */
	private final function autoLoad($a_classname)
	{
		$class_file = $this->getClassesDirectory().'/class.'.$a_classname.'.php';
		if(@include_once($class_file))
		{
			return;
		}
	}
}
