<?php
/**
 * Adildata REST API — Mobile App Backend
 * Deploy to: api.adildata.com.ng/api.php
 * Usage: https://api.adildata.com.ng/api.php?action=XXX
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── DB connection ─────────────────────────────────────────────────────────────
function db_connect() {
    $conn = mysqli_connect('localhost', 'adiliqgs_adildata', 'adildata2026', 'adiliqgs_adildata');
    if (!$conn) {
        http_response_code(503);
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        exit;
    }
    return $conn;
}

// ── Response helpers ──────────────────────────────────────────────────────────
function api_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode(['status' => $code === 200 ? 'success' : 'error', 'data' => $data]);
    exit;
}

function api_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// ── Token verification ────────────────────────────────────────────────────────
// FIX: Direct indexed lookup instead of fetching ALL users and bcrypt-comparing
// each row — the old approach caused 30-60 s response times with many users.
// Tokens are now stored as plain random hex strings so WHERE token=? is instant.
// A bcrypt fallback handles legacy sessions until users re-login.
function verify_token($conn, $incoming_token) {
    if (empty($incoming_token)) return null;

    // Fast path: plain token direct lookup (O(1) with an index)
    $ts = mysqli_real_escape_string($conn, $incoming_token);
    $q  = mysqli_query($conn, "SELECT * FROM users_tbl WHERE token = '$ts' AND status = 1 LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) return mysqli_fetch_assoc($q);

    // Slow legacy fallback: bcrypt-hashed token — only for old sessions
    // (Users who re-login will get a plain token and never hit this path again)
    $q2 = mysqli_query($conn, "SELECT * FROM users_tbl WHERE token IS NOT NULL AND token != '' AND status = 1");
    if ($q2) {
        while ($row = mysqli_fetch_assoc($q2)) {
            if (password_verify($incoming_token, $row['token'])) return $row;
        }
    }
    return null;
}

function get_token_from_request() {
    return $_SERVER['HTTP_X_API_TOKEN']
        ?? $_GET['token']
        ?? $_POST['token']
        ?? (json_decode(file_get_contents('php://input'), true)['token'] ?? '');
}

function require_auth($conn) {
    $token = get_token_from_request();
    if (empty($token)) api_error('Unauthorized: token required', 401);
    $user = verify_token($conn, $token);
    if (!$user) api_error('Unauthorized: invalid or expired token', 401);
    return $user;
}

// ── Monnify helpers ───────────────────────────────────────────────────────────
function monnify_login($api_key, $api_secret, $base_url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => rtrim($base_url, '/') . '/api/v1/auth/login',
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '',
        CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . base64_encode("$api_key:$api_secret"), 'Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($resp, true);
    return $data['responseBody']['accessToken'] ?? null;
}

function monnify_get_credentials($conn) {
    $q = mysqli_query($conn, "SELECT setting_key, setting_value FROM edutech_settings WHERE setting_key LIKE 'MONNIFY_%'");
    $keys = [];
    while ($r = mysqli_fetch_assoc($q)) $keys[$r['setting_key']] = $r['setting_value'];
    return [
        'api_key'      => $keys['MONNIFY_API_KEY'] ?? '',
        'api_secret'   => $keys['MONNIFY_API_SECRET'] ?? '881J3RXH6Z6LDVJWG76P1YHW8VCECAE5',
        'base_url'     => rtrim($keys['MONNIFY_BASE_URL'] ?? 'https://api.monnify.com', '/'),
        'contract'     => $keys['MONNIFY_API_CONTRACT'] ?? '',
    ];
}

// ── PaymentPoint helpers ──────────────────────────────────────────────────────
function paymentpoint_create_account($email, $name, $phone, $conn) {
    $apiSecret  = '1e5466700ff67b7c91e73ce36d2d0b630777c825e64438bc70c9b342a1e1afa6ff20b81c4b51bc7bf771c0e5e73666f2d089c145c5c5782ccd489290';
    $apiKey     = 'ac82b8a0a46c6ff27bebb20960a70891525828a6';
    $businessId = '51f60608cd7b92cdd95182ecb0fc4862ec0753fe';
    $url        = 'https://api.paymentpoint.co/api/v1/createVirtualAccount';

    $phoneDigits = preg_replace('/\D+/', '', (string)$phone);
    if (strlen($phoneDigits) < 11) {
        $phoneDigits = str_pad($phoneDigits, 11, (string)random_int(0, 9));
    } elseif (strlen($phoneDigits) > 11) {
        $phoneDigits = substr($phoneDigits, 0, 11);
    }

    $payload = json_encode([
        'email'       => $email,
        'name'        => $name,
        'phoneNumber' => $phoneDigits,
        'bankCode'    => ['20946', '20897'],
        'businessId'  => $businessId,
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer $apiSecret",
            'Content-Type: application/json',
            "api-key: $apiKey",
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

// ─────────────────────────────────────────────────────────────────────────────
$action = strtolower(trim($_GET['action'] ?? $_POST['action'] ?? (json_decode(file_get_contents('php://input'), true)['action'] ?? '')));
$conn   = db_connect();

switch ($action) {

// ── HEALTH ────────────────────────────────────────────────────────────────────
case 'health':
case 'ping':
    api_response(['message' => 'Adildata API is running', 'version' => '2.0', 'time' => date('Y-m-d H:i:s')]);
    break;

// ── LOGIN ─────────────────────────────────────────────────────────────────────
case 'login':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error('POST required', 405);
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $email    = trim($body['email']    ?? $_POST['email']    ?? '');
    $password = trim($body['password'] ?? $_POST['password'] ?? '');
    if (empty($email) || empty($password)) api_error('Email and password required');

    $em = mysqli_real_escape_string($conn, $email);
    $r  = mysqli_query($conn, "SELECT * FROM users_tbl WHERE email = '$em' AND status = 1 LIMIT 1");
    if (!$r || mysqli_num_rows($r) === 0) api_error('Invalid credentials', 401);
    $user = mysqli_fetch_assoc($r);
    if (!password_verify($password, $user['password'])) api_error('Invalid credentials', 401);

    // FIX: Store plain random token — NOT bcrypt-hashed.
    // The token is already secure (bin2hex of 32 random bytes = 256-bit entropy).
    // Bcrypt-hashing it forces every auth check to do a full table scan + bcrypt
    // compare on each row, which is the root cause of 30-60 s response times.
    $api_token = bin2hex(random_bytes(32));
    $ts        = mysqli_real_escape_string($conn, $api_token);
    mysqli_query($conn, "UPDATE users_tbl SET token = '$ts' WHERE id = " . intval($user['id']));

    // Get wallet balance — use floatval so decimal kobo are not truncated
    $wq  = mysqli_query($conn, "SELECT balance FROM wallet_tbl WHERE user_id = '$em' LIMIT 1");
    $bal = $wq && mysqli_num_rows($wq) > 0 ? floatval(mysqli_fetch_assoc($wq)['balance']) : 0;

    api_response([
        'token'        => $api_token,
        'id'           => $user['id'],
        'email'        => $user['email'],
        'sname'        => $user['sname'],
        'oname'        => $user['oname'],
        'phone'        => $user['phone'],
        'admin_role'   => $user['admin_role'],
        'wallet_balance' => $bal,
    ]);
    break;

// ── REGISTER ─────────────────────────────────────────────────────────────────
case 'register':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error('POST required', 405);
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $body = array_merge($_POST, $body);
    foreach (['email','password','sname','oname','phone','pin','state'] as $f) {
        if (empty(trim($body[$f] ?? ''))) api_error("$f is required");
    }
    $em = mysqli_real_escape_string($conn, trim($body['email']));
    $ex = mysqli_query($conn, "SELECT id FROM users_tbl WHERE email = '$em' LIMIT 1");
    if ($ex && mysqli_num_rows($ex) > 0) api_error('Email already registered');

    $pass  = password_hash(trim($body['password']), PASSWORD_DEFAULT);
    $sname = mysqli_real_escape_string($conn, trim($body['sname']));
    $oname = mysqli_real_escape_string($conn, trim($body['oname']));
    $phone = mysqli_real_escape_string($conn, trim($body['phone']));
    $pin   = md5(trim($body['pin']));
    $state = mysqli_real_escape_string($conn, trim($body['state']));
    $ref   = md5(trim($body['email']));
    $ref_by = mysqli_real_escape_string($conn, trim($body['referal'] ?? ''));

    $ins = mysqli_query($conn, "INSERT INTO users_tbl(sname,oname,password,email,phone,referal_token,pin,state) VALUES('$sname','$oname','$pass','$em','$phone','$ref','$pin','$state')");
    if (!$ins) api_error('Registration failed: ' . mysqli_error($conn));

    mysqli_query($conn, "INSERT INTO wallet_tbl(user_id, balance, status) VALUES('$em', 0, 1)");
    if (!empty($ref_by)) {
        mysqli_query($conn, "INSERT INTO referal_tbl(referal, referee) VALUES('$ref_by', '$ref')");
    }
    api_response(['message' => 'Registration successful. Please login.']);
    break;

// ── PROFILE ───────────────────────────────────────────────────────────────────
case 'profile':
    $user = require_auth($conn);
    $em   = mysqli_real_escape_string($conn, $user['email']);
    $wq   = mysqli_query($conn, "SELECT balance FROM wallet_tbl WHERE user_id = '$em' LIMIT 1");
    $bal  = $wq && mysqli_num_rows($wq) > 0 ? floatval(mysqli_fetch_assoc($wq)['balance']) : 0;
    api_response([
        'id'             => $user['id'],
        'email'          => $user['email'],
        'sname'          => $user['sname'],
        'oname'          => $user['oname'],
        'phone'          => $user['phone'],
        'state'          => $user['state'],
        'admin_role'     => $user['admin_role'],
        'super_admin'    => $user['super_admin'],
        'referral_code'  => $user['referal_token'],
        'wallet_balance' => $bal,
        'has_monnify'    => !empty($user['monnify_account_details']),
        'has_payment_point' => !empty($user['acc_no']),
        'bvn'            => !empty($user['bvn']) ? '****' . substr($user['bvn'], -4) : null,
    ]);
    break;

// ── WALLET BALANCE ────────────────────────────────────────────────────────────
case 'wallet':
    $user = require_auth($conn);
    $em   = mysqli_real_escape_string($conn, $user['email']);
    $wq   = mysqli_query($conn, "SELECT balance FROM wallet_tbl WHERE user_id = '$em' LIMIT 1");
    $bal  = $wq && mysqli_num_rows($wq) > 0 ? floatval(mysqli_fetch_assoc($wq)['balance']) : 0;
    api_response(['balance' => $bal, 'email' => $user['email']]);
    break;

// ── WALLET HISTORY ────────────────────────────────────────────────────────────
case 'wallet_history':
    $user = require_auth($conn);
    $em   = mysqli_real_escape_string($conn, $user['email']);
    $q    = mysqli_query($conn, "SELECT * FROM wallet_history_tbl WHERE email = '$em' ORDER BY id DESC LIMIT 50");
    $rows = [];
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    api_response(['transactions' => $rows]);
    break;

// ── TRANSACTION HISTORY ───────────────────────────────────────────────────────
case 'transactions':
    $user = require_auth($conn);
    $em   = mysqli_real_escape_string($conn, $user['email']);
    $q    = mysqli_query($conn, "SELECT * FROM transactions_tbl WHERE email = '$em' ORDER BY id DESC LIMIT 50");
    $rows = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $rows[] = [
            'id'          => $row['id'],
            'title'       => $row['product_name'] ?? 'Transaction',
            'phone'       => $row['phone'] ?? '-',
            'date'        => $row['transaction_date'] ?? '-',
            'subtitle'    => ($row['status'] == 1) ? 'Successful' : 'Failed / Refunded',
            'amount'      => number_format($row['amount'], 0),
            'status'      => intval($row['status']),
            'negative'    => $row['status'] == 1,
            'request_id'  => $row['request_id'] ?? '',
        ];
    }
    api_response(['transactions' => $rows]);
    break;

// ── DASHBOARD STATS ───────────────────────────────────────────────────────────
case 'dashboard_stats':
    $user = require_auth($conn);
    $em   = mysqli_real_escape_string($conn, $user['email']);

    $wq   = mysqli_query($conn, "SELECT balance FROM wallet_tbl WHERE user_id = '$em' LIMIT 1");
    $bal  = $wq && mysqli_num_rows($wq) > 0 ? intval(mysqli_fetch_assoc($wq)['balance']) : 0;

    $tq   = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(CASE WHEN status=1 THEN 1 ELSE 0 END) as success, SUM(CASE WHEN status=0 THEN 1 ELSE 0 END) as failed FROM transactions_tbl WHERE email='$em'");
    $ts   = $tq ? mysqli_fetch_assoc($tq) : ['total' => 0, 'success' => 0, 'failed' => 0];

    $nq   = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications_tbl WHERE status=1 AND (target='all' OR target_email='$em')");
    $nc   = $nq ? intval(mysqli_fetch_assoc($nq)['cnt']) : 0;

    $rq   = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM referal_tbl WHERE referal=(SELECT referal_token FROM users_tbl WHERE email='$em' LIMIT 1)");
    $rc   = $rq ? intval(mysqli_fetch_assoc($rq)['cnt']) : 0;

    api_response([
        'wallet_balance'     => $bal,
        'total_transactions' => intval($ts['total']),
        'success_transactions' => intval($ts['success']),
        'failed_transactions'  => intval($ts['failed']),
        'notifications_count'  => $nc,
        'referral_count'       => $rc,
        'has_monnify'          => !empty($user['monnify_account_details']),
        'has_payment_point'    => !empty($user['acc_no']),
    ]);
    break;

// ── FUNDING ACCOUNTS ─────────────────────────────────────────────────────────
case 'funding_accounts':
    $user    = require_auth($conn);
    $em      = mysqli_real_escape_string($conn, $user['email']);
    $q       = mysqli_query($conn, "SELECT acc_no, bank_name, acc_name, acc_no2, bank_name2, acc_name2, monnify_account_details FROM users_tbl WHERE email='$em' LIMIT 1");
    $row     = $q ? mysqli_fetch_assoc($q) : [];
    $accounts = [];

    // PaymentPoint accounts
    if (!empty($row['acc_no'])) {
        $accounts[] = [
            'provider'       => 'PaymentPoint',
            'account_number' => $row['acc_no'],
            'bank_name'      => $row['bank_name'],
            'account_name'   => $row['acc_name'],
        ];
    }
    if (!empty($row['acc_no2'])) {
        $accounts[] = [
            'provider'       => 'PaymentPoint',
            'account_number' => $row['acc_no2'],
            'bank_name'      => $row['bank_name2'],
            'account_name'   => $row['acc_name2'],
        ];
    }

    // Monnify accounts
    if (!empty($row['monnify_account_details'])) {
        foreach (explode(', ', $row['monnify_account_details']) as $acct) {
            $p = explode(' - ', trim($acct));
            if (count($p) >= 2) {
                $accounts[] = [
                    'provider'       => 'Monnify',
                    'bank_name'      => $p[0] ?? '',
                    'account_number' => $p[1] ?? '',
                    'account_name'   => $p[2] ?? '',
                ];
            }
        }
    }

    // Flat top-level fields for APK backward-compatibility
    $primary = $accounts[0] ?? null;
    api_response([
        'accounts'          => $accounts,
        'has_accounts'      => count($accounts) > 0,
        'has_monnify'       => !empty($row['monnify_account_details']),
        'has_payment_point' => !empty($row['acc_no']),
        'monnify_raw'       => $row['monnify_account_details'] ?? '',
        // Flat fields — primary account (APK compatibility)
        'acc_no'            => $primary['account_number'] ?? '',
        'bank_name'         => $primary['bank_name'] ?? '',
        'acc_name'          => $primary['account_name'] ?? '',
        'account_number'    => $primary['account_number'] ?? '',
        'account_name'      => $primary['account_name'] ?? '',
        'provider'          => $primary['provider'] ?? '',
        // Second account flat fields
        'acc_no2'           => $accounts[1]['account_number'] ?? '',
        'bank_name2'        => $accounts[1]['bank_name'] ?? '',
        'acc_name2'         => $accounts[1]['account_name'] ?? '',
    ]);
    break;

// ── GENERATE PAYMENT POINT ACCOUNT ───────────────────────────────────────────
case 'generate_payment_point':
    $user = require_auth($conn);
    $em   = mysqli_real_escape_string($conn, $user['email']);

    // Check if already has two accounts
    $cq = mysqli_query($conn, "SELECT acc_no, acc_no2 FROM users_tbl WHERE email='$em' LIMIT 1");
    $cu = $cq ? mysqli_fetch_assoc($cq) : [];
    if (!empty($cu['acc_no']) && !empty($cu['acc_no2'])) {
        api_response(['message' => 'Already has payment point accounts', 'already_exists' => true]);
    }

    $fullName = trim($user['sname'] . ' ' . $user['oname']);
    $result   = paymentpoint_create_account($user['email'], $fullName, $user['phone'], $conn);

    if (!isset($result['status']) || $result['status'] !== 'success') {
        api_error('PaymentPoint error: ' . ($result['message'] ?? json_encode($result)));
    }

    $bankAccounts = $result['bankAccounts'] ?? [];
    $updates      = [];
    $account1     = $bankAccounts[0] ?? null;
    $account2     = $bankAccounts[1] ?? null;

    if (empty($cu['acc_no']) && $account1) {
        $an = mysqli_real_escape_string($conn, $account1['accountNumber']);
        $bn = mysqli_real_escape_string($conn, $account1['bankName']);
        $nm = mysqli_real_escape_string($conn, $account1['accountName']);
        $updates[] = "acc_no='$an', bank_name='$bn', acc_name='$nm'";
    }
    if (empty($cu['acc_no2']) && $account2) {
        $an2 = mysqli_real_escape_string($conn, $account2['accountNumber']);
        $bn2 = mysqli_real_escape_string($conn, $account2['bankName']);
        $nm2 = mysqli_real_escape_string($conn, $account2['accountName']);
        $updates[] = "acc_no2='$an2', bank_name2='$bn2', acc_name2='$nm2'";
    }
    if (!empty($updates)) {
        mysqli_query($conn, "UPDATE users_tbl SET " . implode(', ', $updates) . " WHERE email='$em'");
    }

    // Return fresh account data
    $fq = mysqli_query($conn, "SELECT acc_no, bank_name, acc_name, acc_no2, bank_name2, acc_name2 FROM users_tbl WHERE email='$em' LIMIT 1");
    $fw = mysqli_fetch_assoc($fq);
    api_response([
        'message'  => 'PaymentPoint accounts ready',
        'account1' => ['account_number' => $fw['acc_no'], 'bank_name' => $fw['bank_name'], 'account_name' => $fw['acc_name']],
        'account2' => ['account_number' => $fw['acc_no2'], 'bank_name' => $fw['bank_name2'], 'account_name' => $fw['acc_name2']],
    ]);
    break;

// ── GENERATE MONNIFY ACCOUNT ──────────────────────────────────────────────────
case 'generate_monnify':
    $user = require_auth($conn);
    $em   = mysqli_real_escape_string($conn, $user['email']);

    // Return existing if already has it
    if (!empty($user['monnify_account_details'])) {
        api_response(['message' => 'already_exists', 'account_details' => $user['monnify_account_details']]);
    }

    // Accept BVN/NIN from request body to save before generating
    $body_input = json_decode(file_get_contents('php://input'), true) ?? [];
    $input_bvn  = preg_replace('/\D/', '', trim($body_input['bvn'] ?? $_POST['bvn'] ?? ''));
    $input_nin  = preg_replace('/\D/', '', trim($body_input['nin'] ?? $_POST['nin'] ?? ''));
    if (!empty($input_bvn) && strlen($input_bvn) === 11) {
        $bvns = mysqli_real_escape_string($conn, $input_bvn);
        // FIX: Reject if this BVN is already linked to another account
        $dup = mysqli_query($conn, "SELECT id FROM users_tbl WHERE bvn='$bvns' AND email != '$em' LIMIT 1");
        if ($dup && mysqli_num_rows($dup) > 0) {
            api_error('This BVN is already linked to another account', 409);
        }
        mysqli_query($conn, "UPDATE users_tbl SET bvn='$bvns' WHERE email='$em'");
        $user['bvn'] = $input_bvn;
    }
    if (!empty($input_nin) && strlen($input_nin) === 11) {
        $nins = mysqli_real_escape_string($conn, $input_nin);
        // FIX: Reject if this NIN is already linked to another account
        $dup = mysqli_query($conn, "SELECT id FROM users_tbl WHERE nin='$nins' AND email != '$em' LIMIT 1");
        if ($dup && mysqli_num_rows($dup) > 0) {
            api_error('This NIN is already linked to another account', 409);
        }
        mysqli_query($conn, "UPDATE users_tbl SET nin='$nins' WHERE email='$em'");
        $user['nin'] = $input_nin;
    }

    // Validate BVN/NIN length
    $valid_bvn = !empty($user['bvn']) && strlen(preg_replace('/\D/', '', $user['bvn'])) === 11;
    $valid_nin = !empty($user['nin']) && strlen(preg_replace('/\D/', '', $user['nin'])) === 11;
    if (!$valid_bvn && !$valid_nin) {
        api_error('bvn_required: Please provide your valid 11-digit BVN or NIN to generate a Monnify account.', 422);
    }
    // Use cleaned BVN/NIN
    if ($valid_bvn) $user['bvn'] = preg_replace('/\D/', '', $user['bvn']);
    if ($valid_nin) $user['nin'] = preg_replace('/\D/', '', $user['nin']);

    $creds    = monnify_get_credentials($conn);
    if (empty($creds['api_key'])) api_error('Monnify not configured. Contact support.');

    $token = monnify_login($creds['api_key'], $creds['api_secret'], $creds['base_url']);
    if (!$token) api_error('Monnify authentication failed. Please try again.');

    $reference    = uniqid('ADL_');
    $account_data = [
        'accountReference'   => $reference,
        'accountName'        => $user['sname'] . '_' . rand(1111, 9999),
        'currencyCode'       => 'NGN',
        'contractCode'       => $creds['contract'],
        'customerEmail'      => $user['email'],
        'customerName'       => trim($user['sname'] . ' ' . $user['oname']),
        'getAllAvailableBanks'=> true,
    ];
    if (!empty($user['bvn'])) $account_data['bvn'] = $user['bvn'];
    elseif (!empty($user['nin'])) $account_data['nin'] = $user['nin'];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $creds['base_url'] . '/api/v2/bank-transfer/reserved-accounts',
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($account_data),
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    $res = json_decode($resp, true);
    if (empty($res['requestSuccessful']) || empty($res['responseBody']['accounts'])) {
        $monnify_msg = strtolower($res['responseMessage'] ?? '');
        if (strpos($monnify_msg, 'bvn') !== false || strpos($monnify_msg, 'invalid') !== false) {
            // Clear the invalid BVN so user can re-enter
            mysqli_query($conn, "UPDATE users_tbl SET bvn='' WHERE email='$em'");
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'bvn_invalid: Your BVN was rejected. Please provide your correct 11-digit BVN.', 'error_code' => 'bvn_invalid']);
            exit;
        }
        api_error('Monnify account creation failed: ' . ($res['responseMessage'] ?? 'Unknown error'));
    }

    $accounts = $res['responseBody']['accounts'];
    $parts    = [];
    foreach ($accounts as $a) $parts[] = $a['bankName'] . ' - ' . $a['accountNumber'] . ' - ' . $a['accountName'];
    $details = implode(', ', $parts);
    $ds = mysqli_real_escape_string($conn, $details);
    mysqli_query($conn, "UPDATE users_tbl SET monnify_account_details='$ds' WHERE email='$em'");

    api_response(['message' => 'Monnify account created', 'account_details' => $details, 'accounts' => $accounts]);
    break;

// ── VERIFY MONNIFY PAYMENT ────────────────────────────────────────────────────
case 'verify_monnify':
    $user   = require_auth($conn);
    $em     = mysqli_real_escape_string($conn, $user['email']);
    $creds  = monnify_get_credentials($conn);
    if (empty($creds['api_key'])) api_error('Monnify not configured');

    $token = monnify_login($creds['api_key'], $creds['api_secret'], $creds['base_url']);
    if (!$token) api_error('Could not connect to Monnify');

    $url = $creds['base_url'] . '/api/v1/transactions/search?customerEmail=' . urlencode($user['email']) . '&paymentStatus=PAID&page=0&size=20';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($resp, true);
    $transactions = $data['responseBody']['content'] ?? [];

    $credited = 0;
    foreach ($transactions as $tx) {
        $ref      = $tx['transactionReference'] ?? '';
        $amt_paid = floatval($tx['amountPaid'] ?? 0);
        $charge   = ($amt_paid < 10000) ? 50 : 100;
        $to_credit= max(0, $amt_paid - $charge);
        if (empty($ref) || $to_credit <= 0) continue;

        $rs    = mysqli_real_escape_string($conn, $ref);
        $exist = mysqli_query($conn, "SELECT id FROM wallet_history_tbl WHERE trans_id='$rs' LIMIT 1");
        if (mysqli_num_rows($exist) > 0) continue;

        mysqli_query($conn, "UPDATE wallet_tbl SET balance=balance+" . intval($to_credit) . ", last_transanction=NOW() WHERE user_id='$em'");
        $balR = mysqli_query($conn, "SELECT balance FROM wallet_tbl WHERE user_id='$em' LIMIT 1");
        $newB = $balR && mysqli_num_rows($balR) > 0 ? intval(mysqli_fetch_assoc($balR)['balance']) : 0;
        mysqli_query($conn, "INSERT INTO wallet_history_tbl(trans_id,email,trans_amount,available_balance,wallet_status,trans_date,status,super_admin) VALUES('$rs','$em'," . intval($amt_paid) . ",$newB,'credit',NOW(),1,1)");
        $credited += intval($to_credit);
    }

    $wq  = mysqli_query($conn, "SELECT balance FROM wallet_tbl WHERE user_id='$em' LIMIT 1");
    $bal = $wq && mysqli_num_rows($wq) > 0 ? intval(mysqli_fetch_assoc($wq)['balance']) : 0;

    api_response([
        'credited'       => $credited,
        'wallet_balance' => $bal,
        'message'        => $credited > 0 ? "NGN {$credited} credited to your wallet" : 'No new payments found',
    ]);
    break;

// ── BUY AIRTIME ───────────────────────────────────────────────────────────────
case 'buy_airtime':
    $user = require_auth($conn);
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $amount    = intval($body['amount']  ?? $_POST['amount']  ?? 0);
    $number    = trim($body['number']   ?? $_POST['number']   ?? '');
    $network   = strtolower(trim($body['network'] ?? $_POST['network'] ?? $body['serviceID'] ?? ''));
    $pin       = trim($body['pin']      ?? $_POST['pin']      ?? '');

    if (!$amount || !$number || !$network || !$pin) api_error('amount, number, network and pin are required');
    if ($pin !== 'fingerprint' && md5($pin) !== $user['pin']) api_error('Invalid PIN');

    $em = mysqli_real_escape_string($conn, $user['email']);
    $wq = mysqli_query($conn, "SELECT balance FROM wallet_tbl WHERE user_id='$em' LIMIT 1");
    if (!$wq || mysqli_num_rows($wq) === 0) api_error('Wallet not found');
    $wallet = mysqli_fetch_assoc($wq);
    if ($wallet['balance'] < $amount) api_error('Insufficient balance');

    $newBalance = $wallet['balance'] - $amount;
    mysqli_query($conn, "UPDATE wallet_tbl SET balance='$newBalance' WHERE user_id='$em'");

    $apiQ = mysqli_query($conn, "SELECT * FROM api_settings WHERE api_name='vtpass' LIMIT 1");
    if (!$apiQ || mysqli_num_rows($apiQ) === 0) { mysqli_query($conn, "UPDATE wallet_tbl SET balance='{$wallet['balance']}' WHERE user_id='$em'"); api_error('Service not configured'); }
    $api  = mysqli_fetch_assoc($apiQ);

    $requestId = uniqid('AIRT_');
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => rtrim($api['api_url'], '/') . '/api/pay',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['request_id' => $requestId, 'serviceID' => $network, 'amount' => $amount, 'phone' => $number]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'api-key: ' . $api['api_key'], 'secret-key: ' . $api['secret']],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $apiResponse = curl_exec($ch);
    $curlError   = curl_error($ch);
    curl_close($ch);

    $res    = json_decode($apiResponse, true);
    $status = !$curlError && $res && strtolower($res['code'] ?? '') === '000';

    if (!$status) mysqli_query($conn, "UPDATE wallet_tbl SET balance='{$wallet['balance']}' WHERE user_id='$em'");
    $txId = $res['content']['transactions']['transactionId'] ?? null;
    $nm   = mysqli_real_escape_string($conn, $number);
    $resJ = mysqli_real_escape_string($conn, json_encode($res));
    $rid  = mysqli_real_escape_string($conn, $requestId);
    mysqli_query($conn, "INSERT INTO transactions_tbl(unique_element,amount,real_amount,email,phone,transaction_id,request_id,product_name,response_description,status,transaction_date,is_bill,our_commission) VALUES('$nm','$amount','$amount','$em','$nm','" . mysqli_real_escape_string($conn, $txId ?? '') . "','$rid','" . strtoupper($network) . " Airtime','$resJ'," . ($status?1:0) . ",NOW(),1,0)");

    api_response(['success' => $status, 'message' => $status ? 'Airtime purchase successful' : 'Transaction failed, refunded', 'balance' => $status ? $newBalance : $wallet['balance']]);
    break;

// ── BUY DATA ──────────────────────────────────────────────────────────────────
case 'buy_data':
    $user = require_auth($conn);
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $amount    = intval($body['amount']    ?? $_POST['amount']    ?? 0);
    $number    = trim($body['number']     ?? $_POST['number']     ?? '');
    $serviceID = trim($body['serviceID']  ?? $_POST['serviceID']  ?? '');
    $variation = trim($body['variation']  ?? $_POST['variation']  ?? '');
    $pin       = trim($body['pin']        ?? $_POST['pin']        ?? '');

    if (!$amount || !$number || !$serviceID || !$variation || !$pin) api_error('amount, number, serviceID, variation and pin are required');
    if ($pin !== 'fingerprint' && md5($pin) !== $user['pin']) api_error('Invalid PIN');

    $em = mysqli_real_escape_string($conn, $user['email']);
    $wq = mysqli_query($conn, "SELECT balance FROM wallet_tbl WHERE user_id='$em' LIMIT 1");
    if (!$wq || mysqli_num_rows($wq) === 0) api_error('Wallet not found');
    $wallet = mysqli_fetch_assoc($wq);
    if ($wallet['balance'] < $amount) api_error('Insufficient balance');

    $newBalance = $wallet['balance'] - $amount;
    mysqli_query($conn, "UPDATE wallet_tbl SET balance='$newBalance' WHERE user_id='$em'");

    $apiQ = mysqli_query($conn, "SELECT * FROM api_settings WHERE api_name='vtpass' LIMIT 1");
    if (!$apiQ || mysqli_num_rows($apiQ) === 0) { mysqli_query($conn, "UPDATE wallet_tbl SET balance='{$wallet['balance']}' WHERE user_id='$em'"); api_error('Service not configured'); }
    $api  = mysqli_fetch_assoc($apiQ);

    $requestId = uniqid('DATA_');
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => rtrim($api['api_url'], '/') . '/api/pay',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['request_id' => $requestId, 'serviceID' => strtolower($serviceID), 'billersCode' => $number, 'variation_code' => $variation, 'amount' => $amount, 'phone' => $number]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'api-key: ' . $api['api_key'], 'secret-key: ' . $api['secret']],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $apiResponse = curl_exec($ch);
    $curlError   = curl_error($ch);
    curl_close($ch);

    $res    = json_decode($apiResponse, true);
    $status = !$curlError && $res && strtolower($res['code'] ?? '') === '000';

    if (!$status) mysqli_query($conn, "UPDATE wallet_tbl SET balance='{$wallet['balance']}' WHERE user_id='$em'");
    $txId = $res['content']['transactions']['transactionId'] ?? null;
    $nm   = mysqli_real_escape_string($conn, $number);
    $resJ = mysqli_real_escape_string($conn, json_encode($res));
    $rid  = mysqli_real_escape_string($conn, $requestId);
    $pn   = mysqli_real_escape_string($conn, $res['content']['transactions']['product_name'] ?? 'Data Purchase');
    mysqli_query($conn, "INSERT INTO transactions_tbl(unique_element,amount,real_amount,email,phone,transaction_id,request_id,product_name,response_description,status,transaction_date,is_bill,our_commission) VALUES('$nm','$amount','$amount','$em','$nm','" . mysqli_real_escape_string($conn, $txId ?? '') . "','$rid','$pn','$resJ'," . ($status?1:0) . ",NOW(),1,0)");

    api_response(['success' => $status, 'message' => $status ? 'Data purchase successful' : 'Transaction failed, refunded', 'balance' => $status ? $newBalance : $wallet['balance']]);
    break;

// ── DATA PLANS ────────────────────────────────────────────────────────────────
case 'data_plans':
    $serviceID = trim($_GET['serviceID'] ?? $_POST['serviceID'] ?? (json_decode(file_get_contents('php://input'), true)['serviceID'] ?? ''));
    if (empty($serviceID)) api_error('serviceID required');

    $apiQ = mysqli_query($conn, "SELECT * FROM api_settings WHERE api_name='vtpass' LIMIT 1");
    $api  = $apiQ && mysqli_num_rows($apiQ) > 0 ? mysqli_fetch_assoc($apiQ) : null;
    $url  = $api ? rtrim($api['api_url'], '/') . '/api/service-variations?serviceID=' . urlencode(strtolower($serviceID)) : 'https://vtpass.com/api/service-variations?serviceID=' . urlencode(strtolower($serviceID));

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false]);
    if ($api) curl_setopt($ch, CURLOPT_HTTPHEADER, ['api-key: ' . $api['api_key'], 'secret-key: ' . $api['secret']]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data  = json_decode($resp, true);
    $plans = [];
    foreach (($data['content']['variations'] ?? []) as $p) {
        $plans[] = ['plan_id' => $p['variation_code'], 'name' => $p['name'], 'amount' => $p['variation_amount']];
    }
    api_response(['plans' => $plans]);
    break;

// ── NOTIFICATIONS ─────────────────────────────────────────────────────────────
case 'notifications':
    $user = require_auth($conn);
    $em   = mysqli_real_escape_string($conn, $user['email']);
    $q    = mysqli_query($conn, "SELECT * FROM notifications_tbl WHERE status=1 AND (target='all' OR target_email='$em') ORDER BY id DESC LIMIT 50");
    $rows = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $readers     = json_decode($row['is_read_by'] ?: '[]', true);
        $row['read'] = in_array($user['email'], $readers);
        unset($row['is_read_by']);
        $rows[] = $row;
    }
    api_response(['notifications' => $rows]);
    break;

// ── MARK NOTIFICATION READ ────────────────────────────────────────────────────
case 'mark_notification_read':
    $user = require_auth($conn);
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = intval($body['id'] ?? $_POST['id'] ?? $_GET['id'] ?? 0);
    if (!$id) api_error('Notification ID required');
    $q    = mysqli_query($conn, "SELECT is_read_by FROM notifications_tbl WHERE id=$id AND status=1 LIMIT 1");
    if (!$q || mysqli_num_rows($q) === 0) api_error('Notification not found', 404);
    $row     = mysqli_fetch_assoc($q);
    $readers = json_decode($row['is_read_by'] ?: '[]', true);
    if (!in_array($user['email'], $readers)) {
        $readers[] = $user['email'];
        $rj = mysqli_real_escape_string($conn, json_encode($readers));
        mysqli_query($conn, "UPDATE notifications_tbl SET is_read_by='$rj' WHERE id=$id");
    }
    api_response(['message' => 'Marked as read']);
    break;

// ── REFERRAL ──────────────────────────────────────────────────────────────────
case 'referral':
    $user = require_auth($conn);
    $em   = mysqli_real_escape_string($conn, $user['email']);
    $rq   = mysqli_query($conn, "SELECT u.sname, u.oname, u.email, u.date_join FROM referal_tbl rt JOIN users_tbl u ON u.email=(SELECT email FROM users_tbl WHERE MD5(email)=rt.referee LIMIT 1) WHERE rt.referal=(SELECT referal_token FROM users_tbl WHERE email='$em' LIMIT 1) ORDER BY rt.id DESC");
    $referred = [];
    while ($r = mysqli_fetch_assoc($rq)) $referred[] = $r;
    $tq   = mysqli_query($conn, "SELECT COALESCE(SUM(earn_amount),0) as total FROM referal_earn_transaction_tbl WHERE referal_email='$em'");
    $total= intval(mysqli_fetch_assoc($tq)['total'] ?? 0);
    api_response([
        'referral_code'  => $user['referal_token'],
        'referral_link'  => 'https://adildata.com.ng/easyfinder/dashboard/register?join_with_referal=' . $user['referal_token'],
        'total_earnings' => $total,
        'referred_users' => $referred,
    ]);
    break;

// ── CHANGE PASSWORD ───────────────────────────────────────────────────────────
case 'change_password':
    $user = require_auth($conn);
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $old  = trim($body['old_password'] ?? $_POST['old_password'] ?? '');
    $new  = trim($body['new_password'] ?? $_POST['new_password'] ?? '');
    if (empty($old) || empty($new)) api_error('old_password and new_password required');
    if (!password_verify($old, $user['password'])) api_error('Current password is incorrect');
    $hash = mysqli_real_escape_string($conn, password_hash($new, PASSWORD_DEFAULT));
    $em   = mysqli_real_escape_string($conn, $user['email']);
    mysqli_query($conn, "UPDATE users_tbl SET password='$hash' WHERE email='$em'");
    api_response(['message' => 'Password changed successfully']);
    break;

// ── CHANGE PIN ────────────────────────────────────────────────────────────────
case 'change_pin':
    $user = require_auth($conn);
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $old  = trim($body['old_pin'] ?? $_POST['old_pin'] ?? '');
    $new  = trim($body['new_pin'] ?? $_POST['new_pin'] ?? '');
    if (empty($old) || empty($new)) api_error('old_pin and new_pin required');
    if (md5($old) !== $user['pin']) api_error('Current PIN is incorrect');
    $newPin = mysqli_real_escape_string($conn, md5($new));
    $em     = mysqli_real_escape_string($conn, $user['email']);
    mysqli_query($conn, "UPDATE users_tbl SET pin='$newPin' WHERE email='$em'");
    api_response(['message' => 'PIN changed successfully']);
    break;

// ── SUBMIT KYC (BVN/NIN) ─────────────────────────────────────────────────────
case 'submit_kyc':
    $user = require_auth($conn);
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $bvn  = preg_replace('/\D/', '', trim($body['bvn'] ?? $_POST['bvn'] ?? ''));
    $nin  = preg_replace('/\D/', '', trim($body['nin'] ?? $_POST['nin'] ?? ''));
    if (empty($bvn) && empty($nin)) api_error('BVN or NIN is required');
    $em   = mysqli_real_escape_string($conn, $user['email']);
    $sets = [];

    if (!empty($bvn)) {
        if (strlen($bvn) !== 11) api_error('BVN must be exactly 11 digits');
        $bvnSafe = mysqli_real_escape_string($conn, $bvn);
        // FIX: Reject if this BVN is already linked to a DIFFERENT account
        $dup = mysqli_query($conn, "SELECT id FROM users_tbl WHERE bvn='$bvnSafe' AND email != '$em' LIMIT 1");
        if ($dup && mysqli_num_rows($dup) > 0) {
            api_error('This BVN is already linked to another account', 409);
        }
        $sets[] = "bvn='$bvnSafe'";
    }

    if (!empty($nin)) {
        if (strlen($nin) !== 11) api_error('NIN must be exactly 11 digits');
        $ninSafe = mysqli_real_escape_string($conn, $nin);
        // FIX: Reject if this NIN is already linked to a DIFFERENT account
        $dup = mysqli_query($conn, "SELECT id FROM users_tbl WHERE nin='$ninSafe' AND email != '$em' LIMIT 1");
        if ($dup && mysqli_num_rows($dup) > 0) {
            api_error('This NIN is already linked to another account', 409);
        }
        $sets[] = "nin='$ninSafe'";
    }

    if (empty($sets)) api_error('BVN and NIN must be 11 digits');
    mysqli_query($conn, "UPDATE users_tbl SET " . implode(', ', $sets) . " WHERE email='$em'");
    api_response(['message' => 'KYC submitted successfully']);
    break;

default:
    api_error("Unknown action: '$action'. Available: health, login, register, profile, wallet, wallet_history, transactions, dashboard_stats, funding_accounts, generate_payment_point, generate_monnify, verify_monnify, buy_airtime, buy_data, data_plans, notifications, mark_notification_read, referral, change_password, change_pin, submit_kyc", 404);
}

mysqli_close($conn);
?>
