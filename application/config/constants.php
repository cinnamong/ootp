<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
define('ENV', "live");
/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

/*
|--------------------------------------------------------------------------
| Basic Templating Values
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
//LOGIN
define("MESSAGE_NOTICE",'notice');
define("MESSAGE_FAIL",'error');
define("MESSAGE_SUCCESS",'success');

define("TEMPLATE_DEFAULT",'template');

define('EMPTY_DATE_STR','0000-00-00');
define('EMPTY_DATE_TIME_STR','0000-00-00 00:00:00');

define('EMPTY_TIME_STR','00:00:00');

define('PAGE_NORMAL','normal');
define('PAGE_SEARCH','search');
define('PAGE_FORM','form');

define('NEWS_FANTASY_GAME',1);
define('NEWS_LEAGUE',2);
define('NEWS_PLAYER',3);
define('NEWS_TEAM',4);

define('RESTRICT_NONE',0);
define('RESCTRICT_ALL',1);
define('RESTRICT_EDIT',2);
define('RESTRICT_INFO',3);
define('RESTRICT_CUSTOM',4);

define('TRANS_OWNER_OWNER',1);
define('TRANS_OWNER_COMMISH',2);
define('TRANS_OWNER_ADMIN',3);
define('TRANS_OWNER_OTHER',4);

define('TRANS_TYPE_ADD',1);
define('TRANS_TYPE_DROP',2);
define('TRANS_TYPE_TRADE_TO',3);
define('TRANS_TYPE_TRADE_FROM',4);
define('TRANS_TYPE_OTHER',100);

define('SQL_OPERATOR_NONE',0);
define('SQL_OPERATOR_SUM',1);
define('SQL_OPERATOR_AVG',2);

// SET THE DEFAULT PATH SEPERATOR
if (substr(PHP_OS, 0, 3) == 'WIN') {
    define("URL_PATH_SEPERATOR","\\");
    define("PATH_SEPERATOR",";");
} else {
    define("URL_PATH_SEPERATOR","/");
    define("PATH_SEPERATOR",":");
}
define("JS_JQUERY","jquery-1.3.2.min.js");

define("MAIN_INSTALL_FILE","install.php");
define("DB_UPDATE_FILE","db_update.sql");
define("CONFIG_UPDATE_FILE","config_update.php");
define("CONSTANTS_UPDATE_FILE","constants_update.php");
define("DATA_CONFIG_UPDATE_FILE","database_update.php");
define("SL_CONNECTION_FILE","dbopen.php");
define("DB_CONNECTION_FILE","ootpfl_db.php");

define('QUERY_BASIC',1);
define('QUERY_STANDARD',2);
define('QUERY_EXTENDED',3);
/*
|--------------------------------------------------------------------------
| File/Path Defaults
|--------------------------------------------------------------------------
|
| Default include files and paths
|
*/
define("SITE_URL",",http://localhost/ootp_fantasy/");
define("DIR_APP_ROOT","/ootp_fantasy/");
define("DIR_APP_WRITE_ROOT","/ootp_fantasy/");
define("DIR_WRITE_PATH","C:\\wamp\\www\\ootp_fantasy\\");

define("SITE_URL_SHORT",SITE_URL);
define("DIR_WEB_ROOT",SITE_URL);

define("DIR_VIEWS_USERS","users".URL_PATH_SEPERATOR);
define("DIR_VIEWS_INCLUDES","includes".URL_PATH_SEPERATOR);
define("DIR_VIEWS_SEARCH","search".URL_PATH_SEPERATOR);
define("DIR_VIEWS_BUGS","bug".URL_PATH_SEPERATOR);
define("DIR_VIEWS_PROJECTS","project".URL_PATH_SEPERATOR);
define("DIR_VIEWS_LEAGUES","league".URL_PATH_SEPERATOR);
define("DIR_VIEWS_NEWS","news".URL_PATH_SEPERATOR);

define("PATH_INSTALL",DIR_WRITE_PATH.URL_PATH_SEPERATOR."install".URL_PATH_SEPERATOR);

define("PATH_IMAGES",DIR_APP_ROOT."images/");
define("PATH_IMAGES_WRITE","images".URL_PATH_SEPERATOR);

define("PATH_MEDIA",DIR_APP_ROOT."media/");
define("PATH_MEDIA_WRITE","media".URL_PATH_SEPERATOR);

define("PATH_ATTACHMENTS",PATH_MEDIA."uploads/");
define("PATH_ATTACHMENTS_WRITE","uploads".URL_PATH_SEPERATOR);

define("DEFAULT_AVATAR",'avatar_default.jpg');
define("PATH_AVATARS_WRITE",PATH_IMAGES_WRITE."avatars".URL_PATH_SEPERATOR);
define("PATH_AVATARS",PATH_IMAGES."avatars/");

define("PATH_USERS_AVATAR_WRITE",PATH_AVATARS_WRITE."users".URL_PATH_SEPERATOR);
define("PATH_USERS_AVATARS",PATH_AVATARS."users/");

define("PATH_TEAMS_AVATAR_WRITE",PATH_AVATARS_WRITE."teams".URL_PATH_SEPERATOR);
define("PATH_TEAMS_AVATARS",PATH_AVATARS."teams/");

define("PATH_LEAGUES_AVATAR_WRITE",PATH_AVATARS_WRITE."leagues".URL_PATH_SEPERATOR);
define("PATH_LEAGUES_AVATARS",PATH_AVATARS."leagues/");

define("PATH_NEWS_IMAGES_WRITE",PATH_IMAGES_WRITE."news".URL_PATH_SEPERATOR);
define("PATH_NEWS_IMAGES",PATH_IMAGES."news/");

define("PATH_NEWS_IMAGES_PREV_WRITE",PATH_NEWS_IMAGES_WRITE."preview".URL_PATH_SEPERATOR);
define("PATH_NEWS_IMAGES_PREV",PATH_NEWS_IMAGES."preview/");

define("ACCESS_READ",1);
define("ACCESS_WRITE",2);
define("ACCESS_MODERATE",3);
define("ACCESS_MANAGE",4);
define("ACCESS_DEVELOP",5);
define("ACCESS_ADMINISTRATE",6);
/*
|--------------------------------------------------------------------------
| MOD DETAILS
|--------------------------------------------------------------------------
*/
define('SITE_NAME','OOTP Fantasy Leagues');
define('SITE_VERSION','1.0.3 Beta');
define('MOD_SITE_URL','http://www.ootpfantasyleagues.com/');
define("BUG_URL",'http://nabl.aeoliandigital.com/ootp_fantasy_leagues/about/bug_report');
define("UPDATE_URL",'http://www.ootpfantasyleagues.com/version/curr_version.txt');
/*
|--------------------------------------------------------------------------
| TABLE Defaults
|--------------------------------------------------------------------------
|
| Default include files and paths
|
*/
define("USER_CORE_TABLE","users_core");

define("DEFAULT_RESULTS_COUNT",25);

/* End of file constants.php */
/* Location: ./system/application/config/constants.php */