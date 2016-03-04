<?php

// use chobie;
require_once __DIR__ . '/vendor/autoload.php';
require_once 'functions.php';
require_once 'config.php';
require_once 'Reportable.php';

define("REFERENCE", "Regression Anchor");
$arrayOfRelatedEpicLinksToInclude = array(
		"EQ"  => "MRG",
		"BMQ" => "BM-"
);
$arrayOfStorySummariesToInclude = array(
		"Generic Issues"
);

$sprint = "15S03 - 4G E2E";
$project = "EQ";
$istest = false;

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

if (isset($_GET['test'])){
	$istest = true;
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

// if ($istest) {
// 	echo "Regression Stories <br>", PHP_EOL;
// 	var_dump($arrayOfRegressionStories);
// }

foreach ($allSolutionStories as $issue) {
	$reference = $issue->get("Reference");

	foreach ($arrayOfStorySummariesToInclude as $storySummary){
		if (strpos($issue->getSummary(),$storySummary) === false){
			//
		} else {
			$arrayOfRegressionStories[] = $issue;
		}
	}
}

if ($istest) {
	echo "Regression Stories <br>", PHP_EOL;
	var_dump($arrayOfRegressionStories);
}

foreach ($arrayOfRegressionStories as $issue){

	$reportable = new Reportable();
	$reportable->setIssue($issue);
	
	$allDefectTasksForRegressionStory = new chobie\Jira\Issues\Walker($api);
	$jql = " ( 
				(type = \"Tele2 Defect Sub-Task\" AND status not in (\"Task: Done\", \"Task: Not Required\")) 
					OR (type = \"Tele2 Test Sub-Task\" AND status in (\"Task: In Testing\", \"Task: Not Required\", \"Postponed\", \"Moved to Next Sprint\" ))  
				) AND parent =  \"" . $issue->getKey() . "\"";
	
	$jql = " (
				(type = \"Tele2 Defect Sub-Task\" AND status not in (\"Task: Done\", \"Task: Not Required\"))
				) AND parent =  \"" . $issue->getKey() . "\"";
	
// 	echo $jql;
	$allDefectTasksForRegressionStory->push($jql);
	
	$tempArrayOfDefectsAndFailedTests = array();
	foreach ($allDefectTasksForRegressionStory as $defect){
		$tempArrayOfDefectsAndFailedTests[] = $defect;
	}
	
	$reportable->setArrayOfDefects($tempArrayOfDefectsAndFailedTests);
	$arrayOfReportablesForRegressionStories[] = $reportable;
}

returnJsonData ( $arrayOfReportablesForRegressionStories ) ;



function returnJsonData ( $arrayOfReportables ) {
	
	$jsonArray = array();
	
	foreach ($arrayOfReportables as $reportable){
		
		$issueTempArray = array();
		$issueDefectsTempArray = array();
		
		$issue = $reportable->getIssue();
		$statuses = $issue->getStatus();
		
		$issueTempArray["issueKey"] 		= $issue->getKey(); 
		$issueTempArray["issueSummary"] 	= $issue->getSummary();
		$issueTempArray["issueStatus"]		= $statuses['name'];
		
		$arrayOfDefects = $reportable->getArrayOfDefects();
		foreach ($arrayOfDefects as $defect){
			$defectTempArray = array();
			$defectTempArray["defectKey"] 		= $defect->getKey(); 
			$defectTempArray["defectImpact"] 	= $defect->get("Impact");
			$priority 							=  $defect->getPriority();
			$defectTempArray["defectPriorityUrl"]	= $priority["iconUrl"];
			$defectTempArray["defectPriorityNumber"]= getPriotiryNumberForPriorityUrl($priority["iconUrl"]);
			$defectTempArray["defectApplication"]	= $defect->get("Application")["value"];
			$issueDefectsTempArray[] = $defectTempArray;
		}
		$issueTempArray["issueDefects"] = $issueDefectsTempArray;
		
		$jsonArray[] = $issueTempArray;
	}
	
	echo json_encode($jsonArray);
}


function getPriotiryNumberForPriorityUrl($priorityUrl){
	
	$defaultPriorityNumber = 0;
	
	$arrayOfUrl = explode("/", $priorityUrl);
	$urlIcon = $arrayOfUrl[count($arrayOfUrl) - 1];
	
	$arrayOfIconPriorityNumberMapoping = array(
			"blocker.png" => 0,
			"critical.png" => 1,
			"major.png" => 2,
			"minor.png" => 3,
			"trivial.png" => 4
	);
	
	if (array_key_exists("$urlIcon", $arrayOfIconPriorityNumberMapoping))
		return $arrayOfIconPriorityNumberMapoping[$urlIcon];
	else 
		return $defaultPriorityNumber;
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