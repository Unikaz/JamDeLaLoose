<?php

function SaveConfig($key, $newValue){
	global $config, $dictionary, $loggedInUser;

	if(IsAdmin($loggedInUser) === false){
		return "NOT_AUTHORIZED";
	}

	if (!isset($config[$key])) {
		//Invalid configuration key
		return;
	}

	if ($config[$key]["EDITABLE"] != true) {
		//Some configuration settings cannot be set via this interface for security reasons.
		return;
	}

	if ($newValue == $config[$key]) {
		return;
	}

	UpdateConfig($config, $key, $newValue, $loggedInUser['id'], $loggedInUser["username"]);
	return "SUCCESS";
}

function PerformAction(&$loggedInUser){
	global $_POST;
	
	if(IsAdmin($loggedInUser) !== false){
		$actionResult = "NO_CHANGE";
		foreach($_POST as $key => $value){
			$actionResult = SaveConfig($key, $value);
		}
		return $actionResult;
	}
}

?>