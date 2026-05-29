<?php
require_once '../inc/user_session.inc.php';
$PAGE_TITLE = 'Create Notification';
$URL_NAME   = 'admin-notification-create';

if (!in_array(1, explode(',', $Auth->admin_role)) && !in_array(2, explode(',', $Auth->admin_role))) {
    header('Location: ./'); exit;
}

$conn = mysqli_connect('localhost','adiliqgs_adildata','adildata2026','adiliqgs_adildata');

/* ── Ensure tables exist ─────────────────────────────────────────────────── */
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin_notifications_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notif_type ENUM('important','update','promotion','system_alert','general') DEFAULT 'general',
    priority ENUM('low','medium','high') DEFAULT 'medium',
    target ENUM('all','specific') DEFAULT 'all',
    target_email VARCHAR(255) NULL,
    status ENUM('draft','pending','sent','failed') DEFAULT 'draft',
    channels VARCHAR(100) DEFAULT 'inapp',
    scheduled_at DATETIME NULL,
    sent_at DATETIME NULL,
    created_by VARCHAR(255) NULL,
    total_recipients INT DEFAULT 0,
    delivered_count INT DEFAULT 0,
    read_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    email_sent INT DEFAULT 0,
    sms_sent INT DEFAULT 0,
    legacy_notif_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin_notif_delivery_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    user_id INT NULL,
    user_name VARCHAR(255) NULL,
    user_email VARCHAR(255) NULL,
    user_phone VARCHAR(50) NULL,
    delivery_status ENUM('pending','sent','delivered','failed','read') DEFAULT 'sent',
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    read_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_id (notification_id),
    INDEX idx_user_email (user_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin_notif_api_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── Helpers ─────────────────────────────────────────────────────────────── */
function getSetting($conn, $key) {
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT setting_value FROM admin_notif_api_settings WHERE setting_key='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['setting_value'];
    return '';
}

function formatNgPhone($p) {
    $p = preg_replace('/[^0-9]/', '', $p);
    if (strlen($p) < 7) return '';
    if (strlen($p) === 11 && $p[0] === '0') return '234'.substr($p,1);
    if (substr($p,0,3) === '234' && strlen($p) >= 13) return $p;
    if (strlen($p) === 10) return '234'.$p;
    return $p;
}

function sendEmailsViaResend($conn, $notif_id, $title, $message, $ntype, $target, $target_email_only) {
    $api_key   = getSetting($conn, 'resend_api_key');
    $from_em   = getSetting($conn, 'resend_from_email') ?: 'onboarding@resend.dev';
    $from_name = getSetting($conn, 'resend_from_name') ?: 'Adildata';
    if (empty($api_key)) return ['sent'=>0,'failed'=>0,'error'=>'Resend API key not configured'];

    $type_colors = ['important'=>'#ffc107','update'=>'#10d596','promotion'=>'#6f42c1','system_alert'=>'#dc3545','general'=>'#17a2b8'];
    $color = $type_colors[$ntype] ?? '#10d596';

    $html_template = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;border:1px solid #e5e5e5;border-radius:8px;overflow:hidden;">'
        . '<div style="background:'.$color.';padding:20px 24px;">'
        . '<h2 style="color:#fff;margin:0;font-size:20px;">'.htmlspecialchars($title).'</h2>'
        . '</div>'
        . '<div style="padding:24px;">'
        . '<p style="font-size:15px;color:#333;line-height:1.6;">'.nl2br(htmlspecialchars($message)).'</p>'
        . '</div>'
        . '<div style="background:#f8f9fa;padding:14px 24px;border-top:1px solid #e5e5e5;">'
        . '<p style="font-size:12px;color:#999;margin:0;">This notification was sent from <strong>Adildata</strong>. © '.date('Y').'</p>'
        . '</div></div>';

    if ($target === 'specific' && $target_email_only) {
        $emails = [$target_email_only];
    } else {
        $ur = mysqli_query($conn, "SELECT email FROM users_tbl WHERE email IS NOT NULL AND email != '' ORDER BY id ASC");
        $emails = [];
        if ($ur) { while ($ue = mysqli_fetch_row($ur)) $emails[] = $ue[0]; }
    }

    $sent = 0; $failed = 0;
    /* Batch in chunks of 50 */
    $chunks = array_chunk($emails, 50);
    foreach ($chunks as $chunk) {
        $batch = [];
        foreach ($chunk as $em) {
            $batch[] = [
                'from'    => $from_name.' <'.$from_em.'>',
                'to'      => [$em],
                'subject' => $title,
                'html'    => $html_template,
            ];
        }
        $ch = curl_init('https://api.resend.com/emails/batch');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($batch),
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$api_key, 'Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 || $code === 201) {
            $sent += count($chunk);
        } else {
            $failed += count($chunk);
        }
    }
    mysqli_query($conn, "UPDATE admin_notifications_tbl SET email_sent=$sent WHERE id=$notif_id");
    return ['sent'=>$sent,'failed'=>$failed];
}

function sendSMSViaTermii($conn, $notif_id, $title, $message, $target, $target_email_only) {
    $api_key  = getSetting($conn, 'termii_api_key');
    $sender   = getSetting($conn, 'termii_sender_id') ?: 'Adildata';
    $channel  = getSetting($conn, 'termii_channel') ?: 'generic';
    if (empty($api_key)) return ['sent'=>0,'failed'=>0,'error'=>'Termii API key not configured'];

    $sms_text = strip_tags($title)."\n".strip_tags($message);
    if (strlen($sms_text) > 160) $sms_text = substr($sms_text, 0, 157).'...';

    if ($target === 'specific' && $target_email_only) {
        $pr = mysqli_query($conn, "SELECT phone FROM users_tbl WHERE email='".mysqli_real_escape_string($conn,$target_email_only)."' LIMIT 1");
        $phones = [];
        if ($pr && $prow = mysqli_fetch_assoc($pr)) $phones[] = $prow['phone'];
    } else {
        $pr = mysqli_query($conn, "SELECT phone FROM users_tbl WHERE phone IS NOT NULL AND phone != '' ORDER BY id ASC");
        $phones = [];
        if ($pr) { while ($prow = mysqli_fetch_row($pr)) $phones[] = $prow[0]; }
    }

    /* Normalise to Nigerian international format */
    $valid_phones = [];
    foreach ($phones as $ph) {
        $formatted = formatNgPhone($ph);
        if ($formatted && strlen($formatted) >= 12) $valid_phones[] = $formatted;
    }

    if (empty($valid_phones)) return ['sent'=>0,'failed'=>0,'error'=>'No valid phone numbers found'];

    $sent = 0; $failed = 0;
    $chunks = array_chunk($valid_phones, 100);
    foreach ($chunks as $chunk) {
        $payload = json_encode([
            'to'      => $chunk,
            'from'    => $sender,
            'sms'     => $sms_text,
            'type'    => 'plain',
            'channel' => $channel,
            'api_key' => $api_key,
        ]);
        $ch = curl_init('https://api.ng.termii.com/api/sms/send/bulk');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $res  = json_decode($resp, true);
        if ($code === 200 && isset($res['message_id'])) {
            $sent += count($chunk);
        } else {
            $failed += count($chunk);
        }
    }
    mysqli_query($conn, "UPDATE admin_notifications_tbl SET sms_sent=$sent WHERE id=$notif_id");
    return ['sent'=>$sent,'failed'=>$failed];
}

/* ── Load edit data ──────────────────────────────────────────────────────── */
$edit_id   = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = null;
if ($edit_id > 0) {
    $er = mysqli_query($conn, "SELECT * FROM admin_notifications_tbl WHERE id=$edit_id AND status IN('draft','failed') LIMIT 1");
    if ($er) $edit_data = mysqli_fetch_assoc($er);
    if ($edit_data) $PAGE_TITLE = 'Edit Notification';
}

$user_count_r = mysqli_query($conn, "SELECT COUNT(*) FROM users_tbl");
$user_count   = $user_count_r ? intval(mysqli_fetch_row($user_count_r)[0]) : 0;

/* ── API keys configured? ────────────────────────────────────────────────── */
$resend_ready = !empty(getSetting($conn, 'resend_api_key'));
$termii_ready = !empty(getSetting($conn, 'termii_api_key'));

/* ── Handle form submission ──────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['form_action'] ?? 'draft';
    $title    = trim($_POST['title'] ?? '');
    $message  = trim($_POST['message'] ?? '');
    $ntype    = in_array($_POST['notif_type']??'', ['important','update','promotion','system_alert','general']) ? $_POST['notif_type'] : 'general';
    $priority = in_array($_POST['priority']??'', ['low','medium','high']) ? $_POST['priority'] : 'medium';
    $target   = ($_POST['target']??'') === 'specific' ? 'specific' : 'all';
    $target_email_only = ($target === 'specific' && !empty($_POST['target_email'])) ? trim($_POST['target_email']) : null;
    $admin_em = $Auth->email;

    /* Channels */
    $ch_inapp = !empty($_POST['ch_inapp']) ? 1 : 0;
    $ch_email = !empty($_POST['ch_email']) ? 1 : 0;
    $ch_sms   = !empty($_POST['ch_sms'])   ? 1 : 0;
    if (!$ch_inapp && !$ch_email && !$ch_sms) $ch_inapp = 1;
    $channels_str = implode(',', array_filter(['inapp'=>$ch_inapp?'inapp':null,'email'=>$ch_email?'email':null,'sms'=>$ch_sms?'sms':null]));

    $sched_sql  = 'NULL';
    $status_val = 'draft';
    if ($action === 'send_now') {
        $status_val = 'sent';
    } elseif ($action === 'schedule' && !empty($_POST['scheduled_at'])) {
        $sched_sql  = "'".date('Y-m-d H:i:s', strtotime($_POST['scheduled_at']))."'";
        $status_val = 'pending';
    }

    if (empty($title) || empty($message)) {
        array_push($SITE_ERRORS, 'Title and Message are required.');
    } else {
        $title_e  = mysqli_real_escape_string($conn, htmlspecialchars($title));
        $msg_e    = mysqli_real_escape_string($conn, $message);
        $te_sql   = $target_email_only ? "'".mysqli_real_escape_string($conn,$target_email_only)."'" : 'NULL';
        $admin_e  = mysqli_real_escape_string($conn, $admin_em);
        $chan_e   = mysqli_real_escape_string($conn, $channels_str);

        $notif_id = 0;

        if ($edit_id > 0 && $edit_data) {
            mysqli_query($conn,
                "UPDATE admin_notifications_tbl SET title='$title_e', message='$msg_e', notif_type='$ntype',
                 priority='$priority', target='$target', target_email=$te_sql, status='$status_val',
                 channels='$chan_e', scheduled_at=$sched_sql, updated_at=NOW() WHERE id=$edit_id");
            $notif_id = $edit_id;
        } else {
            mysqli_query($conn,
                "INSERT INTO admin_notifications_tbl (title,message,notif_type,priority,target,target_email,status,channels,scheduled_at,created_by)
                 VALUES('$title_e','$msg_e','$ntype','$priority','$target',$te_sql,'$status_val','$chan_e',$sched_sql,'$admin_e')");
            $notif_id = intval(mysqli_insert_id($conn));
        }

        if ($notif_id > 0 && $action === 'send_now') {
            /* ── Live send to all users ── */
            $users_r = ($target === 'all')
                ? mysqli_query($conn, "SELECT id, fname, lname, email, phone FROM users_tbl")
                : mysqli_query($conn, "SELECT id, fname, lname, email, phone FROM users_tbl WHERE email='".mysqli_real_escape_string($conn,$target_email_only)."' LIMIT 1");

            $total = 0;
            if ($users_r) {
                while ($u = mysqli_fetch_assoc($users_r)) {
                    $ue  = mysqli_real_escape_string($conn, $u['email']);
                    $un  = mysqli_real_escape_string($conn, trim(($u['fname']??'').' '.($u['lname']??'')));
                    $up  = mysqli_real_escape_string($conn, $u['phone'] ?? '');
                    $uid = intval($u['id']);
                    mysqli_query($conn, "INSERT INTO admin_notif_delivery_tbl
                        (notification_id,user_id,user_name,user_email,user_phone,delivery_status,sent_at,delivered_at)
                        VALUES($notif_id,$uid,'$un','$ue','$up','delivered',NOW(),NOW())");
                    $total++;
                }
            }

            /* In-app: insert into legacy notifications_tbl */
            if ($ch_inapp) {
                $map  = ['important'=>'warning','update'=>'info','promotion'=>'success','system_alert'=>'danger','general'=>'info'];
                $ltype = $map[$ntype] ?? 'info';
                $lt   = mysqli_real_escape_string($conn, strip_tags($title));
                $lm   = mysqli_real_escape_string($conn, strip_tags($message));
                $ltgt = ($target === 'all') ? 'all' : 'specific';
                $ins  = mysqli_query($conn, "INSERT INTO notifications_tbl(title,message,type,target,target_email,created_by,status) VALUES('$lt','$lm','$ltype','$ltgt',$te_sql,'$admin_e',1)");
                $legacy_id = $ins ? intval(mysqli_insert_id($conn)) : 'NULL';
            } else {
                $legacy_id = 'NULL';
            }

            mysqli_query($conn, "UPDATE admin_notifications_tbl SET status='sent', sent_at=NOW(), total_recipients=$total, delivered_count=$total, legacy_notif_id=$legacy_id WHERE id=$notif_id");

            $summary = "In-App notification sent to $total user(s)!";

            /* Email channel */
            if ($ch_email) {
                $er = sendEmailsViaResend($conn, $notif_id, $title, $message, $ntype, $target, $target_email_only);
                if (isset($er['error'])) {
                    array_push($SITE_ERRORS, 'Email: '.$er['error']);
                } else {
                    $summary .= " Email sent: {$er['sent']}.";
                    if ($er['failed'] > 0) array_push($SITE_ERRORS, "Email: {$er['failed']} failed to send.");
                }
            }

            /* SMS channel */
            if ($ch_sms) {
                $sr = sendSMSViaTermii($conn, $notif_id, $title, $message, $target, $target_email_only);
                if (isset($sr['error'])) {
                    array_push($SITE_ERRORS, 'SMS: '.$sr['error']);
                } else {
                    $summary .= " SMS sent: {$sr['sent']}.";
                    if ($sr['failed'] > 0) array_push($SITE_ERRORS, "SMS: {$sr['failed']} failed.");
                }
            }

            array_push($SITE_SUCCESS, $summary);
            mysqli_close($conn);
            header('Location: admin-notification-detail?id='.$notif_id);
            exit;

        } elseif ($action === 'schedule') {
            array_push($SITE_SUCCESS, 'Notification scheduled successfully.');
            mysqli_close($conn);
            header('Location: admin-notifications');
            exit;
        } else {
            array_push($SITE_SUCCESS, 'Draft saved successfully.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <?php require_once 'layout/header-propt.inc.php'; ?>
  <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?></title>
  <style>
    .sticky-bar { position:sticky;top:0;z-index:99;background:#fff;border-bottom:2px solid #10d596;padding:12px 0;box-shadow:0 2px 8px rgba(0,0,0,.06); }
    .channel-card { border:2px solid #dee2e6;border-radius:12px;padding:16px;cursor:pointer;transition:all .2s;position:relative; }
    .channel-card:hover { border-color:#10d596;background:#f0fff8; }
    .channel-card.active-inapp { border-color:#10d596;background:#e8fff6; }
    .channel-card.active-email { border-color:#17a2b8;background:#e3f6fd; }
    .channel-card.active-sms   { border-color:#6f42c1;background:#f3eeff; }
    .channel-card .ch-icon { font-size:28px;margin-bottom:6px; }
    .channel-card .ch-label { font-weight:700;font-size:14px; }
    .channel-card .ch-desc { font-size:11px;color:#777; }
    .channel-card .badge-configured { position:absolute;top:8px;right:8px;font-size:9px; }
    .type-card { border:2px solid #dee2e6;border-radius:10px;padding:12px 8px;cursor:pointer;transition:all .2s;text-align:center; }
    .type-card:hover, .type-card.selected { border-color:#10d596;background:#e8fff6; }
    .priority-btn { border:2px solid #dee2e6;border-radius:8px;padding:10px;cursor:pointer;transition:all .2s;text-align:center; }
    .priority-btn.active-low    { border-color:#6c757d;background:#f8f9fa; }
    .priority-btn.active-medium { border-color:#ffc107;background:#fff8e1; }
    .priority-btn.active-high   { border-color:#dc3545;background:#fdecea; }
    .channel-options { display:none;margin-top:12px;padding:12px;background:#f8f9fa;border-radius:8px; }
    .channel-options.show { display:block; }
    .send-btn { background:#10d596!important;border-color:#10d596!important;font-size:15px;font-weight:700;padding:10px 28px; }
  </style>
</head>
<body>
<?php require_once 'layout/preloader.inc.php'; ?>
<div id="main-wrapper">
  <?php require_once 'layout/header.inc.php'; require_once 'layout/sidebar.inc.php'; ?>

  <div class="content-body">
    <?php include 'layout/minor-top-navbar.inc.php'; ?>

    <form method="POST" id="notifForm">

    <!-- ═══ STICKY ACTION BAR ═══ -->
    <div class="sticky-bar">
      <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <a href="admin-notifications" class="btn btn-outline-secondary btn-sm mr-3">
              <i class="fa fa-arrow-left mr-1"></i> Back
            </a>
            <h5 class="mb-0 font-w700" style="color:#10d596;">
              <i class="fa fa-bell mr-2"></i><?= $PAGE_TITLE ?>
            </h5>
          </div>
          <div class="d-flex align-items-center" style="gap:8px;">
            <button type="submit" name="form_action" value="draft" class="btn btn-outline-secondary btn-sm">
              <i class="fa fa-floppy-o mr-1"></i> Save Draft
            </button>
            <button type="submit" name="form_action" value="schedule" class="btn btn-warning btn-sm" onclick="return validateSchedule()">
              <i class="fa fa-clock-o mr-1"></i> Schedule
            </button>
            <button type="submit" name="form_action" value="send_now" class="btn btn-primary send-btn" onclick="return confirmSend()">
              <i class="fa fa-paper-plane mr-2"></i> SEND NOW
            </button>
          </div>
        </div>
      </div>
    </div>
    <!-- ═══ END STICKY BAR ═══ -->

    <div class="container-fluid pt-4">
      <?php if ($edit_id > 0): ?><input type="hidden" name="edit_id" value="<?= $edit_id ?>"><?php endif; ?>

      <div class="row">
        <!-- LEFT: Content -->
        <div class="col-lg-8">

          <!-- Title & Message -->
          <div class="card">
            <div class="card-header">
              <h4 class="card-title"><i class="fa fa-pencil mr-2" style="color:#10d596;"></i>Notification Content</h4>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="font-w600">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="notifTitle" class="form-control form-control-lg" maxlength="200"
                  placeholder="e.g. Important Update — New Feature Available"
                  value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>" required>
                <div class="d-flex justify-content-end mt-1">
                  <span id="titleCount" style="font-size:11px;color:#6c757d;">0/200</span>
                </div>
              </div>
              <div class="form-group mb-0">
                <label class="font-w600">Message <span class="text-danger">*</span></label>
                <textarea name="message" id="notifMessage" class="form-control" rows="7" maxlength="2000"
                  placeholder="Write your notification message here..."
                  required><?= htmlspecialchars($edit_data['message'] ?? '') ?></textarea>
                <div class="d-flex justify-content-between mt-1">
                  <small class="text-muted">This message is used for all channels (In-App, Email, SMS)</small>
                  <span id="msgCount" style="font-size:11px;color:#6c757d;">0/2000</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Channels -->
          <div class="card">
            <div class="card-header">
              <h4 class="card-title"><i class="fa fa-share-alt mr-2" style="color:#10d596;"></i>Delivery Channels
                <small class="text-muted ml-2" style="font-size:12px;">Select one or more</small>
              </h4>
            </div>
            <div class="card-body">
              <?php
              $cur_channels = explode(',', $edit_data['channels'] ?? 'inapp');
              ?>
              <div class="row">
                <!-- In-App -->
                <div class="col-md-4 mb-3">
                  <div class="channel-card <?= in_array('inapp',$cur_channels)?'active-inapp':'' ?>" onclick="toggleChannel('inapp',this)">
                    <span class="badge badge-success badge-configured">Always Free</span>
                    <div class="ch-icon" style="color:#10d596;"><i class="fa fa-bell"></i></div>
                    <div class="ch-label">In-App</div>
                    <div class="ch-desc">Bell notification on dashboard</div>
                    <input type="checkbox" name="ch_inapp" value="1" id="ch_inapp"
                      <?= in_array('inapp',$cur_channels)?'checked':'' ?> style="display:none;">
                  </div>
                </div>
                <!-- Email -->
                <div class="col-md-4 mb-3">
                  <div class="channel-card <?= in_array('email',$cur_channels)?'active-email':'' ?>" onclick="toggleChannel('email',this)">
                    <?php if ($resend_ready): ?>
                    <span class="badge badge-info badge-configured">Ready</span>
                    <?php else: ?>
                    <span class="badge badge-warning badge-configured">Setup needed</span>
                    <?php endif; ?>
                    <div class="ch-icon" style="color:#17a2b8;"><i class="fa fa-envelope"></i></div>
                    <div class="ch-label">Email</div>
                    <div class="ch-desc">via Resend API</div>
                    <input type="checkbox" name="ch_email" value="1" id="ch_email"
                      <?= in_array('email',$cur_channels)?'checked':'' ?> style="display:none;">
                  </div>
                  <?php if (!$resend_ready): ?>
                  <div class="text-center mt-1">
                    <a href="admin-notification-settings" style="font-size:11px;color:#17a2b8;">⚙ Configure Resend API</a>
                  </div>
                  <?php endif; ?>
                </div>
                <!-- SMS -->
                <div class="col-md-4 mb-3">
                  <div class="channel-card <?= in_array('sms',$cur_channels)?'active-sms':'' ?>" onclick="toggleChannel('sms',this)">
                    <?php if ($termii_ready): ?>
                    <span class="badge badge-configured" style="background:#6f42c1;color:#fff;">Ready</span>
                    <?php else: ?>
                    <span class="badge badge-warning badge-configured">Setup needed</span>
                    <?php endif; ?>
                    <div class="ch-icon" style="color:#6f42c1;"><i class="fa fa-mobile"></i></div>
                    <div class="ch-label">SMS Nigeria</div>
                    <div class="ch-desc">via Termii bulk SMS</div>
                    <input type="checkbox" name="ch_sms" value="1" id="ch_sms"
                      <?= in_array('sms',$cur_channels)?'checked':'' ?> style="display:none;">
                  </div>
                  <?php if (!$termii_ready): ?>
                  <div class="text-center mt-1">
                    <a href="admin-notification-settings" style="font-size:11px;color:#6f42c1;">⚙ Configure Termii SMS</a>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Type -->
          <div class="card">
            <div class="card-header">
              <h4 class="card-title"><i class="fa fa-tag mr-2" style="color:#10d596;"></i>Notification Type</h4>
            </div>
            <div class="card-body">
              <input type="hidden" name="notif_type" id="notifTypeInput" value="<?= htmlspecialchars($edit_data['notif_type'] ?? 'general') ?>">
              <div class="row">
                <?php
                $types = [
                  'general'      => ['icon'=>'fa-bell','color'=>'#17a2b8','label'=>'General'],
                  'update'       => ['icon'=>'fa-refresh','color'=>'#10d596','label'=>'Update'],
                  'important'    => ['icon'=>'fa-exclamation-triangle','color'=>'#ffc107','label'=>'Important'],
                  'promotion'    => ['icon'=>'fa-gift','color'=>'#6f42c1','label'=>'Promotion'],
                  'system_alert' => ['icon'=>'fa-shield','color'=>'#dc3545','label'=>'System Alert'],
                ];
                $cur_type = $edit_data['notif_type'] ?? 'general';
                foreach ($types as $tv => $td): ?>
                <div class="col-md-4 col-6 mb-3">
                  <div class="type-card <?= $cur_type === $tv ? 'selected' : '' ?>"
                       onclick="selectType('<?= $tv ?>')" id="type_<?= $tv ?>">
                    <i class="fa <?= $td['icon'] ?> fa-lg mb-1" style="color:<?= $td['color'] ?>;display:block;"></i>
                    <div style="font-size:13px;font-weight:600;"><?= $td['label'] ?></div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Priority -->
          <div class="card">
            <div class="card-header">
              <h4 class="card-title"><i class="fa fa-signal mr-2" style="color:#10d596;"></i>Priority Level</h4>
            </div>
            <div class="card-body">
              <input type="hidden" name="priority" id="priorityInput" value="<?= htmlspecialchars($edit_data['priority'] ?? 'medium') ?>">
              <div class="row">
                <?php
                $priorities = ['low'=>['#6c757d','Low','Informational'],'medium'=>['#ffc107','Medium','Standard'],'high'=>['#dc3545','High','Urgent']];
                $cur_pri = $edit_data['priority'] ?? 'medium';
                foreach ($priorities as $pv => $pd): ?>
                <div class="col-md-4 mb-2">
                  <div class="priority-btn <?= $cur_pri===$pv?'active-'.$pv:'' ?>"
                       onclick="selectPriority('<?= $pv ?>')" id="pri_<?= $pv ?>">
                    <div style="font-size:20px;color:<?= $pd[0] ?>;"><i class="fa fa-circle"></i></div>
                    <div style="font-weight:700;font-size:14px;"><?= $pd[1] ?></div>
                    <div style="font-size:11px;color:#6c757d;"><?= $pd[2] ?></div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

        </div>

        <!-- RIGHT: Settings & Preview -->
        <div class="col-lg-4">

          <!-- Send Now (also here for large screens) -->
          <div class="card" style="border:2px solid #10d596;">
            <div class="card-body text-center py-4">
              <i class="fa fa-paper-plane fa-3x mb-3" style="color:#10d596;"></i>
              <h5 class="font-w700 mb-2">Ready to send?</h5>
              <p class="text-muted mb-3" style="font-size:13px;">
                Will reach <strong style="color:#10d596;"><?= number_format($user_count) ?></strong> registered users
              </p>
              <button type="submit" name="form_action" value="send_now" class="btn send-btn btn-lg btn-block mb-2" onclick="return confirmSend()">
                <i class="fa fa-paper-plane mr-2"></i> SEND NOW
              </button>
              <button type="submit" name="form_action" value="schedule" class="btn btn-warning btn-block mb-2" onclick="return validateSchedule()">
                <i class="fa fa-clock-o mr-1"></i> Schedule
              </button>
              <button type="submit" name="form_action" value="draft" class="btn btn-outline-secondary btn-block">
                <i class="fa fa-floppy-o mr-1"></i> Save Draft
              </button>
            </div>
          </div>

          <!-- Preview -->
          <div class="card">
            <div class="card-header">
              <h4 class="card-title" style="font-size:14px;"><i class="fa fa-mobile mr-2" style="color:#10d596;"></i>Live Preview</h4>
            </div>
            <div class="card-body p-3">
              <div style="background:#f8fff8;border-left:4px solid #10d596;border-radius:4px;padding:12px;">
                <div style="font-size:10px;color:#10d596;font-weight:700;text-transform:uppercase;margin-bottom:4px;">
                  <i class="fa fa-bell mr-1"></i><span id="previewType">General</span>
                </div>
                <div style="font-weight:700;font-size:14px;" id="previewTitle">Your notification title</div>
                <div style="font-size:12px;color:#555;margin-top:4px;line-height:1.4;" id="previewMsg">Your message will appear here...</div>
                <div style="font-size:10px;color:#aaa;margin-top:6px;"><i class="fa fa-clock-o mr-1"></i>Just now</div>
              </div>
            </div>
          </div>

          <!-- Audience -->
          <div class="card">
            <div class="card-header">
              <h4 class="card-title" style="font-size:14px;"><i class="fa fa-users mr-2" style="color:#10d596;"></i>Audience</h4>
            </div>
            <div class="card-body">
              <select name="target" class="form-control mb-2" id="targetSelect" onchange="toggleTargetEmail(this.value)">
                <option value="all" <?= ($edit_data['target']??'all')==='all'?'selected':'' ?>>
                  All Users (<?= number_format($user_count) ?>)
                </option>
                <option value="specific" <?= ($edit_data['target']??'')==='specific'?'selected':'' ?>>
                  Specific User
                </option>
              </select>
              <div id="specificEmailDiv" style="display:<?= ($edit_data['target']??'')==='specific'?'block':'none' ?>;">
                <input type="text" name="target_email" class="form-control" placeholder="user@example.com"
                  value="<?= htmlspecialchars($edit_data['target_email'] ?? '') ?>">
              </div>
            </div>
          </div>

          <!-- Schedule -->
          <div class="card">
            <div class="card-header">
              <h4 class="card-title" style="font-size:14px;"><i class="fa fa-calendar mr-2" style="color:#10d596;"></i>Schedule (Optional)</h4>
            </div>
            <div class="card-body">
              <input type="datetime-local" name="scheduled_at" class="form-control"
                value="<?= $edit_data['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($edit_data['scheduled_at'])) : '' ?>">
              <small class="text-muted d-block mt-1">Leave blank to send immediately when you click Send Now</small>
            </div>
          </div>

          <!-- Channel info -->
          <div class="card" style="border-left:4px solid #17a2b8;">
            <div class="card-body py-3">
              <h6 class="font-w600 mb-2"><i class="fa fa-info-circle mr-1 text-info"></i>Channel Info</h6>
              <div style="font-size:12px;line-height:1.7;">
                <div><i class="fa fa-bell mr-1" style="color:#10d596;width:16px;"></i>In-App → shows in bell icon</div>
                <div><i class="fa fa-envelope mr-1" style="color:#17a2b8;width:16px;"></i>Email → HTML email via Resend</div>
                <div><i class="fa fa-mobile mr-1" style="color:#6f42c1;width:16px;"></i>SMS → bulk Nigerian numbers</div>
                <div class="mt-2">
                  <a href="admin-notification-settings" style="font-size:11px;"><i class="fa fa-cog mr-1"></i>Manage API Keys</a>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
    </form>

  </div>
  <?php require_once 'layout/footer-propt.inc.php'; ?>
</div>

<script>
/* ── Type selector ── */
function selectType(val) {
  document.getElementById('notifTypeInput').value = val;
  document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
  document.getElementById('type_' + val).classList.add('selected');
  var labels = {general:'General',update:'Update',important:'Important',promotion:'Promotion',system_alert:'System Alert'};
  document.getElementById('previewType').textContent = labels[val] || val;
}

/* ── Priority selector ── */
function selectPriority(val) {
  document.getElementById('priorityInput').value = val;
  document.querySelectorAll('.priority-btn').forEach(b => { b.className = 'priority-btn'; });
  document.getElementById('pri_' + val).classList.add('active-' + val);
}

/* ── Channel toggle ── */
function toggleChannel(ch, card) {
  var chk = document.getElementById('ch_' + ch);
  chk.checked = !chk.checked;
  var cls = {'inapp':'active-inapp','email':'active-email','sms':'active-sms'};
  if (chk.checked) { card.classList.add(cls[ch]); }
  else { card.classList.remove(cls[ch]); }
}

/* ── Target toggle ── */
function toggleTargetEmail(v) {
  document.getElementById('specificEmailDiv').style.display = v === 'specific' ? 'block' : 'none';
}

/* ── Live preview ── */
document.getElementById('notifTitle').addEventListener('input', function(){
  document.getElementById('previewTitle').textContent = this.value.trim() || 'Your notification title';
  document.getElementById('titleCount').textContent = this.value.length + '/200';
});
document.getElementById('notifMessage').addEventListener('input', function(){
  var v = this.value.trim();
  document.getElementById('previewMsg').textContent = (v.substring(0,120) || 'Your message will appear here...') + (v.length>120?'...':'');
  document.getElementById('msgCount').textContent = this.value.length + '/2000';
});

/* ── Init counts ── */
(function(){
  var t = document.getElementById('notifTitle');
  var m = document.getElementById('notifMessage');
  document.getElementById('titleCount').textContent = t.value.length + '/200';
  document.getElementById('msgCount').textContent   = m.value.length + '/2000';
  if (t.value) document.getElementById('previewTitle').textContent = t.value;
  if (m.value) { var v=m.value; document.getElementById('previewMsg').textContent = v.substring(0,120)+(v.length>120?'...':''); }
})();

/* ── Confirm send ── */
function confirmSend() {
  var title = document.getElementById('notifTitle').value.trim();
  var msg   = document.getElementById('notifMessage').value.trim();
  if (!title || !msg) { alert('Please fill in Title and Message.'); return false; }

  var channels = [];
  if (document.getElementById('ch_inapp').checked) channels.push('In-App');
  if (document.getElementById('ch_email').checked) channels.push('Email');
  if (document.getElementById('ch_sms').checked)   channels.push('SMS');
  if (channels.length === 0) { alert('Please select at least one delivery channel.'); return false; }

  var target = document.getElementById('targetSelect').value;
  var audience = target === 'all' ? '<?= number_format($user_count) ?> users' : 'specific user';
  return confirm('Send "' + title + '" via ' + channels.join(' + ') + ' to ' + audience + ' right now?');
}

/* ── Validate schedule ── */
function validateSchedule() {
  var dt = document.querySelector('[name="scheduled_at"]').value;
  if (!dt) { alert('Please select a scheduled date and time first.'); return false; }
  if (new Date(dt) <= new Date()) { alert('Scheduled time must be in the future.'); return false; }
  return true;
}
</script>
<?php mysqli_close($conn); ?>
</body>
</html>
