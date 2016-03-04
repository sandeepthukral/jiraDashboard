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

$isTest = false;
if (isset($_GET['isTest']))
	$isTest=true;

$jql = "project = " . $project . " AND sprint = \"" . $sprint . "\" and type=\"Solution Story\" ORDER BY issuekey";
if($isTest) debugToConsole($jql) ;
$jqlForAllStories = $jql;

$api = getApiClient();

$arrayOfEpicLinkedStories = array();
$arrayOfRegressionStories = array();

$arrayOfReportablesForEpicLinkedStories = array();
$arrayOfReportablesForRegressionStories = array();

$start = microtime(true);
$allSolutionStories = new chobie\Jira\Issues\Walker($api);
$allSolutionStories->push( $jqlForAllStories );

if($isTest)
	debugToConsole("Fetching all stories took " . (microtime(true) - $start) . " s");
if($isTest) debugToConsole("Found ".count($allSolutionStories). " stories in all");


$allStatuses = array();

foreach ($allSolutionStories as $issue) {
	$reference = $issue->get("Reference");
	if ($isTest) debugToConsole("Found reference - " . $issue->get("Epic Link") );
	
	foreach ($arrayOfRelatedEpicLinksToInclude as $key=>$value){
		if ($key == $project){
			if (startsWith($value, $issue->get("Epic Link"))){
				$arrayOfEpicLinkedStories[] = $issue;
			}
		}
	}
}

$allDefectTasksForAStory = new chobie\Jira\Issues\Walker($api);

foreach ($arrayOfEpicLinkedStories as $issue){
	
	$reportable = new Reportable();
	$reportable->setIssue($issue);
	
	$now = microtime(true);
	$allDefectTasksForAStory->push("type = \"Tele2 Defect Sub-Task\" AND status not in (\"Task: Done\", \"Task: Not Required\") AND parent = \"" . $issue->getKey() . "\"");
	if($isTest) 
		debugToConsole("Fetching all defects took " . (microtime(true) - $now) . " s");
	
	$start = microtime(true);
	$tempArrayOfDefects = array();
	foreach ($allDefectTasksForAStory as $defect){
		$tempArrayOfDefects[] = $defect;
	}
	
	$reportable->setArrayOfDefects($tempArrayOfDefects);
	$arrayOfReportablesForEpicLinkedStories[] = $reportable;
	if($isTest) 
		debugToConsole("Processing all defects took " . (microtime(true) - $start) . " s");
}


returnJsonData ( $arrayOfReportablesForEpicLinkedStories ) ;

function returnJsonData ( $arrayOfReportables ) {
	
	global $isTest;
	
	$start = microtime(true);
	
	$jsonArray = array();
	
	foreach ($arrayOfReportables as $reportable){
		
		$issueTempArray = array();
		$issueDefectsTempArray = array();
		
		$issue = $reportable->getIssue();
		$statuses = $issue->getStatus();
		if ($isTest) var_dump($statuses);
		
		$issueTempArray["issueKey"] 		= $issue->getKey(); 
		$issueTempArray["issueSummary"] 	= $issue->getSummary();
		$changeStatus = $issue->get("Chain Status");
		$issueTempArray["issueStatus"]		= $changeStatus['value'];
		// for Epics, we need the Chain Status->value instead fo the story status.
// 		$issueTempArray["issueStatus"]		= refactoredStoryStatus($statuses['name']);
		
		
		$arrayOfDefects = $reportable->getArrayOfDefects();
		foreach ($arrayOfDefects as $defect){
			$defectTempArray = array();
			$defectTempArray["defectKey"] 			= $defect->getKey(); 
			$defectTempArray["defectImpact"] 		= $defect->get("Impact"); 
			$priority =  $defect->getPriority();
			$defectTempArray["defectPriorityUrl"]	= $priority["iconUrl"];
			$defectTempArray["defectPriorityNumber"]= getPriotiryNumberForPriorityUrl($priority["iconUrl"]);
			$defectTempArray["defectApplication"]	= $defect->get("Application")["value"];
			$issueDefectsTempArray[] = $defectTempArray;
		}
		$issueTempArray["issueDefects"] = $issueDefectsTempArray;
		
		$jsonArray[] = $issueTempArray;
	}
	
	if($isTest) 
		debugToConsole("Returning JSON data took " . (microtime(true) - $start) . " s");
	
	echo json_encode($jsonArray);
}


/*
 * Status in Jira translated to more business friendly ones
 */
function refactoredStoryStatus($inputStatus) {
	
	$arrayOfMappings = array(
			"Story: Done" 			=> "Done",
			"Story: In Sprint"		=> "In Progress",
			"Story: In Testing"		=> "In Progress",
			"Story: In Progress"	=> "In Progress",
			"Story: User Acceptance"=> "In Progress",
			"Story: New"			=> "Todo",
			"Story: Sprint Ready"	=> "Todo",
			"Story: Not Required"	=> "Skipped"
	);
	
	if (array_key_exists($inputStatus, $arrayOfMappings))
		return $arrayOfMappings[$inputStatus];
	else 
		return $inputStatus;
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
 * Helper function that instantiates an API client to communicate with Jira
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
