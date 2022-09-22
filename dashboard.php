<?php
define("SCREENING_LOG_DATA_AJAX_URL", $module->getUrl('ajax/getScreeningLogData.php'));
define("ENROLLMENT_CHART_DATA_AJAX_URL", $module->getUrl('ajax/getEnrollmentChartData.php'));
$loader = new \Twig\Loader\FilesystemLoader(__DIR__."/templates");
$twig = new \Twig\Environment($loader);

$template = $twig->load("dashboard.twig");
/** @var $module \Vanderbilt\RAAS_NECTAR\RAAS_NECTAR */
$allSitesData = $module->getAllSitesData();
$mySitesData = $module->getMySiteData();
$siteStartupData = $module->getSiteStartupData();
$module->updateAllSitesData($allSitesData['sites'], $siteStartupData);
$authorized = $module->user->authorized;

// prepare site names for Screening Log Report dropdown
$site_names = [];
if ($authorized == 3) {
	foreach ($module->dags as $dag) {
		$site_names[] = $dag->display;
	}
} else {
	$site_names[] = $module->user->dag_group_name;
}
$randArmlabels = $module->getFieldLabelMapping('randomization_arm');
$screeningLogData = $module->getScreeningLogData();
$enrollmentChartData = $module->getEnrollmentChartData();
$exclusionData = $module->getExclusionReportData();
$screenFailData = $module->getScreenFailData();
$clipboardImageSource = $module->getUrl("images/clipboard.PNG");
$folderImageSource = $module->getUrl("images/folder.png");
$helpfulLinks = $module->getHelpfulLinks();
$helpfulLinkFolders = $module->getHelpfulLinkFolders();
$siteCompletionData = $module->calculateSiteProgress($siteStartupData);
$totals = array_keys(end($enrollmentChartData->rows)[3]);
$randArmlabels = array_intersect($randArmlabels, $totals);
echo $template->render([
	"allSites" => $allSitesData,
	"mySite" => $mySitesData,
	"authorized" => $authorized,
	"site_names" => $site_names,
	"screeningLog" => $screeningLogData,
	"enrollmentChart" => $enrollmentChartData,
	"exclusion" => $exclusionData,
	"screenFail" => $screenFailData,
	"helpfulLinks" => $helpfulLinks,
	"helpfulLinkFolders" => $helpfulLinkFolders,
	"clipboardImageSource" => $clipboardImageSource,
	"folderImageSource" => $folderImageSource,
	"siteStartupData" => $siteStartupData,
    "siteCompletionData" => $siteCompletionData,
    'randArmLabels' => $randArmlabels
]);