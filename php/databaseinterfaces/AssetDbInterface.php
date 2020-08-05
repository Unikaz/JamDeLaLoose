<?php
define("DB_TABLE_ASSET", "asset");

define("DB_COLUMN_ASSET_ID",                "asset_id");
define("DB_COLUMN_ASSET_DATETIME",          "asset_datetime");
define("DB_COLUMN_ASSET_IP",                "asset_ip");
define("DB_COLUMN_ASSET_USER_AGENT",        "asset_user_agent");
define("DB_COLUMN_ASSET_AUTHOR_USER_ID",    "asset_author_user_id");
define("DB_COLUMN_ASSET_TITLE",             "asset_title");
define("DB_COLUMN_ASSET_DESCRIPTION",       "asset_description");
define("DB_COLUMN_ASSET_TYPE",              "asset_type");
define("DB_COLUMN_ASSET_CONTENT",           "asset_content");
define("DB_COLUMN_ASSET_DELETED",           "asset_deleted");

class AssetDbInterface{
    private $dbConnection;
    private $publicColumns = Array(DB_COLUMN_ASSET_ID, DB_COLUMN_ASSET_AUTHOR_USER_ID, DB_COLUMN_ASSET_TITLE, DB_COLUMN_ASSET_DESCRIPTION, DB_COLUMN_ASSET_TYPE, DB_COLUMN_ASSET_CONTENT, DB_COLUMN_ASSET_DELETED);
    private $privateColumns = Array(DB_COLUMN_ASSET_DATETIME, DB_COLUMN_ASSET_IP, DB_COLUMN_ASSET_USER_AGENT);

    function __construct(&$dbConn) {
        $this->dbConnection = $dbConn;
    }

    public function SelectCurrentlyActiveAssets(){
        AddActionLog("AssetDbInterface_SelectCurrentlyActiveAssets");
        StartTimer("AssetDbInterface_SelectCurrentlyActiveAssets");

        $sql = "
            SELECT ".DB_COLUMN_ASSET_ID.", ".DB_COLUMN_ASSET_AUTHOR_USER_ID.", ".DB_COLUMN_ASSET_TITLE.", ".DB_COLUMN_ASSET_DESCRIPTION.", ".DB_COLUMN_ASSET_TYPE.", ".DB_COLUMN_ASSET_CONTENT."
            FROM ".DB_TABLE_ASSET."
            WHERE ".DB_COLUMN_ASSET_DELETED." != 1
        ";

        StopTimer("AssetDbInterface_SelectCurrentlyActiveAssets");
        return mysqli_query($this->dbConnection, $sql);
    }

    public function SelectWhereAuthorUserId($authorUserId){
        AddActionLog("AssetDbInterface_SelectWhereAuthorUserId");
        StartTimer("AssetDbInterface_SelectWhereAuthorUserId");

        $escapedAuthorUserId = mysqli_real_escape_string($this->dbConnection, $authorUserId);
        $sql = "
            SELECT ".DB_COLUMN_ASSET_ID.", ".DB_COLUMN_ASSET_DATETIME.", ".DB_COLUMN_ASSET_AUTHOR_USER_ID.", ".DB_COLUMN_ASSET_TITLE.", ".DB_COLUMN_ASSET_DESCRIPTION.", ".DB_COLUMN_ASSET_TYPE.", ".DB_COLUMN_ASSET_CONTENT.", ".DB_COLUMN_ASSET_DELETED."
            FROM ".DB_TABLE_ASSET."
            WHERE ".DB_COLUMN_ASSET_AUTHOR_USER_ID." = '$escapedAuthorUserId';";

        StopTimer("AssetDbInterface_SelectWhereAuthorUserId");
        return mysqli_query($this->dbConnection, $sql);
    }

    public function SelectSingleAsset($assetId){
        AddActionLog("AssetDbInterface_SelectSingleAsset");
        StartTimer("AssetDbInterface_SelectSingleAsset");

        $escapedAssetId = mysqli_real_escape_string($this->dbConnection, $assetId);
        $sql = "
            SELECT ".DB_COLUMN_ASSET_ID.", ".DB_COLUMN_ASSET_AUTHOR_USER_ID.", ".DB_COLUMN_ASSET_TITLE."
            FROM ".DB_TABLE_ASSET." a
            WHERE ".DB_COLUMN_ASSET_DELETED." != 1
              AND ".DB_COLUMN_ASSET_ID." = $escapedAssetId;
        ";
        StopTimer("AssetDbInterface_SelectSingleAsset");
        return mysqli_query($this->dbConnection, $sql);
    }

    public function Insert($ip, $userAgent, $authorUserId, $title, $description, $type, $assetURL){
        AddActionLog("AssetDbInterface_Insert");
        StartTimer("AssetDbInterface_Insert");

		$escapedIp = mysqli_real_escape_string($this->dbConnection, $ip);
		$escapedUserAgent = mysqli_real_escape_string($this->dbConnection, $userAgent);
		$escapedAuthorUserId = mysqli_real_escape_string($this->dbConnection, $authorUserId);
		$escapedTitle = mysqli_real_escape_string($this->dbConnection, $title);
		$escapedDescription = mysqli_real_escape_string($this->dbConnection, $description);
		$escapedType = mysqli_real_escape_string($this->dbConnection, $type);
		$escapedContent = mysqli_real_escape_string($this->dbConnection, $assetURL);
        
		$sql = "
            INSERT INTO ".DB_TABLE_ASSET."
            (".DB_COLUMN_ASSET_ID.",
            ".DB_COLUMN_ASSET_DATETIME.",
            ".DB_COLUMN_ASSET_IP.",
            ".DB_COLUMN_ASSET_USER_AGENT.",
            ".DB_COLUMN_ASSET_AUTHOR_USER_ID.",
            ".DB_COLUMN_ASSET_TITLE.",
            ".DB_COLUMN_ASSET_DESCRIPTION.",
            ".DB_COLUMN_ASSET_TYPE.",
            ".DB_COLUMN_ASSET_CONTENT.",
            ".DB_COLUMN_ASSET_DELETED.")
            VALUES
            (null,
            Now(),
            '$escapedIp',
            '$escapedUserAgent',
            $escapedAuthorUserId,
            '$escapedTitle',
            '$escapedDescription',
            '$escapedType',
            '$escapedContent',
            0);
        ";
        mysqli_query($this->dbConnection, $sql);

        StopTimer("AssetDbInterface_Insert");
    }

    public function Update($assetId, $authorUserId, $title, $description, $type, $content){
        AddActionLog("AssetDbInterface_Update");
        StartTimer("AssetDbInterface_Update");

		$escapedAssetId = mysqli_real_escape_string($this->dbConnection, $assetId);
		$escapedAuthorUserId = mysqli_real_escape_string($this->dbConnection, $authorUserId);
		$escapedTitle = mysqli_real_escape_string($this->dbConnection, $title);
		$escapedDescription = mysqli_real_escape_string($this->dbConnection, $description);
		$escapedType = mysqli_real_escape_string($this->dbConnection, $type);
		$escapedContent = mysqli_real_escape_string($this->dbConnection, $content);
        
		$sql = "
            UPDATE ".DB_TABLE_ASSET."
            SET
                ".DB_COLUMN_ASSET_AUTHOR_USER_ID." = $escapedAuthorUserId,
                ".DB_COLUMN_ASSET_TITLE." = '$escapedTitle',
                ".DB_COLUMN_ASSET_DESCRIPTION." = '$escapedDescription',
                ".DB_COLUMN_ASSET_TYPE." = '$escapedType',
                ".DB_COLUMN_ASSET_CONTENT." = '$escapedContent'
            WHERE ".DB_COLUMN_ASSET_ID." = $escapedAssetId;
        ";
        mysqli_query($this->dbConnection, $sql);

        StopTimer("AssetDbInterface_Update");
    }

    public function SoftDelete($assetId){
        AddActionLog("AssetDbInterface_SoftDelete");
        StartTimer("AssetDbInterface_SoftDelete");

		$escapedAssetId = mysqli_real_escape_string($this->dbConnection, $assetId);
        
        $sql = "
            UPDATE ".DB_TABLE_ASSET."
            SET
                ".DB_COLUMN_ASSET_DELETED." = 1
            WHERE ".DB_COLUMN_ASSET_ID." = $escapedAssetId;
        ";
        mysqli_query($this->dbConnection, $sql);

        StopTimer("AssetDbInterface_SoftDelete");
    }

    public function SelectPublicData(){
        AddActionLog("AssetDbInterface_SelectPublicData");
        StartTimer("AssetDbInterface_SelectPublicData");

        $sql = "
            SELECT ".implode(",", $this->publicColumns)."
            FROM ".DB_TABLE_ASSET.";
        ";

        StopTimer("AssetDbInterface_SelectPublicData");
        return mysqli_query($this->dbConnection, $sql);
    }
}

?>