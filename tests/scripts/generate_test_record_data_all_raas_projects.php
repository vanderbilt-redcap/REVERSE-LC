<?php

$edc_pid = $module->getProjectId();
$dags = $module->getDAGs();

$edc_record_fields = [
	'screening_id',
	'redcap_data_access_group',
	'dag',	// dag->unique
	'dag_name',	//	dag->display
	'sex',	// 1 or 2 (or 3 or 4)
	'race_ethnicity',	/*
		1	race_eth___1	American Indian or Alaska Native
		2	race_eth___2	Asian
		3	race_eth___3	Black, African American, or African
		4	race_eth___4	Middle Eastern or North African
		5	race_eth___5	Native Hawaiian or Other Pacific Islander
		6	race_eth___6	White
		7	race_eth___7	None of the above
		8	race_eth___8	Unknown
	*/
	// 'transfusion_datetime',	// Y-m-d H:m
	'randomization_date',	// Y-m-d H:m
	'randomization',	// 1, 2, Z
	// 'transfusion_given',
	'append_d_calc',	// 1, 0
	'append_e_calc',	// 1, 0
	'randomization_arm',	// 1 - 4
	'sda_a1d1',	// 1, 0
	'sda_a1d2', // 1, 0
	'sda_a1d3', // 1, 0
	'sda_a1d4', // 1, 0
	'sda_a1d5', // 1, 0
	'sda_a3d1'	// 1, 0
];

$records = [];
for ($i = 1; $i <= 100; $i++) {
	// create new record object
	$record = new \stdClass();
	
	$record->screening_id = $i;
	$record->subjid = $i;
	
	// determine dag value
	$dags_index = round($i / 10);
	$current_dag_index = 1;
	foreach ($dags as $actual_index => $dag_obj) {
		if ($current_dag_index == $dags_index) {
			$record->redcap_data_access_group = $dag_obj->unique;
			break;
		}
		$current_dag_index++;
	}
	
	$record->sex = ($i + 1) % 2 + 1;
	$record->randomization = ($i + 1) % 2 + 1;
	$record->append_d_calc = ($i + 1) % 2;
	$record->append_e_calc = ($i + 1) % 2;
	$record->randomization_arm = ($i + 1) % 4 + 1;
	$record->sda_a1d1 = ($i + 1) % 2;
	$record->sda_a1d2 = ($i + 1) % 2;
	$record->sda_a1d3 = ($i + 1) % 2;
	$record->sda_a1d4 = ($i + 1) % 2;
	$record->sda_a1d5 = ($i + 1) % 2;
	$record->sda_a3d1 = ($i + 1) % 2;
	$record->randomization_date = date("Y-m-d H:m", strtotime("-" . (100 - $i) . " days"));
	
	$records[] = array([101] => [$record], ["subjid"] => $i);
}

// $abc = \REDCap::getData();
// echo ("\$abc: " . print_r($abc, true));


$result = \REDCap::saveData($edc_pid, 'json', json_encode($records), 'overwrite');
echo ("\$result: " . print_r($result, true));