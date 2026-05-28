<?php
session_start();

$SITE_ERRORS= [];
$SITE_SUCCESS = [];


	define('SITE_Script', TRUE);
	define('PUBLIC_HTML_PATH', $_SERVER['DOCUMENT_ROOT']);
	define('DOCS_ROOT', $_SERVER['DOCUMENT_ROOT']);
	define('SITE_ROOT', dirname(__FILE__) . '/');
	define('SITE_PAGE', TRUE);

require_once(SITE_ROOT."../vendor/autoload.php");

$site_settings = new EduTech\C_Base;
$UserAuth = new EduTech\Controller\UserController;
$AdminTask = new EduTech\Controller\AdminController;
$WalletController = new EduTech\Controller\WalletController;
$Reloadly_API = new EduTech\Controller\ReloadlyApiController;
$TopupController = new EduTech\Controller\TopupController;
$VerificationController = new EduTech\Controller\VerificationController;

require_once(SITE_ROOT."siteconfig.inc.php");



date_default_timezone_set("Africa/Lagos");
// Get Current date, time
$current_time = time();
$current_date = date("Y-m-d H:i:s", $current_time);

// Set Cookie expiration for 1 month
$cookie_expiration_time = $current_time + (30 * 24 * 60 * 60);  // for 1 month























 if (isset($_GET['User_logout']) && ($_GET['User_logout'] == 'true')) {
    $UserAuth->log_out_user();
	$site_settings->redirect(SITE_URL.'easyfinder/dashboard/login');
    //$Student_Class->clearAuthCookie();
    //$d_school_settings->redirect(SITE_URL);

}
