<?php

// use chobie;
require_once __DIR__ . '/vendor/autoload.php';
require_once 'functions.php';
require_once 'config.php';
require_once 'Reportable.php';

define("REFERENCE", "Regression Anchor");

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
	$jql = "type = \"Tele2 Test Sub-Task\" AND parent =  \"" . $issue->getKey() . "\"";
// 	echo $jql;
	$allTestTasksForRegressionStory->push($jql);
	
	$tempArrayOfAllRegressionTests = array();
	foreach ($allTestTasksForRegressionStory as $testTask){
		$tempArrayOfAllRegressionTests[] = $testTask;
	}
	
	$reportable->setArrayOfDefects($tempArrayOfAllRegressionTests);
	$arrayOfReportablesForRegressionStories[] = $reportable;
}

// debugToConsole (count($arrayOfReportablesForRegressionStories));

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
			$defectStatus = $defect->getStatus();
			$defectTempArray = array();
			$defectTempArray["defectKey"] 		= $defect->getKey(); 
			$defectTempArray["defectImpact"] 	= $defect->get("Impact");
			$defectTempArray["defectSummary"]	= $defect->getSummary();
			$defectTempArray["defectStatus"]	= mapTestTaskStatus($defectStatus['name']);
			$priority =  $defect->getPriority();
			$defectTempArray["defectPriorityUrl"]			= $priority["iconUrl"];
			$defectTempArray["defectPercentageComplete"] 	= $defect->get("Percentage Complete");
			$issueDefectsTempArray[] = $defectTempArray;
		}
		$issueTempArray["issueDefects"] = $issueDefectsTempArray;
		
		$jsonArray[] = $issueTempArray;
	}
	
	echo json_encode($jsonArray);
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



function mapTestTaskStatus($originalStatus){

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
	
	if (key_exists($originalStatus, $arrayOfMappings))
		return $arrayOfMappings[$originalStatus];
	else 
		return "Unmapped Status";
}