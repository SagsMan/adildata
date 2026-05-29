<?php
require_once '../inc/user_session.inc.php';
$PAGE_TITLE = 'Notification API Settings';
$URL_NAME   = 'admin-notification-settings';

if (!in_array(1, explode(',', $Auth->admin_role)) && !in_array(2, explode(',', $Auth->admin_role))) {
    header('Location: ./'); exit;
}

$conn = mysqli_connect('localhost','adiliqgs_adildata','adildata2026','adiliqgs_adildata');

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin_notif_api_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function getSetting($conn, $key) {
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT setting_value FROM admin_notif_api_settings WHERE setting_key='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['setting_value'];
    return '';
}

function saveSetting($conn, $key, $value) {
    $k = mysqli_real_escape_string($conn, $key);
    $v = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT INTO admin_notif_api_settings (setting_key, setting_value) VALUES ('$k','$v') ON DUPLICATE KEY UPDATE setting_value='$v', updated_at=NOW()");
}

/* ── Handle form save ────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $fields = [
        'resend_api_key', 'resend_from_email', 'resend_from_name',
        'bulksms_api_token', 'bulksms_sender_id', 'bulksms_gateway',
        'sms_enabled', 'email_enabled'
    ];
    foreach ($fields as $f) {
        saveSetting($conn, $f, trim($_POST[$f] ?? ''));
    }
    array_push($SITE_SUCCESS, 'API settings saved successfully.');
}

/* ── Handle Resend test ──────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $key       = getSetting($conn, 'resend_api_key');
    $from_em   = getSetting($conn, 'resend_from_email') ?: 'onboarding@resend.dev';
    $from_name = getSetting($conn, 'resend_from_name') ?: 'Adildata';
    $test_to   = $Auth->email;

    if (empty($key)) {
        array_push($SITE_ERRORS, 'Resend API Key is not configured.');
    } else {
        $payload = json_encode([
            'from'    => "$from_name <$from_em>",
            'to'      => [$test_to],
            'subject' => 'Adildata — Test Email Notification',
            'html'    => '<div style="font-family:sans-serif;max-width:500px;margin:auto;padding:24px;border:1px solid #e5e5e5;border-radius:8px;"><h2 style="color:#10d596;">✅ Email Notifications Working!</h2><p>This is a test email from your <strong>Adildata</strong> Notifications system.</p><p style="color:#777;font-size:13px;">Sent via Resend API</p></div>'
        ]);
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$key, 'Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $res  = json_decode($resp, true);
        if ($code === 200 || $code === 201) {
            array_push($SITE_SUCCESS, 'Test email sent to ' . $test_to . ' successfully! ID: ' . ($res['id'] ?? ''));
        } else {
            array_push($SITE_ERRORS, 'Email test failed (HTTP '.$code.'): ' . ($res['message'] ?? $resp));
        }
    }
}

/* ── Handle Bulk SMS Nigeria test ────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_sms'])) {
    $api_token  = getSetting($conn, 'bulksms_api_token');
    $sender     = getSetting($conn, 'bulksms_sender_id') ?: 'Adildata';
    $gateway    = getSetting($conn, 'bulksms_gateway') ?: '0';
    $test_phone = preg_replace('/[^0-9]/', '', $Auth->phone ?? '');
    if (strlen($test_phone) === 11 && $test_phone[0] === '0') $test_phone = '234'.substr($test_phone,1);

    if (empty($api_token)) {
        array_push($SITE_ERRORS, 'Bulk SMS Nigeria API Token is not configured.');
    } elseif (empty($test_phone)) {
        array_push($SITE_ERRORS, 'No phone number found on your profile to test SMS.');
    } else {
        $payload = http_build_query([
            'api_token'     => $api_token,
            'from'          => $sender,
            'to'            => $test_phone,
            'body'          => 'Adildata Test: SMS notifications are working! Your Bulk SMS Nigeria is connected.',
            'gateway'       => $gateway,
            'append_sender' => '0',
        ]);
        $ch = curl_init('https://www.bulksmsnigeria.com/api/v1/sms/create');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded','Accept: application/json'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $res  = json_decode($resp, true);
        if ($code === 200 || $code === 201) {
            array_push($SITE_SUCCESS, 'Test SMS sent to ' . $test_phone . ' via Bulk SMS Nigeria successfully!');
        } else {
            array_push($SITE_ERRORS, 'SMS test failed (HTTP '.$code.'): ' . ($res['message'] ?? $resp));
        }
    }
}

/* ── Fetch wallet balance ─────────────────────────────────────────────────── */
$wallet_balance = null;
$wallet_error   = null;
$bulksms_token_set = getSetting($conn, 'bulksms_api_token');
if (!empty($bulksms_token_set)) {
    $wch = curl_init('https://www.bulksmsnigeria.com/api/v1/wallet?api_token=' . urlencode($bulksms_token_set));
    curl_setopt_array($wch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $wresp = curl_exec($wch);
    $wcode = curl_getinfo($wch, CURLINFO_HTTP_CODE);
    curl_close($wch);
    $wres = json_decode($wresp, true);
    if ($wcode === 200 && isset($wres['data']['wallet_balance'])) {
        $wallet_balance = number_format(floatval($wres['data']['wallet_balance']), 2);
    } else {
        $wallet_error = $wres['message'] ?? 'Could not fetch balance';
    }
}

/* ── Load current settings ───────────────────────────────────────────────── */
$s = [];
foreach (['resend_api_key','resend_from_email','resend_from_name','bulksms_api_token','bulksms_sender_id','bulksms_gateway','sms_enabled','email_enabled'] as $k) {
    $s[$k] = getSetting($conn, $k);
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <?php require_once 'layout/header-propt.inc.php'; ?>
  <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?></title>
  <style>
    .api-card { border-left: 4px solid #10d596; }
    .api-card.sms-card { border-left-color: #6f42c1; }
    .api-card.email-card { border-left-color: #17a2b8; }
    .key-input { font-family: monospace; letter-spacing: .5px; }
    .toggle-key { cursor:pointer; }
    .status-dot { width:10px;height:10px;border-radius:50%;display:inline-block; }
  </style>
</head>
<body>
<?php require_once 'layout/preloader.inc.php'; ?>
<div id="main-wrapper">
  <?php require_once 'layout/header.inc.php'; require_once 'layout/sidebar.inc.php'; ?>

  <div class="content-body">
    <?php include 'layout/minor-top-navbar.inc.php'; ?>
    <div class="container-fluid">

      <div class="row page-titles mx-0 mb-3">
        <div class="col-sm-6 p-md-0">
          <h4 style="color:#10d596;font-weight:700;"><i class="fa fa-cog mr-2"></i><?= $PAGE_TITLE ?></h4>
          <p class="text-muted mb-0">Configure API keys for Email and SMS notification channels</p>
        </div>
        <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex align-items-center">
          <a href="admin-notifications" class="btn btn-outline-secondary btn-sm mr-2">
            <i class="fa fa-arrow-left mr-1"></i> Dashboard
          </a>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="admin-notifications">Notifications</a></li>
            <li class="breadcrumb-item active">API Settings</li>
          </ol>
        </div>
      </div>

      <form method="POST">
        <div class="row">

          <!-- Email / Resend -->
          <div class="col-lg-6 mb-4">
            <div class="card api-card email-card">
              <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">
                  <i class="fa fa-envelope mr-2" style="color:#17a2b8;"></i>Email Notifications
                  <small class="text-muted ml-2" style="font-size:12px;">via Resend API</small>
                </h4>
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" id="emailToggle" name="email_enabled" value="1" <?= $s['email_enabled'] ? 'checked' : '' ?>>
                  <label class="custom-control-label" for="emailToggle"></label>
                </div>
              </div>
              <div class="card-body">
                <div class="alert alert-info py-2" style="font-size:12px;">
                  <i class="fa fa-info-circle mr-1"></i>
                  Get your API key at <a href="https://resend.com" target="_blank">resend.com</a> — free tier: 3,000 emails/month, 100/day.
                  Add your domain to send from your own email address.
                </div>

                <div class="form-group">
                  <label class="font-w600" style="font-size:13px;">Resend API Key <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="password" name="resend_api_key" id="resendKey" class="form-control key-input"
                      placeholder="re_xxxxxxxxxxxxxxxxxxxx"
                      value="<?= htmlspecialchars($s['resend_api_key']) ?>">
                    <div class="input-group-append">
                      <button type="button" class="btn btn-outline-secondary toggle-key" onclick="toggleField('resendKey')">
                        <i class="fa fa-eye"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label class="font-w600" style="font-size:13px;">From Email Address</label>
                  <input type="email" name="resend_from_email" class="form-control"
                    placeholder="notifications@yourdomain.com"
                    value="<?= htmlspecialchars($s['resend_from_email']) ?>">
                  <small class="text-muted">Must be a verified domain in Resend. Use <code>onboarding@resend.dev</code> for testing.</small>
                </div>

                <div class="form-group mb-0">
                  <label class="font-w600" style="font-size:13px;">From Name</label>
                  <input type="text" name="resend_from_name" class="form-control"
                    placeholder="Adildata"
                    value="<?= htmlspecialchars($s['resend_from_name'] ?: 'Adildata') ?>">
                </div>
              </div>
              <div class="card-footer d-flex justify-content-between align-items-center">
                <button type="submit" name="test_email" class="btn btn-outline-info btn-sm">
                  <i class="fa fa-paper-plane mr-1"></i> Send Test Email to <?= htmlspecialchars($Auth->email) ?>
                </button>
                <?php if (!empty($s['resend_api_key'])): ?>
                <span><span class="status-dot" style="background:#10d596;"></span> <small class="text-success">Configured</small></span>
                <?php else: ?>
                <span><span class="status-dot" style="background:#dc3545;"></span> <small class="text-danger">Not configured</small></span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- SMS / Bulk SMS Nigeria -->
          <div class="col-lg-6 mb-4">
            <div class="card api-card sms-card">
              <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">
                  <i class="fa fa-mobile mr-2" style="color:#6f42c1;"></i>SMS Notifications
                  <small class="text-muted ml-2" style="font-size:12px;">via Bulk SMS Nigeria</small>
                </h4>
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" id="smsToggle" name="sms_enabled" value="1" <?= $s['sms_enabled'] ? 'checked' : '' ?>>
                  <label class="custom-control-label" for="smsToggle"></label>
                </div>
              </div>
              <div class="card-body">

                <!-- Wallet Balance Widget -->
                <?php if (!empty($s['bulksms_api_token'])): ?>
                <div class="d-flex align-items-center justify-content-between mb-3 p-3 rounded"
                     style="background:linear-gradient(135deg,#6f42c1,#9b59b6);color:#fff;">
                  <div>
                    <div style="font-size:11px;opacity:.85;letter-spacing:.5px;">WALLET BALANCE</div>
                    <div style="font-size:26px;font-weight:700;line-height:1.2;">
                      <?php if ($wallet_balance !== null): ?>
                        ₦<?= $wallet_balance ?>
                      <?php else: ?>
                        <span style="font-size:14px;opacity:.8;"><?= htmlspecialchars($wallet_error ?? 'Unable to fetch') ?></span>
                      <?php endif; ?>
                    </div>
                    <div style="font-size:10px;opacity:.7;margin-top:2px;">Bulk SMS Nigeria Account</div>
                  </div>
                  <div>
                    <i class="fa fa-mobile" style="font-size:40px;opacity:.3;"></i>
                  </div>
                </div>
                <?php endif; ?>

                <div class="alert py-2" style="font-size:12px;background:#f3eeff;border:1px solid #6f42c1;border-radius:6px;">
                  <i class="fa fa-info-circle mr-1" style="color:#6f42c1;"></i>
                  Get your API token at <a href="https://www.bulksmsnigeria.com" target="_blank" style="color:#6f42c1;"><strong>bulksmsnigeria.com</strong></a> — Nigeria's most popular bulk SMS platform.
                  Register → Profile → API Token.
                </div>

                <div class="form-group">
                  <label class="font-w600" style="font-size:13px;">API Token <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="password" name="bulksms_api_token" id="bulksmsKey" class="form-control key-input"
                      placeholder="Your Bulk SMS Nigeria API Token"
                      value="<?= htmlspecialchars($s['bulksms_api_token']) ?>">
                    <div class="input-group-append">
                      <button type="button" class="btn btn-outline-secondary toggle-key" onclick="toggleField('bulksmsKey')">
                        <i class="fa fa-eye"></i>
                      </button>
                    </div>
                  </div>
                  <small class="text-muted">Found in your Bulk SMS Nigeria account under Profile → API Token</small>
                </div>

                <div class="form-group">
                  <label class="font-w600" style="font-size:13px;">Sender Name / ID</label>
                  <input type="text" name="bulksms_sender_id" class="form-control" maxlength="11"
                    placeholder="Adildata"
                    value="<?= htmlspecialchars($s['bulksms_sender_id'] ?: 'Adildata') ?>">
                  <small class="text-muted">Max 11 characters — appears as the sender name on recipients' phones.</small>
                </div>

                <div class="form-group mb-0">
                  <label class="font-w600" style="font-size:13px;">Gateway / Route</label>
                  <select name="bulksms_gateway" class="form-control">
                    <option value="0" <?= ($s['bulksms_gateway']??'0')==='0'?'selected':'' ?>>Generic Route (Standard)</option>
                    <option value="1" <?= ($s['bulksms_gateway']??'')==='1'?'selected':'' ?>>DND Route (reaches DND numbers)</option>
                  </select>
                  <small class="text-muted">Use DND route to reach numbers on the Do-Not-Disturb registry.</small>
                </div>
              </div>
              <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                <button type="submit" name="test_sms" class="btn btn-sm" style="border:1px solid #6f42c1;color:#6f42c1;">
                  <i class="fa fa-mobile mr-1"></i> Send Test SMS to your phone
                </button>
                <?php if (!empty($s['bulksms_api_token'])): ?>
                <span><span class="status-dot" style="background:#10d596;"></span> <small class="text-success">Configured</small></span>
                <?php else: ?>
                <span><span class="status-dot" style="background:#dc3545;"></span> <small class="text-danger">Not configured</small></span>
                <?php endif; ?>
              </div>
            </div>
          </div>

        </div>

        <!-- Save Button -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="card">
              <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <h6 class="mb-1 font-w600">Save All Settings</h6>
                    <p class="mb-0 text-muted" style="font-size:12px;">Changes are applied immediately to all future notifications.</p>
                  </div>
                  <button type="submit" name="save_settings" class="btn btn-primary px-4"
                    style="background:#10d596;border-color:#10d596;">
                    <i class="fa fa-save mr-2"></i> Save Settings
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

      </form>

      <!-- How It Works -->
      <div class="row">
        <div class="col-md-4 mb-3">
          <div class="card h-100">
            <div class="card-body">
              <h6 style="color:#10d596;" class="font-w700"><i class="fa fa-bell mr-2"></i>In-App Notifications</h6>
              <p style="font-size:13px;" class="text-muted mb-0">Always available — no API key needed. Notifications appear in the bell icon on the dashboard for all logged-in users.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card h-100">
            <div class="card-body">
              <h6 style="color:#17a2b8;" class="font-w700"><i class="fa fa-envelope mr-2"></i>Email via Resend</h6>
              <p style="font-size:13px;" class="text-muted mb-0">Sends beautiful HTML emails to all registered users' email addresses. Requires a Resend account and API key.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card h-100">
            <div class="card-body">
              <h6 style="color:#6f42c1;" class="font-w700"><i class="fa fa-mobile mr-2"></i>SMS via Bulk SMS Nigeria</h6>
              <p style="font-size:13px;" class="text-muted mb-0">Sends SMS to all users with Nigerian phone numbers. Auto-converts 08XXXXXXXXX → 2348XXXXXXXXX. Wallet balance shows live on this page.</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
  <?php require_once 'layout/footer-propt.inc.php'; ?>
</div>

<script>
function toggleField(id) {
  var f = document.getElementById(id);
  f.type = f.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
