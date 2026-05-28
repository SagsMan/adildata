<?php
/**
 * Monnify Webhook Handler — Adildata
 * Deployed at: https://api.adildata.com.ng/webhook.php
 * Triggered by Monnify on payment events.
 */

$raw_request = file_get_contents('php://input');
$request_array = json_decode($raw_request, true);

// ── DB connection ─────────────────────────────────────────────────────────────
$connect = mysqli_connect('localhost', 'adiliqgs_adildata', 'adildata2026', 'adiliqgs_adildata', 3306);
if (!$connect) {
    http_response_code(500);
    die(json_encode(['status' => 'DB_ERROR']));
}

// ── Load Monnify secret from DB settings ─────────────────────────────────────
$sk_row = mysqli_query($connect, "SELECT setting_value FROM edutech_settings WHERE setting_key = 'MONNIFY_API_SECRET' LIMIT 1");
$SECRET_KEY = '';
if ($sk_row && mysqli_num_rows($sk_row) > 0) {
    $sk_data = mysqli_fetch_assoc($sk_row);
    $SECRET_KEY = $sk_data['setting_value'];
}
if (empty($SECRET_KEY)) {
    $SECRET_KEY = '881J3RXH6Z6LDVJWG76P1YHW8VCECAE5';
}

// ── Verify Monnify signature ──────────────────────────────────────────────────
$signature    = $_SERVER['HTTP_MONNIFY_SIGNATURE'] ?? '';
$computedHash = hash_hmac('sha512', $raw_request, $SECRET_KEY);
if (empty($signature) || !hash_equals($computedHash, $signature)) {
    http_response_code(401);
    die(json_encode(['status' => 'INVALID_SIGNATURE']));
}

// ── Process event ─────────────────────────────────────────────────────────────
$event_type = $request_array['eventType'] ?? '';
$email      = $request_array['eventData']['customer']['email'] ?? '';
$amt_paid   = floatval($request_array['eventData']['amountPaid'] ?? 0);
$reference  = $request_array['eventData']['transactionReference'] ?? '';

// Monnify transfer charge deduction
$charge      = ($amt_paid < 10000) ? 50 : 100;
$amount_paid = $amt_paid - $charge;

if ($event_type === 'SUCCESSFUL_TRANSACTION' && !empty($email) && !empty($reference)) {

    // ── Duplicate protection ──────────────────────────────────────────────────
    $exist = mysqli_query($connect, "SELECT id FROM wallet_history_tbl WHERE trans_id = '" . mysqli_real_escape_string($connect, $reference) . "' LIMIT 1");
    if (mysqli_num_rows($exist) > 0) {
        echo json_encode(['status' => 'ALREADY_PROCESSED']);
        exit;
    }

    $emailSafe = mysqli_real_escape_string($connect, $email);
    $bal_row   = mysqli_query($connect, "SELECT balance FROM wallet_tbl WHERE user_id = '$emailSafe' LIMIT 1");
    $current_balance = 0;
    if ($bal_row && mysqli_num_rows($bal_row) > 0) {
        $bal_data = mysqli_fetch_assoc($bal_row);
        $current_balance = intval($bal_data['balance']);
    } else {
        mysqli_query($connect, "INSERT INTO wallet_tbl(user_id, balance, status) VALUES('$emailSafe', 0, 1)");
    }

    $new_balance = $current_balance + intval($amount_paid);

    $updateWallet = mysqli_query($connect,
        "UPDATE wallet_tbl SET balance = balance + " . intval($amount_paid) . ", last_transanction = NOW() WHERE user_id = '$emailSafe'"
    );

    $refSafe = mysqli_real_escape_string($connect, $reference);
    $insertHistory = mysqli_query($connect,
        "INSERT INTO wallet_history_tbl (trans_id, email, trans_amount, available_balance, wallet_status, trans_date, status, super_admin)
         VALUES ('$refSafe', '$emailSafe', " . intval($amt_paid) . ", $new_balance, 'credit', NOW(), 1, 1)"
    );

    mysqli_query($connect,
        "UPDATE payment_history_tbl SET status = 1, reason = 'success' WHERE trans_id = '$refSafe' LIMIT 1"
    );

    if ($updateWallet && $insertHistory) {
        echo json_encode(['status' => 'OK']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'DB_WRITE_ERROR', 'wallet' => (bool)$updateWallet, 'history' => (bool)$insertHistory]);
    }

} elseif (in_array($event_type, ['FAILED_TRANSACTION', 'REVERSED_TRANSACTION', 'EXPIRED_TRANSACTION'])) {

    $refSafe   = mysqli_real_escape_string($connect, $reference);
    $emailSafe = mysqli_real_escape_string($connect, $email);
    mysqli_query($connect,
        "UPDATE payment_history_tbl SET status = 2, reason = '" . strtolower($event_type) . "' WHERE trans_id = '$refSafe' LIMIT 1"
    );
    echo json_encode(['status' => 'OK']);

} else {
    echo json_encode(['status' => 'IGNORED', 'event' => $event_type]);
}

mysqli_close($connect);
?>
