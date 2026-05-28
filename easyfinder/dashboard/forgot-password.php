<?php
require_once '../inc/config.inc.php';
if (!$UserAuth->is_user_logged_in()) {
    if (isset($_POST['forgot'])) {
        $rules = [
            'email' => ['required', 'email'],
        ];

        $validation_result = SimpleValidator\Validator::validate(
            $_POST,
            $rules
        );
        if ($validation_result->isSuccess()) {
            if ($UserAuth->GetUserId($_POST['email'])) {
                if ($UserAuth->forgot_password($_POST)) {
                    array_push(
                        $SITE_SUCCESS,
                        'Password reset link has been sent to your email'
                    );
                } else {
                    array_push($SITE_ERRORS, 'Something went wrong !');
                }
            } else {
                array_push($SITE_ERRORS, 'Email not found found !');
            }
        } else {
            array_push($SITE_ERRORS, $validation_result->getErrors());
        }
    }
} else {
    $UserAuth->redirect('./');
}

$PAGE_TITLE = 'Forgot Password';
$URL_NAME = 'forgot-password';
?>



<!DOCTYPE html>
<html lang="en" class="h-100">

<head><meta charset="utf-8">
    
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?></title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="./images/favicon.png">
    <link href="./css/style.css" rel="stylesheet">
    <!-- Toastr -->
    <link rel="stylesheet" href="./vendor/toastr/css/toastr.min.css">

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
                                    <div style="text-align: center; margin-bottom: 3%"><img
                                            src="./images/<?= SITE_LOGO ?>" class="img-responsive"></div>
                                    <h4 class="text-center mb-4">Forgot Password</h4>
                                    <form action="" method="POST" class="form-valide-with-icon">


                                        <div id="response_status">
                                            <?php if (count($SITE_ERRORS) > 0): ?>
                                            <?php foreach ($SITE_ERRORS as $error): ?>

                                            <p>
                                            <div class="alert alert-danger" style="text-align:center;"><strong>Error!
                                                </strong> <?= $error ?></div>
                                            </p>

                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                            <?php if (count($SITE_SUCCESS) > 0): ?>
                                            <?php foreach ($SITE_SUCCESS as $good): ?>

                                            <p>
                                            <div class="alert alert-success" style="text-align:center;">
                                                <strong><?= $good ?></strong> </div>
                                            </p>

                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <div class="form-group">
                                            <label><strong>Email : </strong></label>
                                            <div class="input-group">
                                                <input type="email" name="email" required="" class="form-control"
                                                    placeholder="myregisteredemail@gmail.com">
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" name="forgot" value="forgot"
                                                class="btn btn-primary btn-block">SUBMIT</button>
                                        </div>
                                    </form>
                                    <div class="new-account mt-3">
                                        <p>Login instead ? <a class="text-primary" href="login">Sign in</a></p>
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
    <!-- Jquery Validation -->
    <script src="./vendor/jquery-validation/jquery.validate.min.js"></script>
    <!-- Form validate init -->
    <script src="./js/plugins-init/jquery.validate-init.js"></script>

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

    <?php endforeach; ?>
    <?php endif; ?>
    <?php if (count($SITE_SUCCESS) > 0): ?>
    <?php foreach ($SITE_SUCCESS as $good): ?>

    <script type="text/javascript">
    toastr.success("<?= strip_tags($good) ?>", "success!", {
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
    <?php endforeach; ?>
    <?php endif; ?>


</body>

</html>