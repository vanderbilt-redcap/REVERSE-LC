<?php
// http://localhost/redcap/redcap_v10.9.4/ExternalModules/?prefix=RAAS_NECTAR&page=info&pid=88
\REDCap::allowUsers([
	"jolymm",
	"griderj",
	"tregongs",
	"orozcodz",
	"reedcw1",
	"site_admin"
]);
$data = $module->getSiteStartupData();

echo "<pre>";
// print_r($data->personnel);
print_r($data->dags);
echo "</pre>";