<?php

/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Component/classes/class.ilPluginConfigGUI.php';

/**
 * @author Jesus Lopez <lopez@leifos.com>
 */
class ilAfPImportConfigGUI extends ilPluginConfigGUI
{
	/**
	 * Handles all commmands, default is "configure"
	 */
	public function performCommand($cmd)
	{
		global $ilCtrl;
		global $ilTabs;

		$ilTabs->addTab(
			'settings',
			ilAfPImportPlugin::getInstance()->txt('tab_settings'),
			$GLOBALS['ilCtrl']->getLinkTarget($this,'configure')
		);

		$ilTabs->addTab(
			'programs',
			ilAfPImportPlugin::getInstance()->txt('tab_programs'),
			$GLOBALS['ilCtrl']->getLinkTarget($this, 'definePrograms')
		);

		$ilTabs->addTab(
			'credentials',
			ilAfPImportPlugin::getInstance()->txt('tab_credentials'),
			$GLOBALS['ilCtrl']->getLinkTarget($this, 'credentials')
		);

		$ilCtrl->saveParameter($this, "menu_id");

		switch ($cmd)
		{
			default:
				$this->$cmd();
				break;
		}
	}

	/**
	 * Show settings screen
	 * @param ilPropertyFormGUI $form
	 * @global $tpl
	 * @global $ilTabs
	 */
	protected function configure(ilPropertyFormGUI $form = null)
	{
		global $tpl, $ilTabs;

		$ilTabs->activateTab('settings');

		if(!$form instanceof ilPropertyFormGUI)
		{
			$form = $this->initConfigurationForm();
		}
		$tpl->setContent($form->getHTML());
	}

	/**
	 * Init configuration form
	 * @global $ilCtrl
	 * @return ilPropertyFormGUI form
	 */
	protected function initConfigurationForm()
	{
		global $ilCtrl, $lng;

		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';

		$settings = ilAfPSettings::getInstance();

		$form = new ilPropertyFormGUI();
		$form->setTitle($this->getPluginObject()->txt('tbl_afp_settings'));
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->addCommandButton('save', $lng->txt('save'));
		$form->setShowTopButtons(false);

		$lock = new ilCheckboxInputGUI($this->getPluginObject()->txt('tbl_settting_lock'),'lock');
		$lock->setValue(1);
		$lock->setDisabled(!$settings->isLocked());
		$lock->setChecked($settings->isLocked());
		$form->addItem($lock);

		$backup = new ilTextInputGUI($this->getPluginObject()->txt('tbl_settings_backup'),'backup');
		$backup->setRequired(true);
		$backup->setSize(120);
		$backup->setMaxLength(512);
		$backup->setValue($settings->getBackupDir());
		$form->addItem($backup);

		// cron interval
		$cron_i = new ilNumberInputGUI($this->getPluginObject()->txt('cron'),'cron_interval');
		$cron_i->setMinValue(1);
		$cron_i->setSize(2);
		$cron_i->setMaxLength(3);
		$cron_i->setRequired(true);
		$cron_i->setValue($settings->getCronInterval());
		$cron_i->setInfo($this->getPluginObject()->txt('cron_interval'));
		$form->addItem($cron_i);

		return $form;
	}

	/**
	 * Save settings
	 */
	protected function save()
	{
		global $lng, $ilCtrl;

		$form = $this->initConfigurationForm();
		$settings = ilAfPSettings::getInstance();

		try {

			if($form->checkInput())
			{
				$settings->enableLock($form->getInput('lock'));
				//$settings->setImportDir($form->getInput('import'));
				$settings->setBackupDir($form->getInput('backup'));
				$settings->setCronInterval($form->getInput('cron_interval'));
				$settings->save();

				$settings->createDirectories();

				ilUtil::sendSuccess($lng->txt('settings_saved'),true);
				$ilCtrl->redirect($this,'configure');
			}
			$error = $lng->txt('err_check_input');
		}
		catch(ilException $e)
		{
			$error = $e->getMessage();
			ilAfPLogger::getLogger()->write("save() exception: ".$error);
		}
		$form->setValuesByPost();
		ilUtil::sendFailure($error);
		$this->configure($form);
	}

	// PROGRAMS TAB

	function definePrograms(ilPropertyFormGUI $form = null)
	{
		global $tpl, $ilTabs;

		$ilTabs->activateTab('programs');

		if(!$form instanceof ilPropertyFormGUI)
		{
			$form = $this->initProgramsForm();
		}
		$tpl->setContent($form->getHTML());
	}

	protected function initProgramsForm()
	{
		global $ilCtrl;

		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->setTitle($this->getPluginObject()->txt('form_programs_init'));

		$this->addProgramsToForm($form, ilObjAfPProgram::getBasisPrograms(), 'Basis Programs');

		$this->addProgramsToForm($form, ilObjAfPProgram::getCustomPrograms(), 'Custom Programs');

		$this->addProgramsToForm($form, ilObjAfPProgram::getOtherPrograms(), 'Other Trainings');

		$form->addCommandButton('savePrograms', $this->getPluginObject()->txt('btn_save_source_selection'));

		return $form;
	}

	function addProgramsToForm($a_form, $a_programs, $a_header)
	{
		global $lng;

		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($a_header);
		$a_form->addItem($header);

		foreach($a_programs as $id => $desc)
		{
			$ref = new ilNumberInputGUI($id, 'ref_'.$id);
			$ref->setRequired(false);
			$ref->setMinValue(1);
			$ref->setSize(7);
			$ref->setMaxLength(11);

			$obj_program = new ilObjAfPProgram();
			$program_data = $obj_program->getDataFromAfPId($id);

			$ref->setValue($program_data['ilias_ref_id']);

			if($program_data['ilias_ref_id'])
			{
				$obj_id = ilObject::_lookupObjId($program_data['ilias_ref_id']);
				if($obj_id) {
					$ref->setInfo($lng->txt("obj_".ilObject::_lookupType($obj_id)).
						": ".ilObject::_lookupTitle($obj_id));
				} else {
					$ref->setInfo($this->getPluginObject()->txt("no_object_assigned"));
				}
			}

			$a_form->addItem($ref);

		}
	}

	protected function savePrograms()
	{
		$form = $this->initProgramsForm();

		if(!$form->checkInput())
		{
			$form->setValuesByPost();
			ilUtil::sendFailure($GLOBALS['lng']->txt('err_check_input'));

			return $this->initProgramsForm($form);
		}

		try
		{
			foreach($_POST as $key => $value)
			{
				$pieces  = explode("ref_", $key);
				if(count($pieces) > 1)
				{
					$id = $pieces[1];

					$program = new ilObjAfPProgram();
					$program_data = $program->getDataFromAfPId($id);

					if(!empty($program_data))
					{
						$program->setId($program_data['id']);
					}

					$program->setIliasRef($value);
					$program->setAfPId($id);

					$program->saveToDb();
				}

			}

		}
		catch(ilException $e)
		{
			$error = $e->getMessage();
			ilAfPLogger::getLogger()->write("savePrograms() exception: ".$error);
		}
		$form->setValuesByPost();
		ilUtil::sendFailure($error);
		$this->definePrograms($form);
	}


	/* CREDENTIALS SECTION */
	/**
	 * Show credentials screen
	 * @param ilPropertyFormGUI $form
	 * @global $tpl
	 * @global $ilTabs
	 */
	protected function credentials(ilPropertyFormGUI $form = null)
	{
		global $tpl, $ilTabs;

		$ilTabs->activateTab('credentials');

		if(!$form instanceof ilPropertyFormGUI)
		{
			$form = $this->initCredentialsForm();
		}

		$tpl->setContent($form->getHTML());
	}

	/**
	 * Init credentials form
	 * @global $ilCtrl
	 * @return ilPropertyFormGUI form
	 */
	protected function initCredentialsForm()
	{
		global $ilCtrl, $lng;

		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';

		$settings = ilAfPSettings::getInstance();

		$form = new ilPropertyFormGUI();
		$form->setTitle($this->getPluginObject()->txt('tbl_afp_settings'));
		$form->setFormAction($ilCtrl->getFormAction($this));

		$form->addCommandButton('saveCredentials', $lng->txt('save'));
		$form->setShowTopButtons(false);

		$url = new ilTextInputGUI($this->getPluginObject()->txt('credentials_url'), 'resturl');
		$url->setRequired(true);
		$url->setSize(120);
		$url->setMaxLength(512);
		$url->setValue($settings->getRestUrl());
		$form->addItem($url);

		$user = new ilTextInputGUI($this->getPluginObject()->txt('credentials_user'),'restuser');
		$user->setRequired(true);
		$user->setSize(120);
		$user->setMaxLength(512);
		$user->setValue($settings->getRestUser());
		$form->addItem($user);

		$pass = new ilPasswordInputGUI($this->getPluginObject()->txt('credentials_password'), 'restpassword');
		$pass->setRequired(true);
		$pass->setRetype(false);
		$pass->setSize(120);
		$pass->setMaxLength(512);
		//$pass->setValue($settings->getRestPass());
		$form->addItem($pass);

		return $form;
	}

	protected function saveCredentials()
	{
		global $lng, $ilCtrl;

		$form = $this->initCredentialsForm();
		$settings = ilAfPSettings::getInstance();

		try
		{
			if($form->checkInput())
			{
				$settings->setRestUrl($form->getInput('resturl'));
				$settings->setRestUser($form->getInput('restuser'));
				$settings->setRestPassword($form->getInput('restpassword'));
				$settings->save();

				ilUtil::sendSuccess($lng->txt('settings_saved'),true);
				$ilCtrl->redirect($this,'credentials');
			}
			$error = $lng->txt('err_check_input');
		}
		catch(ilException $e)
		{
			$error = $e->getMessage();
			ilAfPLogger::getLogger()->write("saveCredentials() exception: ".$error);
		}
		$form->setValuesByPost();
		ilUtil::sendFailure($error);
		$this->credentials($form);
	}

}