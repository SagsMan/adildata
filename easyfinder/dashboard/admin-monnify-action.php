<?php
/**
 * admin-monnify-action.php — AJAX endpoint for Monnify account generation
 * Only called via fetch() from admin-monnify-users.php
 */
require_once '../inc/user_session.inc.php';

// Must be admin
$adminRoles = array_map('trim', explode(',', $Auth->admin_role ?? ''));
if (!in_array('1', $adminRoles) && !in_array(1, $adminRoles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$conn   = mysqli_connect('localhost', 'adiliqgs_adildata', 'adildata2026', 'adiliqgs_adildata');

// ── Generate for ONE user ────────────────────────────────────────────────────
if ($action === 'gen_one') {
    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'No email provided']);
        exit;
    }
    $user = $UserAuth->GetUserId($email);
    if (!$user || empty($user->email)) {
        echo json_encode(['success' => false, 'message' => "User not found: $email"]);
        exit;
    }
    if (!empty($user->monnify_account_details)) {
        echo json_encode(['success' => true, 'message' => "Already has a Monnify account", 'already' => true]);
        exit;
    }
    $result = $UserAuth->createMonnifyAccount($user);
    if ($result['success'] ?? false) {
        echo json_encode(['success' => true, 'message' => "Monnify account created for $email", 'details' => $result['account_details'] ?? '']);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Failed to create account']);
    }
    exit;
}

// ── Bulk generate (up to 20) ─────────────────────────────────────────────────
if ($action === 'gen_all') {
    $qAll = mysqli_query($conn,
        "SELECT email FROM users_tbl
         WHERE (monnify_account_details IS NULL OR monnify_account_details='')
           AND ((bvn IS NOT NULL AND bvn!='') OR (nin IS NOT NULL AND nin!=''))
         LIMIT 20"
    );
    $ok = 0; $fail = 0; $msgs = [];
    while ($row = mysqli_fetch_assoc($qAll)) {
        $tu = $UserAuth->GetUserId($row['email']);
        if ($tu) {
            $r = $UserAuth->createMonnifyAccount($tu);
            if ($r['success'] ?? false) { $ok++; }
            else { $fail++; $msgs[] = $row['email'] . ': ' . ($r['message'] ?? 'error'); }
        }
    }
    echo json_encode([
        'success' => true,
        'message' => "Bulk done: $ok created, $fail failed.",
        'ok'      => $ok,
        'fail'    => $fail,
        'errors'  => $msgs
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
