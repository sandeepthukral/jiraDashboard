<?php

// use chobie;
require_once __DIR__ . '/vendor/autoload.php';
require_once 'config.php';
require_once 'Reportable.php';

// define("JIRA_URL",      "https://jira.itservices.lan");
// define("JIRA_USERNAME", "s-thukral");
// define("JIRA_PASSWORD", "257776A^6Tu4uDF@dQSF");

define("REFERENCE", "Regression Anchor");
$arrayOfRelatedEpicLinksToInclude = array(
		"MRG"
);

$sprint = "15S03 - 4G E2E";
$project = "EQ";

if (isset($_GET['sprint'])){
	if (trim($_GET['sprint']) != ""){
		$sprint = urldecode(trim($_GET['sprint']));
	}
}

if (isset($_GET['project'])){
	if(trim($_GET['project']) != ""){
		$project = urldecode(trim($_GET['project']));
	}
}

$jqlForAllStories = "project = " . $project . " AND sprint = \"" . $sprint . "\" and type=\"Solution Story\" ORDER BY issuekey";

$api = getApiClient();

$arrayOfEpicLinkedStories = array();
$arrayOfRegressionStories = array();

$arrayOfReportablesForEpicLinkedStories = array();
$arrayOfReportablesForRegressionStories = array();

$allSolutionStories = new chobie\Jira\Issues\Walker($api);
$allSolutionStories->push( $jqlForAllStories );

foreach ($allSolutionStories as $issue) {
	$reference = $issue->get("Reference");
	
	if ($reference === ""){
	}else {
		if (!is_bool( strstr($reference, REFERENCE) ) ){
			$arrayOfRegressionStories[] = $issue;
		}
	}
}

foreach ($arrayOfRegressionStories as $issue){

	$reportable = new Reportable();
	$reportable->setIssue($issue);
	
	$allTestTasksForRegressionStory = new chobie\Jira\Issues\Walker($api);
	$allTestTasksForRegressionStory->push(" (type = \"Tele2 Test Sub-Task\" ) AND parent = \"" . $issue->getKey() . "\"");
	
	$tempArrayOfStatuses = array();
	foreach ($allTestTasksForRegressionStory as $test){
		$statuses = $test->getStatus();
		$testId = $test->getKey();
// 		echo "ID - " . $testId . "  Status - " . $statuses['name'] . "<br>\n";
		// create one array with all statuses
		$tempArrayOfStatuses[] = $statuses['name'] ; 
	}
	
	echo json_encode(array_count_values(mapTaskStatuses($tempArrayOfStatuses)));

}

function mapTaskStatuses($array){
	
	$newStausArray = array();
	
	$arrayOfMappings = array(
			"Task: In Testing" 	   => "Blocked",
			"Task: To Do"		   => "Todo",
			"Task: In Progress"	   => "In Progress",
			"Task: Done"		   => "Pass",
			"Task: Not Required"   => "Skipped",
			"Postponed"			   => "Pass with Exceptions",
			"Moved to next sprint" => "Skipped"
	);
	
	foreach ($array as $status){
// 		echo "Processing status " . $status. " , replacing with " . $arrayOfMappings[$status] . "<br>\n";
		$newStausArray[] = $arrayOfMappings[$status];
	}
	
	return $newStausArray;
}

/*
 * HElper function that instantiates an API client to communicate with Jira
 */
function getApiClient() {
	$api = new chobie\Jira\Api(
		JIRA_URL,
		new chobie\Jira\Api\Authentication\Basic ( JIRA_USERNAME, JIRA_PASSWORD )
	);
	return $api;
}

function startsWith( $needle, $haystack ){
	return $needle === "" || strrpos( $haystack, $needle, -strlen($haystack)) !== FALSE;
}