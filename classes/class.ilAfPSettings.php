<?php

/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author Jesus Lopez <lopez@leifos.com>
 */
class ilAfPSettings
{
	private static $instance = null;
	private $storage = null;

	private $lock = false;
	private $backup_dir;

	private $cron = false;
	private $cron_interval = 5;
	private $cron_last_execution = 0;

	private $restUser;
	private $restUrl;
	private $restPassword;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->storage = new ilSetting('afpimport_config');
		$this->read();
	}

	/**
	 * Get singleton instance
	 *
	 * @return ilAfPSettings
	 */
	public static function getInstance()
	{
		if(self::$instance)
		{
			return self::$instance;
		}
		return self::$instance = new ilAfPSettings();
	}

	/**
	 * Get storage
	 * @return ilSetting
	 */
	public function getStorage()
	{
		return $this->storage;
	}

	public function setCronInterval($a_int)
	{
		$this->cron_interval = $a_int;
	}

	public function getCronInterval()
	{
		return $this->cron_interval;
	}

	/**
	 * Read (default) settings
	 */
	protected function read()
	{
		//we can get rid of the backup configuration and the cron_interval (save,read and gui)
		$this->setBackupDir($this->getStorage()->get('backup_dir',$this->getBackupDir()));
		$this->enableLock($this->getStorage()->get('lock',$this->isLocked()));
		$this->setCronInterval($this->getStorage()->get('cron_interval',$this->getCronInterval()));
		$this->cron_last_execution = $this->getStorage()->get('cron_last_execution',0);

		$this->setRestUrl($this->getStorage()->get('resturl',$this->getRestUrl()));
		$this->setRestUser($this->getStorage()->get('restuser',$this->getRestUser()));
		$this->setRestPassword($this->getStorage()->get('restpassword', $this->getRestPassword()));

	}

	public function setBackupDir($a_dir)
	{
		$this->backup_dir = $a_dir;
	}

	public function getBackupDir()
	{
		return $this->backup_dir;
	}

	public function enableLock($a_lock)
	{
		$this->lock = $a_lock;
	}

	public function isLocked()
	{
		return $this->lock;
	}

	/**
	 * Save settings
	 */
	public function save()
	{
		$this->getStorage()->set('lock',(int) $this->isLocked());
		$this->getStorage()->set('backup_dir',$this->getBackupDir());
		$this->getStorage()->set('cron_interval',$this->getCronInterval());

		$this->getStorage()->set('restpassword',$this->getRestPassword());
		$this->getStorage()->set('restuser',$this->getRestUser());
		$this->getStorage()->set('resturl', $this->getRestUrl());
	}

	public function updateLastCronExecution()
	{
		$this->getStorage()->set('cron_last_execution',time());
	}

	/**
	 * Create directories
	 *
	 * @throws ilException
	 */
	public function createDirectories()
	{
		if(!ilUtil::makeDirParents($this->getBackupDir()))
		{
			throw new ilException("Cannot create backup directory.");
		}
	}

	public function setRestUser($a_user)
	{
		$this->restUser = $a_user;
	}

	public function getRestUser()
	{
		return $this->restUser;
	}

	public function setRestUrl($a_rest_url)
	{
		$this->restUrl = $a_rest_url;
	}

	public function getRestUrl()
	{
		return $this->restUrl;
	}

	public function setRestPassword($a_pass)
	{
		$this->restPassword = $a_pass;
	}

	public function getRestPassword()
	{
		return $this->restPassword;
	}
}
