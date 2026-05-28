<?php
/**
 * Monnify Webhook Handler — Adildata
 * URL: https://api.adildata.com.ng/webhook.php
 */

$raw_request = file_get_contents('php://input');
$request_array = json_decode($raw_request, true);

// ── File logging (always, before anything else) ───────────────────────────────
$log_file = __DIR__ . '/logs/webhook_monnify.log';
@file_put_contents($log_file,
    date('Y-m-d H:i:s')
    . ' | METHOD=' . $_SERVER['REQUEST_METHOD']
    . ' | SIG=' . substr($_SERVER['HTTP_MONNIFY_SIGNATURE'] ?? 'NONE', 0, 16) . '...'
    . ' | EVENT=' . ($request_array['eventType'] ?? 'UNKNOWN')
    . ' | EMAIL=' . ($request_array['eventData']['customer']['email'] ?? 'NONE')
    . ' | AMT=' . ($request_array['eventData']['amountPaid'] ?? '0')
    . ' | REF=' . ($request_array['eventData']['transactionReference'] ?? 'NONE')
    . ' | BODY=' . substr($raw_request, 0, 400)
    . PHP_EOL, FILE_APPEND | LOCK_EX);

// ── DB connection ─────────────────────────────────────────────────────────────
$connect = mysqli_connect('localhost', 'adiliqgs_adildata', 'adildata2026', 'adiliqgs_adildata', 3306);
if (!$connect) {
    http_response_code(500);
    @file_put_contents($log_file, date('Y-m-d H:i:s') . " | DB_CONNECT_FAILED\n", FILE_APPEND | LOCK_EX);
    die(json_encode(['status' => 'DB_ERROR']));
}

// ── Load Monnify secret from DB settings ─────────────────────────────────────
$sk_row = mysqli_query($connect, "SELECT setting_value FROM edutech_settings WHERE setting_key = 'MONNIFY_API_SECRET' LIMIT 1");
$SECRET_KEY = '881J3RXH6Z6LDVJWG76P1YHW8VCECAE5';
if ($sk_row && mysqli_num_rows($sk_row) > 0) {
    $sk_data = mysqli_fetch_assoc($sk_row);
    if (!empty($sk_data['setting_value'])) $SECRET_KEY = $sk_data['setting_value'];
}

// ── Verify Monnify signature ──────────────────────────────────────────────────
$signature    = $_SERVER['HTTP_MONNIFY_SIGNATURE'] ?? '';
$computedHash = hash_hmac('sha512', $raw_request, $SECRET_KEY);

@file_put_contents($log_file,
    date('Y-m-d H:i:s') . " | COMPUTED=" . substr($computedHash,0,20) . "... | RECEIVED=" . substr($signature,0,20) . "...\n",
    FILE_APPEND | LOCK_EX);

if (empty($signature) || !hash_equals($computedHash, $signature)) {
    http_response_code(401);
    die(json_encode(['status' => 'INVALID_SIGNATURE']));
}

// ── Extract event fields ──────────────────────────────────────────────────────
$event_type = $request_array['eventType'] ?? '';
$email      = $request_array['eventData']['customer']['email'] ?? '';
$amt_paid   = floatval($request_array['eventData']['amountPaid'] ?? 0);
$reference  = $request_array['eventData']['transactionReference'] ?? '';

// Monnify transfer charge deduction
$charge      = ($amt_paid < 10000) ? 50 : 100;
$amount_paid = max(0, $amt_paid - $charge);

if ($event_type === 'SUCCESSFUL_TRANSACTION' && !empty($email) && !empty($reference)) {

    // ── Duplicate protection ──────────────────────────────────────────────────
    $refSafe = mysqli_real_escape_string($connect, $reference);
    $exist   = mysqli_query($connect, "SELECT id FROM wallet_history_tbl WHERE trans_id = '$refSafe' LIMIT 1");
    if (mysqli_num_rows($exist) > 0) {
        @file_put_contents($log_file, date('Y-m-d H:i:s') . " | ALREADY_PROCESSED ref=$reference\n", FILE_APPEND | LOCK_EX);
        echo json_encode(['status' => 'ALREADY_PROCESSED']);
        exit;
    }

    $emailSafe = mysqli_real_escape_string($connect, $email);

    // ── Get or create wallet record ───────────────────────────────────────────
    $bal_row = mysqli_query($connect, "SELECT balance FROM wallet_tbl WHERE user_id = '$emailSafe' LIMIT 1");
    $current_balance = 0;
    if ($bal_row && mysqli_num_rows($bal_row) > 0) {
        $current_balance = intval(mysqli_fetch_assoc($bal_row)['balance']);
    } else {
        mysqli_query($connect, "INSERT INTO wallet_tbl(user_id, balance, status) VALUES('$emailSafe', 0, 1)");
    }

    $new_balance = $current_balance + intval($amount_paid);

    $updateWallet = mysqli_query($connect,
        "UPDATE wallet_tbl SET balance = balance + " . intval($amount_paid) . ", last_transanction = NOW() WHERE user_id = '$emailSafe'"
    );

    $insertHistory = mysqli_query($connect,
        "INSERT INTO wallet_history_tbl (trans_id, email, trans_amount, available_balance, wallet_status, trans_date, status, super_admin)
         VALUES ('$refSafe', '$emailSafe', " . intval($amt_paid) . ", $new_balance, 'credit', NOW(), 1, 1)"
    );

    mysqli_query($connect,
        "UPDATE payment_history_tbl SET status = 1, reason = 'monnify_success' WHERE trans_id = '$refSafe' LIMIT 1"
    );

    $walletOk   = (bool)$updateWallet && mysqli_affected_rows($connect) > 0;
    $historyOk  = (bool)$insertHistory;

    @file_put_contents($log_file,
        date('Y-m-d H:i:s') . " | PROCESSED email=$email amt_paid=$amt_paid credited=$amount_paid wallet_ok=" . ($walletOk?'Y':'N') . " history_ok=" . ($historyOk?'Y':'N') . " new_balance=$new_balance\n",
        FILE_APPEND | LOCK_EX);

    if ($walletOk && $historyOk) {
        echo json_encode(['status' => 'OK']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'DB_WRITE_ERROR', 'wallet' => $walletOk, 'history' => $historyOk]);
    }

} elseif (in_array($event_type, ['FAILED_TRANSACTION', 'REVERSED_TRANSACTION', 'EXPIRED_TRANSACTION'])) {
    $refSafe   = mysqli_real_escape_string($connect, $reference);
    mysqli_query($connect,
        "UPDATE payment_history_tbl SET status = 2, reason = '" . strtolower($event_type) . "' WHERE trans_id = '$refSafe' LIMIT 1"
    );
    @file_put_contents($log_file, date('Y-m-d H:i:s') . " | $event_type ref=$reference\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['status' => 'OK']);

} else {
    @file_put_contents($log_file, date('Y-m-d H:i:s') . " | IGNORED event=$event_type\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['status' => 'IGNORED', 'event' => $event_type]);
}

mysqli_close($connect);
?>
