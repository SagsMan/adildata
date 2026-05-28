<?php
require_once '../inc/user_session.inc.php';

if(isset($_POST['change'])){

$rules = [
    'c_password' => [
        'required'
    ],
    'n_password' => [
        'required',
        'equals(:co_password)'
    ],
    'co_password' => [
        'required'
    ]
];

$validation_result = SimpleValidator\Validator::validate($_POST, $rules);
if ($validation_result->isSuccess()) {

if($AdminTask->check_old_password($_POST['c_password'],$Auth->email)){

if($AdminTask->change_password($_POST['n_password'],$Auth->email)){
array_push($SITE_SUCCESS, 'Your Password is Succesful changed'); 
}

}else{
array_push($SITE_ERRORS, 'Your current password is invalid');
}


}else {
array_push($SITE_ERRORS, $validation_result->getErrors());
}


}



$PAGE_TITLE   = 'Change Password';
$URL_NAME     = 'dashboard/change-password';
require_once("../inc/accessbility_controller.inc.php"); 
?>
<!DOCTYPE html>
<html lang="en">

<head><meta charset="utf-8">
   <?php
   require_once 'layout/header-propt.inc.php';
   ?>

<title><?= $PAGE_TITLE." | ".SITE_TITLE ?> </title>
</head>
<body>

   <?php  require_once 'layout/preloader.inc.php'; ?>

    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">

      
   <?php
   require_once 'layout/header.inc.php';
   require_once 'layout/sidebar.inc.php';
   ?>





       
	  <!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body">
            <?php  include('layout/minor-top-navbar.inc.php'); ?>
            <div class="container-fluid">
                <div class="row page-titles mx-0">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4 style="color: #003366; font-size: 20px"><?= $PAGE_TITLE ?></h4>
                            
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0)"><?=SITE_TITLE ?></a></li>
                            <li class="breadcrumb-item active"><a href="javascript:void(0)"><?= $PAGE_TITLE ?></a></li>
                        </ol>
                    </div>
                </div>




              


                <!-- row -->
                <div class="row">
                   
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><?= $PAGE_TITLE  ?></h4>
                            </div>
                            <div class="card-body">
                                <div class="basic-form">
                                

                                    <form action="" method="POST" enctype="multipart/form-data">
                                         <div class="form-group col-12">
                                        <label class="col-form-label">Current Password :</label>
                                        <div>
                                            <input name="c_password" class="form-control" type="text" value="" required="" autocomplete="off" minlength="6">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group col-12">
                                        <label class="col-form-label">New password :</label>
                                        <div>
                                            <input name="n_password" class="form-control" type="password" value="" required="" autocomplete="off" minlength="6">
                                        </div>
                                    </div>
                                    <div class="form-group col-12">
                                        <label class="col-form-label">Confirm Password :</label>
                                        <div>
                                            <input name="co_password" class="form-control" type="password" value="" required="" minlength="6">
                                        </div>
                                    </div>

                                       <div class="form-group col-12">
                                        <button type="submit" class="btn btn-primary" name="change">Save changes</button>
                                    </form>



                                </div>
                            </div>
                        </div>
                    </div>
                </div>






            </div>
        </div>
        <!--**********************************
            Content body end
        ***********************************-->
<?php
   require_once 'layout/footer.inc.php';
 ?>
    </div>
 
	<?php
   require_once 'layout/footer-propt.inc.php';
   ?>
	
</body>
</html>