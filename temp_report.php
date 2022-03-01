<?php

// this report can help troubleshoot dashboard issues
$allSitesData = $module->getAllSitesData();
$report = [
	"sites_no_locality" => $module->all_sites_data->sites_no_locality,
	"allSitesData" => $allSitesData,
	"records" => $module->records,
	"edc_data" => $module->edc_data,
	"dags" => $module->dags,
	"regulatory_data" => $module->regulatory_data,
	"inclusion_data" => $module->getInclusionData()
];

echo json_encode($report);