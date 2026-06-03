<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once 'conn.php';
require_once 'generateBankAccount.php';

$data = json_decode(file_get_contents("php://input"), true);

$response = ["success" => false];

if (empty($data['token'])) {
    $response['message'] = "Token required";
    echo json_encode($response);
    exit;
}

$incomingToken = $data['token'];

// Verify token — fast direct indexed lookup (O(1) instead of full table scan)
// FIX: Old code fetched ALL users and ran password_verify() on each row, which
//      caused 30-60 s delays. Tokens are now plain hex strings, so WHERE token=?
//      is instant. Legacy bcrypt tokens are handled via a fallback below.
$ts    = mysqli_real_escape_string($conn, $incomingToken);
$query = mysqli_query($conn,
    "SELECT email, sname, oname, phone, token FROM users_tbl
     WHERE token = '$ts' AND status = 1 LIMIT 1");

$email    = null;
$fullName = null;
$phone    = null;

if ($query && mysqli_num_rows($query) > 0) {
    $row      = mysqli_fetch_assoc($query);
    $email    = $row['email'];
    $fullName = $row['sname'] . " " . $row['oname'];
    $phone    = $row['phone'];
} else {
    // Legacy fallback: bcrypt-hashed tokens from old login code
    // Only reached until the user logs in again and gets a plain token.
    $q2 = mysqli_query($conn,
        "SELECT email, sname, oname, phone, token FROM users_tbl
         WHERE token IS NOT NULL AND token != '' AND status = 1");
    while ($row = mysqli_fetch_assoc($q2)) {
        if (password_verify($incomingToken, $row['token'])) {
            $email    = $row['email'];
            $fullName = $row['sname'] . " " . $row['oname'];
            $phone    = $row['phone'];
            break;
        }
    }
}

// Invalid token
if (!$email) {
    $response['message'] = "Invalid token";
    echo json_encode($response);
    exit;
}

// Check if account already exists
$stmt = $conn->prepare("SELECT acc_no, bank_name, acc_name FROM users_tbl WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();

if (!empty($user['acc_no'])) {
    // Already exists — return it directly
    $response['success']        = true;
    $response['account_number'] = $user['acc_no'];
    $response['bank_name']      = $user['bank_name'];
    $response['account_name']   = $user['acc_name'];
    echo json_encode($response);
    exit;
}

// Create new account
$create = generateBankAccount($email, $fullName, $phone);

if (!$create['success']) {
    $response['message'] = $create['message'];
    echo json_encode($response);
    exit;
}

// Fetch again after creation
$stmt = $conn->prepare("SELECT acc_no, bank_name, acc_name FROM users_tbl WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();

// Return new account
$response['success']        = true;
$response['account_number'] = $user['acc_no'];
$response['bank_name']      = $user['bank_name'];
$response['account_name']   = $user['acc_name'];

echo json_encode($response);
?>
