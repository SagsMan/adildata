<?php
require_once '../inc/user_session.inc.php';
$PAGE_TITLE = 'Verify Monnify Payment';
$URL_NAME   = 'verify-monnify';

// ── Try to credit any uncredited Monnify transactions ──────────────────────────
$msg = '';
$err = '';
$credited = 0;

function getMonnifyToken($api_key, $api_secret, $base_url) {
    $base64 = base64_encode("$api_key:$api_secret");
    $ch = curl_init($base_url . '/api/v1/auth/login');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '',
        CURLOPT_HTTPHEADER     => ["Authorization: Basic $base64", "Content-Type: application/json"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($resp, true);
    return $data['responseBody']['accessToken'] ?? null;
}

function searchMonnifyTransactions($token, $email, $base_url) {
    $url = $base_url . '/api/v1/transactions/search'
        . '?customerEmail=' . urlencode($email)
        . '&paymentStatus=PAID&page=0&size=20';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token", "Content-Type: application/json"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

// Load Monnify credentials from DB
$conn2 = mysqli_connect('localhost', 'adiliqgs_adildata', 'adildata2026', 'adiliqgs_adildata');
$monKeys = [];
$qk = mysqli_query($conn2, "SELECT setting_key, setting_value FROM edutech_settings WHERE setting_key LIKE 'MONNIFY_%'");
while ($r = mysqli_fetch_assoc($qk)) $monKeys[$r['setting_key']] = $r['setting_value'];
$API_KEY    = $monKeys['MONNIFY_API_KEY']    ?? '';
$API_SECRET = $monKeys['MONNIFY_API_SECRET'] ?? '881J3RXH6Z6LDVJWG76P1YHW8VCECAE5';
$BASE_URL   = rtrim($monKeys['MONNIFY_BASE_URL'] ?? 'https://api.monnify.com', '/');

if (!empty($API_KEY)) {
    $token = getMonnifyToken($API_KEY, $API_SECRET, $BASE_URL);
    if ($token) {
        $result = searchMonnifyTransactions($token, $Auth->email, $BASE_URL);
        $transactions = $result['responseBody']['content'] ?? [];
        foreach ($transactions as $tx) {
            $ref       = $tx['transactionReference'] ?? '';
            $amt_paid  = floatval($tx['amountPaid'] ?? 0);
            $charge    = ($amt_paid < 10000) ? 50 : 100;
            $to_credit = max(0, $amt_paid - $charge);
            if (empty($ref) || $to_credit <= 0) continue;

            $refSafe = mysqli_real_escape_string($conn2, $ref);
            $exist   = mysqli_query($conn2, "SELECT id FROM wallet_history_tbl WHERE trans_id='$refSafe' LIMIT 1");
            if (mysqli_num_rows($exist) > 0) continue; // already credited

            // Credit wallet
            $emailSafe = mysqli_real_escape_string($conn2, $Auth->email);
            $balRow    = mysqli_query($conn2, "SELECT balance FROM wallet_tbl WHERE user_id='$emailSafe' LIMIT 1");
            $curBal    = 0;
            if ($balRow && mysqli_num_rows($balRow) > 0) {
                $curBal = intval(mysqli_fetch_assoc($balRow)['balance']);
            } else {
                mysqli_query($conn2, "INSERT INTO wallet_tbl(user_id,balance,status) VALUES('$emailSafe',0,1)");
            }
            $newBal = $curBal + intval($to_credit);

            mysqli_query($conn2, "UPDATE wallet_tbl SET balance=balance+" . intval($to_credit) . ", last_transanction=NOW() WHERE user_id='$emailSafe'");
            mysqli_query($conn2,
                "INSERT INTO wallet_history_tbl(trans_id,email,trans_amount,available_balance,wallet_status,trans_date,status,super_admin) "
                . "VALUES('$refSafe','$emailSafe'," . intval($amt_paid) . ",$newBal,'credit',NOW(),1,1)"
            );
            $credited += intval($to_credit);
        }
        if ($credited > 0) {
            $msg = "✓ ₦{$credited} has been credited to your wallet! Refreshing balance...";
        } else {
            $msg = "No new uncredited Monnify payments found for this account. If you just sent money, please wait 2–3 minutes and try again.";
        }
    } else {
        $err = "Could not connect to Monnify to verify payment. Please try again in a few minutes.";
    }
} else {
    $err = "Monnify API key not configured. Please contact support.";
}

// Re-fetch fresh balance
$fresh = $UserAuth->GetUserId($Auth->email);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once 'layout/header-propt.inc.php'; ?>
    <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?></title>
</head>
<body>
<?php require_once 'layout/preloader.inc.php'; ?>
<div id="main-wrapper">
    <?php
    require_once 'layout/header.inc.php';
    require_once 'layout/sidebar.inc.php';
    ?>
    <div class="content-body">
        <?php include 'layout/minor-top-navbar.inc.php'; ?>
        <div class="container-fluid">
            <div class="row page-titles mx-0">
                <div class="col-sm-6 p-md-0">
                    <h4 style="color:#10d596;"><?= $PAGE_TITLE ?></h4>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-8">
                    <div class="card">
                        <div class="card-header" style="background:#10d596;">
                            <h4 class="card-title mb-0 text-white">
                                <i class="fa fa-refresh mr-2"></i>Monnify Payment Verification
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if ($credited > 0): ?>
                            <div class="alert alert-success">
                                <i class="fa fa-check-circle mr-2"></i><strong><?= htmlspecialchars($msg) ?></strong>
                            </div>
                            <div class="text-center mt-3">
                                <h4>New Balance:</h4>
                                <h2 style="color:#10d596; font-weight:bold;">
                                    ₦<?= number_format($WalletController->Get_Single_User_Wallet_Balance($Auth->email)->balance ?? 0) ?>.00
                                </h2>
                            </div>
                            <?php elseif (!empty($err)): ?>
                            <div class="alert alert-danger">
                                <i class="fa fa-exclamation-circle mr-2"></i><?= htmlspecialchars($err) ?>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle mr-2"></i><?= htmlspecialchars($msg) ?>
                            </div>
                            <?php endif; ?>

                            <div class="mt-3 p-3" style="background:#f8f9fa;border-radius:8px;">
                                <h5 class="mb-2">Your Monnify Account</h5>
                                <?php
                                $monAcc = $fresh->monnify_account_details ?? '';
                                if (!empty($monAcc)):
                                    foreach (explode(',', $monAcc) as $acct):
                                        $p = explode(' - ', trim($acct));
                                ?>
                                <div class="mb-2">
                                    <span class="badge badge-success mr-2"><?= htmlspecialchars($p[0] ?? '') ?></span>
                                    <strong><?= htmlspecialchars($p[1] ?? '') ?></strong>
                                    <small class="text-muted ml-2"><?= htmlspecialchars($p[2] ?? '') ?></small>
                                </div>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                <p class="text-muted">No Monnify account yet. <a href="credit-wallet">Generate one</a>.</p>
                                <?php endif; ?>
                            </div>

                            <div class="mt-3 p-3" style="background:#fff3cd;border-radius:8px;">
                                <h6><i class="fa fa-info-circle mr-1"></i> How it works</h6>
                                <ul class="mb-0 pl-3" style="font-size:13px;">
                                    <li>Send any amount to your Monnify account above</li>
                                    <li>Wait for the Monnify confirmation email</li>
                                    <li>Click "Verify Again" if your balance hasn't updated yet</li>
                                    <li>Charges: ₦50 for transfers below ₦10,000 | ₦100 for ₦10,000+</li>
                                </ul>
                            </div>

                            <div class="mt-4 text-center">
                                <a href="verify-monnify" class="btn btn-success mr-2"
                                   style="background:#10d596;border-color:#10d596;">
                                    <i class="fa fa-refresh mr-1"></i> Verify Again
                                </a>
                                <a href="<?= SITE_URL ?>easyfinder/dashboard"
                                   class="btn btn-outline-secondary">
                                    Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once 'layout/footer.inc.php';
    require_once 'layout/footer-propt.inc.php';
    ?>
</div>
</body>
</html>
