<?php
namespace Vanderbilt\REVERSE_LC;

use stdClass;
use UserRights;

class REVERSE_LC extends \ExternalModules\AbstractExternalModule {
	public $edc_data;
	public $uad_data;
	private $access_tier_by_role = [
		'1042' => '1',		//	Safety Reviewer/DSMB
		'1045' => '1',		//	Medical Monitor
		'1028' => '3',		//	Principal Investigator (National)
		'1039' => '2',		//	Principal Investigator (Site)
		'1046' => '3',		//	Study Manager (National)
		'1044' => '2',		//	Study Manager (Site)
		'1043' => '3',		//	Statistical Team
		'1041' => '2',		//	Site Study Team
		'1040' => '2',		//	Sub-Investigator (Site)
		'1047' => '3',		//	Financial Team/Administration
		'1048' => '3',		//	Study Leadership/Steering Committee
		'1049' => '1',		//	IDS
		'1050' => '1',		//	Blood Bank
		'1051' => '1',		//	Lab Personnel
		'1030' => '1'		//	Other
	];
	public $record_fields = [
		'screening_id',
		'redcap_data_access_group',
        'dag',
        'dag_name',
		'sex',
		'race_ethnicity',
		'transfusion_datetime',
		'randomization_date',
		'randomization',
		'append_d_calc',
		'append_e_calc',
        'append_f_calc',
        'append_g_calc',
		'randomization_arm',
		'sda_a1d1',
		'sda_a1d2',
		'sda_a1d3',
		'sda_a1d4',
		'sda_a1d5',
		'sda_a3d1',
        'sda_given_a3d1',
        'sda_given_a3d2',
        'sda_given_a3d3',
        'sda_given_a3d4',
        'sda_given_a3d5',
        'sda_given_a3d6',
        'sda_given_a3d7',
        'sda_given_a3d8',
        'sda_given_a3d9',
        'sda_given_a3d10',
        'sda_given_a3d11',
        'sda_given_a3d12',
        'sda_given_a3d13',
        'sda_given_a3d14',
        'sda_given_a3d15',
        'sda_given_a3d16',
        'sda_given_a3d17',
        'sda_given_a3d18',
        'sda_given_a3d19',
        'sda_given_a3d20',
        'sda_given_a3d21',
        'sda_given_a3d22',
        'sda_given_a3d23',
        'sda_given_a3d24',
        'sda_given_a3d25',
        'sda_given_a3d26',
        'sda_given_a3d27',
        'sda_given_a3d28',
        'sda_a4d1',
        'sda_a4d2',
        'sda_a4d3',
        'sda_a4d4',
        'sda_a4d5',
        'sda_a4d6',
        'sda_a4d7',
        'sda_a4d8',
        'sda_a4d9',
        'sda_a4d10'
	];
	public $personnel_roles = [
		'PI',
		'Primary Coordinator',
		'Primary dispensing pharmacist'
	];
	public $document_signoff_fields = [
		'cv' => 'cv_review_vcc',
		'doa' => 'doa_vcc_review',
		'license' => 'license_review_vcc',
		'fdf' => 'fin_dis_review_vcc',
		'hand_prof' => 'handwrite_review_vcc',
		'gcp' => 'gcp_review_vcc',
		'hsp' => 'citi_review_vcc',
		'training' => 'train_review_vcc'
	];
	public $personnel_form_complete_fields = [
		'cv_complete',
		'license_complete',
		'study_training_complete',
		'human_subjects_training_complete',
		'gcp_training_complete',
		'delegation_of_authority_doa_complete',
		'financial_disclosure_complete',
		'iata_training_complete',
		'handwriting_profile_complete'
	];
	public $filteredSiteFields = [
        "template_sent",
        "contract_pe",
        "contract_fe",
        "vcc_survey_sent",
        "vcc_survey_received",
        "vcc_survey_accepted",
        "vcc_survey_accepted",
        "vcc_pt2_received",
        "vcc_pt2_reviewed",
        "vcc_pt2_approved",
        "institutional_profile_complete",
        "hrp_agreement",
        "pi_survey",
        "site_add_ready",
        "site_add"
    ];
	
	private const MAX_FOLDER_NAME_LEN = 60;		// folder names truncated after n characters

	public function __construct() {
		parent::__construct();
		define("CSS_PATH_1",$this->getUrl("css/style.css"));
		define("CSS_MAIN_ACTIVE", $this->getUrl("css/mainActive.css"));

		define("JS_PATH_1",$this->getUrl("js/dashboard.js"));
		define("LOGO_LINK", $this->getUrl("images/main_logo.png"));

		require_once(__DIR__."/vendor/autoload.php");
		
		$id_field_name = "record_id";
		$this->id_field_name = $id_field_name;
		$this->record_fields[] = $id_field_name;
	}
	
	// LOW LEVEL methods
	public function getEventIDs() {
		if (!isset($this->event_ids)) {
			$event_ids = new \stdClass();
			$event_ids->demographics = $this->getProjectSetting('demographics_event');
			$event_ids->transfusion = $this->getProjectSetting('transfusion_event');
			$event_ids->screening = $this->getProjectSetting('screening_event');
			$this->event_ids = $event_ids;
		}
		
		return $this->event_ids;
	}

	public function getDAGs($project_id = false) {
		if (!isset($this->dags)) {
		    if(!$project_id) {
				$edcProjectId = $this->getProjectSetting('edc_project');
		        $project_id   = $edcProjectId;
            }
			
			// create global $Proj that REDCap class uses to generate DAG info
			$EDCProject = new \Project($project_id);
			$dags_unique = $EDCProject->getUniqueGroupNames();
			$dags_display = $EDCProject->getGroups();
			$dags = new \stdClass();
			foreach ($dags_unique as $group_id => $unique_name) {
				// get display name
				if (empty($display_name = $dags_display[$group_id]))
					$display_name = "";
				
				// add entry with unique and display name with group_id as key
				$dags->$group_id = new \stdClass();
				$dags->$group_id->unique = $unique_name;
				$dags->$group_id->display = $display_name;
				
				unset($display_name);
			}
			
			$this->dags = $dags;
		}
		
		return $this->dags;
	}
	public function getFieldLabelMapping($fieldName = false) {

		$edcProjectId = $this->getProjectSetting('edc_project');
		$Proj   = new \Project($edcProjectId);

		if ($Proj->metadata[$fieldName]['element_type'] == 'calc') {
			return false;
		}
		
		if(!isset($this->mappings)) {
			$this->mappings = [];
			foreach($this->record_fields as $thisField) {
				$choices = $this->getChoiceLabels($thisField,$edcProjectId);

				if($choices && (count($choices) > 1 || reset($choices)) != "") {
					$this->mappings[$thisField] = $choices;
				}
			}
		}

		if($fieldName) {
			if(isset($this->mappings[$fieldName])) {
				return $this->mappings[$fieldName];
			}
			else {
				return false;
			}
		}
		else {
			return $this->mappings;
		}
	}

	public function getUADData($project_id = false) {
		if (!isset($this->uad_data)) {
			$this->uad_data = false;
		    if(!$project_id) {
		        $edcProjectId = $this->getProjectSetting('edc_project');
		        $project_id   = $edcProjectId;
            }

			//User Access is defined in Regulatory Database
		    $uadProject = $this->getProjectSetting("site_regulation_project",$project_id);
			$thisUser   = $this->getUser();
			$username   = $thisUser->getUsername();
			$rolField   = $this->getProjectSetting("role_field",$project_id);
			
			$params = [
				'project_id' => $uadProject,
				'return_format' => 'json',
				'fields' => [
					'dashboard',
					$rolField,
					'pref_name'
				],
				'filterLogic' => '[user_name] = "'.$username.'"'
			];
			$uad_data = json_decode(\REDCap::getData($params));
			
			if(!empty($uad_data)) {
				$rights = $thisUser->getRights();
				$this->uad_data = $uad_data[0];
				$this->uad_data->role = $this->uad_data->$rolField;
				if(!empty($rights['group_id'])) {
					$DAGs = (array)$this->getDAGs($uadProject);
					$this->uad_data->dag_group_name = $DAGs[$rights['group_id']]->unique;
				}
			}
		}
		return $this->uad_data;
	}

	public function getEDCData($project_id = false)
	{
		if (!isset($this->edc_data) || !$this->edc_data) {
            if(!$project_id) {
				$edcProjectId = $this->getProjectSetting('edc_project');
                $project_id   = $edcProjectId;
            }
			$this->getEventIDs();
			$params = [
				'project_id' => $project_id,
				'return_format' => 'json',
				'fields' => $this->record_fields,
				'events' => (array) $this->event_ids,
                'exportDataAccessGroups' => true
			];
			$edc_data = json_decode(\REDCap::getData($params));
			$projectDags = $this->getDAGs($project_id);

			// add dag and dag_name property to each record
			foreach ($edc_data as $record) {
				foreach($projectDags as $groupId => $thisDag) {
				    if($thisDag->unique == $record->redcap_data_access_group) {
                        $record->dag = $groupId;
                        $record->dag_name = $thisDag->display;
                        break;
                    }
                }
			}
			$this->edc_data = $edc_data;
		}
		
		return $this->edc_data;
	}

    public function getScreeningData($projectId = false) 
	{
        if(!$this->screening_data) {
            if(!$projectId) {
				$edcProjectId = $this->getProjectSetting('edc_project');
                $projectId    = $edcProjectId;
            }

            $screeningProjectId = $this->getProjectSetting("screening_project", $projectId);

            $this->screening_data = json_decode(\REDCap::getData([
                "project_id" => $screeningProjectId,
				"return_format" => "json",
                'exportDataAccessGroups' => true
            ]));
			
			$screeningProject = new \Project($screeningProjectId);
			preg_match_all("/<li>(.+)$/m", $screeningProject->metadata['excl_desc_4']['element_label'], $labels);
			$this->excl_desc_labels = $labels[1];
        }

        return $this->screening_data;
    }

	public function getInclusionData() 
	{
		if (!$this->screening_data) {
			$this->getScreeningData();
		}
		$inclusionData = [];
		foreach ($this->screening_data as $screening_rid => $screening_record) {
			$dag = $screening_record->redcap_data_access_group;
			if (!isset($inclusionData[$dag])) {
				$inclusionData[$dag] = 0;
			}
			if ($screening_record->continue_yn == '1' && $screening_record->exclude_yn != '1') {
				$total++;
				$inclusionData[$dag]++;
			}
		}
		
		$inclusionData["_total"] = $total;
		return $inclusionData;
	}

	private function getRegulatoryData($projectId = false) 
	{
		if (!$this->regulatory_data) {
			if(!$projectId) {
				$edcProjectId = $this->getProjectSetting('edc_project');
                $projectId    = $edcProjectId;
			}
			
            $regulatoryProjectId = $this->getProjectSetting("site_regulation_project", $projectId);
			$this->regulatory_data = json_decode(\REDCap::getData([
                "project_id" => $regulatoryProjectId,
				"return_format" => "json",
                'exportDataAccessGroups' => true,
				"fields" => ["record_id", "edc_dag_id", "site_international"]
            ]));
			
			$this->regulatory_data['domestic_sites'] = [];
			$this->regulatory_data['international_sites'] = [];
			
			$this->regulatory_data['domestic_dag_ids'] = [];
			$this->regulatory_data['international_dag_ids'] = [];
			
			foreach ($this->regulatory_data as $reg_record) {
				if ($reg_record->site_international == '1') {
					$this->regulatory_data['international_sites'][] = $reg_record->record_id;
					if (!empty($reg_record->edc_dag_id)) {
						$this->regulatory_data['international_dag_ids'][] = $reg_record->edc_dag_id;
					}
				} elseif ($reg_record->site_international == '0') {
					$this->regulatory_data['domestic_sites'][] = $reg_record->record_id;
					if (!empty($reg_record->edc_dag_id)) {
						$this->regulatory_data['domestic_dag_ids'][] = $reg_record->edc_dag_id;
					}
				}
			}
        }
        return $this->regulatory_data;
	}
	
	public function getDashboardUser()
	{
		if (!isset($this->user)) {
			$this->user = $this->getUADData();
			define("PIO_USER_DISPLAY_NAME", $this->user->pref_name);
		}
		return $this->user;
	}

	// HIGHER LEVEL methods
	public function authorizeUser() 
	{
		$this->getDashboardUser();

		//The access level 1, 2, or 3 is assigned to a specific role using the module settings.
		/*
			user->authorized == '1'
                        user can access dashboard
                        user can see all sites data
                        user cannot see my site data
            user->authorized == '2'
                        user can access dashboard
                        user can see all sites data
                        user can my site data -- results limited to records with DAG that matches user's DAG
            user->authorized == '3'
                        user can access dashboard
                        user can see all sites data
                        user can see my site data -- including all patient rows from all sites
		*/
		if ($this->user === false) {
			$this->user = new stdClass();
			//VCC staff won’t be entered as records in the Regulatory Database
			//So we need to look at the system role
			$uadProject = $this->getProjectSetting("site_regulation_project",$this->getProjectId());
			$thisUser   = $this->getUser();
			$username   = $thisUser->getUsername();
			$userRights = UserRights::getPrivileges($uadProject,$username);
			$userRights = $userRights[$uadProject][$username]['role_name'];
			// If role starts with "VCC" give total access
			if (strpos($userRights, "VCC") === 0) {
				$this->user->authorized = 3; //
				$this->user->dashboard  = 1;
				return;
			} else {
				$this->user->authorized = false;
			}
			return;
		} elseif ($this->user->dashboard == 0) {
			$this->user = new stdClass();
			$this->user->authorized = false;
			return;
		} elseif (empty($this->user->authorized)) { //If this user has not been authorized yet.
			$userRole    = $this->user->role;
			$accessLevel = $this->getAccessLevelByRole($userRole);
			$this->user->authorized = $accessLevel;
		}
	}

	public function getRecords($project_id = false)
	{
		if (!isset($this->records)) {
			if($_GET['TESTING']) {
				$this->records = json_decode(file_get_contents(__DIR__."/tests/test_data/records.json"),true);
				
				return $this->records;
			}

            if(!$project_id) {
				$edcProjectId = $this->getProjectSetting('edc_project');
		        $project_id   = $edcProjectId;
            }
			$this->getEDCData($project_id);
			// iterate over edc_data, collating data into record objects
			$temp_records_obj = new \stdClass();
			foreach ($this->edc_data as $record_event) {
				// establish $record and $rid
				$id_field_name = $this->id_field_name;
				$rid = $record_event->$id_field_name;
				if (!$record = $temp_records_obj->$rid) {
					$record = new \stdClass();
					
					// set empty fields
					foreach ($this->record_fields as $field) {
						$record->$field = "";
					}
					
					$record->$id_field_name = $rid;
					$temp_records_obj->$rid = $record;
				}
				
				// set non-empty fields
				foreach ($this->record_fields as $field) {
					if (!empty($record_event->$field)) {
						$labels = $this->getFieldLabelMapping($field);
						
						if($labels != false) {
							$record->$field = $labels[$record_event->$field];
						} else {
							$record->$field = $record_event->$field;
						}

						## Special shortening for certain fields
						if($field == "sex") {
							$record->$field = substr($record->$field, 0, 1);
						}
					}
				}
			}
			
			$records = [];
			foreach ($temp_records_obj as $record) {
				if (!empty($record->redcap_data_access_group))
					$records[] = $record;
			}
			
			$this->records = $records;
		}
		return $this->records;
	}

	public function getAccessLevelByRole($userRole)
	{
		$project_id  = $this->getProjectId();
		$roles       = $this->getProjectSetting("access_level_field_value",$project_id);
		$level       = $this->getProjectSetting("access_level",$project_id);
		$accessLevel = [];
		foreach($roles as $seq => $role) {
			$accessLevel[$role] = $level[$seq];
		}
		return $accessLevel[$userRole];
	}

	public function getMySiteData()
	{
		if($_GET['TESTING']) {
			return json_decode(file_get_contents(__DIR__."/tests/test_data/site_a_data.json"),true);
		}
		
		$this->getDAGs();
		$this->getDashboardUser();
		$this->getRecords();
		$this->authorizeUser();
		
		//Access level pending
		if ($this->user->authorized == false or $this->user->authorized == '1') {
			$this->my_site_data = false;
			return $this->my_site_data;
		}
		$site_data = new \stdClass();
		$site_data->site_name = "";
		$site_data->rows = [];
		$site_data->site_name = $this->user->dag_group_name;
		// add record rows
		foreach ($this->records as $record) {
			if (($this->user->authorized == '2' and $record->dag_name == $this->user->dag_group_name) or $this->user->authorized == '3') {
				$row = new \stdClass();
				$id_field_name = $this->id_field_name;
				$row->id = $record->$id_field_name;
				if ($this->user->authorized == '3') {
					$row->site = $record->dag_name;
				}
				$row->sex = $record->sex;
				$row->race = $record->race_ethnicity;
				$row->enrolled = $record->randomization_date;
				$row->treated = "";
				// convert transfusion_datetime from Y-m-d H:m to Y-m-d
				if (!empty($record->transfusion_datetime))
					$row->treated = date("Y-m-d", strtotime($record->transfusion_datetime));

				$site_data->rows[] = $row;
			}
		}
		
		// sort site level data
		if (!function_exists(__NAMESPACE__ . '\sortSiteData')) {
			function sortSiteData($a, $b) {
				if ($a->enrolled == $b->enrolled)
					return 0;
				return $a->enrolled > $b->enrolled ? -1 : 1;
			}
		}
		uasort($site_data->rows, __NAMESPACE__ . '\sortSiteData');
		
		// return
		$this->my_site_data = $site_data;
		return json_decode(json_encode($this->my_site_data), true);
	}

	public function getAllSitesData() 
	{
		if($_GET['TESTING']) {
			return json_decode(file_get_contents(__DIR__."/tests/test_data/all_sites_data.json"),true);
		}
		$this->getDAGs();
		$this->getRecords();
		$inclusionData = $this->getInclusionData();
		$reg_data = $this->getRegulatoryData();
		
		$data = new \stdClass();
		$data->totals = json_decode('[
			{
				"name": "Target",
				"fpe": "-",
				"lpe": "-",
				"screened": "-",
				"eligible": "-",
				"randomized": 1600,
				"baricitinib": "0",
				"treated": 1600
			},
			{
				"name": "Current Enrolled",
				"fpe": "-",
				"lpe": "-",
				"screened": ' . $inclusionData['_total'] . ',
				"eligible": "0",
				"randomized": "0",
				"baricitinib": "0",
				"treated": "0"
			}
		]');
		
		// create temporary sites container
		$sites = new \stdClass();
		foreach ($this->records as $record) {
			//Ignore the record if doesn't have a DAG
			if (!$patient_dag = $record->dag)
				continue;

			// get or make site object
			if (!$site = $sites->$patient_dag) {
				$sites->$patient_dag = new \stdClass();
				$site = $sites->$patient_dag;
				$site->name = $record->dag_name;
				$site->dag = $record->redcap_data_access_group;
				$site->group_id = $record->dag;
				
				$site->fpe = '-';
				$site->lpe = '-';
				
				$site->screened = 0;
				if (isset($inclusionData[$site->dag])) {
					$site->screened = $inclusionData[$site->dag];
				}
				
				$site->eligible = 0;
				$site->randomized = 0;
                $site->baricitinib = 0;
				$site->treated = 0;
			}
			// // update columns using patient data
			// FPE and LPE
			$enroll_date = $record->randomization_date;

			if (!empty($enroll_date)) {
				if ($site->fpe == '-') {
					$site->fpe = $enroll_date;
				} else {
					if (strtotime($site->fpe) > strtotime($enroll_date))
						$site->fpe = $enroll_date;
				}
				if ($site->lpe == '-') {
					$site->lpe = $enroll_date;
				} else {
					if (strtotime($site->lpe) < strtotime($enroll_date))
						$site->lpe = $enroll_date;
				}
			}
			// Eligible
			if ($record->append_d_calc == 1 ||
                $record->append_e_calc == 1 ||
                $record->append_f_calc == 1 ||
                $record->append_g_calc == 1
            ) {
				$data->totals[1]->eligible++;
				$site->eligible++;
				
			}
			// Randomized
			if ($record->randomization_arm != '') {
				$data->totals[1]->randomized++;
    
				$site->randomized++;
                if ($record->randomization_arm === "Baricitinib") {
                    $data->totals[1]->baricitinib++;
                    $site->baricitinib++;
                }
			}
			// Treated
			if (
                $record->sda_a1d1 == '1' ||
                $record->sda_a1d2 == '1' ||
                $record->sda_a1d3 == '1' ||
                $record->sda_a1d4 == '1' ||
                $record->sda_a1d5 == '1' ||
                $record->sda_a3d1 == '1' ||
                $record->sda_given_a3d1 == '1' ||
                $record->sda_given_a3d2 == '1' ||
                $record->sda_given_a3d3 == '1' ||
                $record->sda_given_a3d4 == '1' ||
                $record->sda_given_a3d5 == '1' ||
                $record->sda_given_a3d6 == '1' ||
                $record->sda_given_a3d7 == '1' ||
                $record->sda_given_a3d8 == '1' ||
                $record->sda_given_a3d9 == '1' ||
                $record->sda_given_a3d10 == '1' ||
                $record->sda_given_a3d11 == '1' ||
                $record->sda_given_a3d12 == '1' ||
                $record->sda_given_a3d13 == '1' ||
                $record->sda_given_a3d14 == '1' ||
                $record->sda_given_a3d15 == '1' ||
                $record->sda_given_a3d16 == '1' ||
                $record->sda_given_a3d17 == '1' ||
                $record->sda_given_a3d18 == '1' ||
                $record->sda_given_a3d19 == '1' ||
                $record->sda_given_a3d20 == '1' ||
                $record->sda_given_a3d21 == '1' ||
                $record->sda_given_a3d22 == '1' ||
                $record->sda_given_a3d23 == '1' ||
                $record->sda_given_a3d24 == '1' ||
                $record->sda_given_a3d25 == '1' ||
                $record->sda_given_a3d26 == '1' ||
                $record->sda_given_a3d27 == '1' ||
                $record->sda_given_a3d28 == '1' ||
                $record->sda_a4d1 == '1' ||
                $record->sda_a4d2 == '1' ||
                $record->sda_a4d3 == '1' ||
                $record->sda_a4d4 == '1' ||
                $record->sda_a4d5 == '1' ||
                $record->sda_a4d6 == '1' ||
                $record->sda_a4d7 == '1' ||
                $record->sda_a4d8 == '1' ||
                $record->sda_a4d9 == '1' ||
                $record->sda_a4d10 == '1'
			) {
				$data->totals[1]->treated++;
				$site->treated++;
			}
		}
		
		// site objects updated with patient data, dump into $data->sites
		// effectively removing keys and keeping values in array
		foreach ($sites as $site) {
			$data->sites[] = $site;

			// Ensure $data->totals[1] and 'screened' are initialized
			$data->totals[1] = $data->totals[1] ?? new stdClass();
			$data->totals[1]->screened = $data->totals[1]->screened ?? 0;

			$data->totals[1]->screened += $site->screened;
		}

		// sort all sites, randomized descending
		if (!function_exists(__NAMESPACE__ . '\sortAllSitesData')) {
			function sortAllSitesData($a, $b) {
				if ($a->randomized == $b->randomized)
					return 0;
				return $a->randomized < $b->randomized ? 1 : -1;
			}
		}
		
		// return
		$this->all_sites_data = $data;
		return json_decode(json_encode($this->all_sites_data), true);
	}

	public function updateAllSitesData(&$sites, $startup) 
	{
		foreach ($sites as &$site) {
			// Site Activation (date)
			foreach ($startup->sites as $site_i => $siteData) {
				$site_number = $siteData->site_number;
				if ($site_number == substr($site['name'], 0, strlen($site_number))) {
					$site['open_date'] = $siteData->open_date;
					break;
				}
			}
		}
	}
	public function getScreeningLogData($site = null) {
		// determine earliest screened date (upon which weeks array will be based)
		$screening_data = $this->getScreeningData();
		$first_date = date("Y-m-d");
		$last_date = date("Y-m-d", 0);
		foreach ($screening_data as $record) {
			$site_match_or_null = $site === null ? true : $record->redcap_data_access_group == $site;
			if (!empty($record->dos) and $site_match_or_null) {
				if (strtotime($record->dos) < strtotime($first_date))
					$first_date = date("Y-m-d", strtotime($record->dos));
				if (strtotime($record->dos) > strtotime($last_date))
					$last_date = date("Y-m-d", strtotime($record->dos));
			}
		}
		if (strtotime($last_date) == 0)
			$last_date = $first_date;
		
		// determine date of Monday on or before first_date found
		$day_of_week = date("N", strtotime($first_date));
		$rewind_x_days = $day_of_week - 1;
		$first_monday = date("Y-m-d", strtotime("-$rewind_x_days days", strtotime($first_date)));
		
		// make report data object and rows
		$screening_log_data = new \stdClass();
		$screening_log_data->rows = [];
		$total_screened = 0;
		$iterations = 0;
		while (true) {
			$screened_this_week = 0;
			
			// determine week boundary dates
			$day_offset1 = ($iterations) * 7;
			$day_offset2 = $day_offset1 + 4;
			$date1 = date("Y-m-d", strtotime("+$day_offset1 days", strtotime($first_monday)));
			$date2 = date("Y-m-d", strtotime("+$day_offset2 days", strtotime($first_monday)));
			
			$row = [];
			$row[0] = date("n/j/y", strtotime($date1)) . "-" . date("n/j/y", strtotime($date2));
			$row[0] = str_replace("\\", "", $row[0]);
			
			// count records that were screened this week
			$ts_a = strtotime($date1);
			$ts_b = strtotime("+24 hours", strtotime($date2));
			// echo "\$date1, \$date2, \$ts_a, \$ts_b: $date1, $date2, $ts_a, $ts_b\n";
			foreach ($screening_data as $record) {
				$ts_x = strtotime($record->dos);
				$site_match_or_null = $site === null ? true : $record->redcap_data_access_group == $site;
				if ($ts_a <= $ts_x and $ts_x <= $ts_b and $site_match_or_null)
					$screened_this_week++;
			}
			$total_screened += $screened_this_week;
			
			$row[1] = $screened_this_week;
			$row[2] = $total_screened;
			
			$screening_log_data->rows[] = $row;
			
			$iterations++;
			
			// see if the week row just created captures the last screened date
			// if so, break here
			$cutoff_timestamp = strtotime("+1 days", $ts_b);
			if ($cutoff_timestamp > strtotime($last_date) or $iterations > 999)
				break;
		}
		$screening_log_data->rows[] = ["Grand Total", $total_screened, $total_screened];
		return $screening_log_data;
	}

	public function getEnrollmentChartData($options = null) 
	{
		// determine earliest screened date (upon which weeks array will be based)
		// 2021-08-05 we're changing to count "Randomized" instead of "Enrolled"
		
		if (isset($options['site_dag'])) {
			$site = $options['site_dag'];
		}
		if (isset($options['site_locality'])) {
			$site_locality = $options['site_locality'];
		}
		$all_enroll_data = $this->getEDCData();
        $randArmlabels = $this->getFieldLabelMapping('randomization_arm');
		if(is_bool($randArmlabels)){
			$randArmlabels = [];
		}
		$enroll_data_to_consider = [];
		$first_date = date("Y-m-d");
		$last_date = date("Y-m-d", 0);
		foreach ($all_enroll_data as $record) {
			if ($site_locality == "Domestic" and $this->isSiteDomestic($record->dag) === false) {
				continue;
			} elseif ($site_locality == "International" and $this->isSiteInternational($record->dag) === false) {
				continue;
			}
			
			$site_match_or_null = $site === null ? true : $record->redcap_data_access_group == $site;
			if (!empty($record->randomization_date) and $site_match_or_null) {
				if (strtotime($record->randomization_date) < strtotime($first_date))
					$first_date = date("Y-m-d", strtotime($record->randomization_date));
				if (strtotime($record->randomization_date) > strtotime($last_date))
					$last_date = date("Y-m-d", strtotime($record->randomization_date));
			}
			$enroll_data_to_consider[] = $record;
		}
		if (strtotime($last_date) == 0)
			$last_date = $first_date;
		
		// determine date of sunday on or before first_date found
		$day_of_week = date("N", strtotime($first_date));
		$rewind_x_days = $day_of_week;
		$first_sunday = date("Y-m-d", strtotime("-$rewind_x_days days", strtotime($first_date)));
		
		// make report data object and rows
		$enrollment_chart_data = new \stdClass();
		$enrollment_chart_data->rows = [];
		$cumulative_randomized = 0;
        $cumulative_arms = array_fill_keys($randArmlabels, 0);
		$iterations = 0;
		while (true) {
			$randomized_this_week = 0;
			$arms_this_week = array_fill_keys($randArmlabels, 0);
			// determine week boundary dates
			$day_offset1 = ($iterations) * 7;
			$day_offset2 = $day_offset1 + 6;
			$date1 = date("Y-m-d", strtotime("+$day_offset1 days", strtotime($first_sunday)));
			$date2 = date("Y-m-d", strtotime("+$day_offset2 days", strtotime($first_sunday)));
			
			$row = [];
			$row[0] = date("n/j/y", strtotime($date1)) . "-" . date("n/j/y", strtotime($date2));
			$row[0] = str_replace("\\", "", $row[0]);
			
			// count records that were screened this week
			$ts_a = strtotime($date1);
			$ts_b = strtotime($date2);
			
			foreach ($enroll_data_to_consider as $record) {
				// making sure the H:m part of the d-m-Y H:m field doesn't cause us to miscount
				$ts_x = strtotime(date("Y-m-d", strtotime($record->randomization_date)));
				$site_match_or_null = $site === null ? true : $record->redcap_data_access_group == $site;
				if ($ts_a <= $ts_x and $ts_x <= $ts_b and $site_match_or_null) {
                    $randomized_this_week++;
                    $arms_this_week[$randArmlabels[$record->randomization_arm]]++;
                }
			}
            foreach ($cumulative_arms as $arm => $total) {
                $cumulative_arms[$arm] += $arms_this_week[$arm];
                if ($cumulative_arms[$arm] === 0) {
                    $cumulative_arms[$arm] = null;
                }
            }
			$cumulative_randomized += $randomized_this_week;
			
			$row[1] = $randomized_this_week;
			$row[2] = $cumulative_randomized;
			$row[3] = $cumulative_arms;
			$enrollment_chart_data->rows[] = $row;
			
			$iterations++;
			
			// see if the week row just created captures the last screened date
			// if so, break here
			$cutoff_timestamp = strtotime("+1 days", $ts_b);
			if ($cutoff_timestamp > strtotime($last_date) or $iterations > 999)
				break;
		}
        $cumulative_arms = array_filter($cumulative_arms);
		$enrollment_chart_data->rows[] = ["Grand Total", $cumulative_randomized, $cumulative_randomized, $cumulative_arms];
  
		return $enrollment_chart_data;
	}
	public function getExclusionReportData() {
		if (!isset($this->exclusion_data)) {
			// create data object
			$exclusion_data = new \stdClass();
			$exclusion_data->rows = [];
			
			// get labels, init exclusion counts
			$screening_pid = $this->getProjectSetting('screening_project');
			$labels = $this->excl_desc_labels;
			$exclusion_counts = [];
			foreach ($labels as $i => $label) {
				$exclusion_counts[$i] = 0;
			}
			
			// iterate through screening records, summing exclusion reasons from [exclude_primary_reason]
			$screening_data = $this->getScreeningData();
			foreach ($screening_data as $record) {
				// // use below if exclude_primary_reason is a dropdown field type
				// if (!empty($record->exclude_primary_reason) and isset($exclusion_counts[$record->exclude_primary_reason]))
					// $exclusion_counts[$record->exclude_primary_reason]++;
				
				// use below if exclude_primary_reason is a checkbox field type	//	can't think of a smarter way to do this
				foreach($record as $field_name => $value) {
					preg_match("/exclude_primary_reason___(\d*)/", $field_name, $match);
					$exclusion_index = intval($match[1]);
					unset($report_table_index);
					if ($value && $exclusion_index > 0) {
						switch ($exclusion_index) {
							case 2:
								$report_table_index = 0;
								break;
							case 3:
								$report_table_index = 1;
								break;
							case 5:
								$report_table_index = 2;
								break;
							case 6:
								$report_table_index = 3;
								break;
							case 7:
								$report_table_index = 4;
								break;
							case 8:
								$report_table_index = 5;
								break;
							case 9:
								$report_table_index = 6;
								break;
							case 13:
								$report_table_index = 7;
								break;
							case 14:
								$report_table_index = 0;
								break;
							case 15:
								$report_table_index = 1;
								break;
							case 16:
								$report_table_index = 2;
								break;
							case 17:
								$report_table_index = 3;
								break;
							case 18:
								$report_table_index = 4;
								break;
							case 19:
								$report_table_index = 5;
								break;
							case 20:
								$report_table_index = 6;
								break;
							case 21:
								$report_table_index = 7;
								break;
                            case 22:
                                $report_table_index = 1;
                                break;
                            case 23:
                                $report_table_index = 2;
                                break;
                            case 24:
                                $report_table_index = 3;
                                break;
                            case 25:
                                $report_table_index = 4;
                                break;
                            case 26:
                                $report_table_index = 5;
                                break;
                            case 27:
                                $report_table_index = 6;
                                break;
                            case 28:
                                $report_table_index = 7;
                                break;
                            case 29:
                                $report_table_index = 8;
                                break;
                            case 30:
                                $report_table_index = 9;
                                break;
						}
					}
					
					if (is_numeric($report_table_index)) {
						$exclusion_counts[$report_table_index]++;
					}
				}
			}
			
			// add rows to data object
			foreach ($labels as $i => $label) {
				$exclusion_data->rows[] = [
					"#" . ($i+1),
					$label,
					$exclusion_counts[$i]
				];
			}
			$this->exclusion_data = $exclusion_data;
		}
		return $this->exclusion_data;
	}
	public function getScreenFailData() {
		if (!isset($this->screen_fail_data)) {
			$screen_fail_data = new \stdClass();
			$screen_fail_data->rows = [];
			$labels = $this->getChoiceLabels("not_enrolled_reason", $this->getProjectSetting('screening_project'));
			$screen_fail_counts = [];
			foreach ($labels as $i => $label) {
				$screen_fail_counts[$i] = 0;
			}
			$screening_data = $this->getScreeningData();
			foreach ($screening_data as $record) {
				if (!empty($record->not_enrolled_reason) and isset($screen_fail_counts[$record->not_enrolled_reason]))
					$screen_fail_counts[$record->not_enrolled_reason]++;
			}
			foreach ($labels as $i => $label) {
				$screen_fail_data->rows[] = [
					"#$i",
					$label,
					$screen_fail_counts[$i]
				];
			}
			$this->screen_fail_data = $screen_fail_data;
		}
		
		return $this->screen_fail_data;
	}
	public function getHelpfulLinks() {
		$link_settings = $this->getSubSettings('helpful_links_folders');
		$links = [];
		
		foreach($link_settings as $i => $folder) {
			foreach($folder['helpful_links'] as $link_info) {
				// skip links with missing URL
				if (empty($link_info['link_url'])) {
					continue;
				}
				
				$link = new \stdClass();
				$link->url = $link_info['link_url'];
				
				// prepend http protocol text if missing to avoid pathing to ExternalModules/...
				if (strpos($link->url, "http") === false) {
					$link->url = "http://" . $link->url;
				}
				
				if (empty($link_info['link_display'])) {
					$link->display = $link->url;
				} else {
					$link->display = $link_info['link_display'];
				}
				
				$link->folder_index = $i;
				
				$links[] = $link;
			}
		}
		
		return $links;
	}
	public function getHelpfulLinkFolders() {
		$link_settings = $this->getSubSettings('helpful_links_folders');
		
		$folders = [];
		foreach($link_settings as $i => $folder_info) {
			$folder = new \stdClass();
			
			$folder->name = $folder_info['helpful_links_folder_text'];
			if (empty($folder->name)) {
				$folder->name = "Folder " . ($i + 1);
			} elseif (strlen($folder->name) > $this::MAX_FOLDER_NAME_LEN) {
				$folder->name = substr($folder->name, 0, $this::MAX_FOLDER_NAME_LEN) . "...";
			}
			
			$folder->color = $folder_info['helpful_links_folder_color'];
			$css_hex_color_pattern = "/#([[:xdigit:]]{3}){1,2}\b/";
			if (!preg_match($css_hex_color_pattern, $folder->color)) {
				// Ensures folders have a valid color
				$folder->color = "#edebb4";
			}
			
			// if $folder_info['helpful_links'] not array, throw exception
			
			$folder->linkCount = count($folder_info['helpful_links']);
			if (!is_numeric($folder->linkCount)) {
				$folder->linkCount = 0;
			}
			
			$folders[] = $folder;
		}
		
		return $folders;
	}
	
	public function getVCCSiteStartUpFieldList($regulatoryPID) {
		$reg_dd = json_decode(\REDCap::getDataDictionary($regulatoryPID, 'json'));
		if (empty($reg_dd)) {
			$this->addStartupError("The REVERSE-LC module couldn't get start-up fields -- fatal error trying to decode the Data Dictionary (json) for the regulatory project (PID: " . $regulatoryPID . ")", "danger");
		}
		
		$field_names = [];
		foreach($reg_dd as $field_info) {
			$name = $field_info->field_name;
			if ($field_info->form_name == 'vcc_site_start_up') {
				$field_names[] = $name;
			}
		}
		
		return $field_names;
	}
	public function getSiteStartupData() {
		$startup_data = new \stdClass();
		
		// initialize array to collect any startup errors that may occur
		$this->startup_errors = [];
		$startup_data->dags = [];
		
		// get regulatory Project instance (for groups/DAGs data)
		$regulatoryPID = $this->getProjectSetting('site_regulation_project');
		if (empty($regulatoryPID)) {
			$this->addStartupError("The REVERSE-LC module couldn't find a project ID for a corresponding regulatory project because the 'REVERSE_LC Site Regulation Project ID' setting is not configured. Please configure the module by selecting a regulatory project.", "danger");
			return $startup_data;
		}
		
		// return array of site objects, each with data used to build Site Activation tables
		$activation_fields = $this->getVCCSiteStartUpFieldList($regulatoryPID);
		if (empty($activation_fields)) {
			$this->addStartupError("The REVERSE-LC module couldn't retrieve the list of fields in the VCC Site Start Up form (in the regulatory project)", "danger");
		}
		
		// add extra field(s) useful for site activation tables
		$activation_fields[] = $this->id_field_name;
		$activation_fields[] = 'role';
		$activation_fields[] = 'site_number';
		
		// these fields are used for the site activation table cells information/statuses
		$activation_fields = array_merge($activation_fields, array_values($this->document_signoff_fields));
		
		// if stop is true for a record, don't select that record to be the role personnel for any role
		$activation_fields[] = 'stop';
		
		// add form complete fields so we get all instances (even if instances are full of empty field values)
		$activation_fields = array_merge($activation_fields, $this->personnel_form_complete_fields);
		$params = [
			"project_id" => $regulatoryPID,
			"return_format" => 'json',
			"fields" => $activation_fields,
			"exportAsLabels" => true,
			"exportDataAccessGroups" => true
		];
		$data = json_decode(\REDCap::getData($params));
		if (empty($data)) {
			$this->addStartupError("Couldn't retrieve site activation data from regulatory project.", "danger");
		}
		
		// separate data entries into sites[] and personnel[]
		$startup_data->sites = [];
		$startup_data->personnel = [];
		foreach($data as $index => $entry) {
			if (strpos($entry->redcap_event_name, 'Site Documents') !== false) {
			    if($this->user->authorized != "3") {
			        foreach($this->filteredSiteFields as $filteredField) {
			            ## Replace all date values with "Complete" if no permission to view dates
			            if(strtolower($entry->$filteredField) != "not applicable" && !empty($entry->$filteredField)) {
			                $entry->$filteredField = "Complete";
                        }
                    }
                }

				$startup_data->sites[] = $entry;
				$dag = $entry->redcap_data_access_group;
				if (array_search($dag, $startup_data->dags, true) === false && !empty($dag)) {
					$startup_data->dags[] = $dag;
				}
			} elseif (strpos($entry->redcap_event_name, 'Personnel Documents') !== false) {
				$startup_data->personnel[] = $entry;
			}
		}
		unset($data);
		
		$this->processStartupPersonnelData($startup_data->personnel, $startup_data->dags);
		$this->processStartupSiteData($startup_data->sites, $startup_data->personnel);
		$startup_data->errors = $this->startup_errors;
		
		return $startup_data;
	}
	public function calculateSiteProgress($siteStartupData) {
	    ## Don't return data unless user authorized
	    if($this->user->authorized !== '3') {
	        return false;
        }

	    $siteCompletionData = [];
	    ## IREX is special because completed steps show as dates, not ("Confirmed" || "Not Applicable")
	    $areasOfProgress = [
            [
                "any_value" => true,
                "title" => "Site Contract",
                "fields" => [
                    "template_sent",
                    "contract_pe",
                    "contract_fe"
                ]
            ],
            [
                "any_value" => true,
                "title" => "Site IREX Agreements",
                "fields" => [
                    "vcc_survey_sent",
                    "vcc_survey_received",
                    "vcc_survey_accepted",
                    "vcc_survey_accepted",
                    "vcc_pt2_received",
                    "vcc_pt2_reviewed",
                    "vcc_pt2_approved",
                    "institutional_profile_complete",
                    "hrp_agreement",
                    "pi_survey",
                    "site_add_ready",
                    "site_add"
                ]
            ],
            [
                "any_value" => false,
                "title" => "Site Reg Documents",
                "fields" => [
                    "ctom_site_docs_approve",
					"doc_1572",
					"ib_ack",
					"lab_docs",
					"psp_signed",
					"sirb_approved",
					"site_info_confirm",
					"ua_received",
                ]
            ],
            [
                "any_value" => false,
                "title" => "Site KSP Docs",
                "fields" => [
                    "pi.cv.value",
					"pi.doa.value",
					"pi.license.value",
					"pi.fdf.value",
					"pi.hand_prof.value",
					"pi.gcp.value",
					"pi.hsp.value",
					"pi.training.value",
                    "primary_coordinator.cv.value",
					"primary_coordinator.doa.value",
					"primary_coordinator.license.value",
					"primary_coordinator.fdf.value",
					"primary_coordinator.hand_prof.value",
					"primary_coordinator.gcp.value",
					"primary_coordinator.hsp.value",
					"primary_coordinator.training.value",
                    "primary_dispensing_pharmacist.cv.value",
					"primary_dispensing_pharmacist.doa.value",
					"primary_dispensing_pharmacist.license.value",
					"primary_dispensing_pharmacist.gcp.value",
					"primary_dispensing_pharmacist.hsp.value",
					"primary_dispensing_pharmacist.training.value",
                ]
            ]
        ];

	    ## DEACTIVATED
//        [
//            "any_value" => false,
//            "title" => "Site Activated",
//            "fields" => [
//
//                "start_to_finish_duration",
//                "open_date",
//            ]
//        ]

	    $completedValues = [
	        "confirmed by vcc",
            "not applicable"
        ];
	    
	    foreach($siteStartupData->sites as $siteIndex => $thisSite) {
            foreach($areasOfProgress as $thisArea) {
                $completeTasks = 0;
                $totalCount = count($thisArea["fields"]);
                foreach($thisArea["fields"] as $thisKey) {
                    $subFields = explode(".",$thisKey);
                    $value = $thisSite;
                    foreach($subFields as $subIndex => $thisField) {
                        if($subIndex > 0) {
                            $value = $value[$thisField];
                        }
                        else {
                            $value = $value->$thisField;
                        }
                    }

                    if($thisArea["any_value"]) {
                        if(!empty($value)) {
                            $completeTasks++;
                        }
                    }
                    else {
                        foreach ($completedValues as $matchingValue) {
                            if (substr(strtolower($value), 0, strlen($matchingValue)) == $matchingValue) {
                                $completeTasks++;
                                break;
                            }
                        }
                    }
                }

                $siteCompletionData[$thisSite->redcap_data_access_group][$thisArea["title"]] = [
                    "total" => $totalCount,
                    "complete" => $completeTasks,
                    "percent" => round($completeTasks / $totalCount * 100)
                ];
            }
        }
        return $siteCompletionData;
    }
	public function processStartupPersonnelData(&$personnel_data, $dags) {
		foreach($personnel_data as &$data) {
			foreach($data as $key => $value) {
				if (empty($value))
					unset($data->$key);
			}
		}
		
		// array for complete personnel data objects
		$personnel = new \stdClass();
		$reg_pid = $this->getProjectSetting('site_regulation_project');
		$reg_project_event_table = \REDCap::getLogEventTable($reg_pid);
		$reg_id_field = $this->getRecordIdField($reg_pid);
		
		// filter out older records if multiple exist for a given [role]
		// throw exception if can't determine a single record for a given role
		$candidates = [];
		foreach($personnel_data as $i => $personnel_record_event) {
			if (empty($personnel_record_event->redcap_repeat_instrument)) {
				// [stop] is set if a person leaves the org or is no longer that acts as that role for the study(ies)
				if (!empty($personnel_record_event->stop)) {
					continue;
				}
				// skip if no dag/site defined
				if (empty($personnel_record_event->redcap_data_access_group)) {
					continue;
				}
				
				// create candidate objects, we're not sure which personnel record we're going to select to be the person for each role yet
				// we want to select personnel based on the record's creation date (recent records will get chosen over older records) and their [role] field value
				// we also want to make sure we select 1 and only 1 person for each role PER SITE (there is a 1:1 site:DAG correlation)
				$candidate = new \stdClass();
				$rid = $personnel_record_event->$reg_id_field;
				$candidate->$reg_id_field = $rid;
				$candidate->role = $personnel_record_event->role;
				$candidate->dag = $personnel_record_event->redcap_data_access_group;
				
				// try to determine when this record was created
				$result = $this->query("SELECT ts FROM $reg_project_event_table WHERE project_id = ? AND data_values LIKE ? AND description = 'Create record'",
					[$reg_pid,
					"%$rid_field = '$rid'%"]
				);
				$result_rows = [];
				while($row = $result->fetch_assoc()) {
					$result_rows[] = $row;
				}
				if (count($result_rows) > 1) {
					$this->addStartupError("The REVERSE-LC module couldn't determine a timestamp for when this record ($rid) was created.", "warning", $candidate->dag);
				}
				if (isset($result_rows[0])) {
					$candidate->create_ts = $result_rows[0]['ts'];
				}
				$candidates[] = $candidate;
			}
		}
		
		// now that we have creation timestamps and role info, select our personnel records for each site (filter out others)
		foreach($dags as $dag) {
			foreach($this->personnel_roles as $i => $role) {
				$max_ts = 0;
				$count_role = 0;
				$selected_candidate = null;
				foreach($candidates as $j => $candidate) {
					if ($candidate->role == $role && $candidate->dag == $dag) {
						$count_role++;
						if (isset($candidate->create_ts)) {
							if ($candidate->create_ts > $max_ts) {
								$selected_candidate = $candidate;
							}
							$max_ts = max($candidate->create_ts, $max_ts);
						} elseif ($selected_candidate == null) {
							$selected_candidate = $candidate;
						}
					}
				}
				
				if ($max_ts == 0 and $count_role > 1) {
					// none of our personnel records have creation timestamps and there are more than one... so which one do we use? we can't determine
					$this->addStartupError("The REVERSE-LC module couldn't determine which personnel record to use for role '$role' (most likely there are multiple personnel records with this role and the module can't determine when each were created)", "danger", $dag);
				}
				
				if (empty($selected_candidate)) {
					$this->addStartupError("The REVERSE-LC module couldn't determine which personnel record to use for role '$role' for site '$dag' -- most likely there are no records created with this [role] value assigned to DAG '$dag'.", "danger", $dag);
				}
				
				$role_name = strtolower(preg_replace('/[ ]+/', '_', $role));
				if (!$personnel->$dag) {
					$personnel->$dag = new \stdClass();
				}
				$personnel->$dag->$role_name = $selected_candidate;
			}
			
			// use all record-events in personnel data to fill in field info for candidates (matching on record id)
			foreach($personnel->$dag as $role => &$data) {
				$latest_instances = new \stdClass();	// holds max instance id found for repeating instances (we want to ignore older instances)
				
				// loop over instance info to record max instance id for each personnel form
				foreach($personnel_data as $i => $record_data) {
					if ($record_data->$reg_id_field == $data->$reg_id_field && isset($record_data->redcap_repeat_instrument)) {
						$form_name = $record_data->redcap_repeat_instrument;
						if (!isset($latest_instances->$form_name)) {
							$latest_instances->$form_name = $record_data->redcap_repeat_instance;
						} else {
							$latest_instances->$form_name = max($record_data->redcap_repeat_instance, $latest_instances->$form_name);
						}
					}
				}
				
				foreach($personnel_data as $i => $record_data) {
					if (
						// if it's the latest repeated instance for this form, or not a repeated form, and the record's id field matches this personnel: copy properties
						((isset($latest_instances->{$record_data->redcap_repeat_instrument})
						&&
						$latest_instances->{$record_data->redcap_repeat_instrument} == $record_data->redcap_repeat_instance)
						||
						!isset($record_data->redcap_repeat_instrument))
						&&
						$record_data->$reg_id_field == $data->$reg_id_field
					) {
						foreach($record_data as $key => $value) {
							$data->$key = $value;
						}
					}
				}
			}
		}
		
		$personnel_data = $personnel;
	}
	public function processStartupSiteData(&$sites, $personnel) {
		foreach($sites as &$site) {
			foreach($site as $key => $value) {
				if (empty($value))
					unset($site->$key);
			}
		}
		
		$reg_pid = $this->getProjectSetting('site_regulation_project');
		$reg_project = new \Project($reg_pid);
		$personnel_event_id = array_key_first($reg_project->events[2]['events']);
		$todays_date = new \DateTime(date("Y-m-d", time()));
		
		// calculate study admin cell values and classes
		foreach($sites as &$site) {
			$dag = $site->redcap_data_access_group;
			foreach($this->personnel_roles as $role_name) {
				$role = str_replace(' ', '_', strtolower($role_name));
				$site->$role = [];
				$cells = &$site->$role;
				if (empty($personnel->$dag) || empty($personnel->$dag->$role)) {
                    $this->addStartupError("The REVERSE-LC module couldn't determine which record to use for $role_name role information for this site.", "danger", $dag);
				}
				
				foreach($this->document_signoff_fields as $data_field => $check_field) {
					// cbox value stored with suffix in personnel->dag->role
					$check_field_prop = $check_field . "___1";
					
					// append prefixes where needed
					if ($role == 'primary_coordinator') {
						$db_data_field = 'ksp_' . $data_field;
					} elseif ($role == 'primary_dispensing_pharmacist') {
						$db_data_field = 'pharm_' . $data_field;
					} else {
						$db_data_field = $data_field;
					}
					
					if ($data_field == 'doa') {
						if ($role == "pi") {
							$db_data_field = 'doa_pi';
						} elseif ($role == "primary_coordinator") {
							$db_data_field = 'pi_ksp_doa';
						} elseif ($role == "primary_dispensing_pharmacist") {
							$db_data_field = 'pharm_doa_pi';
						}
					}
					
					$cells[$data_field] = [];
					$cells[$data_field]['value'] = $site->$db_data_field;
					if (empty($site->$db_data_field)) {
						$cells[$data_field]['class'] = 'signoff';
					} elseif ($site->$db_data_field == "Confirmed by VCC") {
						if ($personnel->$dag->$role->$check_field_prop == 'Checked') {
							// get most recent sign-off date (from most recent instance)
							$id_field_name = $this->id_field_name;
							$max_instance = $this->getMaxInstance($reg_pid, $personnel->$dag->$role->$id_field_name, $personnel_event_id, $check_field);
							$history = $this->getDataHistoryLog($reg_pid, $personnel->$dag->$role->$id_field_name, $personnel_event_id, $check_field, $max_instance);
							if (!empty($history)) {
								$cells[$data_field]['last_changed'] = substr(array_key_first($history), 0, -2);	// chop off last two digits -- timestamp was previously multiplied by 100
								$checked_date = new \DateTime(date("Y-m-d", $cells[$data_field]['last_changed']));
								$cells[$data_field]['value'] = $cells[$data_field]['value'] . " (" . $todays_date->diff($checked_date)->format("%a") . " days)";
								
								$cells[$data_field]['class'] = 'signoff green';
							} else {
								$cells[$data_field]['class'] = 'signoff red';
							}
						} else {
							$cells[$data_field]['class'] = 'signoff red';
						}
					} elseif ($site->$db_data_field == 'Initiated' || $site->$db_data_field == 'Awaiting Site Response') {
						$cells[$data_field]['class'] = 'signoff red';
					} else {
						$cells[$data_field]['class'] = 'signoff yellow';
					}
				}
			}
		}
		
		// add count of days between site engaged and site open for enrollment
		foreach($sites as &$site) {
			$site_start_ts = strtotime($site->site_engaged);
			$site_open_ts = strtotime($site->open_date);
			if ($site_start_ts && $site_open_ts) {
				$site_start_date = new \DateTime(date("Y-m-d", $site_start_ts));
				$site_open_date = new \DateTime(date("Y-m-d", $site_open_ts));
				$site->start_to_finish_duration = $site_open_date->diff($site_start_date)->format("%a") . " days to site activation";
			}
		}
	}
	public function getDataHistoryLog($project_id, $record, $event_id, $field_name) {
		// copied closely from \Form::getDataHistoryLog but allows dev to provide $project_id to target other projects
		
		global $lang;
		
		$maxInstance = $this->getMaxInstance($project_id, $record, $event_id, $field_name);
		$instance = $maxInstance;
		
		$GLOBALS['Proj'] = $Proj = new \Project($project_id);
		$longitudinal = $Proj->longitudinal;
		$missingDataCodes = parseEnum($Proj->project['missing_data_codes']);
		
		// Set field values
		$field_type = $Proj->metadata[$field_name]['element_type'];
        $field_val_type = $Proj->metadata[$field_name]['element_validation_type'];

		// Version history enabled
        $version_history_enabled = ($field_type == 'file' && $field_val_type != 'signature' && \Files::fileUploadVersionHistoryEnabledProject($project_id));

		// Determine if a multiple choice field (do not include checkboxes because we'll used their native logging format for display)
		$isMC = ($Proj->isMultipleChoice($field_name) && $field_type != 'checkbox');
		if ($isMC) {
			$field_choices = parseEnum($Proj->metadata[$field_name]['element_enum']);
		}
		
		$hasFieldViewingRights = true;
		
		// Format the field_name with escaped underscores for the query
		$field_name_q = str_replace("_", "\\_", $field_name);
		
		// REPEATING FORMS/EVENTS: Check for "instance" number if the form is set to repeat
		$instanceSql = "";
		$isRepeatingFormOrEvent = $Proj->isRepeatingFormOrEvent($event_id, $Proj->metadata[$field_name]['form_name']);
		if ($isRepeatingFormOrEvent) {
			// Set $instance
			$instance = is_numeric($instance) ? (int)$instance : 1;
			if ($instance > 1) {
				$instanceSql = "and data_values like '[instance = $instance]%'";
			} else {
				$instanceSql = "and data_values not like '[instance = %'";
			}
		}
		
		// Default
		$time_value_array = array();
		$arm = isset($Proj->eventInfo[$event_id]) ? $Proj->eventInfo[$event_id]['arm_num'] : getArm();

		// Retrieve history and parse field data values to obtain value for specific field
		$sql = "SELECT user, timestamp(ts) as ts, data_values, description, change_reason, event 
                FROM ".\Logging::getLogEventTable($project_id)." WHERE project_id = " . $project_id . " and pk = '" . db_escape($record) . "'
				and (
				(
					(event_id = $event_id " . ($longitudinal ? "" : "or event_id is null") . ")
					and legacy = 0 $instanceSql
					and
					(
						(
							event in ('INSERT', 'UPDATE')
							and description in ('Create record', 'Update record', 'Update record (import)',
								'Create record (import)', 'Merge records', 'Update record (API)', 'Create record (API)',
								'Update record (DTS)', 'Update record (DDP)', 'Erase survey responses and start survey over',
								'Update survey response', 'Create survey response', 'Update record (Auto calculation)',
								'Update survey response (Auto calculation)', 'Delete all record data for single form',
								'Delete all record data for single event', 'Update record (API) (Auto calculation)')
							and (data_values like '%\\n{$field_name_q} = %' or data_values like '{$field_name_q} = %' 
								or data_values like '%\\n{$field_name_q}(%) = %' or data_values like '{$field_name_q}(%) = %')
						)
						or
						(event = 'DOC_DELETE' and data_values = '$field_name')
						or
						(event = 'DOC_UPLOAD' and (data_values like '%\\n{$field_name_q} = %' or data_values like '{$field_name_q} = %' 
													or data_values like '%\\n{$field_name_q}(%) = %' or data_values like '{$field_name_q}(%) = %'))
					)
				)
				or 
				(event = 'DELETE' and description like 'Delete record%' and (event_id is null or event_id in (".prep_implode(array_keys($Proj->events[$arm]['events'])).")))
				)
				order by log_event_id";
		$q = db_query($sql);
		// Loop through each row from log_event table. Each will become a row in the new table displayed.
        $version_num = 0;
        $this_version_num = "";
        $rows = array();
        $deleted_doc_ids = array();
        while ($row = db_fetch_assoc($q))
        {
            $rows[] = $row;
            // For File Version History for file upload fields, get doc_id all any that were deleted
            if ($version_history_enabled) {
                $value = html_entity_decode($row['data_values'], ENT_QUOTES);
                foreach (explode(",\n", $value) as $this_piece) {
                    $doc_id = \Form::dataHistoryMatchLogString($field_name, $field_type, $this_piece);
                    if (is_numeric($doc_id)) {
                        $doc_delete_time = Files::wasEdocDeleted($doc_id);
                        if ($doc_delete_time) {
                            $deleted_doc_ids[$doc_id] = $doc_delete_time;
                        }
                    }
                }
            }
        }
        // Loop through all rows
		foreach ($rows as $row)
		{
			// If the record was deleted in the past, then remove all activity before that point
			if ($row['event'] == 'DELETE') {
				$time_value_array = array();
                $version_num = 0;
				continue;
			}
			// Flag to denote if found match in this row
			$matchedThisRow = false;
			// Get timestamp
			$ts = $row['ts'];
			// Get username
			$user = $row['user'];
			// Decode values
			$value = html_entity_decode($row['data_values'], ENT_QUOTES);
            // Default return string
            $this_value = "";
            // Split each field into lines/array elements.
            // Loop to find the string match
            foreach (explode(",\n", $value) as $this_piece)
            {
                $isMissingCode = false;
                // Does this line match the logging format?
                $matched = \Form::dataHistoryMatchLogString($field_name, $field_type, $this_piece);
                if ($matched !== false || ($field_type == "file" && ($this_piece == $field_name || strpos($this_piece, "$field_name = ") === 0)))
                {
                    // Set flag that match was found
                    $matchedThisRow = true;
                    // File Upload fields
                    if ($field_type == "file")
                    {
						if (isset($missingDataCodes[$matched])) {
							// Set text
							$this_value = $matched;
							$doc_id = null;
							$this_version_num = "";
							$isMissingCode = true;
						} elseif ($matched === false || $matched == '') {
                            // For File Version History, don't show separate rows for deleted files
                            if ($version_history_enabled) continue 2;
                            // Deleted
                            $doc_id = null;
                            $this_version_num = "";
                            // Set text
                            $this_value = \RCView::span(array('style'=>'color:#A00000;'), $lang['docs_72']);
                        } elseif (is_numeric($matched)) {
                            // Uploaded
                            $doc_id = $matched;
                            $doc_name = \Files::getEdocName($doc_id);
                            $version_num++;
                            $this_version_num = $version_num;
                            // Set text
                            $this_value = \RCView::span(array('style'=>'color:green;'),
                                            $lang['data_import_tool_20']
                                            ). " - \"{$doc_name}\"";
                        }
                        break;
                    }
                    // Stop looping once we have the value (except for checkboxes)
                    elseif ($field_type != "checkbox")
                    {
                        $this_value = $matched;
                        break;
                    }
                    // Checkboxes may have multiple values, so append onto each other if another match occurs
                    else
                    {
                        $this_value .= $matched . "<br>";
                    }
                }
            }

            // If a multiple choice question, give label AND coding
            if ($isMC && $this_value != "")
            {
                if (isset($missingDataCodes[$this_value])) {
					$this_value = decode_filter_tags($missingDataCodes[$this_value]) . " ($this_value)";
                } else {
					$this_value = decode_filter_tags($field_choices[$this_value]) . " ($this_value)";
				}
            }

			// Add to array (if match was found in this row)
			if ($matchedThisRow) {			
				// If user does not have privileges to view field's form, redact data
				if (!$hasFieldViewingRights) {
					$this_value = "<code>".$lang['dataqueries_304']."</code>";
				} elseif ($field_type != "file") {
					$this_value = nl2br(htmlspecialchars(br2nl(label_decode($this_value)), ENT_QUOTES));
				}
				// Set array key as timestamp + extra digits for padding for simultaneous events
				$key = strtotime($ts)*100;
				// Ensure that we don't overwrite existing logged events
				while (isset($time_value_array[$key.""])) $key++;
				// Display missing data code?
				$returningMissingCode = (isset($missingDataCodes[$this_value]) && !\Form::hasActionTag("@NOMISSING", $Proj->metadata[$field_name]['misc']));
				// Add to array
				$time_value_array[$key.""] = array( 'ts'=>$ts, 'value'=>$this_value, 'user'=>$user, 'change_reason'=>nl2br($row['change_reason']),
                                                    'doc_version'=>$this_version_num, 'doc_id'=>(isset($doc_id) ? $doc_id : null),
                                                    'doc_deleted'=>(isset($doc_id) && isset($deleted_doc_ids[$doc_id]) ? $deleted_doc_ids[$doc_id] : ""),
                                                    'missing_data_code'=>($returningMissingCode ? $this_value : ''));
			}
		}
		// Sort by timestamp
		ksort($time_value_array);
		// Return data history log
		return $time_value_array;
	}
	public function getMaxInstance($project_id, $record, $event_id, $field_name) {
		$q = $this->createQuery();
		$q->add("SELECT MAX(instance) FROM redcap_data WHERE project_id = ? AND record = ? AND event_id = ? AND field_name = ?", [
			$project_id,
			$record,
			$event_id,
			$field_name
		]);
		$result = $q->execute();
		return $result->fetch_assoc()['MAX(instance)'];
	}
	public function addStartupError($error_message, $error_class, $dag=null) {
		if (gettype($this->startup_errors) !== 'array') {
			throw new \Exception("Tried to add a startup error without initializing startup_errors array. It's likely that a call to `addStartupError` is out of place.");
		}
		
		// don't add duplicates
		foreach($this->startup_errors as $error) {
			if ($error['text'] == $error_message) {
				return;
			}
		}
		
		$this->startup_errors[] = [
			"text" => $error_message,
			"class" => $error_class,
			"dag" => $dag
		];
	}
	
	// hooks
	public function redcap_module_link_check_display($pid, $link) {
		if ($link['name'] == 'REVERSE_LC Dashboard') {
			$this->getDashboardUser();
			$this->authorizeUser();
			if ($this->user->authorized === false) {
				return false;
			} else {
				return $link;
			}
		} else {
			return $link;
		}
	}

}