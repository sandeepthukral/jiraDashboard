<?php

// use chobie;
require_once __DIR__ . '/vendor/autoload.php';
require_once 'config.php';
require_once 'Reportable.php';

define("REFERENCE", "Regression Anchor");
$arrayOfRelatedEpicLinksToInclude = array(
		"MRG"
);


$jqlForAllTestTasks = "project = EQ AND sprint = \"15S04 - 4G E2E\" and type=\"Tele2 Test Sub-Task\" ORDER BY issuekey";

$api = getApiClient();

$arrayOfEpicLinkedStories = array();
$arrayOfRegressionStories = array();

$arrayOfReportablesForEpicLinkedStories = array();
$arrayOfReportablesForRegressionStories = array();

$allTestTasks = new chobie\Jira\Issues\Walker($api);
$allTestTasks->push( $jqlForAllTestTasks );

$arrayOfReportables = array();

$counter = 0;

foreach ($allTestTasks as $testTask) {
	if ($counter > 0)
		continue;
	else {
		echo "Issue Key \"" . $testTask->getKey() . "\"<br/>" . PHP_EOL;
		echo "Percentage Complete \"" . $testTask->get("Percentage Complete") . "\"<br/>" . PHP_EOL;
		echo "Application \"" . $testTask->get("Application")["value"] . "\"<br/>" . PHP_EOL;
		var_dump($testTask);
		$arrayOfReportables[] = $testTask; 
		$counter += 1;
	}
}

// displayReportables($arrayOfReportables);


// foreach ($arrayOfEpicLinkedStories as $issue){
	
// 	$reportable = new Reportable();
// 	$reportable->setIssue($issue);
	
// 	$allDefectTasksForAStory = new chobie\Jira\Issues\Walker($api);
// 	$allDefectTasksForAStory->push("type = \"Tele2 Defect Sub-Task\" AND status not in (\"Task: Done\", \"Task: Not Required\") AND parent = \"" . $issue->getKey() . "\"");
	
// 	$tempArrayOfDefects = array();
// 	foreach ($allDefectTasksForAStory as $defect){
// 		$tempArrayOfDefects[] = $defect;
// 	}
	
// 	$reportable->setArrayOfDefects($tempArrayOfDefects);
// 	$arrayOfReportablesForEpicLinkedStories[] = $reportable;
// }


// foreach ($arrayOfRegressionStories as $issue){

// 	$reportable = new Reportable();
// 	$reportable->setIssue($issue);
	
// 	$allDefectTasksForRegressionStory = new chobie\Jira\Issues\Walker($api);
// 	$allDefectTasksForRegressionStory->push(" (type = \"Tele2 Defect Sub-Task\" AND status not in (\"Task: Done\", \"Task: Not Required\")) OR (type = \"Tele2 Test Sub-Task\" AND status in (\"Task: In Testing\")) ) AND parent = \"" . $issue->getKey() . "\"");
	
// 	$tempArrayOfDefectsAndFailedTests = array();
// 	foreach ($allDefectTasksForRegressionStory as $defect){
// 		$tempArrayOfDefectsAndFailedTests[] = $defect;
// 	}
	
// 	$reportable->setArrayOfDefects($tempArrayOfDefectsAndFailedTests);
// 	$arrayOfReportablesForRegressionStories[] = $reportable;
// }



// echo "Stories that are linked to Epics<hr>", PHP_EOL;
// displayReportables ( $arrayOfReportablesForEpicLinkedStories );

// echo "Stories that are Regression Related<hr>", PHP_EOL;
// displayReportables ( $arrayOfReportablesForRegressionStories );

/*
 * Displays the array of Reportables
 */
function displayReportables ( $arrayOfReportables ) {
	
	foreach ($arrayOfReportables as $reportable){
	
		$issue = $reportable->getIssue();
		$statuses = $issue->getStatus();
		echo "<br>" . $issue->getKey() . " Status = "  . $statuses['name'] . "<br>";
		echo $issue->getSummary() . "<br>";
		$arrayOfDefects = $reportable->getArrayOfDefects();
		if (count($arrayOfDefects) > 0){
			echo "Defects for this issue<br>";
		} else {
			echo "No open defects for this story<br>";
		}
		foreach ($arrayOfDefects as $defect){
			echo $defect->getKey() . " - " . $defect->getSummary() . "<br>";
			echo $defect->get("Impact");
		}
	}
	
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