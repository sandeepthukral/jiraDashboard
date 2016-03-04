<?php 
$priorityUrl = "https://jira.itservices.lan/images/icons/priorities/major.png";

$arrayOfUrl = explode("/", $priorityUrl);
$urlIcon = $arrayOfUrl[count($arrayOfUrl) - 1];

var_dump($arrayOfUrl);

echo "<br>";
echo $urlIcon;