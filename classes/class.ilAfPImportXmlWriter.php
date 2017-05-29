<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* XML writer for Users, if more entities needed we can split it in different classes.
* @author Jesus Lopez <lopez@leifos.com>
*/
class ilAfPImportXmlWriter
{
	protected static $instance = null;

	protected $users_imported = array();

	protected $writer;

	protected $main_tag = "Users";

	function ilAfPImportXmlWriter()
	{
		$this->initWriter();
		$this->xmlHeading();
	}

	function setMainTag($a_tag)
	{
		$this->main_tag = $a_tag;
	}

	function getMainTag()
	{
		return $this->main_tag;
	}

	/**
	 * Init writer
	 */
	protected function initWriter()
	{
		include_once './Services/Xml/classes/class.ilXmlWriter.php';
		$this->writer = new ilXmlWriter();
	}

	protected function xmlHeading()
	{
		$this->writer->xmlHeader();
		$this->writer->xmlStartTag($this->main_tag);

	}

	protected function xmlEnding()
	{
		$this->writer->xmlEndTag($this->main_tag);
	}

	public function createXMLFile()
	{
		$this->xmlEnding();
		$this->writer->xmlDumpFile(ilAfPImport::getInstance()->getXmlFile(), false);
	}

	function fillData($a_data)
	{

		foreach ($a_data as $data)
		{
			//login must be name.lastname or name.lastname.number
			$login = $data['name'].".".$data['lastname'];
			$count = 1;
			while(in_array($login, $this->users_imported))
			{
				$login = $login.$count;
				$count ++;
			}

			//gender forced as 'f' or 'm'
			if(strtolower($data['gender']) == 'frau') {
				$gender = 'f';
			} else {
				$gender = 'm';
			}

			array_push($this->users_imported, $login);

			//TODO which value is the COURSE?
			$this->writer->xmlStartTag(
				'User',
				array(
					"Login" => $login,
					"Action" => "Insert"
				)
			);

			$this->writer->xmlElement('Login', null, $login);
			$this->writer->xmlElement('Role', array("Id"=>"il_0_role_4", "Type"=>"Global"),'User'); //TODO Ask which role we have to assign for the users.
			$this->writer->xmlElement('Title', null, $data['title']);
			$this->writer->xmlElement('Firstname', null, $data['name']);
			$this->writer->xmlElement('Lastname', null, $data['lastname']);
			$this->writer->xmlElement('Gender', null, $gender);
			$this->writer->xmlElement('Street', null, $data['street']);
			$this->writer->xmlElement('PostalCode', null, $data['postcode']);
			$this->writer->xmlElement('City', null, $data['city']);
			$this->writer->xmlElement('PhoneOffice', null, $data['phone']);//Is this OK?  //TODO some phones are like -> "phone": "0049 160 96588870 Patientenhandy"
			$this->writer->xmlElement('Email',null, $data['email']);  //TODO some people doesn't have email... yes, it's true :S
			$this->writer->xmlElement("Institution", null, $data['company']);
ilLoggerFactory::getRootLogger()->debug("user id => ".$data['user_id']);
			$this->writer->xmlElement("ImportId", null, $data['user_id']);

			$this->writer->xmlEndTag('User');

		}


	}

}

/*
 * DATA NEEDED
 * User (Login, Role*, Password?, Firstname?, Lastname?, Title?, PersonalPicture?, Gender?, Email?, Birthday?,
	Institution?, Street?, City?, PostalCode?, Country?, SelCountry?, PhoneOffice?, PhoneHome?,
	PhoneMobile?, Fax?, Hobby?, Department?, Comment?, Matriculation?, Active?, ClientIP?,
	TimeLimitOwner?, TimeLimitUnlimited?, TimeLimitFrom?, TimeLimitUntil?, TimeLimitMessage?,
	ApproveDate?, AgreeDate?, AuthMode?, ExternalAccount?, Look?, LastUpdate?, LastLogin?, UserDefinedField*, AccountInfo*, GMapsInfo?)>

 */