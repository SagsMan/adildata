<?php
require_once '../inc/config.inc.php';
if (!$UserAuth->is_user_logged_in()) {
    if (isset($_POST['login'])) {

        $rules = [
            'email' => [
                'required',
                'email'
            ],
            'password' => [
                'required'
            ]
        ];

        if (isset($_POST['remember'])) {
            $remember = trim($_POST['remember']);
        }

        $validation_result = SimpleValidator\Validator::validate($_POST, $rules);
        if ($validation_result->isSuccess()) {
            if ($UserAuth->LogInUser($_POST)) {




                if (! empty($_POST["remember"])) {
                    setcookie("member_login", $_POST['email'], $cookie_expiration_time);

                    $random_password = $UserAuth->getToken(16);
                    setcookie("random_password", $random_password, $cookie_expiration_time);

                    $random_selector = $UserAuth->getToken(32);
                    setcookie("random_selector", $random_selector, $cookie_expiration_time);

                    $random_password_hash = password_hash($random_password, PASSWORD_DEFAULT);
                    $random_selector_hash = password_hash($random_selector, PASSWORD_DEFAULT);

                    $expiry_date = date("Y-m-d H:i:s", $cookie_expiration_time);

                    // mark existing token as expired
                    $userToken = $UserAuth->getTokenByUsername($_POST['email'], 0);
                    if (! empty($userToken->id)) {
                        $UserAuth->markAsExpired($userToken->id);
                    }
                    // Insert new token
                    $UserAuth->insertToken($_POST['email'], $random_password_hash, $random_selector_hash, $expiry_date);
                }







                $UserAuth->redirect('./');
                exit();
            }
            array_push($SITE_ERRORS, 'Invalid Login Credentials');
        } else {
            array_push($SITE_ERRORS, $validation_result->getErrors());
        }
    }
} else {
    $UserAuth->redirect('./');
}



$PAGE_TITLE   = 'LogIn';
$URL_NAME     = 'login';
?>



<!DOCTYPE html>
<html lang="en" class="h-100">

<head><meta charset="utf-8">
    
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $PAGE_TITLE . " | " . SITE_TITLE ?></title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="images/<?= SITE_LOGO ?>">
    <!--<link href="./css/dashboard-style.css" rel="stylesheet">-->
    <link href="./css/style.css" rel="stylesheet">
    <!-- Toastr -->
    <link rel="stylesheet" href="vendor/toastr/css/toastr.min.css">

</head>

<body class="h-100">
    <div class="authincation h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-6">
                    <div class="authincation-content">
                        <div class="row no-gutters">
                            <div class="col-xl-12">
                                <div class="auth-form">
                                    <div style="text-align: center; margin-bottom: 3%"><img src="./images/<?= SITE_LOGO ?>" class="img-responsive"></div>
                                    <h4 class="text-center mb-4">Sign in your account</h4>
                                    <form method="POST" action="">
                                        <div id="response_status">
                                            <?php if (count($SITE_ERRORS) > 0): ?>
                                                <?php foreach ($SITE_ERRORS as $error): ?>

                                                    <p>
                                                    <div class="alert alert-danger" style="text-align:center;"><strong>Error! </strong> <?= $error ?></div>
                                                    </p>

                                                <?php endforeach ?>
                                            <?php endif ?>
                                            <?php if (count($SITE_SUCCESS) > 0): ?>
                                                <?php foreach ($SITE_SUCCESS as $good): ?>

                                                    <p>
                                                    <div class="alert alert-success" style="text-align:center;"><strong><?= $good ?></strong> </div>
                                                    </p>

                                                <?php endforeach ?>
                                            <?php endif ?>
                                        </div>

                                        <div class="form-group">
                                            <label class="mb-1"><strong>Email</strong></label>
                                            <input type="email" name="email" required="" class="form-control" value="<?php if (isset($_COOKIE["member_login"])) {
                                                                                                                            echo $_COOKIE["member_login"];
                                                                                                                        } ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Password</strong></label>
                                            <input type="password" class="form-control" value="<?php if (isset($_COOKIE["member_password"])) {
                                                                                                    echo $_COOKIE["member_password"];
                                                                                                } ?>" name="password" required="">
                                        </div>
                                        <div class="form-row d-flex justify-content-between mt-4 mb-2">
                                            <div class="form-group">


                                                <div class="custom-control custom-checkbox ml-1">
                                                    <input type="checkbox" checked="" class="custom-control-input" name="remember" id="basic_checkbox_1" <?php if (isset($_COOKIE["member_login"])) { ?> checked
                                                        <?php } ?>>
                                                    <label class="custom-control-label" for="basic_checkbox_1">Remember Me</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <a href="forgot-password">Forgot Password?</a>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <input name="login" type="submit" value="Sign Me In" style="background-color: rgb(16, 213, 150) !important; color: white; border-color: rgb(16, 213, 150)" class="btn btn-primary btn-block">
                                        </div>
                                    </form>
                                    <div class="new-account mt-3">
                                        <p>Don't have an account? <a style="color: rgb(16, 213, 150) !important;" class="text-primary" href="register">Sign up</a></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <script src="./vendor/global/global.min.js"></script>
    <script src="./vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script src="./js/custom.min.js"></script>
    <script src="./js/deznav-init.js"></script>
    <!-- Toastr -->
    <script src="./vendor/toastr/js/toastr.min.js"></script>

    <!-- All init script -->
    <script src="./js/plugins-init/toastr-init.js"></script>


    <?php if (count($SITE_ERRORS) > 0): ?>
        <?php foreach ($SITE_ERRORS as $error): ?>

            <script type="text/javascript">
                toastr.error("<?= strip_tags($error) ?>", "Error Occurs!", {
                    positionClass: "toast-top-right",
                    timeOut: 5e3,
                    closeButton: !0,
                    debug: !1,
                    newestOnTop: !0,
                    progressBar: !0,
                    preventDuplicates: !0,
                    onclick: null,
                    showDuration: "300",
                    hideDuration: "1000",
                    extendedTimeOut: "1000",
                    showEasing: "swing",
                    hideEasing: "linear",
                    showMethod: "fadeIn",
                    hideMethod: "fadeOut",
                    tapToDismiss: !1
                })
            </script>

        <?php endforeach ?>
    <?php endif ?>
    <?php if (count($SITE_SUCCESS) > 0): ?>
        <?php foreach ($SITE_SUCCESS as $good): ?>

            <script type="text/javascript">
                toastr.success("<?= strip_tags($good) ?>", "Error Occurs!", {
                    positionClass: "toast-top-right",
                    timeOut: 5e3,
                    closeButton: !0,
                    debug: !1,
                    newestOnTop: !0,
                    progressBar: !0,
                    preventDuplicates: !0,
                    onclick: null,
                    showDuration: "300",
                    hideDuration: "1000",
                    extendedTimeOut: "1000",
                    showEasing: "swing",
                    hideEasing: "linear",
                    showMethod: "fadeIn",
                    hideMethod: "fadeOut",
                    tapToDismiss: !1
                })
            </script>
        <?php endforeach ?>
    <?php endif ?>

</body>

</html>