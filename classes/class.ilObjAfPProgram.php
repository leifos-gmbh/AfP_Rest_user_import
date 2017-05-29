<?php
/**
* Class ilObjAfPProgram
*
* @author Jesus Lopez <lopez@leifos.com>
* @version $Id$
*
* @extends ilObject2
*/

require_once "./Services/Object/classes/class.ilObject.php";

class ilObjAfPProgram extends ilObject
{
	var $id;
	var $afpid;
	var $type;
	var $ilias_ref;

	function ilObjAfPProgram($a_id = 0, $a_reference = false)
	{
		parent::__construct($a_id, $a_reference);
	}

	public function setId($a_id)
	{
		$this->id = $a_id;
	}

	public function getId()
	{
		return $this->id;
	}

	public function setAfPId($a_id)
	{
		$this->afpid = $a_id;
	}

	public function getAfPId()
	{
		return $this->afpid;
	}

	public function setIliasRef($a_ilias_ref)
	{
		$this->ilias_ref = $a_ilias_ref;
	}

	public function getIliasRef()
	{
		return $this->ilias_ref;
	}

	public function getDataFromAfPId($a_afpid)
	{
		global $ilDB;

		$query = "SELECT * FROM afp_ids WHERE ".
					"afp_id = ".$ilDB->quote($a_afpid, 'text');
		$res = $ilDB->query($query);
		$row = $ilDB->fetchAssoc($res);

		return $row;
	}

	function saveToDb()
	{
		global $ilDB;

		if ($this->getId() < 1)
		{
			$next_id = $ilDB->nextId('afp_ids');
			$affectedRows = $ilDB->insert("afp_ids", array(
				"id" => array("integer", $next_id),
				"afp_id" => array("text", $this->getAfPId()),
				"ilias_ref_id" => array("integer", $this->getIliasRef())
			));
			$this->setId($next_id);
		}
		else
		{
			$affectedRows = $ilDB->update("afp_ids", array(
				"ilias_ref_id" => array("text", $this->getIliasRef()),
			), array(
				"id" => array("integer", $this->getId())
			));
		}

	}


	static function getBasisPrograms()
	{
		$programs = array();

		$programs["KJP_VT"] = "Kinder- und Jugendlichenpsychotherapeut für Verhaltenstherapie";
		$programs["KJP_TP"] = "Kinder- und Jugendlichenpsychotherapeut für tiefenpsychologisch fundierte Psychotherapie";
		$programs["KJP_Psychoanalyse_(Verklammerte_A)"] = "Kinder und Jugendlichenpsychotherapeut (Verklammerte Ausbildung) - Psychoanalyse";
		$programs["PP_VT"] = "Psychologischer Psychotherapeut für Verhaltenstherapie";
		$programs["PP_TP"] = "Psychologischer Psychotherapeut für tiefenpsychologisch fundierte Psychotherapie";
		$programs["PP_Psychoanalyse_(Verklammerte_A)"] = "Psychologischer Psychotherapeut (Verklammerte Ausbildung) – Psychoanalyse";
		$programs["FA_KJP_Psychiatrie_TP"] = "Facharzt für Kinder- und Jugendpsychiatrie und -psychotherapie im Verfahren tiefenpsychologisch fundierte Psychotherapie";
		$programs["FA_KJP_Psychiatrie_VT"] = "Facharzt für Kinder- und Jugendpsychiatrie und -psychotherapie im Verfahren Verhaltenstherapie";
		$programs["FA_PP_Psychiatrie_TP"] = "Facharzt für Psychiatrie und Psychotherapie (Erwachsene) im Verfahren tiefenpsychologisch fundierte Psychotherapie";
		$programs["FA_PP_Psychiatrie_VT"] = "Facharzt für Psychiatrie und Psychotherapie (Erwachsene) im Verfahren Verhaltenstherapie";
		$programs["A_PP_Psychosomatik_TP"] = "Facharzt für Psychotherapie (Erwachsene) im Verfahren tiefenpsychologisch fundierte Psychotherapie";
		$programs["FA_PP_Psychosomatik_VT"] = "Facharzt für Psychotherapie (Erwachsene) im Verfahren Verhaltenstherapie";
		$programs["Zusatzbez_Psychoanalyse_PP"] = "Zusatzbezeichnung Psychoanalyse (Erwachsene)";
		$programs["Zusatzbez_Psychoanalyse_KJ"] = "Zusatzbezeichnung Psychoanalyse für Kinder und Jugendliche";
		$programs["Zusatzbez_TfP_PP"] = "Zusatzbezeichnung tiefenpsychologisch fundierte Psychotherapie (Erwachsene)";
		$programs["Zusatzbez_TfP_KJ"] = "Zusatzbezeichnung tiefenpsychologisch fundierte Psychotherapie für Kinder und Jugendliche";
		$programs["Zusatzbez_VT_PP"] = "Zusatzbezeichnung Verhaltenstherapie (Erwachsene)";
		$programs["Zusatzbez_VT_KJ"] = "Zusatzbezeichnung Verhaltenstherapie für Kinder und Jugendliche";

		return $programs;
	}

	static function getCustomPrograms()
	{
		$programs = array();

		$programs["FK_KJP_A"] = "Fachkunde für Kinder- und Jugendlichenpsychotherapeuten im Verfahren der Psychoanalyse";
		$programs["FK_KJP_VT"] = "Fachkunde für Kinder- und Jugendlichenpsychotherapie im Verfahren Verhaltenstherapie";
		$programs["FK_KJP_TP"] = "Fachkunde für Kinder- und Jugendlichenpsychotherapie im Verfahren tiefenpsychologisch fundierte Psychotherapie";
		$programs["FK_PP_A"] = "Fachkunde für Psychologische Psychotherapeuten im Verfahren der Psychoanalyse";
		$programs["FK_PP_VT"] = "Fachkunde für Psychologische Psychotherapeuten im Verfahren Verhaltenstherapie";
		$programs["FK_PP_TP"] = "Fachkunde für Psychologische Psychotherapeuten im Verfahren tiefenpsychologisch fundierte Psychotherapie";

		return $programs;
	}

	static function getOtherPrograms()
	{
		$programs = array();

		$programs ["GruppenFK_KJP_TP"] = "Gruppenfachkunde Kinder- und Jugendlichenpsychotherapie im Verfahren tiefenpsychologisch fundierte Psychotherapie";
		$programs ["GruppenFK_KJP_VT"] = "Gruppenfachkunde für Kinder- und Jugendlichenpsychotherapeuten im Verfahren Verhaltenstherapie";
		$programs ["GruppenFK_PP_TP"] = "Gruppenfachkunde im Bereich der Erwachsenentherapie im Verfahren tiefenpsychologisch fundierte Psychotherapie";
		$programs ["GruppenFK_PP_VT"] = "Gruppenfachkunde im Bereich der Erwachsenentherapie im Verfahren Verhaltenstherapie";
		$programs ["Sozialtherapie_/_Sucht"] = "";
		$programs ["Balintgruppe"] = "";
		$programs ["IFA"] = "";
		$programs ["Selbsterfahrung"] = "";
		$programs ["Entspannung_(AT_PMR_-_PP)"] = "";
		$programs ["Entspannung_(AT_PMR_-_KJ)"] = "";
		$programs ["Psychosomatische_Grundversorgung_(PGV)"] = "";
		$programs ["Traumatherapie_/_EMDR"] = "";

		return $programs;
	}




}