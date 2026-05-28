<?php
require_once '../inc/config.inc.php';
if(!$UserAuth->is_user_logged_in()){
if(empty($_GET['email']) && empty($_GET['token'])){
   $UserAuth->redirect('forgot-password'); 
}
if(isset($_POST['reset'])){

$rules = [
   'password' => [
        'required',
        'equals(:cpassword)'
    ],
    'cpassword' => [
        'required'
    ]
];

$validation_result = SimpleValidator\Validator::validate($_POST, $rules);
if ($validation_result->isSuccess()) {
if($UserAuth->reset_password($_POST, $_GET['email'],$_GET['token'])){
array_push($SITE_SUCCESS, 'Your Password has been reset. You will be redirect to login after 5secs. Please wait!');

echo"<script>
         setTimeout(function(){
            window.location.href = 'login';
         }, 5000);
      </script>";
}else{
array_push($SITE_ERRORS, 'Email not found or token expired');
}
} else {
array_push($SITE_ERRORS, $validation_result->getErrors());
}


}
}else{
  $UserAuth->redirect('./');
}



$PAGE_TITLE   = 'Reset Password';
$URL_NAME     = 'register';
?>



<!DOCTYPE html>
<html lang="en" class="h-100">

<head><meta charset="utf-8">
    
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $PAGE_TITLE." | ".SITE_TITLE ?></title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="<?= SITE_URL ?>easyfinder/dashboard/images/<?=SITE_LOGO?>">
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
                                    <div style="text-align: center; margin-bottom: 3%"><img src="./images/<?=SITE_LOGO?>" class="img-responsive"></div>
                                    <h4 class="text-center mb-4" style="color: #003366; font-size: 20px">Reset Password</h4>
                                    <form action="" method="POST" class="form-valide-with-icon">


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
                                        </div>
                                    </div>
                                        <div class="text-center">
                                            <button type="submit" name="reset" value="reset" class="btn btn-primary btn-block">SUBMIT</button>
                                        </div>
                                    </form>
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