<?php

define("PREFERENCE_DISABLE_THEMES_NOTIFICATION", "DISABLE_THEMES_NOTIFICATION");

$userPreferenceSettings = Array(
	Array("PREFERENCE_KEY" => PREFERENCE_DISABLE_THEMES_NOTIFICATION, "BIT_FLAG_EXPONENT" => 0)
);

class UserModel
{
    public $Id;
    public $Username;
    public $DisplayName;
    public $Twitter;
    public $TwitterTextOnly;
    public $Email;
    public $Salt;
    public $PasswordHash;
    public $PasswordIterations;
    public $Admin;
    public $UserPreferences;
    public $Preferences;
    public $DaysSinceLastLogin;
    public $DaysSinceLastAdminAction;
    public $IsSponsored;
    public $SponsoredByUserId;
}

interface IUserDisplay{
	function HasUser($userId);
	function GetUserDisplayName($userId);
	function GetUserIdentifiableName($userId);
}

class UserData implements IUserDisplay{
    public $UserModels;
    public $UsernameToId;

    private $userDbInterface;
    private $sessionDbInterface;

    function __construct(&$userDbInterface, &$sessionDbInterface) {
        $this->userDbInterface = $userDbInterface;
        $this->sessionDbInterface = $sessionDbInterface;
        $this->UserModels = $this->LoadUsers();
        $this->UsernameToId = $this->GenerateUsernameToId();
    }

    public function HasUser($userId){
        return isset($this->UserModels[$userId]);
    }

    public function GetUserDisplayName($userId){
        return $this->UserModels[$userId]->DisplayName;
    }

    public function GetUserIdentifiableName($userId){
        return $this->UserModels[$userId]->Username;
    }

//////////////////////// MODEL CONSTRUCTOR

    function LoadUsers(){
        global $userPreferenceSettings;
        AddActionLog("LoadUsers");
        StartTimer("LoadUsers");
    
        $data = $this->userDbInterface->SelectAll();
    
        $userModels = Array();
        while($info = mysqli_fetch_array($data)){
            $currentUser = Array();
            
            $user = new UserModel();
            $user->Id = $info[DB_COLUMN_USER_ID];
            $user->Username = $info[DB_COLUMN_USER_USERNAME];
            $user->DisplayName = $info[DB_COLUMN_USER_DISPLAY_NAME];
            $user->Twitter = $info[DB_COLUMN_USER_TWITTER];
            $user->TwitterTextOnly = str_replace("@", "", $info[DB_COLUMN_USER_TWITTER]);
            $user->Email = $info[DB_COLUMN_USER_EMAIL];
            $user->Salt = $info[DB_COLUMN_USER_SALT];
            $user->PasswordHash = $info[DB_COLUMN_USER_PASSWORD_HASH];
            $user->PasswordIterations = intval($info[DB_COLUMN_USER_PASSWORD_ITERATIONS]);
            $user->Admin = intval($info[DB_COLUMN_USER_ROLE]);
            $user->UserPreferences = intval($info[DB_COLUMN_USER_PREFERENCES]);
            $user->Preferences = Array();
            $user->DaysSinceLastLogin = 1000000;
            $user->DaysSinceLastAdminAction = 1000000;
            $user->IsSponsored = 0;
            $user->SponsoredBy = "";
    
            foreach($userPreferenceSettings as $i => $preferenceSetting){
                $preferenceFlag = pow(2, $preferenceSetting["BIT_FLAG_EXPONENT"]);
                $preferenceKey = $preferenceSetting["PREFERENCE_KEY"];
    
                $user->Preferences[$preferenceKey] = $user->UserPreferences & $preferenceFlag;
            }
    
            //This fixes an issue where user_last_login_datetime was not set properly in the database, which results in days_since_last_login being null for users who have not logged in since the fix was applied
            if($info["days_since_last_login"] == null){
                $info["days_since_last_login"] = 1000000;
            }
    
            //For cases where users have never performed an admin action
            if($info["days_since_last_admin_action"] == null){
                $info["days_since_last_admin_action"] = 1000000;
            }
    
            $user->DaysSinceLastLogin = intval($info["days_since_last_login"]);
            $user->DaysSinceLastAdminAction = intval($info["days_since_last_admin_action"]);
    
            $userModels[$user->Id] = $user;
        }
    
        ksort($userModels);
    
        $data = $this->userDbInterface->SelectAdminCandidates();
    
        while($info = mysqli_fetch_array($data)){
            $voteVoterUserId = $info[DB_COLUMN_ADMINVOTE_VOTER_USER_ID];
            $voteSubjectUserId = $info[DB_COLUMN_ADMINVOTE_SUBJECT_USER_ID];
    
            foreach($userModels as $i => $userModel){
                if($userModel->Id == $voteSubjectUserId){
                    $userModels[$i]->IsSponsored = 1;
                    $userModels[$i]->SponsoredByUserId = $voteVoterUserId;
                }
            }
        }
    
        StopTimer("LoadUsers");
        return $userModels;
    }

    function GenerateUsernameToId(){
        $usernamesToIds = Array();

        foreach($this->UserModels as $i => $userModel){
            $usernamesToIds[$userModel->Username] = $userModel->Id;
        }

        return $usernamesToIds;
    }

//////////////////////// END MODEL CONSTRUCTOR
    
//////////////////////// DATABASE ACTIONS (select, insert, update)

    function LoadBio($userId) {
        AddActionLog("LoadBio");
        StartTimer("LoadBio");
    
        $data = $this->userDbInterface->SelectBioOfUser($userId);

        $info = mysqli_fetch_array($data);
        $bio = $info[DB_COLUMN_USER_BIO];
    
        StopTimer("LoadBio");
        return $bio;
    }

    function GetUsersOfUserFormatted($userId){
        AddActionLog("GetUsersOfUserFormatted");
        StartTimer("GetUsersOfUserFormatted");
    
        $data = $this->userDbInterface->SelectUsersOfUser($userId);
    
        StopTimer("GetUsersOfUserFormatted");
        return ArrayToHTML(MySQLDataToArray($data));
    }
    
    function GetSessionsOfUserFormatted($userId){
        AddActionLog("GetSessionsOfUserFormatted");
        StartTimer("GetSessionsOfUserFormatted");
    
        $data = $this->sessionDbInterface->SelectSessionsOfUser($userId);
    
        StopTimer("GetSessionsOfUserFormatted");
        return ArrayToHTML(MySQLDataToArray($data));
    }

//////////////////////// END DATABASE ACTIONS

//////////////////////// PUBLIC DATA EXPORT
    function GenerateRandomString($length) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function GetAllPublicUserData(){
        global $configData;
        AddActionLog("UserData_GetAllPublicUserData");
        StartTimer("UserData_GetAllPublicUserData");
        
        $dataFromDatabase = MySQLDataToArray($userDbInterface->SelectPublicData());

        foreach($dataFromDatabase as $i => $row){
            $password = $this->GenerateRandomString(30);
            $passwordHashIterations = GenerateUserHashIterations($configData);
            $salt = GenerateSalt();
            $hashedPassword = HashPassword($password, $salt, $passwordHashIterations, $configData);

            $dataFromDatabase[$i][DB_COLUMN_USER_DATETIME] = gmdate("Y-m-d H:i:s", time());
            $dataFromDatabase[$i][DB_COLUMN_USER_IP] = OVERRIDE_MIGRATION;
            $dataFromDatabase[$i][DB_COLUMN_USER_USER_AGENT] = OVERRIDE_MIGRATION;
            $dataFromDatabase[$i][DB_COLUMN_USER_SALT] = $salt;
            $dataFromDatabase[$i][DB_COLUMN_USER_PASSWORD_HASH] = $hashedPassword;
            $dataFromDatabase[$i][DB_COLUMN_USER_PASSWORD_ITERATIONS] = $passwordHashIterations;
            $dataFromDatabase[$i][DB_COLUMN_USER_LAST_LOGIN_DATETIME] = gmdate("Y-m-d H:i:s", time());
            $dataFromDatabase[$i][DB_COLUMN_USER_LAST_IP] = OVERRIDE_MIGRATION;
            $dataFromDatabase[$i][DB_COLUMN_USER_LAST_USER_AGENT] = OVERRIDE_MIGRATION;
            $dataFromDatabase[$i][DB_COLUMN_USER_EMAIL] = "";
            $dataFromDatabase[$i][DB_COLUMN_USER_ROLE] = 0;
            $dataFromDatabase[$i][DB_COLUMN_USER_PREFERENCES] = rand(0, 1);

            $password = "";
            $passwordHashIterations = "";
            $salt = "";
            $hashedPassword = "";
        }

        StopTimer("UserData_GetAllPublicUserData");
        return $dataFromDatabase;
    }

    function GetAllPublicSessionData($sessionHashIterations, $pepper){
        global $configData;
        AddActionLog("UserData_GetAllPublicSessionData");
        StartTimer("UserData_GetAllPublicSessionData");
        
        $dataFromDatabase = MySQLDataToArray($this->SelectPublicSessionData());

        foreach($dataFromDatabase as $i => $row){
            $sessionId = GenerateSalt();
            $hashedSessionId = HashPassword($sessionId, $pepper, $sessionHashIterations, $configData);

            $dataFromDatabase[$i][DB_COLUMN_SESSION_ID] = $hashedSessionId;
            $dataFromDatabase[$i][DB_COLUMN_SESSION_DATETIME_STARTED] = gmdate("Y-m-d H:i:s", time());
            $dataFromDatabase[$i][DB_COLUMN_SESSION_DATETIME_LAST_USED] = gmdate("Y-m-d H:i:s", time());
            
            $sessionId = "";
            $hashedSessionId = "";
        }

        StopTimer("UserData_GetAllPublicSessionData");
        return $dataFromDatabase;
    }

//////////////////////// END PUBLIC DATA EXPORT
}

?>