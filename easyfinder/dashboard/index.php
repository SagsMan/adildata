<?php
require_once '../inc/user_session.inc.php';
$_SESSION['Trans_id'] = $WalletController->Generate_Trans_id();
$PAGE_TITLE = 'Dashboard';
$URL_NAME = 'dashboard';

$error = '';

// $monnify_account_details = $Auth->monnify_account_details;
if (empty($Auth->monnify_account_details)) {
    $accountResponse = $UserAuth->createMonnifyAccount($Auth);

    if ($accountResponse['success'] === true) {
        $Auth->monnify_account_details = $accountResponse['account_details'];
    } else {
        $error = $accountResponse['message'];
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head><meta charset="utf-8">
    <?php require_once 'layout/header-propt.inc.php'; ?>
    <link rel="stylesheet" type="text/css" href="../../assets/vendor/font-awesome/css/fontawesome-all.min.css" />

    <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?> </title>
</head>

<body>

    <?php require_once 'layout/preloader.inc.php'; ?>
    

    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">


        <?php
        require_once 'layout/header.inc.php';
        require_once 'layout/sidebar.inc.php';
        ?>

        <a class="whats-app fixed" href="https://api.whatsapp.com/send/?phone=2348037747842&amp;text&amp;app_absent=0" target="_blank">
            <i class="fab fa-whatsapp my-float"></i>
        </a>
        <!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body">

            <?php include 'layout/minor-top-navbar.inc.php'; ?>

            <p></p>
            <!-- <img src="<?= SITE_URL ?>dashboard/images/top-banner.gif" style="width: 100%;" > -->
            <!-- row -->
            <div class="container-fluid">
                <div class="form-head d-flex mb-3 align-items-start">
                    <div class="mr-auto d-none d-lg-block">
                        <h4 class=" font-w600 mb-0" style="color: rgb(16, 213, 150);">Dashboard</h4>
                        <p class="mb-0">Welcome To <?= SITE_TITLE ?> Admin!</p>
                    </div>

                    <div class="dropdown custom-dropdown">
                        <div style="color: #B4F5E0!important; background-color: #B4F5E0 !important; border-color: #B4F5E0 !important" class="btn btn-sm btn-danger light d-flex align-items-center svg-btn"
                            data-toggle="dropdown">
                            <!--<svg style="fill: #10d596;" viewBox="207.1984 273.6843 26.88 28.028" width="28" height="28" xmlns="http://www.w3.org/2000/svg">-->
                            <!--  <g transform="matrix(1, 0, 0, 1, 206.6383819580078, 273.684326171875)">-->
                            <!--    <path d="M22.4281 2.856H21.8681V1.428C21.8681 0.56 21.2801 0 20.4401 0C19.6001 0 19.0121 0.56 19.0121 1.428V2.856H9.71606V1.428C9.71606 0.56 9.15606 0 8.28806 0C7.42006 0 6.86006 0.56 6.86006 1.428V2.856H5.57206C2.85606 2.856 0.560059 5.152 0.560059 7.868V23.016C0.560059 25.732 2.85606 28.028 5.57206 28.028H22.4281C25.1441 28.028 27.4401 25.732 27.4401 23.016V7.868C27.4401 5.152 25.1441 2.856 22.4281 2.856ZM5.57206 5.712H22.4281C23.5761 5.712 24.5841 6.72 24.5841 7.868V9.856H3.41606V7.868C3.41606 6.72 4.42406 5.712 5.57206 5.712ZM22.4281 25.144H5.57206C4.42406 25.144 3.41606 24.136 3.41606 22.988V12.712H24.5561V22.988C24.5841 24.136 23.5761 25.144 22.4281 25.144Z" fill="#10d596"/>-->
                            <!--  </g>-->
                            <!--</svg>-->
                            <svg viewBox="191.5365 228.6969 26.88 28.028" width="26.88" height="28.028" xmlns="http://www.w3.org/2000/svg">
                                <g transform="matrix(1, 0, 0, 1, 190.9763946533203, 228.6969451904297)">
                                    <path d="M22.4281 2.856H21.8681V1.428C21.8681 0.56 21.2801 0 20.4401 0C19.6001 0 19.0121 0.56 19.0121 1.428V2.856H9.71606V1.428C9.71606 0.56 9.15606 0 8.28806 0C7.42006 0 6.86006 0.56 6.86006 1.428V2.856H5.57206C2.85606 2.856 0.560059 5.152 0.560059 7.868V23.016C0.560059 25.732 2.85606 28.028 5.57206 28.028H22.4281C25.1441 28.028 27.4401 25.732 27.4401 23.016V7.868C27.4401 5.152 25.1441 2.856 22.4281 2.856ZM5.57206 5.712H22.4281C23.5761 5.712 24.5841 6.72 24.5841 7.868V9.856H3.41606V7.868C3.41606 6.72 4.42406 5.712 5.57206 5.712ZM22.4281 25.144H5.57206C4.42406 25.144 3.41606 24.136 3.41606 22.988V12.712H24.5561V22.988C24.5841 24.136 23.5761 25.144 22.4281 25.144Z" style="fill: rgb(2, 51, 35);" />
                                </g>
                            </svg>
                            <div class="text-left ml-3">
                                <span style="color: #10d596" class="d-block fs-16">Today's Date</span>
                                <small style="color: #10d596" class="d-block fs-13"><?= date('Y-M-d h:m:a') ?></small>
                            </div>
                            <i style="color: rgb(16, 213, 150)" class="fa fa-angle-down scale5 ml-3"></i>
                        </div>

                    </div>
                </div>


                <div class="row">



                    <div class="col-xl-4 col-xxl-4 col-lg-6 col-md-6 col-sm-6">

                        <div class="widget-stat card">
                            <div class="card-body p-4">
                                <div class="media ai-icon">

                                    <!--<span class="mr-3 bgl-primary text-primary">
									
									
									</span>-->

                                    <div class="media-body">
                                        <h4 class="mb-0"
                                            style="font-weight: bold; font-size: 22px; font-family: time roman;">My
                                            Balance</h4>
                                        <h3 class="mb-0 text-black"
                                            style="font-weight: bold; font-size: 30px; font-family: time roman; text-align: center;">
                                            <span class="ml-0">

                                                ₦ <?= $WalletController->Get_Single_User_Wallet_Balance($Auth->email)
                                                        ? intval(
                                                            $WalletController->Get_Single_User_Wallet_Balance($Auth->email)->balance
                                                        )
                                                        : '0' ?>.00 NGN

                                            </span>
                                        </h3>
                                        <div class="mt-4" style="text-align: center;">
                                            <h4 style="font-weight: bold; font-size: 18px;">Account Number</h4>
                                            <?php
                                                if (!empty($Auth->monnify_account_details)) {
                                                    echo "<p><strong>Account Details: </strong>$Auth->monnify_account_details</p><br/><b style='color:red;font-size:12px;'>NOTE THAT #50 charge is applied to every transfers below #10,000 and
                                                #100 charge on 10,000 and above made to this account</b><hr>";
                                                } else {
                                                    echo "<p>No Monnify accounts available.</p>";
                                                }
                                            ?>

                                        </div>

                                        <div style="text-align: center;" class="mt-4">

                                            <button name="signup" type="submit" value="Submit" style="background-color: rgb(16, 213, 150) !important; color: white; border-color: rgb(16, 213, 150)"
                                                class="btn btn-danger btn-block" data-toggle="modal"
                                                data-target="#exampleModalpopover">Recharge Wallet</button>
                                            <p></p>
                                            <p><a href="wallet-transaction">Transaction History</a></p>
                                        </div>



                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="col-xl-4 col-xxl-4 col-lg-6 col-md-6 col-sm-6">

                        <div class="widget-stat card">
                            <div class="card-body p-4">
                                <div class="media ai-icon">
                                    <div class="media-body">
                                       <h4 class="mb-0"
                                            style="font-weight: bold; font-size: 22px; font-family: time roman;">
                                            Auto Funding </h4>
                                            <p>Kindly make a bank transfer to this account to fund your wallet</p>
                                             <?php 
                                            
                                            $conn = mysqli_connect("localhost", "adiliqgs_adildata", "adildata2026", "adiliqgs_adildata");
                                            $useraa =  $_SESSION['Login_User'];
                                            
                                            $q = mysqli_query($conn, "SELECT * FROM users_tbl WHERE email = '$useraa'");
                                            
                                            if(mysqli_num_rows($q)>0){
                                                $row = mysqli_fetch_array($q);
                                                
                                                if(empty($row['acc_no'])){
                                                include_once("generateBankAccount.php");
                                                
                                                $result = generateBankAccount($row['email'], $row['sname'], $row['phone']);

                                                echo "<a href='' class='btn btn-success btn-mini'>Refresh Account</a>";
                                                    
                                                }
                                                
                                            ?>
                                        <div class="mt-4" style="text-align: center;">
                                            <h4 style="font-weight: bold; font-size: 18px;">Account Details</h4>
                                            <p><strong>Account Number: </strong><?php echo $row['acc_no']; ?></p>
                                            <p><strong>Account Name: </strong><?php echo $row['acc_name']; ?></p>
                                            <p><strong>Bank: </strong><?php echo $row['bank_name']; ?></p>
                                        </div>
                                        <?php
                                        
                                            }else{
                                                echo "";
                                            }
                                            ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="col-xl-4 col-xxl-4 col-lg-6 col-md-6 col-sm-6">

                        <div class="widget-stat card">
                            <div class="card-body p-4">
                                <div class="media ai-icon">

                                    <div class="media-body">
                                        <h4 class="mb-0"
                                            style="font-weight: bold; font-size: 22px; font-family: time roman;">
                                            One-time mobile top-up</h4>
                                        <p style="text-align: center;"><small style="font-size: 13px; color:#10d596">Quick Airtime
                                                Purchase !</small></p>

                                        <form action="topup" method="GET" class="form-valide-with-icon">
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <input type="tel" name="phone" required=""
                                                        placeholder="Enter Your phone number" class="form-control"
                                                        autocomplete="off">
                                                </div>
                                            </div>
                                            <div class="text-center">
                                                <button style="background-color: rgb(16, 213, 150) !important; color: white; border-color: rgb(16, 213, 150)" class="btn btn-danger btn-sm">Next</button>
                                            </div>
                                        </form>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>




                    <?php if (
                        in_array(1, explode(',', $Auth->admin_role)) ||
                        in_array(2, explode(',', $Auth->admin_role))
                    ) { ?>

                        <!-- Reserve for admin   -->



                        <div class="col-xl-4 col-xxl-4 col-lg-6 col-md-6 col-sm-6">
                            <a href="my-trans-history">
                                <div class="widget-stat card">
                                    <div class="card-body p-4">
                                        <div class="media ai-icon">

                                            <div class="media-body">

                                                <h4 class="mb-0"
                                                    style="font-weight: bold; font-size: 22px; font-family: time roman;">
                                                    Transactions History</h4>
                                                <p><small>Total of Your Transactions History:</small></p>
                                                <span class="mr-3 bgl-primary text-primary pull-left">
                                                    <!-- <i class="ti-user"></i> -->
                                                    <svg viewBox="129.978 115.003 26.25 25" width="32" height="32" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M 133.728 140.003 L 152.478 140.003 C 153.472 140.002 154.425 139.606 155.128 138.903 C 155.831 138.2 156.226 137.247 156.228 136.253 L 156.228 126.253 C 156.228 125.921 156.096 125.603 155.861 125.369 C 155.627 125.135 155.309 125.003 154.978 125.003 C 154.646 125.003 154.328 125.135 154.094 125.369 C 153.859 125.603 153.728 125.921 153.728 126.253 L 153.728 136.253 C 153.727 136.584 153.595 136.902 153.361 137.137 C 153.127 137.371 152.809 137.503 152.478 137.503 L 133.728 137.503 C 133.396 137.503 133.078 137.371 132.844 137.137 C 132.61 136.902 132.478 136.584 132.478 136.253 L 132.478 118.753 C 132.478 118.421 132.61 118.104 132.844 117.869 C 133.078 117.635 133.396 117.503 133.728 117.503 L 144.978 117.503 C 145.309 117.503 145.627 117.371 145.861 117.137 C 146.096 116.902 146.228 116.584 146.228 116.253 C 146.228 115.921 146.096 115.603 145.861 115.369 C 145.627 115.135 145.309 115.003 144.978 115.003 L 133.728 115.003 C 132.733 115.004 131.78 115.399 131.077 116.102 C 130.374 116.806 129.979 117.759 129.978 118.753 L 129.978 136.253 C 129.979 137.247 130.374 138.2 131.077 138.903 C 131.78 139.606 132.733 140.002 133.728 140.003 Z" style="fill: rgb(16, 213, 150);" transform="matrix(1, 0, 0, 1, -2.842170943040401e-14, 0)" />
                                                    </svg>
                                                    <!--<svg width="32" height="31" viewBox="0 0 32 31" fill="none"-->
                                                    <!--    xmlns="http://www.w3.org/2000/svg">-->
                                                    <!--    <path-->
                                                    <!--        d="M4 30.5H22.75C23.7442 30.4989 24.6974 30.1035 25.4004 29.4004C26.1035 28.6974 26.4989 27.7442 26.5 26.75V16.75C26.5 16.4185 26.3683 16.1005 26.1339 15.8661C25.8995 15.6317 25.5815 15.5 25.25 15.5C24.9185 15.5 24.6005 15.6317 24.3661 15.8661C24.1317 16.1005 24 16.4185 24 16.75V26.75C23.9997 27.0814 23.8679 27.3992 23.6336 27.6336C23.3992 27.8679 23.0814 27.9997 22.75 28H4C3.66857 27.9997 3.3508 27.8679 3.11645 27.6336C2.88209 27.3992 2.7503 27.0814 2.75 26.75V9.25C2.7503 8.91857 2.88209 8.6008 3.11645 8.36645C3.3508 8.13209 3.66857 8.0003 4 8H15.25C15.5815 8 15.8995 7.8683 16.1339 7.63388C16.3683 7.39946 16.5 7.08152 16.5 6.75C16.5 6.41848 16.3683 6.10054 16.1339 5.86612C15.8995 5.6317 15.5815 5.5 15.25 5.5H4C3.00577 5.50109 2.05258 5.89653 1.34956 6.59956C0.646531 7.30258 0.251092 8.25577 0.25 9.25V26.75C0.251092 27.7442 0.646531 28.6974 1.34956 29.4004C2.05258 30.1035 3.00577 30.4989 4 30.5Z"-->
                                                    <!--        fill="rgb(16, 213, 150)" />-->
                                                    <!--    <path-->
                                                    <!--        d="M25.25 0.5C24.0139 0.5 22.8055 0.866556 21.7777 1.55331C20.7499 2.24007 19.9488 3.21619 19.4758 4.35823C19.0027 5.50027 18.8789 6.75693 19.1201 7.96931C19.3613 9.1817 19.9565 10.2953 20.8306 11.1694C21.7047 12.0435 22.8183 12.6388 24.0307 12.8799C25.2431 13.1211 26.4997 12.9973 27.6418 12.5242C28.7838 12.0512 29.7599 11.2501 30.4467 10.2223C31.1334 9.19451 31.5 7.98613 31.5 6.75C31.498 5.093 30.8389 3.50442 29.6673 2.33274C28.4956 1.16106 26.907 0.501952 25.25 0.5ZM25.25 10.5C24.5083 10.5 23.7833 10.2801 23.1666 9.86801C22.5499 9.45596 22.0693 8.87029 21.7855 8.18506C21.5016 7.49984 21.4274 6.74584 21.5721 6.01841C21.7167 5.29098 22.0739 4.6228 22.5983 4.09835C23.1228 3.5739 23.791 3.21675 24.5184 3.07206C25.2458 2.92736 25.9998 3.00162 26.6851 3.28545C27.3703 3.56928 27.9559 4.04993 28.368 4.66661C28.7801 5.2833 29 6.00832 29 6.75C28.9989 7.74423 28.6035 8.69742 27.9004 9.40044C27.1974 10.1035 26.2442 10.4989 25.25 10.5Z"-->
                                                    <!--        fill="rgb(16, 213, 150)" />-->
                                                    <!--    <path-->
                                                    <!--        d="M6.5 13H12.75C13.0815 13 13.3995 12.8683 13.6339 12.6339C13.8683 12.3995 14 12.0815 14 11.75C14 11.4185 13.8683 11.1005 13.6339 10.8661C13.3995 10.6317 13.0815 10.5 12.75 10.5H6.5C6.16848 10.5 5.85054 10.6317 5.61612 10.8661C5.3817 11.1005 5.25 11.4185 5.25 11.75C5.25 12.0815 5.3817 12.3995 5.61612 12.6339C5.85054 12.8683 6.16848 13 6.5 13Z"-->
                                                    <!--        fill="rgb(16, 213, 150)" />-->
                                                    <!--    <path-->
                                                    <!--        d="M5.25 16.75C5.25 17.0815 5.3817 17.3995 5.61612 17.6339C5.85054 17.8683 6.16848 18 6.5 18H17.75C18.0815 18 18.3995 17.8683 18.6339 17.6339C18.8683 17.3995 19 17.0815 19 16.75C19 16.4185 18.8683 16.1005 18.6339 15.8661C18.3995 15.6317 18.0815 15.5 17.75 15.5H6.5C6.16848 15.5 5.85054 15.6317 5.61612 15.8661C5.3817 16.1005 5.25 16.4185 5.25 16.75Z"-->
                                                    <!--        fill="rgb(16, 213, 150)" />-->
                                                    <!--</svg>-->
                                                </span>
                                                <h3 style="color: #10d596!important" class="mb-0 text-black" style="text-align: center;"><span
                                                        class="counter ml-0"><?= $AdminTask->Get_User_Payment_History(
                                                                                    $Auth->email,
                                                                                    $Auth->admin_role
                                                                                ) != false
                                                                                    ? count(
                                                                                        $AdminTask->Get_User_Payment_History(
                                                                                            $Auth->email,
                                                                                            $Auth->admin_role
                                                                                        )
                                                                                    )
                                                                                    : 0 ?></span></h3>
                                                <p class="pull-left">
                                                    Bill Payment<br>
                                                    <span style="color: #000; font-weight: bolder; font-size: 20px;">
                                                        <?= $TopupController->Get_Trans_Category(0, $Auth->email, $Auth->admin_role) !=
                                                            false
                                                            ? count(
                                                                $TopupController->Get_Trans_Category(
                                                                    0,
                                                                    $Auth->email,
                                                                    $Auth->admin_role
                                                                )
                                                            )
                                                            : 0 ?>
                                                    </span>
                                                </p>
                                                <p class="pull-right">
                                                    Airtimes<br>
                                                    <span style="color: #000; font-weight: bolder; font-size: 20px;"><?= $TopupController->Get_Trans_Category(
                                                                                                                            1,
                                                                                                                            $Auth->email,
                                                                                                                            $Auth->admin_role
                                                                                                                        ) != false
                                                                                                                            ? count(
                                                                                                                                $TopupController->Get_Trans_Category(
                                                                                                                                    1,
                                                                                                                                    $Auth->email,
                                                                                                                                    $Auth->admin_role
                                                                                                                                )
                                                                                                                            )
                                                                                                                            : 0 ?>
                                                    </span>
                                                </p>


                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>




                    <?php } else { ?>




                        <div class="col-xl-4 col-xxl-4 col-lg-6 col-md-6 col-sm-6">
                            <a href="my-trans-history">
                                <div class="widget-stat card">
                                    <div class="card-body p-4">
                                        <div class="media ai-icon">

                                            <div class="media-body">

                                                <h4 class="mb-0"
                                                    style="font-weight: bold; font-size: 22px; font-family: time roman;">
                                                    Transactions History</h4>
                                                <p><small>Total of Your Transactions History:</small></p>
                                                <span class="mr-3 bgl-primary text-primary pull-left">
                                                    <!-- <i class="ti-user"></i> -->
                                                    <!--<svg width="32" height="31" viewBox="0 0 32 31" fill="none"-->
                                                    <!--    xmlns="http://www.w3.org/2000/svg">-->
                                                    <!--    <path-->
                                                    <!--        d="M4 30.5H22.75C23.7442 30.4989 24.6974 30.1035 25.4004 29.4004C26.1035 28.6974 26.4989 27.7442 26.5 26.75V16.75C26.5 16.4185 26.3683 16.1005 26.1339 15.8661C25.8995 15.6317 25.5815 15.5 25.25 15.5C24.9185 15.5 24.6005 15.6317 24.3661 15.8661C24.1317 16.1005 24 16.4185 24 16.75V26.75C23.9997 27.0814 23.8679 27.3992 23.6336 27.6336C23.3992 27.8679 23.0814 27.9997 22.75 28H4C3.66857 27.9997 3.3508 27.8679 3.11645 27.6336C2.88209 27.3992 2.7503 27.0814 2.75 26.75V9.25C2.7503 8.91857 2.88209 8.6008 3.11645 8.36645C3.3508 8.13209 3.66857 8.0003 4 8H15.25C15.5815 8 15.8995 7.8683 16.1339 7.63388C16.3683 7.39946 16.5 7.08152 16.5 6.75C16.5 6.41848 16.3683 6.10054 16.1339 5.86612C15.8995 5.6317 15.5815 5.5 15.25 5.5H4C3.00577 5.50109 2.05258 5.89653 1.34956 6.59956C0.646531 7.30258 0.251092 8.25577 0.25 9.25V26.75C0.251092 27.7442 0.646531 28.6974 1.34956 29.4004C2.05258 30.1035 3.00577 30.4989 4 30.5Z"-->
                                                    <!--        fill="#003366" />-->
                                                    <!--    <path-->
                                                    <!--        d="M25.25 0.5C24.0139 0.5 22.8055 0.866556 21.7777 1.55331C20.7499 2.24007 19.9488 3.21619 19.4758 4.35823C19.0027 5.50027 18.8789 6.75693 19.1201 7.96931C19.3613 9.1817 19.9565 10.2953 20.8306 11.1694C21.7047 12.0435 22.8183 12.6388 24.0307 12.8799C25.2431 13.1211 26.4997 12.9973 27.6418 12.5242C28.7838 12.0512 29.7599 11.2501 30.4467 10.2223C31.1334 9.19451 31.5 7.98613 31.5 6.75C31.498 5.093 30.8389 3.50442 29.6673 2.33274C28.4956 1.16106 26.907 0.501952 25.25 0.5ZM25.25 10.5C24.5083 10.5 23.7833 10.2801 23.1666 9.86801C22.5499 9.45596 22.0693 8.87029 21.7855 8.18506C21.5016 7.49984 21.4274 6.74584 21.5721 6.01841C21.7167 5.29098 22.0739 4.6228 22.5983 4.09835C23.1228 3.5739 23.791 3.21675 24.5184 3.07206C25.2458 2.92736 25.9998 3.00162 26.6851 3.28545C27.3703 3.56928 27.9559 4.04993 28.368 4.66661C28.7801 5.2833 29 6.00832 29 6.75C28.9989 7.74423 28.6035 8.69742 27.9004 9.40044C27.1974 10.1035 26.2442 10.4989 25.25 10.5Z"-->
                                                    <!--        fill="#003366" />-->
                                                    <!--    <path-->
                                                    <!--        d="M6.5 13H12.75C13.0815 13 13.3995 12.8683 13.6339 12.6339C13.8683 12.3995 14 12.0815 14 11.75C14 11.4185 13.8683 11.1005 13.6339 10.8661C13.3995 10.6317 13.0815 10.5 12.75 10.5H6.5C6.16848 10.5 5.85054 10.6317 5.61612 10.8661C5.3817 11.1005 5.25 11.4185 5.25 11.75C5.25 12.0815 5.3817 12.3995 5.61612 12.6339C5.85054 12.8683 6.16848 13 6.5 13Z"-->
                                                    <!--        fill="#003366" />-->
                                                    <!--    <path-->
                                                    <!--        d="M5.25 16.75C5.25 17.0815 5.3817 17.3995 5.61612 17.6339C5.85054 17.8683 6.16848 18 6.5 18H17.75C18.0815 18 18.3995 17.8683 18.6339 17.6339C18.8683 17.3995 19 17.0815 19 16.75C19 16.4185 18.8683 16.1005 18.6339 15.8661C18.3995 15.6317 18.0815 15.5 17.75 15.5H6.5C6.16848 15.5 5.85054 15.6317 5.61612 15.8661C5.3817 16.1005 5.25 16.4185 5.25 16.75Z"-->
                                                    <!--        fill="#003366" />-->
                                                    <!--</svg>-->
                                                    <svg viewBox="129.978 110.003 31.25 30" width="32" height="31" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M 133.728 140.003 L 152.478 140.003 C 153.472 140.002 154.425 139.606 155.128 138.903 C 155.831 138.2 156.226 137.247 156.228 136.253 L 156.228 126.253 C 156.228 125.921 156.096 125.603 155.861 125.369 C 155.627 125.135 155.309 125.003 154.978 125.003 C 154.646 125.003 154.328 125.135 154.094 125.369 C 153.859 125.603 153.728 125.921 153.728 126.253 L 153.728 136.253 C 153.727 136.584 153.595 136.902 153.361 137.137 C 153.127 137.371 152.809 137.503 152.478 137.503 L 133.728 137.503 C 133.396 137.503 133.078 137.371 132.844 137.137 C 132.61 136.902 132.478 136.584 132.478 136.253 L 132.478 118.753 C 132.478 118.421 132.61 118.104 132.844 117.869 C 133.078 117.635 133.396 117.503 133.728 117.503 L 144.978 117.503 C 145.309 117.503 145.627 117.371 145.861 117.137 C 146.096 116.902 146.228 116.584 146.228 116.253 C 146.228 115.921 146.096 115.603 145.861 115.369 C 145.627 115.135 145.309 115.003 144.978 115.003 L 133.728 115.003 C 132.733 115.004 131.78 115.399 131.077 116.102 C 130.374 116.806 129.979 117.759 129.978 118.753 L 129.978 136.253 C 129.979 137.247 130.374 138.2 131.077 138.903 C 131.78 139.606 132.733 140.002 133.728 140.003 Z" style="fill: rgb(16, 213, 150);" />
                                                        <path d="M 154.978 110.003 C 153.741 110.003 152.533 110.369 151.505 111.056 C 150.477 111.743 149.676 112.719 149.203 113.861 C 148.73 115.003 148.606 116.26 148.848 117.472 C 149.089 118.685 149.684 119.798 150.558 120.672 C 151.432 121.546 152.546 122.142 153.758 122.383 C 154.971 122.624 156.227 122.5 157.369 122.027 C 158.511 121.554 159.487 120.753 160.174 119.725 C 160.861 118.697 161.228 117.489 161.228 116.253 C 161.226 114.596 160.566 113.007 159.395 111.836 C 158.223 110.664 156.635 110.005 154.978 110.003 Z M 154.978 120.003 C 154.236 120.003 153.511 119.783 152.894 119.371 C 152.277 118.959 151.797 118.373 151.513 117.688 C 151.229 117.003 151.155 116.249 151.3 115.521 C 151.444 114.794 151.801 114.126 152.326 113.601 C 152.85 113.077 153.519 112.72 154.246 112.575 C 154.973 112.43 155.727 112.505 156.413 112.788 C 157.098 113.072 157.683 113.553 158.096 114.17 C 158.508 114.786 158.728 115.511 158.728 116.253 C 158.726 117.247 158.331 118.2 157.628 118.903 C 156.925 119.606 155.972 120.002 154.978 120.003 Z" style="fill: rgb(16, 213, 150);" />
                                                        <path d="M 136.228 122.503 L 142.478 122.503 C 142.809 122.503 143.127 122.371 143.361 122.137 C 143.596 121.902 143.728 121.584 143.728 121.253 C 143.728 120.921 143.596 120.603 143.361 120.369 C 143.127 120.135 142.809 120.003 142.478 120.003 L 136.228 120.003 C 135.896 120.003 135.578 120.135 135.344 120.369 C 135.109 120.603 134.978 120.921 134.978 121.253 C 134.978 121.584 135.109 121.902 135.344 122.137 C 135.578 122.371 135.896 122.503 136.228 122.503 Z" style="fill: rgb(16, 213, 150);" />
                                                        <path d="M 134.978 126.253 C 134.978 126.584 135.109 126.902 135.344 127.137 C 135.578 127.371 135.896 127.503 136.228 127.503 L 147.478 127.503 C 147.809 127.503 148.127 127.371 148.361 127.137 C 148.596 126.902 148.728 126.584 148.728 126.253 C 148.728 125.921 148.596 125.603 148.361 125.369 C 148.127 125.135 147.809 125.003 147.478 125.003 L 136.228 125.003 C 135.896 125.003 135.578 125.135 135.344 125.369 C 135.109 125.603 134.978 125.921 134.978 126.253 Z" style="fill: rgb(16, 213, 150);" />
                                                    </svg>
                                                </span>
                                                <h3 class="mb-0 text-black" style="text-align: center;"><span
                                                        class="counter ml-0"><?= $AdminTask->Get_User_Payment_History(
                                                                                    $Auth->email,
                                                                                    $Auth->admin_role
                                                                                ) != false
                                                                                    ? count(
                                                                                        $AdminTask->Get_User_Payment_History(
                                                                                            $Auth->email,
                                                                                            $Auth->admin_role
                                                                                        )
                                                                                    )
                                                                                    : 0 ?></span></h3>
                                                <p style="color: #10d596" class="pull-left">
                                                    Bill Payment<br>
                                                    <span style="color: #000; font-weight: bolder; font-size: 20px;">
                                                        <?= $TopupController->Get_Trans_Category(0, $Auth->email, $Auth->admin_role) !=
                                                            false
                                                            ? count(
                                                                $TopupController->Get_Trans_Category(
                                                                    0,
                                                                    $Auth->email,
                                                                    $Auth->admin_role
                                                                )
                                                            )
                                                            : 0 ?>
                                                    </span>
                                                </p>
                                                <p style="color: #10d596" class="pull-right"">
                                                Airtimes<br>
                                                <span style=" color: #000; font-weight: bolder; font-size: 20px;"><?= $TopupController->Get_Trans_Category(
                                                                                                                        1,
                                                                                                                        $Auth->email,
                                                                                                                        $Auth->admin_role
                                                                                                                    ) != false
                                                                                                                        ? count(
                                                                                                                            $TopupController->Get_Trans_Category(
                                                                                                                                1,
                                                                                                                                $Auth->email,
                                                                                                                                $Auth->admin_role
                                                                                                                            )
                                                                                                                        )
                                                                                                                        : 0 ?>
                                                    </span>
                                                </p>


                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>




                    <?php } ?>
                    <div class="col-xl-6 col-xxl-6 col-lg-12 col-md-12">
                        <div class="card">
                            <div class="card-header border-0 pb-0 d-sm-flex d-block">
                                <div>
                                    <h4 class="card-title mb-1">Transaction(s)</h4>
                                    <small class="mb-0">Recent Transaction Summary</small>
                                </div>
                                <div class="card-action card-tabs mt-3 mt-sm-0">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-toggle="tab" href="#user" role="tab">
                                                Monthly
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#bounce" role="tab">
                                                Weekly
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#session-duration" role="tab">
                                                Today
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body orders-summary">
                                <div class="d-flex order-manage p-3 align-items-center mb-4">
                                    <a style="background: #10d596; border-color: #10d596" href="javascript:void(0);" class="btn fs-22 py-1 btn-danger px-4 mr-3"><?= $AdminTask->Get_User_Payment_History(
                                                                                                                                                                        $Auth->email,
                                                                                                                                                                        $Auth->admin_role
                                                                                                                                                                    ) != false
                                                                                                                                                                        ? ($Trans = count(
                                                                                                                                                                            $AdminTask->Get_User_Payment_History(
                                                                                                                                                                                $Auth->email,
                                                                                                                                                                                $Auth->admin_role
                                                                                                                                                                            )
                                                                                                                                                                        ))
                                                                                                                                                                        : ($Trans = 0) ?></a>
                                    <h4 style="color: #10d596 !important" class="mb-0">New Orders <i class="fa fa-circle text-success ml-1 fs-15"></i>
                                    </h4>
                                    <a href="javascript:void(0);" style="color: #10d596 !important" class="ml-auto text-primary font-w500">Manage orders
                                        <i class="ti-angle-right ml-1"></i></a>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4 mb-4">
                                        <div class="border px-3 py-3 rounded-xl">
                                            <h2 class="fs-32 font-w600 counter">
                                                <?= $AdminTask->Get_All_Trans_Status_Records(
                                                    1,
                                                    $Auth->email,
                                                    $Auth->admin_role
                                                ) != false
                                                    ? ($S_trans = count(
                                                        $AdminTask->Get_All_Trans_Status_Records(
                                                            1,
                                                            $Auth->email,
                                                            $Auth->admin_role
                                                        )
                                                    ))
                                                    : ($S_trans = 0) ?>
                                            </h2>
                                            <p class="fs-16 mb-0">Successful</p>
                                        </div>
                                    </div>
                                    <div class="col-sm-4 mb-4">
                                        <div class="border px-3 py-3 rounded-xl">
                                            <h2 class="fs-32 font-w600 counter">
                                                <?= $AdminTask->Get_All_Trans_Status_Records(
                                                    2,
                                                    $Auth->email,
                                                    $Auth->admin_role
                                                ) != false
                                                    ? ($F_trans = count(
                                                        $AdminTask->Get_All_Trans_Status_Records(
                                                            2,
                                                            $Auth->email,
                                                            $Auth->admin_role
                                                        )
                                                    ))
                                                    : ($F_trans = 0) ?>
                                            </h2>
                                            <p class="fs-16 mb-0">Failed</p>
                                        </div>
                                    </div>
                                    <div class="col-sm-4 mb-4">
                                        <div class="border px-3 py-3 rounded-xl">
                                            <h2 class="fs-32 font-w600 counter"><?= $AdminTask->Get_All_Trans_Status_Records(
                                                                                    0,
                                                                                    $Auth->email,
                                                                                    $Auth->admin_role
                                                                                ) != false
                                                                                    ? ($P_trans = count(
                                                                                        $AdminTask->Get_All_Trans_Status_Records(
                                                                                            0,
                                                                                            $Auth->email,
                                                                                            $Auth->admin_role
                                                                                        )
                                                                                    ))
                                                                                    : ($P_trans = 0) ?></h2>
                                            <p class="fs-16 mb-0">Pending</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="widget-timeline-icon">
                                    <div class="row align-items-center mx-0">
                                        <div
                                            class="col-xl-3 col-lg-4 col-xxl-4 col-sm-4 px-0 my-2 text-center text-sm-left">
                                            <span class="donut"
                                                data-peity='{ "fill": ["rgb(62, 73, 84)", "rgba(255, 109, 76, 1)","rgba(43, 193, 85, 1)"]}'>0,<?= $F_trans ?>,<?= $S_trans ?></span>

                                        </div>
                                        <div class="col-xl-9 col-lg-8 col-xxl-8 col-sm-8 px-0">
                                            <div class="d-flex align-items-center mb-3">
                                                <p class="mb-0 fs-14 mr-2 col-4 px-0">Sucessful (<?= $S_trans > 0
                                                                                                        ? floor(($S_trans / $Trans) * 100)
                                                                                                        : 0 ?>%)</p>
                                                <div class="progress mb-0" style="height:8px; width:100%;">
                                                    <div class="progress-bar bg-success progress-animated" style="width:<?= $S_trans >
                                                                                                                            0
                                                                                                                            ? floor(($S_trans / $Trans) * 100)
                                                                                                                            : 0 ?>%; height:8px;" role="progressbar">
                                                        <span class="sr-only"><?= $S_trans > 0
                                                                                    ? floor(($S_trans / $Trans) * 100)
                                                                                    : 0 ?>%</span>
                                                    </div>
                                                </div>
                                                <span class="pull-right ml-auto col-1 px-0 text-right"><?= number_format(
                                                                                                            $S_trans
                                                                                                        ) ?></span>
                                            </div>
                                            <div class="d-flex align-items-center  mb-3">
                                                <p class="mb-0 fs-14 mr-2 col-4 px-0">Pending (<?= $P_trans > 0
                                                                                                    ? floor(($P_trans / $Trans) * 100)
                                                                                                    : 0 ?>%)</p>
                                                <div class="progress mb-0" style="height:8px; width:100%;">
                                                    <div class="progress-bar bg-dark progress-animated" style="width:<?= $P_trans >
                                                                                                                            0
                                                                                                                            ? floor(($P_trans / $Trans) * 100)
                                                                                                                            : 0 ?>%; height:8px;" role="progressbar">
                                                        <span class="sr-only"><?= $P_trans > 0
                                                                                    ? floor(($P_trans / $Trans) * 100)
                                                                                    : 0 ?>%</span>
                                                    </div>
                                                </div>
                                                <span class="pull-right ml-auto col-1 px-0 text-right"><?= number_format(
                                                                                                            $P_trans
                                                                                                        ) ?></span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <p class="mb-0 fs-14 mr-2 col-4 px-0">Failed (<?= $F_trans > 0
                                                                                                    ? floor(($F_trans / $Trans) * 100)
                                                                                                    : 0 ?>%)</p>
                                                <div class="progress mb-0" style="height:8px; width:100%;">
                                                    <div class="progress-bar bg-warning progress-animated" style="width:<?= $F_trans >
                                                                                                                            0
                                                                                                                            ? floor(($F_trans / $Trans) * 100)
                                                                                                                            : 0 ?>%; height:8px;" role="progressbar">
                                                        <span class="sr-only"><?= $F_trans > 0
                                                                                    ? floor(($F_trans / $Trans) * 100)
                                                                                    : 0 ?>%</span>
                                                    </div>
                                                </div>
                                                <span class="pull-right ml-auto col-1 px-0 text-right"><?= number_format(
                                                                                                            $F_trans
                                                                                                        ) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 col-xxl-6 col-lg-12 col-md-12">




                        <?php if (
                            in_array(1, explode(',', $Auth->admin_role)) ||
                            in_array(2, explode(',', $Auth->admin_role))
                        ) { ?>



                            <div class="card">
                                <div class="card-header border-0 pb-0 d-sm-flex d-block">
                                    <div>
                                        <h4 class="card-title mb-1">New Client(s)</h4>
                                        <small class="mb-0">List of New Registered User(s)</small>
                                    </div>
                                    <div class="card-action card-tabs mt-3 mt-sm-0">
                                        <ul class="nav nav-tabs" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" data-toggle="tab" href="#monthly" role="tab">
                                                    Monthly
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-toggle="tab" href="#weekly" role="tab">
                                                    Weekly
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-toggle="tab" href="#today" role="tab">
                                                    Today
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body tab-content">
                                    <div class="tab-pane fade show active" id="monthly">

                                        <?php if ($Users = $AdminTask->Get_All_Users()) {
                                            foreach ($Users as $user) { ?>

                                                <div class="media items-list-2">
                                                    <img class="img-fluid rounded mr-3" width="85" src="./images/avatar/1.png"
                                                        alt="DexignZone">
                                                    <div class="media-body col-6 px-0">
                                                        <h5 class="mt-0 mb-1 text-black"><?= strtoupper(
                                                                                                $user->sname . ' ' . $user->oname
                                                                                            ) ?></h5>
                                                        <small class="text-primary font-w500 mb-3"><?= $user->email ?></small>
                                                        <span class="text-secondary mr-2 fo"></span>
                                                        <ul class="fs-14 list-inline">
                                                            <li class="mr-3"><?= $user->phone ?></li>

                                                        </ul>
                                                    </div>
                                                    <div
                                                        class="media-footer align-self-center ml-auto d-block align-items-center d-sm-flex">
                                                        <h4 class="mb-0 font-w600 text-secondary"><?= $site_settings->humanTiming(
                                                                                                        strtotime($user->date_join)
                                                                                                    ) ?> ago</h4>
                                                        <div class="dropdown ml-3 ">
                                                            <button type="button" class="btn btn-secondary sharp tp-btn-light "
                                                                data-toggle="dropdown">
                                                                <svg width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                                                    <g stroke="none" stroke-width="1" fill="none"
                                                                        fill-rule="evenodd">
                                                                        <rect x="0" y="0" width="24" height="24"></rect>
                                                                        <circle fill="#000000" cx="5" cy="12" r="2"></circle>
                                                                        <circle fill="#000000" cx="12" cy="12" r="2"></circle>
                                                                        <circle fill="#000000" cx="19" cy="12" r="2"></circle>
                                                                    </g>
                                                                </svg>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                <a class="dropdown-item" href="#">Edit</a>
                                                                <a class="dropdown-item" href="#">Delete</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <br>


                                        <?php }
                                        } ?>

                                    </div>




                                    <div class="tab-pane fade" id="weekly">

                                        <?php
                                        $count = 0;
                                        if ($Users = $AdminTask->Get_All_Users()) {
                                            foreach ($Users as $user) {

                                                $count++;

                                                if ($count == 4) {
                                                    break;
                                                }
                                        ?>

                                                <div class="media items-list-2">
                                                    <img class="img-fluid rounded mr-3" width="85" src="./images/avatar/1.png"
                                                        alt="DexignZone">
                                                    <div class="media-body col-6 px-0">
                                                        <h5 class="mt-0 mb-1 text-black"><?= strtoupper(
                                                                                                $user->sname . ' ' . $user->oname
                                                                                            ) ?></h5>
                                                        <small class="text-primary font-w500 mb-3"><?= $user->email ?></small>
                                                        <span class="text-secondary mr-2 fo"></span>
                                                        <ul class="fs-14 list-inline">
                                                            <li class="mr-3"><?= $user->phone ?></li>

                                                        </ul>
                                                    </div>
                                                    <div
                                                        class="media-footer align-self-center ml-auto d-block align-items-center d-sm-flex">
                                                        <h4 class="mb-0 font-w600 text-secondary"><?= $site_settings->humanTiming(
                                                                                                        strtotime($user->date_join)
                                                                                                    ) ?> ago</h4>
                                                        <div class="dropdown ml-3 ">
                                                            <button type="button" class="btn btn-secondary sharp tp-btn-light "
                                                                data-toggle="dropdown">
                                                                <svg width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                                                    <g stroke="none" stroke-width="1" fill="none"
                                                                        fill-rule="evenodd">
                                                                        <rect x="0" y="0" width="24" height="24"></rect>
                                                                        <circle fill="#000000" cx="5" cy="12" r="2"></circle>
                                                                        <circle fill="#000000" cx="12" cy="12" r="2"></circle>
                                                                        <circle fill="#000000" cx="19" cy="12" r="2"></circle>
                                                                    </g>
                                                                </svg>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                <a class="dropdown-item" href="#">Edit</a>
                                                                <a class="dropdown-item" href="#">Delete</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <br>


                                        <?php
                                            }
                                        }
                                        ?>
                                    </div>






                                    <div class="tab-pane fade" id="today">

                                        <?php
                                        $count = 0;
                                        if ($Users = $AdminTask->Get_All_Users()) {
                                            foreach ($Users as $user) {

                                                $count++;

                                                if ($count == 4) {
                                                    break;
                                                }
                                        ?>

                                                <div class="media items-list-2">
                                                    <img class="img-fluid rounded mr-3" width="85" src="./images/avatar/1.png"
                                                        alt="DexignZone">
                                                    <div class="media-body col-6 px-0">
                                                        <h5 class="mt-0 mb-1 text-black"><?= strtoupper(
                                                                                                $user->sname . ' ' . $user->oname
                                                                                            ) ?></h5>
                                                        <small class="text-primary font-w500 mb-3"><?= $user->email ?></small>
                                                        <span class="text-secondary mr-2 fo"></span>
                                                        <ul class="fs-14 list-inline">
                                                            <li class="mr-3"><?= $user->phone ?></li>

                                                        </ul>
                                                    </div>
                                                    <div
                                                        class="media-footer align-self-center ml-auto d-block align-items-center d-sm-flex">
                                                        <h4 class="mb-0 font-w600 text-secondary"><?= $site_settings->humanTiming(
                                                                                                        strtotime($user->date_join)
                                                                                                    ) ?> ago</h4>
                                                        <div class="dropdown ml-3 ">
                                                            <button type="button" class="btn btn-secondary sharp tp-btn-light "
                                                                data-toggle="dropdown">
                                                                <svg width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                                                    <g stroke="none" stroke-width="1" fill="none"
                                                                        fill-rule="evenodd">
                                                                        <rect x="0" y="0" width="24" height="24"></rect>
                                                                        <circle fill="#000000" cx="5" cy="12" r="2"></circle>
                                                                        <circle fill="#000000" cx="12" cy="12" r="2"></circle>
                                                                        <circle fill="#000000" cx="19" cy="12" r="2"></circle>
                                                                    </g>
                                                                </svg>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                <a class="dropdown-item" href="#">Edit</a>
                                                                <a class="dropdown-item" href="#">Delete</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <br>


                                        <?php
                                            }
                                        }
                                        ?>


                                    </div>
                                </div>
                                <div class="card-footer border-0 pt-0 text-center">
                                    <a href="manage-user" class="btn-link">View more <i
                                            class="fa fa-angle-down ml-2 scale-2"></i></a>
                                </div>
                            </div>

                        <?php } else { ?>




                            <div class="card">
                                <div class="card-header border-0 pb-0 d-sm-flex d-block">
                                    <div>
                                        <h4 class="card-title mb-1">Your Transaction(s)</h4>
                                        <small class="mb-0">List of your transaction activities </small>
                                    </div>

                                </div>
                                <div class="card-body tab-content">
                                    <div class="tab-pane fade show active" id="monthly">
                                        <?php if (
                                            $MyTrans = $AdminTask->Get_User_Payment_History(
                                                $Auth->email,
                                                $Auth->admin_role
                                            )
                                        ) {
                                            $count = 0;
                                            foreach ($MyTrans as $MyTran) {

                                                $count++;
                                                if ($count == 6) {
                                                    break;
                                                }
                                        ?>


                                                <div class="media items-list-2">
                                                    <img class="img-fluid rounded mr-3" width="85" src="./images/dish/pic1.jpg"
                                                        alt="DexignZone">
                                                    <div class="media-body col-6 px-0">
                                                        <h5 class="mt-0 mb-1 text-black">#<?= $MyTran->request_id ?></h5>

                                                        <?= $MyTran->status == 1
                                                            ? '<a href="#"> <small class="text-success font-w500 mb-3">' .
                                                            $MyTran->response_description .
                                                            '</small></a>'
                                                            : '<a href="#"> <small class="text-danger font-w500 mb-3">' .
                                                            $MyTran->response_description .
                                                            ' </small> </a>' ?>

                                                        <span class="text-secondary mr-2 fo"></span>
                                                        <ul class="fs-14 list-inline">
                                                            <li class="mr-3">Date : <?= $MyTran->transaction_date ?></li>

                                                        </ul>
                                                    </div>
                                                    <div
                                                        class="media-footer align-self-center ml-auto d-block align-items-center d-sm-flex">
                                                        <h4 class="mb-0 font-w600 text-secondary"></h4>
                                                        <div class="dropdown ml-3 ">
                                                            <button type="button" class="btn btn-secondary sharp tp-btn-light "
                                                                data-toggle="dropdown">
                                                                <svg width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                                                    <g stroke="none" stroke-width="1" fill="none"
                                                                        fill-rule="evenodd">
                                                                        <rect x="0" y="0" width="24" height="24"></rect>
                                                                        <circle fill="#000000" cx="5" cy="12" r="2"></circle>
                                                                        <circle fill="#000000" cx="12" cy="12" r="2"></circle>
                                                                        <circle fill="#000000" cx="19" cy="12" r="2"></circle>
                                                                    </g>
                                                                </svg>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                <a class="dropdown-item" href="#">Edit</a>
                                                                <a class="dropdown-item" href="#">Delete</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>


                                        <?php
                                            }
                                        } ?>

                                    </div>





                                <?php } ?>




                                </div>


                            </div>
                    </div>
                </div>
                <!--**********************************
            Content body end
        ***********************************-->


            </div>

            <?php require_once 'layout/footer.inc.php'; ?>


            <!-- Modal -->
            <div class="modal fade" id="exampleModalpopover">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <form action="credit-wallet" method="POST" class="form-valide">
                            <div class="modal-header">
                                <h5 class="modal-title">Credit My Wallet </h5>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label class="mb-1"><strong>Enter Amount</strong></label>
                                    <div class="input-group transparent-append">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"> <i style="font-size: 18px;">₦</i> </span>
                                        </div>
                                        <input type="number" name="amount" required="" class="form-control">


                                    </div>
                                </div>


                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger light" data-dismiss="modal">Close</button>
                                <button name="make_payment" type="submit" class="btn btn-danger">Continue</button>
                            </div>
                        </form>
                    </div>
                </div>


            </div>
            
            <?php if ((empty($Auth->nin) && empty($Auth->bvn)) || empty($Auth->monnify_account_details)):?>
            <div class="modal fade" id="KYCModal">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">KYC Verification</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="basic-form">
                                <form id="kyc-form" method="post">
                                    <div class="alert alert-info">Enter either your <strong>NIN</strong> or <strong>BVN</strong> to generate account numbers.</div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label>BVN</label>
                                            <input type="text" class="form-control" placeholder="Enter BVN" id="bvn" 
                                                name="bvn" pattern="\d{11}" maxlength="11">
                                        </div>
                                        <div class="form-group col-md-12">
                                            <label>NIN</label>
                                            <input type="text" class="form-control" placeholder="Enter NIN" id="nin" 
                                                name="nin" pattern="\d{11}" maxlength="11">
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <span class="spinner-grow spinner-grow-sm text-light d-none" id="spinner" role="status" aria-hidden="true"></span>
                                        Save Changes
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>


            <?php require_once 'layout/footer-propt.inc.php'; ?>
            <?php if ($error != '') { ?>
                    <script>
                        //Swal.fire({
                          //  title: 'Account Error',
                        //    text: '<?php echo $error ?>',
                          //  icon: 'error',
                        //})
                    </script>
            <?php } ?>
            <?php if ((empty($Auth->nin) && empty($Auth->bvn)) || empty($Auth->monnify_account_details)):?>
            <script>
                $(document).ready(function () {
                    // $('#KYCModal').modal('show');
                });
            </script>
            <?php endif; ?>
</body>

</html>