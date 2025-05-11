<?php 
    // Basics
    define('PEPPER', 'cc42e68333d7ba68f1b104114264cf3d');
    define('HTTP_ADRESS', 'http://localhost/YourCert/');
    define('DEV_DEBUG_MODE', true);

    // Paths
    define('PUBLIC_ASSETS', HTTP_ADRESS . 'assets/');
    define('TERMS_PAGE', HTTP_ADRESS . 'terms/');
    define('VERIFY_CERTIFICATE_PAGE', HTTP_ADRESS . 'verify-certificate/');
    define('UPLOADS_FOLDER', HTTP_ADRESS . 'uploads/');
    define('ADMIN_PANEL', HTTP_ADRESS . 'admin/');
    define('REGISTRY_FORM', HTTP_ADRESS . 'account/register/');
    define('LOGIN_PAGE', HTTP_ADRESS . 'account/login/');
    define('LOGOUT_SCRIPT', HTTP_ADRESS . 'account/logout/');
    define('SUCCESS_PAGE', HTTP_ADRESS . 'panel/');
    define('CONFIRMATION_PAGE', HTTP_ADRESS . 'account/register/confirmation.php');
    define('PASSWORD_REQUEST_PAGE', HTTP_ADRESS . 'account/reset/password_change_request.php');
    define('PASSWORD_RESET_PAGE', HTTP_ADRESS . 'account/reset/confirm_password_change.php');
    define('EMAIL_RESET_PAGE', HTTP_ADRESS . 'account/reset/confirm_email_change.php');
    define('MY_CERTIFICATES_PAGE', HTTP_ADRESS . 'account/my-certificates/');
    define('MY_ACCOUNT_PAGE', HTTP_ADRESS . 'account/settings/');
    define('ACCOUNT_DELETE_PAGE', HTTP_ADRESS . 'account/reset/delete_my_account.php');

    // Logic
    if (DEV_DEBUG_MODE === true) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }
?>