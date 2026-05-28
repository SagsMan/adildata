<?php
require_once '../inc/config.inc.php';
if(!$UserAuth->is_user_logged_in()){
if(isset($_POST['signup'])){

$rules = [
    'email' => [
        'required',
        'email'
    ],
    'password' => [
        'required',
        'equals(:cpassword)'
    ],
    'cpassword' => [
        'required'
    ], 
    'sname' => [
        'required',
    ],
    'oname' => [
        'required'
    ],
    'phone' => [
        'required',
         'numeric'
    ],
    'pin' => [
        'required',
         'numeric'
    ],
    'state' => [
        'required',
    ]
];

$validation_result = SimpleValidator\Validator::validate($_POST, $rules);
if ($validation_result->isSuccess()) {
if($UserAuth->Apply($_POST)){
 $UserAuth->redirect('./');
}else{
array_push($SITE_ERRORS, 'This email has been registerd');
}
} else {
array_push($SITE_ERRORS, $validation_result->getErrors());
}


}
}else{
  $UserAuth->redirect('./');
}



$PAGE_TITLE   = 'Register Now';
$URL_NAME     = 'register';
?>



<!DOCTYPE html>
<html lang="en" class="h-100">

<head><meta charset="utf-8">
    
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $PAGE_TITLE." | ".SITE_TITLE ?></title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="images/<?=SITE_LOGO?>">
    <link href="./css/style.css" rel="stylesheet">
    <!-- Toastr -->
    <link rel="stylesheet" href="./vendor/toastr/css/toastr.min.css">

</head>

<body class="h-100">
    <div class="authincation">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-6">
                    <div class="authincation-content">
                        <div class="row no-gutters">
                            <div class="col-xl-12">
                                <div class="auth-form">
            <div style="text-align:center;margin-bottom:3%;padding:10px 0;">
                                        <span style="font-weight:900;font-size:32px;color:#10d596;letter-spacing:3px;font-family:sans-serif;display:inline-block;">Adildata</span>
                                    </div>
                                    <h4 class="text-center mb-4">Sign up your account </h4>
                                  <div id="response_status">
<?php if (count($SITE_ERRORS) > 0): ?>
        <?php foreach ($SITE_ERRORS as $error): ?>
     
   <p><div class="alert alert-danger" style="text-align:center;"><strong>Error! </strong> <?= $error ?></div></p>

        <?php endforeach ?>
    <?php endif ?>
<?php if (count($SITE_SUCCESS) > 0): ?>
        <?php foreach ($SITE_SUCCESS as $good): ?>
             
        <p><div class="alert alert-success" style="text-align:center;"><strong><?= $good ?></strong> </div></p>
  
        <?php endforeach ?>
    <?php endif ?>
</div>
                                    <form action="" method="POST" class="form-valide-with-icon">
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Surname </strong></label>
                                            <div class="input-group">
                                           <input type="text" name="sname" required="" class="form-control">
                                        </div>
                                    </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>OtherNames </strong></label>
                                            <div class="input-group">
                                          <input type="text" name="oname"  required="" class="form-control">
                                        </div>
                                    </div>

                                            <div class="form-group">
                                            <label class="mb-1"><strong>State Of Origin </strong></label>
                                            <div class="input-group">
                                          <input type="text" name="state"  required="" class="form-control">
                                        </div>
                                    </div>

                                <div class="form-group">
                                            <label class="mb-1"><strong>Phone Number </strong></label>
                                            <div class="input-group">
                                          <input type="tel" name="phone" required="" class="form-control">
                                        </div>
                                    </div>



                                        <div class="form-group">
                                            
                                            <label class="mb-1"><strong>Email</strong></label><div class="input-group">
                                           <input type="email" name="email" required="" class="form-control">
                                        </div>
                                    </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Password</strong></label>
                                            <div class="input-group">
                                           <input type="password" name="password" class="form-control" required="" id="password">
                                        </div>
                                    </div>
                                          <div class="form-group">

                                            <label class="mb-1"><strong>Re-type Password </strong></label>
                                            <div class="input-group">
                                          <input type="password" name="cpassword" class="form-control" required="">
                                          <!-- <input type="hidden" name="referal" class="form-control" <?php if(isset($_GET['join_with_referal'])){?> value="<?= $_GET['join_with_referal'] ?>" <?php } ?>>-->
                                        </div>
                                    </div>


                                         <div class="form-group">
                                            <label class="mb-1"><strong>Transaction/Pass Pin: </strong></label>
                                            <div class="input-group">
                                          <input type="password" name="pin" required="" class="form-control" minlength="4" maxlength="4">
                                        </div>
                                    </div>


                                     <div class="form-group">

                                            <label class="mb-1"><strong>Referal Token </strong><span style="color: #10d596">(Optional)</span></label>
                                            <div class="input-group">
                                           <input type="text" name="referal" class="form-control" placeholder="Enter referral code (optional)" value="<?= isset($_GET['join_with_referal']) ? htmlspecialchars($_GET['join_with_referal']) : '' ?>">
                                        </div>
                                    </div>
                                        <div class="text-center mt-4">
                                            <button name="signup" type="submit" value="Submit" style="background-color: rgb(16, 213, 150) !important; color: white; border-color: rgb(16, 213, 150)" class="btn btn-primary btn-block">Sign me up</button>
                                        </div>
                                    </form>
                                    <div class="new-account mt-3">
                                        <p>Already have an account? <a style="color: #10d596" href="login">Sign in</a></p>
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