<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE = 'Fund Your Wallet ';
$URL_NAME = 'dashboard/credit-wallet';
require_once '../inc/accessbility_controller.inc.php';
include '../inc/payment_api_code.php';
// ── Monnify: Generate Reserved Account ────────────────────────────────────────
$monnify_msg = null;
$monnify_err = null;
if (isset($_POST['generate_monnify'])) {
    if (!empty(trim($_POST['bvn'] ?? ''))) {
        $bvn_clean = preg_replace('/\D/', '', trim($_POST['bvn']));
        if (strlen($bvn_clean) === 11) {
            $conn_tmp = mysqli_connect('localhost','adiliqgs_adildata','adildata2026','adiliqgs_adildata');
            if ($conn_tmp) {
                $es = mysqli_real_escape_string($conn_tmp, $Auth->email);
                $bvn_s = mysqli_real_escape_string($conn_tmp, $bvn_clean);
                mysqli_query($conn_tmp, "UPDATE users_tbl SET bvn='$bvn_s' WHERE email='$es'");
                mysqli_close($conn_tmp);
                $Auth->bvn = $bvn_clean;
            }
        }
    }
    // Re-fetch to pick up saved BVN
    $Auth = $UserAuth->GetUserId($Auth->email);
    $result = $UserAuth->createMonnifyAccount($Auth);
    if ($result['success']) {
        $monnify_msg = 'Monnify account created! Details: ' . $result['account_details'];
        $Auth = $UserAuth->GetUserId($Auth->email);
    } else {
        $monnify_err = 'Could not create Monnify account: ' . ($result['message'] ?? 'Unknown error');
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
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



                                </div><!-- end PayStack card-body -->
                                </div><!-- end PayStack card -->

                                <?php if (!empty($monnify_msg)): ?>
                                <div class="alert alert-success mt-3"><i class="fa fa-check-circle mr-2"></i><?= htmlspecialchars($monnify_msg) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($monnify_err)): ?>
                                <div class="alert alert-danger mt-3"><i class="fa fa-exclamation-circle mr-2"></i><?= htmlspecialchars($monnify_err) ?></div>
                                <?php endif; ?>

                                <!-- ══ MONNIFY RESERVED ACCOUNT ═══════════════════════════════════════ -->
                                <div class="card mt-3">
                                    <div class="card-header" style="background:#10d596;">
                                        <h4 class="card-title mb-0" style="color:#fff;">
                                            <i class="fa fa-university mr-2"></i>Fund via Bank Transfer (Monnify)
                                        </h4>
                                    </div>
                                    <div class="card-body">
                                    <?php
                                        $fresh_auth = $UserAuth->GetUserId($Auth->email);
                                        $mon_details = $fresh_auth->monnify_account_details ?? '';
                                    ?>
                                    <?php if (!empty($mon_details)): ?>
                                        <div class="alert alert-info mb-3">
                                            <h5 class="mb-2"><i class="fa fa-check-circle mr-1"></i> Your Dedicated Monnify Accounts</h5>
                                            <p class="mb-2 text-muted">Send any amount to the accounts below — your wallet is credited automatically.</p>
                                            <?php foreach (explode(', ', $mon_details) as $acct): ?>
                                            <div class="p-2 mb-2" style="background:#f8f9fa;border-radius:6px;font-weight:bold;">
                                                <?= htmlspecialchars(trim($acct)) ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="mb-3">Generate a permanent bank account number for instant wallet funding via Monnify.</p>
                                        <?php if (empty($fresh_auth->bvn) && empty($fresh_auth->nin)): ?>
                                        <div class="alert alert-warning"><i class="fa fa-exclamation-triangle mr-1"></i>
                                            Monnify requires your BVN (CBN compliance). Enter it below to activate.
                                        </div>
                                        <?php endif; ?>
                                        <form method="POST" action="">
                                            <?php if (empty($fresh_auth->bvn) && empty($fresh_auth->nin)): ?>
                                            <div class="form-group">
                                                <label><strong>Your BVN:</strong></label>
                                                <input type="text" name="bvn" class="form-control" maxlength="11"
                                                    placeholder="11-digit BVN" pattern="[0-9]{11}" required>
                                                <small class="text-muted">Kept private — used only for bank compliance.</small>
                                            </div>
                                            <?php endif; ?>
                                            <button type="submit" name="generate_monnify" value="1"
                                                class="btn btn-success"
                                                style="background:#10d596!important;border-color:#10d596!important;">
                                                <i class="fa fa-university mr-1"></i> Generate Monnify Account
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    </div>
                                </div>
                                <!-- ══ END MONNIFY ══════════════════════════════════════════════════════ -->


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