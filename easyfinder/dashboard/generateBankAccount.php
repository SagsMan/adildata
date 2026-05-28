<?php
/**
 * generateBankAccount.php
 * Called via AJAX or direct POST to generate a Monnify reserved account.
 * Requires user to be logged in.
 */
require_once '../inc/user_session.inc.php';

header('Content-Type: application/json');

if (!isset($Auth) || empty($Auth->email)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// If BVN provided, save it first
if (!empty(trim($_POST['bvn'] ?? ''))) {
    $conn_tmp = mysqli_connect('localhost', 'adiliqgs_adildata', 'adildata2026', 'adiliqgs_adildata');
    if ($conn_tmp) {
        $bvn_clean = preg_replace('/\D/', '', trim($_POST['bvn']));
        if (strlen($bvn_clean) === 11) {
            $es = mysqli_real_escape_string($conn_tmp, $Auth->email);
            $bvn_s = mysqli_real_escape_string($conn_tmp, $bvn_clean);
            mysqli_query($conn_tmp, "UPDATE users_tbl SET bvn='$bvn_s' WHERE email='$es'");
            $Auth->bvn = $bvn_clean;
        }
        mysqli_close($conn_tmp);
    }
}

// If user already has monnify account, return it
$fresh = $UserAuth->GetUserId($Auth->email);
if (!empty($fresh->monnify_account_details)) {
    echo json_encode([
        'success' => true,
        'message' => 'existing',
        'account_details' => $fresh->monnify_account_details,
    ]);
    exit;
}

// Generate new Monnify account via UserController
$result = $UserAuth->createMonnifyAccount($Auth);
echo json_encode($result);
