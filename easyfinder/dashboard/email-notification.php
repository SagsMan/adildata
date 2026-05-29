<?php
require_once '../inc/user_session.inc.php';
$PAGE_TITLE = 'Email Notification';
$URL_NAME = 'email-notification';

// Admin-only guard
if (!in_array(1, explode(',', $Auth->admin_role)) && !in_array(2, explode(',', $Auth->admin_role))) {
    header('Location: ./');
    exit;
}

$conn = mysqli_connect('localhost','adiliqgs_adildata','adildata2026','adiliqgs_adildata');

// ── Auto-create table if not exists ─────────────────────────────────────────
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS email_notifications_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    target ENUM('all','specific') DEFAULT 'all',
    target_email VARCHAR(255) NULL,
    sent_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    sent_by VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Handle Send Email ────────────────────────────────────────────────────────
if (isset($_POST['send_email'])) {
    $subject      = htmlspecialchars(trim($_POST['subject']));
    $body_text    = trim($_POST['message']);
    $target       = ($_POST['target'] === 'specific') ? 'specific' : 'all';
    $target_email = ($target === 'specific' && !empty($_POST['target_email']))
                        ? trim($_POST['target_email']) : null;

    $site_name  = SITE_TITLE;
    $site_url   = rtrim(SITE_URL, '/');
    // Derive from-email using the site domain
    $domain     = parse_url($site_url, PHP_URL_HOST) ?: 'adildata.com.ng';
    $from_email = 'noreply@' . $domain;

    // Build recipient list
    $recipients = [];
    if ($target === 'all') {
        $r = mysqli_query($conn, "SELECT email, sname FROM users_tbl WHERE status = 1 OR status IS NULL");
        if ($r) {
            while ($row = mysqli_fetch_assoc($r)) $recipients[] = $row;
        }
    } else {
        $r = mysqli_query($conn, "SELECT email, sname FROM users_tbl WHERE email = '" . mysqli_real_escape_string($conn, $target_email) . "' LIMIT 1");
        $name = 'User';
        if ($r && mysqli_num_rows($r) > 0) { $row = mysqli_fetch_assoc($r); $name = $row['sname']; }
        $recipients[] = ['email' => $target_email, 'sname' => $name];
    }

    $sent = 0; $failed = 0;

    foreach ($recipients as $user) {
        if (empty($user['email'])) { $failed++; continue; }
        $to   = $user['email'];
        $name = htmlspecialchars($user['sname']);

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$site_name} <{$from_email}>\r\n";
        $headers .= "Reply-To: {$from_email}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        $html_body = '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>' . htmlspecialchars($subject) . '</title></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
        <tr><td style="background:#10d596;padding:24px 32px;">
          <h1 style="margin:0;color:#fff;font-size:22px;font-weight:700;">' . $site_name . '</h1>
        </td></tr>
        <tr><td style="padding:32px;">
          <p style="color:#444;font-size:15px;margin-top:0;">Dear ' . $name . ',</p>
          <div style="color:#555;font-size:15px;line-height:1.8;">' . nl2br(htmlspecialchars($body_text)) . '</div>
          <hr style="margin:28px 0;border:none;border-top:1px solid #eee;">
          <p style="color:#aaa;font-size:12px;margin:0;">You received this message from <strong>' . $site_name . '</strong>. Please do not reply to this email.</p>
        </td></tr>
        <tr><td style="background:#f9f9f9;padding:16px 32px;text-align:center;">
          <p style="color:#bbb;font-size:11px;margin:0;">&copy; ' . date('Y') . ' ' . $site_name . '. All rights reserved.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';

        if (@mail($to, $subject, $html_body, $headers)) {
            $sent++;
        } else {
            $failed++;
        }
    }

    // Log
    $sub_s   = mysqli_real_escape_string($conn, $subject);
    $msg_s   = mysqli_real_escape_string($conn, $body_text);
    $te_s    = $target_email ? "'" . mysqli_real_escape_string($conn, $target_email) . "'" : 'NULL';
    $by_s    = mysqli_real_escape_string($conn, $Auth->email);
    mysqli_query($conn,
        "INSERT INTO email_notifications_log(subject,message,target,target_email,sent_count,failed_count,sent_by)
         VALUES('$sub_s','$msg_s','$target',$te_s,$sent,$failed,'$by_s')"
    );

    if ($sent > 0) {
        array_push($SITE_SUCCESS, "Email sent to {$sent} recipient(s)" . ($failed > 0 ? " ({$failed} failed)." : "."));
    } else {
        array_push($SITE_ERRORS, "Failed to send email(s). Check mail server configuration.");
    }
}

// ── Fetch logs ───────────────────────────────────────────────────────────────
$logs = [];
$r = mysqli_query($conn, "SELECT * FROM email_notifications_log ORDER BY id DESC LIMIT 50");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $logs[] = $row; } }

// ── User count ───────────────────────────────────────────────────────────────
$user_count = 0;
$rc = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users_tbl");
if ($rc) { $row = mysqli_fetch_assoc($rc); $user_count = $row['cnt']; }

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <?php require_once 'layout/header-propt.inc.php'; ?>
  <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?></title>
</head>
<body>
<?php require_once 'layout/preloader.inc.php'; ?>
<div id="main-wrapper">
  <?php
    require_once 'layout/header.inc.php';
    require_once 'layout/sidebar.inc.php';
  ?>
  <div class="content-body">
    <?php include 'layout/minor-top-navbar.inc.php'; ?>
    <div class="container-fluid">

      <div class="form-head d-flex mb-3 align-items-start">
        <div class="mr-auto d-none d-lg-block">
          <h4 class="font-w600 mb-0" style="color:#10d596;">Email Notification</h4>
          <p class="mb-0">Send email messages to users &mdash; <strong><?= number_format($user_count) ?></strong> total users</p>
        </div>
      </div>

      <div class="row">
        <!-- Compose Form -->
        <div class="col-xl-5 col-lg-5">
          <div class="card">
            <div class="card-header">
              <h4 class="card-title"><i class="fa fa-envelope mr-2" style="color:#10d596;"></i>Compose Email</h4>
            </div>
            <div class="card-body">
              <form method="POST" action="">
                <div class="form-group">
                  <label>Subject</label>
                  <input type="text" name="subject" class="form-control" placeholder="Email subject" required>
                </div>
                <div class="form-group">
                  <label>Message</label>
                  <textarea name="message" class="form-control" rows="6" placeholder="Write your message here..." required></textarea>
                </div>
                <div class="form-group">
                  <label>Send To</label>
                  <select name="target" class="form-control" id="emailTargetSelect" onchange="toggleEmailTarget(this)">
                    <option value="all">All Users (<?= number_format($user_count) ?> users)</option>
                    <option value="specific">Specific User</option>
                  </select>
                </div>
                <div class="form-group" id="specificEmailField" style="display:none;">
                  <label>User Email Address</label>
                  <input type="email" name="target_email" class="form-control" placeholder="user@example.com">
                </div>
                <div class="alert alert-warning" style="font-size:13px;">
                  <i class="fa fa-info-circle mr-1"></i> Emails are sent using the server mail system. Ensure your hosting mail is configured.
                </div>
                <button type="submit" name="send_email" class="btn btn-primary btn-block"
                  style="background-color:#10d596!important;border-color:#10d596!important;">
                  <i class="fa fa-paper-plane mr-2"></i> Send Email
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Email History -->
        <div class="col-xl-7 col-lg-7">
          <div class="card">
            <div class="card-header">
              <h4 class="card-title">Email History (<?= count($logs) ?>)</h4>
            </div>
            <div class="card-body">
              <?php if (empty($logs)): ?>
                <div class="text-center py-4">
                  <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                  <p class="text-muted">No emails sent yet.</p>
                </div>
              <?php else: ?>
              <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                  <thead>
                    <tr>
                      <th>Subject</th>
                      <th>Target</th>
                      <th>Sent/Failed</th>
                      <th>Sent By</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                      <td>
                        <strong><?= htmlspecialchars($log['subject']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars(substr($log['message'], 0, 60)) ?>...</small>
                      </td>
                      <td>
                        <?php if ($log['target'] === 'all'): ?>
                          <span class="badge badge-info">All Users</span>
                        <?php else: ?>
                          <span class="badge badge-warning"><?= htmlspecialchars($log['target_email']) ?></span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="badge badge-success"><?= $log['sent_count'] ?> sent</span>
                        <?php if ($log['failed_count'] > 0): ?>
                          <span class="badge badge-danger"><?= $log['failed_count'] ?> failed</span>
                        <?php endif; ?>
                      </td>
                      <td><small><?= htmlspecialchars($log['sent_by']) ?></small></td>
                      <td><small><?= !empty($log['created_at']) ? date('d M Y H:i', strtotime($log['created_at'])) : '—' ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php require_once 'layout/footer-propt.inc.php'; ?>
</div>
</body>
</html>
<script>
function toggleEmailTarget(sel) {
  document.getElementById('specificEmailField').style.display = sel.value === 'specific' ? 'block' : 'none';
}
</script>
