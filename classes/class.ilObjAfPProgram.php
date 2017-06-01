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

	/**
	 * Only Users who belongs to this programs will be migrated to ILIAS
	 * 	"BC", "BC + Vorkolloquium", "Vertiefte Ausbildung", "nur Behandlungstätigkeit", "in Weiterbildung"
	 */
	static function getProgramsToMigrate()
	{
		return array(
			"BC" => "BC",
			"BCV" => "BC + Vorkolloquium",
			"VA" => "Vertiefte Ausbildung",
			"BT" => "nur Behandlungstätigkeit",
			"inWB" => "in Weiterbildung"
		);
	}

	//ausbildungsphase
	static function getTrainingPeriodPrograms()
	{
		return array (
			"Abruch" => "abgebrochen",
			"Appr" => "bei uns approbiert",
			"Appr+WB" => "bei uns approbiert + WB abgeschl.",
			"BC" => "BC",
			"BCV" => "BC + Vorkolloquium",
			"BT" => "nur Behandlungstätigkeit",
			"inWB" => "in Weiterbildung",
			"VA" => "Vertiefte Ausbildung",
			"WB" => "WB abgeschlossen"
		);
	}

	//basisprogramme
	static function getBasisPrograms()
	{
		return array (
			"FA2PPTP" => "FA PP Psychosomatik TP",
			"FA2PPVT" => "FA PP Psychosomatik VT",
			"FAKJTP" => "FA KJP Psychiatrie TP",
			"FAKJVT" => "FA KJP Psychiatrie VT",
			"FAPPTP" => "FA PP Psychiatrie TP",
			"FAPPVT" => "FA PP Psychiatrie VT",
			"KJAP" => "KJP Psychoanalyse (Verklammerte A.)",
			"KJTP" => "KJP TP",
			"KJVT" => "KJP VT",
			"NZ" => "nicht zutreffend",
			"PPAP" => "PP Psychoanalyse (Verklammerte A.",
			"PPTP" => "PP TP",
			"PPVT" => "PP VT",
			"ZBAPKJ" => "Zusatzbez. Psychoanalyse KJ",
			"ZBAPPP" => "Zusatzbez. Psychoanalyse PP",
			"ZBTPKJ" => "Zusatzbez. TfP KJ",
			"ZBTPPP" => "Zusatzbez. TfP PP",
			"ZBVTKJ" => "Zusatzbez. VT KJ",
			"ZBVTPP" => "Zusatzbez. VT PP",
		);
	}

	//Weitere Angebote
	static function getMoreOffersPrograms()
	{
		return array (
			"ATPMRKJ" => "Entspannung (AT, PMR - KJ)",
			"ATPMRPP" => "Entspannung (AT, PMR - PP",
			"Balint" => "Balintgruppe",
			"GKJTP" => "GruppenFK KJP TP",
			"GKJVT" => "GruppenFK KJP VT",
			"GPPTP" => "GruppenFK PP TP",
			"GPPVT" => "GruppenFK PP VT",
			"IFA" => "IFA",
			"NZ" => "nicht zutreffend",
			"PGV" => "Psychosomatische Grundversorgung (PGV)",
			"SE" => "Selbsterfahrung",
			"Sozi" => "Socialtherapie / Sucht",
			"Trauma" => "Traumatherapie / EMDR",
		);
	}

	//Fachkunde
	static function getCustomerSubjectPrograms()
	{
		return array (
			"FKKJAP" => "FK KJP A",
			"FKKJTP" => "FK KJP TP",
			"FKKJVT" => "FK KJP VT",
			"FKPPAP" => "FK PP A",
			"FKPPTP" => "FK PP TP",
			"FKPPVT" => "FK PP VT",
			"NZ" => "nicht zutreffend",
		);
	}

}