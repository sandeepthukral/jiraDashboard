<?php

/*
 * A class that lets you create a reportable object.
 * This is an object that stores JIRA information that needs to be reported.
 */
class Reportable {
	protected  $_issue;
	protected $_arrayOfDefects;
	protected $_status;
	protected $_application;
	protected $_percentageCompleted;

	public function __constructor(){
	}

	public function setIssue($issue){
		$this->_issue = $issue;
	}

	public function setArrayOfDefects ($inputArray) {
		if (is_array($inputArray)){
			$this->_arrayOfDefects = $inputArray;
		}
	}

	public function setStatus($status){
		$this->_status = $status;
	}
	
	public function setApplication($application){
		$this->_application = $application;
	}
	
	public function setPercentageCompleted($percentageCompleted){
		$this->_percentageCompleted = $percentageCompleted;
	}

	public function getIssue(){
		return $this->_issue;
	}

	public function getArrayOfDefects(){
		return $this->_arrayOfDefects;
	}

	public function getStatus(){
		return $this->_status;
	}
	
	public function getApplication(){
		return $this->_application;
	}
	
	public function getPercentageCompleted(){
		return $this->_percentageCompleted;
	}
}