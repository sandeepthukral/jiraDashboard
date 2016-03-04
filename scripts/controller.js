"use strict";

angular.module("myapp", [])
.controller("JiraReportController", function($scope, $http, $interval) {
	
	$scope.projects = [{"id":"EQ", "name":"Consumer Mobile 4G", sprint:"15S12 - 4G E2E"}, 
	                   {"id":"BMQ", "name":"Business Mobile Quality", sprint:"15S12 BMQ"}]
	
	$scope.project = $scope.projects[0];
	
	$scope.setProject = function(proj){
		
		for( var i=0, l=$scope.projects.length; i<l; i++ ) {
		    if( $scope.projects[i].id == proj){
		    	$scope.project = $scope.projects[i];
		    }
		}
		
		/*$scope.project = proj;
		if (proj == "EQ"){
    		$scope.sprint = "15S08 - 4G E2E";
    	} else {
    		$scope.sprint = "15S08 - BMQ";
    	}*/
	}
	
	/*$scope.$watch("project", function(newValue, oldValue) {
		$scope.getEpicsDefectInfo(null,null);
		$scope.getRegressionDefectInfo();
		$scope.getRegressionTestCasesInfo();
		$scope.getRegressionStatistics();
	  });*/
	
    $scope.Epics 				= null;
    $scope.Regression  			= null;
    $scope.RegressionTestCases 	= null;
    
    $scope.RegressionStatisticsLoaded = false;
    
    
    
    $scope.setSprint = function(proj){
    	
    }
    
	$scope.getEpicsDefectInfo = function(item, event) {
    	
		$scope.Epics = [{
    						"issueKey": "Loading...",
    						"issueSummary": "Loading...",
    						"issueStatus": "Loading...",
    						"issueDefects": [{
    											"defectKey": "Loading...",
    											"defectImpact": null
    	}]}];
		
        var funcEpicDefects = function(){
        	var url = "getDefectsForEpics.php?sprint="+ $scope.project.sprint + "&project=" + $scope.project.id;
        	console.log(url);
        	$http.get(url)
        	.success(function(data, status, headers, config) {
        		$scope.Epics = data;
        		$scope.Epics.allStatuses = [];
        		
        		angular.forEach($scope.Epics, function(value, key){
                	var status = value.issueStatus;
                	$scope.Epics.allStatuses.push(status);
                });
        		console.log($scope.Epics.allStatuses);
        		
        		Object.defineProperty($scope, 'filteredEpics', {
        	        get: function(){
        	            var list = {};
        	            $scope.Epics.allStatuses.forEach(function (item) {
        	                if (list[item] === undefined) {
        	                     list[item] = 1;
        	                } else {
        	                   list[item] += 1;
        	                }
        	            });          
        	            var newItems = [];    
        	            Object.keys(list).forEach(function(key){      
        	              newItems.push({  
        	                 type :key,
        	                 amount: list[key]
        	              });    
        	            });
        	            return newItems;
        	        }
        	    });
        		
        	})
        	.error(function(data, status, headers, config) {
        		console.log("AJAX failed!" + config.url + data);
        	}
        );}
        
        funcEpicDefects();
        
        setInterval(function(){funcEpicDefects(); console.log("Updated Defects for Epics");}, 180000);
    }
	
	
	$scope.getRegressionDefectInfo = function(item, event) {
		
		if ($scope.Regression){			
		}
		else {
			$scope.Regression = [{
				"issueKey": "Loading...",
				"issueSummary": "Loading...",
				"issueStatus": "Loading...",
				"issueDefects": [{
									"defectKey": "Loading...",
									"defectImpact": null
			}]}];
		}
		
		var funcRegressionDefects = function (){
			var url = "getDefectsForRegression.php?sprint="+ $scope.project.sprint + "&project=" + $scope.project.id;
			console.log(url);
			$http.get(url)
	        	.success(function(data, status, headers, config) {
	        		$scope.Regression = data;
	        		$scope.Regression.allStatuses = [];
	        		angular.forEach($scope.Regression, function(value, key){
	                	var status = value.issueStatus;
	                	$scope.Regression.allStatuses.push(status);
	                });
	        		
	        		var a = [], b = [], prev;
	        		$scope.Regression.allStatuses.sort();
	        	    for ( var i = 0; i < $scope.Regression.allStatuses.length; i++ ) {
	        	        if ( $scope.Regression.allStatuses[i] !== prev ) {
	        	            a[$scope.Regression.allStatuses[i]] = 1;
	        	            
	        	        } else {
	        	            a[$scope.Regression.allStatuses[i]]++;
	        	        }
	        	        prev = $scope.Regression.allStatuses[i];
	        	    }
	//        	    console.log(a);
	        	})
	        	.error(function(data, status, headers, config) {
	        		console.log("AJAX failed!" + config.url);
	        	}
        	);
		};
        
		funcRegressionDefects();
        
        setInterval(function(){funcRegressionDefects(); console.log("Updated Defects for Regression");}, 180000);
    }
	
	
	
	$scope.getRegressionTestCasesInfo = function(item, event) {
		
		if ($scope.RegressionTestCases){
			
		}
		else {
			$scope.RegressionTestCases = [{
				"issueKey": "Loading...",
				"issueSummary": "Loading...",
				"issueStatus": "Loading...",
				"issueDefects": [{
									"defectKey": "Loading...",
									"defectImpact": null
			}]}];
		}
		
		var funcRegTestCases = function() {
			var url = "getTestCasesForRegression.php?sprint="+ $scope.project.sprint + "&project=" + $scope.project.id;
			console.log(url);
			$http.get(url)
	        	.success(function(data, status, headers, config) {
	        		$scope.RegressionTestCases = data;
	        		$scope.RegressionTestCases.allStatuses = [];
	        		angular.forEach($scope.RegressionTestCases, function(value, key){
	                	var status = value.issueStatus;
	                	$scope.RegressionTestCases.allStatuses.push(status);
	                });
	        		
	        		var a = [], b = [], prev;
	        		$scope.RegressionTestCases.allStatuses.sort();
	        	    for ( var i = 0; i < $scope.RegressionTestCases.allStatuses.length; i++ ) {
	        	        if ( $scope.RegressionTestCases.allStatuses[i] !== prev ) {
	        	            a[$scope.RegressionTestCases.allStatuses[i]] = 1;
	        	            
	        	        } else {
	        	            a[$scope.RegressionTestCases.allStatuses[i]]++;
	        	        }
	        	        prev = $scope.RegressionTestCases.allStatuses[i];
	        	    }
	//        	    console.log(a);
	        	})
	        	.error(function(data, status, headers, config) {
	        		console.log("AJAX failed!" + config.url);
	        	}
	        );
		};
		
		funcRegTestCases();
		
		setInterval(function(){funcRegTestCases(); console.log("Updated Regression Test Cases");}, 60000);
		
         
    }

	
	$scope.getRegressionStatistics = function (item, event) {
		$scope.RegressionStatistics = [];
		
		
		var funcStatisticsForRegression = function() {
			$http.get("getStatisticsForRegression.php?sprint="+ $scope.project.sprint + "&project=" + $scope.project.id)
			.success(function(data, status, headers, config){
				$scope.RegressionStatistics = data;
//				console.log(data);
				$scope.RegressionStatisticsLoaded = true;
			})
			.error(function(data, status, headers, config){
				console.log("AJAX failed!" + config.url);
			});
		};
		
		funcStatisticsForRegression();
		setInterval(function(){funcStatisticsForRegression(); console.log("Updated Statistics for Regression");}, 180000);
		
	};
	
	$scope.setSprint = function(sprintName){
    	$scope.sprint = sprintName;
    	$scope.getRegressionStatistics();
    	$scope.getEpicsDefectInfo();
    	$scope.getRegressionTestCasesInfo();
    	$scope.getRegressionDefectInfo();
    };


} )
.filter("toArray", function(){
   
	return function(obj) {
        var result = [];
        angular.forEach(obj, function(val, key) {
            result.push(val);
        });
//        console.log(result);
        return result;
    };
    
});
