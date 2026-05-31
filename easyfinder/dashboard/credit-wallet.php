<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE = 'Fund Your Wallet ';
$URL_NAME = 'dashboard/credit-wallet';
require_once '../inc/accessbility_controller.inc.php';
include '../inc/payment_api_code.php';
// ── Monnify: Generate Reserved Account ────────────────────────────────────────
$monnify_msg     = null;
$monnify_err     = null;
$need_bvn_form   = false;

// Handle manual generate button — collect BVN first if not on record
if (isset($_POST['generate_monnify'])) {
    $Auth = $UserAuth->GetUserId($Auth->email);
    // Save BVN or NIN if submitted
    $conn_bvn = mysqli_connect('localhost','adiliqgs_adildata','adildata2026','adiliqgs_adildata');
    $es = mysqli_real_escape_string($conn_bvn, $Auth->email);
    if (!empty(trim($_POST['bvn'] ?? ''))) {
        $bvn_clean = preg_replace('/\D/', '', trim($_POST['bvn']));
        if (strlen($bvn_clean) === 11) {
            $bvs = mysqli_real_escape_string($conn_bvn, $bvn_clean);
            mysqli_query($conn_bvn, "UPDATE users_tbl SET bvn='$bvs' WHERE email='$es'");
            $Auth = $UserAuth->GetUserId($Auth->email);
        }
    } elseif (!empty(trim($_POST['nin'] ?? ''))) {
        $nin_clean = preg_replace('/\D/', '', trim($_POST['nin']));
        if (strlen($nin_clean) === 11) {
            $nis = mysqli_real_escape_string($conn_bvn, $nin_clean);
            mysqli_query($conn_bvn, "UPDATE users_tbl SET nin='$nis' WHERE email='$es'");
            $Auth = $UserAuth->GetUserId($Auth->email);
        }
    }
    mysqli_close($conn_bvn);
    // If still no BVN/NIN, show collection form
    if (empty($Auth->bvn) && empty($Auth->nin)) {
        $need_bvn_form = true;
    } else {
        $result = $UserAuth->createMonnifyAccount($Auth);
        if ($result['success']) {
            $monnify_msg = 'Monnify account ready! Details: ' . $result['account_details'];
            $Auth = $UserAuth->GetUserId($Auth->email);
        } else {
            $err_msg = strtolower($result['message'] ?? '');
            // Monnify rejected the BVN — ask user to correct it
            if (strpos($err_msg, 'bvn') !== false || strpos($err_msg, 'nin') !== false || strpos($err_msg, 'invalid') !== false) {
                $need_bvn_form = true;
                $monnify_err  = 'Your BVN/NIN was rejected by Monnify. Please re-enter your correct 11-digit BVN or NIN below.';
            } else {
                $monnify_err = 'Could not create Monnify account: ' . ($result['message'] ?? 'Unknown error');
            }
        }
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
                                            <?php foreach (explode(', ', $mon_details) as $acct): 
                                                $prt = explode(' - ', trim($acct));
                                            ?>
                                            <div class="p-2 mb-2" style="background:#f8f9fa;border-radius:6px;">
                                                <span style="color:#10d596;font-weight:bold;"><?= htmlspecialchars($prt[0] ?? '') ?></span>
                                                &nbsp;|&nbsp;
                                                <strong style="font-size:18px;"><?= htmlspecialchars($prt[1] ?? '') ?></strong>
                                                &nbsp;&mdash;&nbsp;
                                                <span class="text-muted"><?= htmlspecialchars($prt[2] ?? '') ?></span>
                                            </div>
                                            <?php endforeach; ?>
                                            <a href="verify-monnify" class="btn btn-outline-success btn-sm mt-2">
                                                <i class="fa fa-refresh mr-1"></i> Balance not updated? Verify Payment
                                            </a>
                                        </div>
                                    <?php elseif ($need_bvn_form): ?>
                                        <div class="alert alert-info mb-3">
                                            <i class="fa fa-id-card mr-1"></i>
                                            <strong>Identity Verification Required</strong><br>
                                            To generate your dedicated Monnify bank account (CBN compliance), please provide your <strong>BVN</strong> or your <strong>NIN</strong> — whichever you have.
                                        </div>
                                        <form method="POST" action="">
                                            <!-- Toggle Tabs -->
                                            <div class="mb-3">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-success active" id="tab-bvn"
                                                        onclick="switchTab('bvn')"
                                                        style="background:#10d596;border-color:#10d596;">
                                                        Use BVN
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success" id="tab-nin"
                                                        onclick="switchTab('nin')"
                                                        style="color:#10d596;border-color:#10d596;">
                                                        Use NIN instead
                                                    </button>
                                                </div>
                                            </div>
                                            <!-- BVN field -->
                                            <div id="field-bvn" class="form-group">
                                                <label><strong>BVN (11 digits)</strong></label>
                                                <input type="text" name="bvn" id="input-bvn" class="form-control" maxlength="11"
                                                    pattern="\d{11}" placeholder="Enter your 11-digit BVN"
                                                    style="max-width:280px;">
                                                <small class="form-text text-muted">
                                                    Dial <strong>*565*0#</strong> on your registered line to get your BVN.
                                                </small>
                                            </div>
                                            <!-- NIN field (hidden by default) -->
                                            <div id="field-nin" class="form-group" style="display:none;">
                                                <label><strong>NIN (11 digits)</strong></label>
                                                <input type="text" name="nin" id="input-nin" class="form-control" maxlength="11"
                                                    pattern="\d{11}" placeholder="Enter your 11-digit NIN"
                                                    style="max-width:280px;">
                                                <small class="form-text text-muted">
                                                    Dial <strong>*346#</strong> on your registered line to get your NIN.
                                                </small>
                                            </div>
                                            <button type="submit" name="generate_monnify" value="1"
                                                class="btn btn-success"
                                                style="background:#10d596!important;border-color:#10d596!important;">
                                                <i class="fa fa-university mr-1"></i> Generate Monnify Account
                                            </button>
                                        </form>
                                        <script>
                                        function switchTab(type) {
                                            var bvnField = document.getElementById('field-bvn');
                                            var ninField = document.getElementById('field-nin');
                                            var bvnInput = document.getElementById('input-bvn');
                                            var ninInput = document.getElementById('input-nin');
                                            var tabBvn   = document.getElementById('tab-bvn');
                                            var tabNin   = document.getElementById('tab-nin');
                                            if (type === 'bvn') {
                                                bvnField.style.display = '';
                                                ninField.style.display = 'none';
                                                bvnInput.required = true;
                                                ninInput.required = false;
                                                ninInput.value = '';
                                                tabBvn.className = 'btn btn-success active';
                                                tabBvn.style.cssText = 'background:#10d596;border-color:#10d596;';
                                                tabNin.className = 'btn btn-outline-success';
                                                tabNin.style.cssText = 'color:#10d596;border-color:#10d596;';
                                            } else {
                                                ninField.style.display = '';
                                                bvnField.style.display = 'none';
                                                ninInput.required = true;
                                                bvnInput.required = false;
                                                bvnInput.value = '';
                                                tabNin.className = 'btn btn-success active';
                                                tabNin.style.cssText = 'background:#10d596;border-color:#10d596;';
                                                tabBvn.className = 'btn btn-outline-success';
                                                tabBvn.style.cssText = 'color:#10d596;border-color:#10d596;';
                                            }
                                        }
                                        // Set BVN as required by default on load
                                        document.addEventListener('DOMContentLoaded', function() {
                                            document.getElementById('input-bvn').required = true;
                                        });
                                        </script>
                                    <?php else: ?>
                                        <p class="mb-3">Your permanent bank account number is being set up. This usually completes automatically.</p>
                                        <form method="POST" action="">
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