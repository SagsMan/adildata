<?php
require_once 'config.inc.php';
if ($UserAuth->is_user_logged_in()) {
    if ($Auth = $UserAuth->GetUserId($_SESSION['Login_User'])) {
    } else {
        $UserAuth->redirect(SITE_URL . 'easyfinder/dashboard/login');
    }
} else if (! empty($_COOKIE["member_login"]) && ! empty($_COOKIE["random_password"]) && ! empty($_COOKIE["random_selector"])) {
    // Initiate auth token verification diirective to false
    $isPasswordVerified = false;
    $isSelectorVerified = false;
    $isExpiryDateVerified = false;

    // Get token for username
    $userToken = $UserAuth->getTokenByUsername($_COOKIE["member_login"], 0);

    // Validate random password cookie with database
    if (password_verify($_COOKIE["random_password"], $userToken->password_hash)) {
        $isPasswordVerified = true;
    }

    // Validate random selector cookie with database
    if (password_verify($_COOKIE["random_selector"], $userToken->selector_hash)) {
        $isSelectorVerified = true;
    }

    // check cookie expiration by date
    if ($userToken->expiry_date >= $current_date) {
        $isExpiryDareVerified = true;
    }

    // Redirect if all cookie based validation retuens true
    // Else, mark the token as expired and clear cookies
    if (!empty($userToken->id) && $isPasswordVerified && $isSelectorVerified && $isExpiryDareVerified) {
        $_SESSION['Login_User'] = $userToken->email;
        if ($UserAuth->Get_Loggined_student_email_for_cookie_token($userToken->email)) {

            $UserAuth->redirect(SITE_URL . 'easyfinder/dashboard');
        }
    } else {
        if (!empty($userToken->id)) {
            $UserAuth->markAsExpired($userToken->id);
        }
        // clear cookies
        $UserAuth->clearAuthCookie();
    }
} else {

    $UserAuth->redirect(SITE_URL . 'easyfinder/dashboard/login');
}
