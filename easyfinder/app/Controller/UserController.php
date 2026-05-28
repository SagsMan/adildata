<?php

namespace EduTech\Controller;

use \PDO;
use EduTech\C_Base;
use EduTech\SessionHelper\Session;
use EduTech\EmailNotification;
use SimpleValidator\Validator;

class UserController extends C_base
{
    public function Apply($ArrayForm)
    {
        if (!$this->GetUserId($_POST['email'])) {
            $password = password_hash(
                htmlspecialchars(trim($_POST['password'])),
                PASSWORD_DEFAULT
            );
            if (
                $this->data = parent::$db->run_insert(
                    'INSERT INTO users_tbl(sname,oname,password,email,phone,referal_token,pin,state) VALUES(:sname,:oname,:password,:email,:phone,:referal_token,:pin,:state)',
                    [
                        ':sname' => htmlspecialchars(trim($_POST['sname'])),
                        ':oname' => htmlspecialchars(trim($_POST['oname'])),
                        ':password' => $password,
                        ':email' => htmlspecialchars(trim($_POST['email'])),
                        ':phone' => htmlspecialchars(trim($_POST['phone'])),
                        ':referal_token' => md5(
                            htmlspecialchars(trim($_POST['email']))
                        ),
                        ':pin' => md5(htmlspecialchars(trim($_POST['pin']))),
                        ':state' => htmlspecialchars(trim($_POST['state'])),
                    ]
                )
            ) {
                if ($this->Create_Wallet_For_User($_POST['email'])) {
                    if (
                        isset($_POST['referal']) &&
                        !empty(strip_tags(htmlspecialchars($_POST['referal'])))
                    ) {
                        $this->Save_Referal_User(
                            strip_tags(htmlspecialchars($_POST['referal'])),
                            md5($_POST['email'])
                        );
                    }

                    $variables = [];
                    $variables['password'] = $_POST['password'];
                    $variables['email'] = $_POST['email'];
                    $variables['name'] =
                        $_POST['sname'] . ' ' . $_POST['oname'];

                    $template = file_get_contents(
                        '../dashboard/layout/signup_email_template.inc.html'
                    );

                    foreach ($variables as $key => $value) {
                        $template = str_replace(
                            '{{ ' . $key . ' }}',
                            $value,
                            $template
                        );
                    }
                    // EmailNotification::Send(
                    //     $_POST['email'],
                    //     $_POST['sname'],
                    //     'Welcome To TruedTech Digital Solution',
                    //     $template
                    // );
                    $_SESSION['Login_User'] = $_POST['email'];
                    return true;
                    exit();
                }
            }
            return false;
        }
    }

    public function createMonnifyAccount($auth)
    {
        $api_url = MONNIFY_BASE_URL;
        $api_contract = MONNIFY_API_CONTRACT;

        try {

            $token = $this->loginToMonnify();

            // Create Reserved Account
            $account_endpoint = "/api/v2/bank-transfer/reserved-accounts";
            $account_headers = [
                "Authorization: Bearer $token",
                "Content-Type: application/json",
            ];

            $reference = uniqid();
            $account_data = [
                "accountReference" => $reference,
                "accountName" => $auth->sname . "_" . rand(1111, 9999),
                "currencyCode" => "NGN",
                "contractCode" => $api_contract,
                "customerEmail" => $auth->email,
                "customerName" => $auth->sname . ' ' . $auth->oname,
                "getAllAvailableBanks" => true,
            ];

            if (!empty($auth->bvn)) {
                $account_data['bvn'] = $auth->bvn;
            } elseif (!empty($auth->nin)) {
                $account_data['nin'] = $auth->nin;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url . $account_endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($account_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $account_headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $account_response = curl_exec($ch);

            if (curl_errno($ch)) {
                error_log("Reserved account cURL error: " . curl_error($ch));
                throw new \LogicException("Failed to create reserved account.");
            }
            curl_close($ch);

            $account_data = json_decode($account_response, true);
            @file_put_contents('monnify_account.txt', $account_response . PHP_EOL, FILE_APPEND); 
            if ($account_data['requestSuccessful'] === false) {
                error_log("Account creation failed: " . $account_response);
                throw new \LogicException($account_data['responseMessage']);
            }

            if (empty($account_data['responseBody']['accounts'])) {
                error_log("Account creation failed: " . $account_response);
                throw new \LogicException("No accounts returned by Monnify.");
            }

            // Process Account Details
            $accounts = $account_data['responseBody']['accounts'];
            $account_details = [];
            foreach ($accounts as $account) {
                $account_details[] = implode(' - ', [
                    $account['bankName'],
                    $account['accountNumber'],
                    $account['accountName'],
                ]);
            }
            $all_account_details = implode(', ', $account_details);

            $this->data = parent::$db->run_insert(
                'UPDATE users_tbl SET monnify_account_details = :monnify_account_details WHERE email = :email',
                [
                    'monnify_account_details' => htmlspecialchars(trim($all_account_details)),
                    'email' => $auth->email
                ]
            );

            return [
                'success' => true,
                'account_details' => $all_account_details,
            ];

        } catch (\Exception $e) {
            // Handle exception
            error_log("Monnify API call failed: ". $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function loginToMonnify ()
    {
        $api_url = MONNIFY_BASE_URL;
        $api_key = MONNIFY_API_KEY;
        $api_secret = MONNIFY_API_SECRET;

        // Authenticate with Monnify API
        $auth_endpoint = "/api/v1/auth/login";
        $auth_headers = [
            "Content-Type: application/json",
            "Authorization: Basic " . base64_encode("$api_key:$api_secret"),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url . $auth_endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $auth_headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $auth_response = curl_exec($ch);

        if (curl_errno($ch)) {
            error_log("Authentication cURL error: " . curl_error($ch));
            throw new \LogicException("Failed to authenticate with Monnify.");
        }
        curl_close($ch);

        $auth_data = json_decode($auth_response, true);
        if (empty($auth_data['responseBody']['accessToken'])) {
            error_log("Authentication failed: " . $auth_response);
            throw new \LogicException("Invalid Monnify authentication response.");
        }

        return $auth_data['responseBody']['accessToken'];
    }

    public function Create_Wallet_For_User($email)
    {
        if (
            $this->data = parent::$db->run_insert(
                'INSERT INTO wallet_tbl(user_id) VALUES(?)',
                [$email]
            )
        ) {
            return true;
        }
    }

    public function Save_Referal_User($referal, $referee)
    {
        if (
            $this->data = parent::$db->run_insert(
                'INSERT INTO referal_tbl(referal,referee) VALUES(?,?)',
                [$referal, $referee]
            )
        ) {
            return true;
        }
    }

    public function GetUserId($email)
    {
        if (
            $this->data = parent::$db->run_select(
                'SELECT users_tbl.*, admin_role_tbl.role_name FROM users_tbl LEFT JOIN admin_role_tbl ON users_tbl.admin_role = admin_role_tbl.role WHERE users_tbl.email =:email LIMIT 1',
                [':email' => $email]
            )
        ) {
            return $this->data[0];
        }
    }

    public function LogInUser($arrayForm)
    {
        if ($row = $this->GetUserId($_POST['email'])) {
            if (password_verify($_POST['password'], $row->password)) {
                // Define session on successful login
                $_SESSION['Login_User'] = $row->email;
                return true;
            }
        }
    }

    public function forgot_password($arrayForm)
    {
        $token = password_hash(trim($_POST['email']), PASSWORD_DEFAULT);
        if (
            $this->data = parent::$db->run_insert(
                'UPDATE users_tbl SET token = ?, expired_token = 0 WHERE email =?',
                [$token, $_POST['email']]
            )
        ) {
            $variables = [];
            $variables['name'] = 'Costumer';
            $variables['email'] = $_POST['email'];
            $variables['token'] = $token;

            $template = file_get_contents(
                '../dashboard/layout/forgot_password_email_template.inc'
            );

            foreach ($variables as $key => $value) {
                $template = str_replace(
                    '{{ ' . $key . ' }}',
                    $value,
                    $template
                );
            }
            EmailNotification::Send(
                $_POST['email'],
                'Costumer',
                'Reset Login Password',
                $template
            );
            return true;
            exit();
        } else {
            return false;
        }
    }

    public function reset_password($arrayForm, $email, $token)
    {
        if ($row = $this->GetUserId($email)) {
            if (
                password_verify($email, $row->token) &&
                $row->expired_token == 0
            ) {
                $new_password = password_hash(
                    trim($_POST['password']),
                    PASSWORD_DEFAULT
                );
                if (
                    $this->data = parent::$db->run_insert(
                        'UPDATE users_tbl SET password = ?, expired_token = 1 WHERE email =?',
                        [trim($new_password), trim($email)]
                    )
                ) {
                    return true;
                    exit();
                } elseif (!empty($auth->nin)) {
                    return false;
                }
            }
        }
    }

    public function GatUserInfo($arrayForm) {}

    public function Get_Loggined_student_email_for_cookie_token($email)
    {
        if (
            $this->data = parent::$db->run_select(
                'SELECT email FROM users_tbl WHERE email=? LIMIT 1',
                [$email]
            )
        ) {
            $_SESSION['Login_User'] = $this->data[0]->email;
            return $_SESSION['Login_User'];
        } else {
            return false;
        }
    }

    function getTokenByUsername($email, $expired)
    {
        if (
            $this->data = parent::$db->run_select(
                'SELECT * FROM tbl_token_auth WHERE email=? AND is_expired=? LIMIT 1',
                [$email, $expired]
            )
        ) {
            return $this->data[0];
        } else {
            return false;
        }
    }
    function markAsExpired($tokenId)
    {
        $expired = 1;
        if (
            $this->data = parent::$db->run_select(
                'UPDATE tbl_token_auth SET is_expired =:is_expired WHERE id =:id',
                [$expired, $tokenId]
            )
        ) {
            return true;
        }
    }

    function submitKYC($data)
    {
        if ($data['nin'] == '' && $data['bvn'] == '') 
        {
            echo json_encode([
                'success' => false,
                'message' => 'BVN or NIN is required.'
            ]);
            return;
        }

        $fields = [];
    $params = ['email' => $data['email']];

    if (!empty($data['bvn'])) {
        $fields[] = 'bvn = :bvn';
        $params['bvn'] = $data['bvn'];
    }

    if (!empty($data['nin'])) {
        $fields[] = 'nin = :nin';
        $params['nin'] = $data['nin'];
    }

    $sql = 'UPDATE users_tbl SET ' . implode(', ', $fields) . ' WHERE email = :email';

    if (
        $this->data = parent::$db->run_insert(
            $sql,
            $params
        )
    ) {
        echo json_encode([
            'success' => true,
            'message' => 'KYC updated successfully.'
        ]);
        return;
    }

        echo json_encode([
            'success' => false,
            'message' => 'Failed to update KYC.'
        ]);
        return;
    }

    function insertToken(
        $email,
        $random_password_hash,
        $random_selector_hash,
        $expiry_date
    ) {
        if (
            parent::$db->run_insert(
                'INSERT INTO tbl_token_auth(email, password_hash, selector_hash, expiry_date) VALUES (?,?,?,?)',
                [
                    $email,
                    $random_password_hash,
                    $random_selector_hash,
                    $expiry_date,
                ]
            )
        ) {
            return true;
        }
    }

    public function getToken($length)
    {
        $token = '';
        $codeAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $codeAlphabet .= 'abcdefghijklmnopqrstuvwxyz';
        $codeAlphabet .= '0123456789';
        $max = strlen($codeAlphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->cryptoRandSecure(0, $max)];
        }
        return $token;
    }

    public function cryptoRandSecure($min, $max)
    {
        $range = $max - $min;
        if ($range < 1) {
            return $min; // not so random...
        }
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    }

    public function clearAuthCookie()
    {
        if (isset($_COOKIE['member_login'])) {
            setcookie('member_login', '');
        }
        if (isset($_COOKIE['random_password'])) {
            setcookie('random_password', '');
        }
        if (isset($_COOKIE['random_selector'])) {
            setcookie('random_selector', '');
        }
    }

    // Check if the Adminuser is already logged in
    public static function is_user_logged_in()
    {
        // Check if user session has been set
        if (
            isset($_SESSION['Login_User']) &&
            strlen($_SESSION['Login_User']) > 0
        ) {
            return true;
        }
    }
    // Log out user
    public function log_out_user()
    {
        // Destroy and unset active session
        session_destroy();
        unset($_SESSION['Login_User']);
        $this->clearAuthCookie();
        return true;
    }

    /**
     * Get user by API token (for mobile API auth)
     */
    public function GetUserByApiToken($token)
    {
        if (empty($token)) return null;
        if (
            $this->data = parent::$db->run_select(
                'SELECT * FROM users_tbl WHERE token = ? AND status = 1 LIMIT 1',
                [htmlspecialchars(trim($token))]
            )
        ) {
            return $this->data[0];
        }
        return null;
    }

}