<?php
/**
 * Adildata REST API — Mobile App Backend
 * Base URL: https://api.adildata.com.ng/api.php?action=XXX
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── Resolve config path (works whether api.php is in its own dir or public_html) ──
$config_candidates = [
    __DIR__ . '/inc/config.inc.php',
    __DIR__ . '/../public_html/easyfinder/inc/config.inc.php',
    __DIR__ . '/easyfinder/inc/config.inc.php',
];
$config_loaded = false;
foreach ($config_candidates as $path) {
    if (file_exists($path)) {
        require_once $path;
        $config_loaded = true;
        break;
    }
}
if (!$config_loaded) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'API config not found']);
    exit;
}

$action = strtolower(trim($_GET['action'] ?? $_POST['action'] ?? ''));
$method = $_SERVER['REQUEST_METHOD'];

// ── Shared DB connection helper ───────────────────────────────────────────────
function db_connect() {
    $conn = mysqli_connect('localhost', 'adiliqgs_adildata', 'adildata2026', 'adiliqgs_adildata');
    if (!$conn) {
        http_response_code(503);
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        exit;
    }
    return $conn;
}

// ── API Token Auth helper ─────────────────────────────────────────────────────
function api_auth() {
    global $UserAuth;
    $token = $_SERVER['HTTP_X_API_TOKEN'] ?? $_GET['token'] ?? $_POST['token'] ?? '';
    if (empty($token)) return null;
    $user = $UserAuth->GetUserByApiToken($token);
    return $user ?: null;
}

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

// ─────────────────────────────────────────────────────────────────────────────
switch ($action) {

// ── AUTH ──────────────────────────────────────────────────────────────────────
case 'login':
    if ($method !== 'POST') api_error('POST required', 405);
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (empty($email) || empty($password)) api_error('Email and password required');

    $conn = db_connect();
    $em   = mysqli_real_escape_string($conn, $email);
    $r    = mysqli_query($conn, "SELECT * FROM users_tbl WHERE email = '$em' AND status = 1 LIMIT 1");
    if (!$r || mysqli_num_rows($r) === 0) api_error('Invalid credentials', 401);
    $user = mysqli_fetch_assoc($r);

    if (!password_verify($password, $user['password'])) api_error('Invalid credentials', 401);

    // Generate or refresh API token
    $api_token = bin2hex(random_bytes(32));
    $tokenSafe = mysqli_real_escape_string($conn, $api_token);
    mysqli_query($conn, "UPDATE users_tbl SET token = '$tokenSafe' WHERE id = " . intval($user['id']));
    mysqli_close($conn);

    api_response([
        'token'      => $api_token,
        'id'         => $user['id'],
        'email'      => $user['email'],
        'sname'      => $user['sname'],
        'oname'      => $user['oname'],
        'phone'      => $user['phone'],
        'admin_role' => $user['admin_role'],
    ]);
    break;

// ── REGISTER ──────────────────────────────────────────────────────────────────
case 'register':
    if ($method !== 'POST') api_error('POST required', 405);
    $required = ['email','password','sname','oname','phone','pin','state'];
    foreach ($required as $f) {
        if (empty(trim($_POST[$f] ?? ''))) api_error("$f is required");
    }
    if ($UserAuth->Apply($_POST)) {
        api_response(['message' => 'Registration successful. Please login.']);
    } else {
        api_error('Email already registered or invalid data');
    }
    break;

// ── WALLET BALANCE ────────────────────────────────────────────────────────────
case 'wallet':
    $user = api_auth();
    if (!$user) api_error('Unauthorized', 401);
    $balance = $WalletController->Get_Single_User_Wallet_Balance($user->email);
    api_response(['balance' => $balance ? intval($balance->balance) : 0, 'email' => $user->email]);
    break;

// ── WALLET HISTORY ────────────────────────────────────────────────────────────
case 'wallet_history':
    $user = api_auth();
    if (!$user) api_error('Unauthorized', 401);
    $role = $user->admin_role ?: '';
    $history = $WalletController->Get_Wallet_Money_Trans($user->email, $role);
    api_response(['transactions' => $history ?: []]);
    break;

// ── NOTIFICATIONS ─────────────────────────────────────────────────────────────
case 'notifications':
    $user = api_auth();
    if (!$user) api_error('Unauthorized', 401);
    $conn     = db_connect();
    $em       = mysqli_real_escape_string($conn, $user->email);
    $r        = mysqli_query($conn, "SELECT * FROM notifications_tbl WHERE status = 1 AND (target = 'all' OR target_email = '$em') ORDER BY id DESC LIMIT 50");
    $notifs   = [];
    while ($row = mysqli_fetch_assoc($r)) {
        $readers     = json_decode($row['is_read_by'] ?: '[]', true);
        $row['read'] = in_array($user->email, $readers);
        unset($row['is_read_by']);
        $notifs[] = $row;
    }
    mysqli_close($conn);
    api_response(['notifications' => $notifs]);
    break;

// ── MARK NOTIFICATION READ ────────────────────────────────────────────────────
case 'mark_notification_read':
    $user = api_auth();
    if (!$user) api_error('Unauthorized', 401);
    $id   = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    if (!$id) api_error('Notification ID required');
    $conn = db_connect();
    $r    = mysqli_query($conn, "SELECT is_read_by FROM notifications_tbl WHERE id = $id AND status = 1 LIMIT 1");
    if (!$r || mysqli_num_rows($r) === 0) api_error('Notification not found', 404);
    $row     = mysqli_fetch_assoc($r);
    $readers = json_decode($row['is_read_by'] ?: '[]', true);
    if (!in_array($user->email, $readers)) {
        $readers[] = $user->email;
        $rj = mysqli_real_escape_string($conn, json_encode($readers));
        mysqli_query($conn, "UPDATE notifications_tbl SET is_read_by = '$rj' WHERE id = $id");
    }
    mysqli_close($conn);
    api_response(['message' => 'Marked as read']);
    break;

// ── REFERRAL INFO ─────────────────────────────────────────────────────────────
case 'referral':
    $user = api_auth();
    if (!$user) api_error('Unauthorized', 401);
    $role    = $user->admin_role ?: '';
    $history = $WalletController->Get_User_Earn_History($user->email, $role);
    $conn    = db_connect();
    $em      = mysqli_real_escape_string($conn, $user->email);
    $referred_r = mysqli_query($conn,
        "SELECT u.sname, u.oname, u.email, u.date_join FROM referal_tbl rt
         JOIN users_tbl u ON u.email = (SELECT email FROM users_tbl WHERE MD5(email) = rt.referee LIMIT 1)
         WHERE rt.referal = (SELECT referal_token FROM users_tbl WHERE email = '$em' LIMIT 1)
         ORDER BY rt.id DESC"
    );
    $referred = [];
    while ($row = mysqli_fetch_assoc($referred_r)) { $referred[] = $row; }
    $total_r = mysqli_query($conn,
        "SELECT COALESCE(SUM(earn_amount),0) as total FROM referal_earn_transaction_tbl WHERE referal_email = '$em'"
    );
    $total = mysqli_fetch_assoc($total_r)['total'] ?? 0;
    mysqli_close($conn);
    api_response([
        'referral_code' => $user->referal_token,
        'referral_link' => SITE_URL . 'easyfinder/dashboard/register?join_with_referal=' . $user->referal_token,
        'total_earnings' => intval($total),
        'referred_users' => $referred,
        'earn_history'   => $history ?: [],
    ]);
    break;

// ── PROFILE ───────────────────────────────────────────────────────────────────
case 'profile':
    $user = api_auth();
    if (!$user) api_error('Unauthorized', 401);
    api_response([
        'id'         => $user->id,
        'email'      => $user->email,
        'sname'      => $user->sname,
        'oname'      => $user->oname,
        'phone'      => $user->phone,
        'state'      => $user->state,
        'admin_role' => $user->admin_role,
        'super_admin'=> $user->super_admin,
        'referral_code' => $user->referal_token,
        'referral_link' => SITE_URL . 'easyfinder/dashboard/register?join_with_referal=' . $user->referal_token,
    ]);
    break;

// ── HEALTHCHECK ───────────────────────────────────────────────────────────────
case 'health':
case 'ping':
    api_response(['message' => 'Adildata API is running', 'version' => '1.0', 'time' => date('Y-m-d H:i:s')]);
    break;

default:
    api_error("Unknown action: '$action'. Available: login, register, wallet, wallet_history, notifications, mark_notification_read, referral, profile, health", 404);
}
?>
