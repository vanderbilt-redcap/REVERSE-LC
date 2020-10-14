<?php

namespace Vanderbilt\PassItOn;

// For now, the path to "redcap_connect.php" on your system must be hard coded.
require_once dirname(dirname(dirname(__DIR__))) . '/redcap_connect.php';
require_once APP_PATH_DOCROOT . '/ExternalModules/tests/ModuleBaseTest.php';

final class RecordsTest extends \ExternalModules\ModuleBaseTest
{
	## these static variables will hold values that we will compare our function results to
	// static $correct_site_a_data;

	public function setUp() : void {
		parent::setUp();

		## Initialize module cached data here
		$this->module->dags = 				json_decode(file_get_contents(__DIR__."/test_data/dags.json"));
		$this->module->project_ids = 		json_decode(file_get_contents(__DIR__."/test_data/project_ids.json"));
		$this->module->event_ids = 			json_decode(file_get_contents(__DIR__."/test_data/event_ids.json"));
		$this->module->edc_data = 			json_decode(file_get_contents(__DIR__."/test_data/edc_data.json"));
	}

	// output from these functions should be very predictable given the constrained test inputs
	public function testGetRecords() {
		$this->module->getRecords();
		$records = $this->module->records;
		
		// ensure our records are structured as expected
		$this->assertIsArray($records, "PassItOn->records is not an array after calling ->getRecords()");
		
		// ensure we have the correct number of rows
		$record_count = count($records);
		$this->assertTrue($record_count == 7, "Expected 7 records in ->records -- actual count: $record_count");
		
		// ensure each record is structured as expected
		$expected_properties = ['record_id', 'dag', 'sex', 'race_ethnicity', 'screen_date', 'randomization_date', 'transfusion_given'];
		foreach($records as $record) {
			foreach ($expected_properties as $property) {
				$this->assertObjectHasAttribute($property, $record, "record #{$record->record_id} is missing its '$property' property");
			}
		}
		
		// compare to file for other discrepancies
		$this->assertEquals($records, json_decode(file_get_contents(__DIR__."/test_data/records.json")));
	}
}