<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE = 'Fund Your Wallet ';
$URL_NAME = 'dashboard/credit-wallet';
require_once '../inc/accessbility_controller.inc.php';
include '../inc/payment_api_code.php';
?>
<!DOCTYPE html>
<html lang="en">

<head><meta charset="utf-8">
    <?php require_once 'layout/header-propt.inc.php'; ?>

    <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?> </title>
    <style type="text/css">
    table {
        width: 100%
    }

    #table th,
    #table td {
        border: none;
        padding: 7px;
    }
    </style>
</head>

<body>

    <?php
//require_once 'layout/preloader.inc.php';
?>

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

            <?php include 'layout/minor-top-navbar.inc.php'; ?>

            <div class="container-fluid">
                <div class="row page-titles mx-0">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4 style="color: #003366; font-size: 20px"><?= $PAGE_TITLE ?></h4>

                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0)"><?= SITE_TITLE ?> </a></li>
                            <li class="breadcrumb-item active"><a href="javascript:void(0)"><?= $PAGE_TITLE ?></a></li>
                        </ol>
                    </div>
                </div>





                <div class="row ">
                    <div class="col-12">
                        <div class="card ">
                            <div class="card-body ">
                                <?php if (
                                    isset($_POST['make_payment']) &&
                                    is_numeric(
                                        strip_tags(trim($_POST['amount']))
                                    )
                                ) {
                                    $amount = htmlspecialchars(
                                        intval($_POST['amount'])
                                    );
                                    $trans_id = $WalletController->Generate_Trans_id();
                                    ?>
                                <div class="col-md-10 offset-md-1">
                                    <div class="card-header">
                                        <h5 class="card-title"> Transaction Summary</h5>
                                    </div>
                                    <div class="card-body mb-0">
                                        <div class="">
                                            <img src="<?= SITE_URL ?>easyfinder/dashboard/images/secured_by_paystack.png"
                                                class="img-fluid" style="">
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table" id="table">

                                                <tr>
                                                    <th>Amount </th>
                                                    <td>₦ <?= number_format(
                                                        $amount
                                                    ) ?>
                                                    <td>
                                                </tr>
                                                <tr>
                                                    <th>Charges Amount </th>
                                                    <td>₦ <?= $charges = number_format(
                                                        (2 / 100) * $amount
                                                    ) ?>
                                                    <td>
                                                </tr>
                                                <tr>
                                                    <th>Phone Number </th>
                                                    <td><?= $Auth->phone ?>
                                                    <td>
                                                </tr>

                                                <tr>
                                                    <th>Email </th>
                                                    <td><?= $Auth->email ?>
                                                    <td>
                                                </tr>

                                                <tr>
                                                    <th>Transaction ID </th>
                                                    <td><?= $trans_id ?>
                                                    <td>
                                                </tr>

                                            </table>
                                        </div>
                                        <hr style="">



                                        <form class="form-valide-with-icon" method="POST" action="">
                                            <input type="hidden" name="user_email" value="<?= $Auth->email ?>">
                                            <input type="hidden" name="amount" value="<?= $amount ?>">
                                            <input type="hidden" name="Trans_id" value="<?= $trans_id ?>">
                                            <input type="hidden" name="charges" value="<?= $charges ?>">



                                            <a href="<?= SITE_URL ?>easyfinder/dashboard"
                                                class="btn btn-danger light btn-sm pull-left">Cancel</a>
                                            <button class="btn btn-primary btn-sm pull-right" id="btn-submit"
                                                name="pay_now">Pay Now</button>

                                        </form>



                                    </div>
                                </div>



                                <?php
                                } elseif (isset($_GET['reference'])) {
                                    include '../inc/payment-verify-api.php'; ?>

                                <div id="response_status" style="text-align: center;">
                                    <?php if (count($SITE_ERRORS) > 0): ?>
                                    <?php foreach ($SITE_ERRORS as $error): ?>

                                    <p style="text-align: center;">
                                    <div class="alert alert-danger" style="text-align:center;"><strong>Error! </strong>
                                        <?= $error ?></div>
                                    </p>
                                    <a href="<?= SITE_URL ?>easyfinder/dashboard/credit-wallet" class="btn btn-primary">Re-try the
                                        Transaction</a>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if (count($SITE_SUCCESS) > 0): ?>
                                    <?php foreach ($SITE_SUCCESS as $good): ?>

                                    <p style="text-align: center;">
                                    <div class="alert alert-success" style="text-align:center;">
                                        <strong><?= $good ?></strong>
                                    </div>
                                    </p>
                                    <a href="<?= SITE_URL ?>easyfinder/dashboard" class="btn btn-danger">Dashboard</a>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <?php
                                } else {
                                     ?>








                                <div class="card-header">
                                    <h4 class="card-title">Fund Your Wallet</h4>
                                </div>
                                <div class="card-body">

                                    <form action="" method="POST" class="form-valide-with-icon">

                                        <div class="form-group">
                                            <label class="text-label">Enter Amount: </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"> ₦</span>
                                                </div>
                                                <input type="number" name="amount" required="" class="form-control"
                                                    autocomplete="off">
                                            </div>
                                        </div>

                                        <button class="btn btn-primary" name="make_payment"
                                            value="make_payment">Continue</button>



                                    </form>


                                </div>







                                <?php
                                } ?>



                            </div>
                        </div>

                    </div>








                </div>
            </div>
            <!--**********************************
            Content body end
        ***********************************-->

        </div>

        <?php
        require_once 'layout/footer.inc.php';
        require_once 'layout/footer-propt.inc.php';
        ?>





</body>

</html>