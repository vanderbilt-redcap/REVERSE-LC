<?php

$options = [];

// determine site_dag option
if (isset($_POST['site_dag'])) {
	// allow, -()*, alphanum and space in site/dag name
	$options['site_dag'] = trim(preg_replace("/[^A-Za-z0-9 \-\(\)*]/", "", $_POST['site_dag']));
}

// determine site_locality option
$accepted_localities = [
	"Global",
	"Domestic",
	"International"
];
foreach ($accepted_localities as $locality) {
	if ($_POST['site_locality'] === $locality) {
		$options['site_locality'] = $locality;
	}
}

header('Content-Type: application/json');
echo json_encode($module->getEnrollmentChartData($options), JSON_UNESCAPED_SLASHES);

