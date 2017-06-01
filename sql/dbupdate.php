<#1>
<?php
if (!$ilDB->tableExists('afp_ids'))
{
	$fields = array(
		'id' => array(
			'type' => 'integer',
			'length' => 8,
			'notnull' => true
		),
		'afp_id' => array(
			'type' => 'text',
			'length' => 100,
			'notnull' => true
		),
		'ilias_ref_id' => array(
			'type' => 'integer',
			'length' => 8,
			'notnull' => false
		)
	);

	$ilDB->createTable("afp_ids", $fields);
	$ilDB->createSequence("afp_ids");
	$ilDB->addPrimaryKey("afp_ids", array("id", "afp_id"));
}
?>
