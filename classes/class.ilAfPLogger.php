<?php

/* Copyright (c) 1998-20017 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Logging/classes/class.ilLog.php';

/**
 *
 * @author Jesus Lopez <lopez@leifos.com>
 */
class ilAfPLogger extends ilLog
{
	const LOG_TAG = 'afp_import';

	protected static $instance = null;

	protected function __construct()
	{
		include_once './Services/Calendar/classes/class.ilDateTime.php';
		$now = new ilDateTime(time(), IL_CAL_UNIX);

		parent::__construct(
			ilAfPSettings::getInstance()->getBackupDir(),
			$now->get(IL_CAL_FKT_DATE, 'Ymd_').'import.log',
			self::LOG_TAG
		);
	}
	/**
	 * Get logger
	 * @return ilAfPLogger
	 */
	public static function getLogger()
	{
		if(self::$instance != null)
		{
			return self::$instance;
		}
		return self::$instance = new self();
	}


	/**
	 * Write message
	 * @param type $a_message
	 */
	public function write($a_message)
	{
		$this->setLogFormat(date('[Y-m-d H:i:s] '));
		parent::write($a_message);
	}
}