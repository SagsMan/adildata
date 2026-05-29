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
    scheduled_at DATETIME NULL,
    sent_at DATETIME NULL,
    created_by VARCHAR(255) NULL,
    total_recipients INT DEFAULT 0,
    delivered_count INT DEFAULT 0,
    read_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
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
    INDEX idx_user_email (user_email),
    INDEX idx_delivery_status (delivery_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── Load existing notification for editing ──────────────────────────────── */
$edit_id   = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = null;
if ($edit_id > 0) {
    $er = mysqli_query($conn, "SELECT * FROM admin_notifications_tbl WHERE id=$edit_id AND status IN('draft','failed') LIMIT 1");
    if ($er) $edit_data = mysqli_fetch_assoc($er);
    if ($edit_data) $PAGE_TITLE = 'Edit Notification';
}

$user_count_r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users_tbl");
$user_count   = 0;
if ($user_count_r) { $uc = mysqli_fetch_assoc($user_count_r); $user_count = intval($uc['cnt']); }

/* ── Handle form submission ──────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['form_action'] ?? 'draft';
    $title    = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['title'] ?? '')));
    $message  = mysqli_real_escape_string($conn, trim($_POST['message'] ?? ''));
    $ntype    = in_array($_POST['notif_type']??'',['important','update','promotion','system_alert','general']) ? $_POST['notif_type'] : 'general';
    $priority = in_array($_POST['priority']??'',['low','medium','high']) ? $_POST['priority'] : 'medium';
    $target   = ($_POST['target']??'') === 'specific' ? 'specific' : 'all';
    $target_email = ($target === 'specific' && !empty($_POST['target_email'])) ? mysqli_real_escape_string($conn, trim($_POST['target_email'])) : null;
    $te_sql   = $target_email ? "'$target_email'" : 'NULL';
    $admin_em = mysqli_real_escape_string($conn, $Auth->email);

    $sched_at   = null;
    $sched_sql  = 'NULL';
    $status_val = 'draft';

    if ($action === 'send_now') {
        $status_val = 'sent';
    } elseif ($action === 'schedule' && !empty($_POST['scheduled_at'])) {
        $sched_at  = date('Y-m-d H:i:s', strtotime($_POST['scheduled_at']));
        $sched_sql = "'$sched_at'";
        $status_val = 'pending';
    } else {
        $status_val = 'draft';
    }

    if (empty($title) || empty($message)) {
        array_push($SITE_ERRORS, 'Title and Message are required.');
    } else {
        if ($edit_id > 0 && $edit_data) {
            /* Update existing draft/failed */
            $upd = mysqli_query($conn,
                "UPDATE admin_notifications_tbl SET title='$title', message='$message', notif_type='$ntype',
                 priority='$priority', target='$target', target_email=$te_sql, status='$status_val',
                 scheduled_at=$sched_sql, updated_at=NOW()
                 WHERE id=$edit_id");
            if (!$upd) {
                array_push($SITE_ERRORS, 'Update failed: ' . mysqli_error($conn));
            } else {
                $notif_id = $edit_id;
                if ($action === 'send_now') {
                    goto do_send;
                }
                array_push($SITE_SUCCESS, $status_val === 'draft' ? 'Draft saved.' : ($status_val === 'pending' ? 'Notification scheduled successfully.' : 'Done.'));
                if ($status_val !== 'draft') {
                    mysqli_close($conn);
                    header('Location: admin-notifications'); exit;
                }
            }
        } else {
            /* Insert new */
            $ins = mysqli_query($conn,
                "INSERT INTO admin_notifications_tbl (title,message,notif_type,priority,target,target_email,status,scheduled_at,created_by)
                 VALUES('$title','$message','$ntype','$priority','$target',$te_sql,'$status_val',$sched_sql,'$admin_em')");
            if (!$ins) {
                array_push($SITE_ERRORS, 'Failed to save: ' . mysqli_error($conn));
            } else {
                $notif_id = mysqli_insert_id($conn);
                if ($action === 'send_now') {
                    goto do_send;
                }
                $msg = $status_val === 'draft' ? 'Draft saved successfully.' : 'Notification scheduled successfully.';
                array_push($SITE_SUCCESS, $msg);
                if ($status_val !== 'draft') {
                    mysqli_close($conn);
                    header('Location: admin-notifications'); exit;
                }
            }
        }

        if (false) {
            do_send:
            /* ── Send immediately: create delivery records + insert into notifications_tbl ── */
            $total    = 0;
            $failed_c = 0;

            if ($target === 'all') {
                $users_r = mysqli_query($conn, "SELECT id, fname, lname, email, phone FROM users_tbl");
            } else {
                $te2 = $target_email;
                $users_r = mysqli_query($conn, "SELECT id, fname, lname, email, phone FROM users_tbl WHERE email='$te2' LIMIT 1");
            }

            if ($users_r) {
                while ($u = mysqli_fetch_assoc($users_r)) {
                    $ue = mysqli_real_escape_string($conn, $u['email']);
                    $un = mysqli_real_escape_string($conn, trim(($u['fname']??'').' '.($u['lname']??'')));
                    $up = mysqli_real_escape_string($conn, $u['phone'] ?? '');
                    $uid = intval($u['id']);
                    mysqli_query($conn,
                        "INSERT INTO admin_notif_delivery_tbl (notification_id,user_id,user_name,user_email,user_phone,delivery_status,sent_at,delivered_at)
                         VALUES($notif_id,$uid,'$un','$ue','$up','delivered',NOW(),NOW())");
                    $total++;
                }
            }

            /* Insert into legacy notifications_tbl so users see it in their bell */
            $map = ['important'=>'warning','update'=>'info','promotion'=>'success','system_alert'=>'danger','general'=>'info'];
            $lt  = mysqli_real_escape_string($conn, strip_tags($title));
            $lm  = mysqli_real_escape_string($conn, strip_tags($message));
            $ltype = $map[$ntype] ?? 'info';
            $ltgt  = ($target === 'all') ? 'all' : 'specific';
            $lte   = $target_email ? "'$target_email'" : 'NULL';
            $leg   = mysqli_query($conn,
                "INSERT INTO notifications_tbl(title,message,type,target,target_email,created_by,status)
                 VALUES('$lt','$lm','$ltype','$ltgt',$lte,'$admin_em',1)");
            $legacy_id = $leg ? mysqli_insert_id($conn) : 'NULL';

            mysqli_query($conn,
                "UPDATE admin_notifications_tbl SET status='sent', sent_at=NOW(), total_recipients=$total,
                 delivered_count=$total, legacy_notif_id=$legacy_id WHERE id=$notif_id");

            array_push($SITE_SUCCESS, "Notification sent live to $total user(s) successfully!");
            mysqli_close($conn);
            header('Location: admin-notification-detail?id=' . $notif_id); exit;
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
    .priority-btn { border: 2px solid #dee2e6; border-radius: 8px; padding: 10px 20px; cursor: pointer; transition: all .2s; text-align:center; }
    .priority-btn.active-low    { border-color: #6c757d; background: #f8f9fa; }
    .priority-btn.active-medium { border-color: #ffc107; background: #fff8e1; }
    .priority-btn.active-high   { border-color: #dc3545; background: #fdecea; }
    .type-card { border: 2px solid #dee2e6; border-radius: 10px; padding: 14px 10px; cursor: pointer; transition: all .2s; text-align:center; }
    .type-card:hover { border-color: #10d596; background: #f0fff8; }
    .type-card.selected { border-color: #10d596; background: #e8fff6; }
    .type-card .type-icon { font-size: 22px; margin-bottom: 4px; }
    .char-count { font-size: 11px; color: #6c757d; }
    .preview-box { background: #f8f9fa; border-radius: 8px; border-left: 4px solid #10d596; padding: 14px; min-height: 80px; }
  </style>
</head>
<body>
<?php require_once 'layout/preloader.inc.php'; ?>
<div id="main-wrapper">
  <?php require_once 'layout/header.inc.php'; require_once 'layout/sidebar.inc.php'; ?>

  <div class="content-body">
    <?php include 'layout/minor-top-navbar.inc.php'; ?>
    <div class="container-fluid">

      <!-- Page Title -->
      <div class="row page-titles mx-0 mb-3">
        <div class="col-sm-6 p-md-0">
          <h4 style="color:#10d596;font-weight:700;"><i class="fa fa-bell mr-2"></i><?= $PAGE_TITLE ?></h4>
          <p class="mb-0 text-muted"><?= $edit_data ? 'Edit and resend this notification' : 'Compose and send notifications to your users' ?></p>
        </div>
        <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex align-items-center">
          <a href="admin-notifications" class="btn btn-outline-secondary btn-sm mr-2">
            <i class="fa fa-arrow-left mr-1"></i> Back
          </a>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="admin-notifications">Notifications</a></li>
            <li class="breadcrumb-item active"><?= $PAGE_TITLE ?></li>
          </ol>
        </div>
      </div>

      <form method="POST" id="notifForm">
        <?php if ($edit_id > 0): ?><input type="hidden" name="edit_id" value="<?= $edit_id ?>"><?php endif; ?>

        <div class="row">
          <!-- Left: Compose -->
          <div class="col-xl-8 col-lg-7">

            <!-- Title & Message -->
            <div class="card">
              <div class="card-header">
                <h4 class="card-title"><i class="fa fa-pencil mr-2" style="color:#10d596;"></i>Notification Content</h4>
              </div>
              <div class="card-body">
                <div class="form-group">
                  <label class="font-w600">Notification Title <span class="text-danger">*</span></label>
                  <input type="text" name="title" id="notifTitle" class="form-control form-control-lg" maxlength="200"
                    placeholder="e.g. Important System Update" required
                    value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>">
                  <div class="d-flex justify-content-between mt-1">
                    <small class="text-muted">Keep it short and clear</small>
                    <span class="char-count" id="titleCount">0/200</span>
                  </div>
                </div>
                <div class="form-group">
                  <label class="font-w600">Notification Message <span class="text-danger">*</span></label>
                  <textarea name="message" id="notifMessage" class="form-control" rows="6" maxlength="2000"
                    placeholder="Write your notification message here..."><?= htmlspecialchars($edit_data['message'] ?? '') ?></textarea>
                  <div class="d-flex justify-content-between mt-1">
                    <small class="text-muted">Maximum 2000 characters</small>
                    <span class="char-count" id="msgCount">0/2000</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notification Type -->
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
                      <div class="type-icon"><i class="fa <?= $td['icon'] ?>" style="color:<?= $td['color'] ?>;"></i></div>
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
                  $priorities = [
                    'low'    => ['color'=>'#6c757d','label'=>'Low','desc'=>'Informational, no urgency'],
                    'medium' => ['color'=>'#ffc107','label'=>'Medium','desc'=>'Standard notification'],
                    'high'   => ['color'=>'#dc3545','label'=>'High','desc'=>'Urgent — requires attention'],
                  ];
                  $cur_pri = $edit_data['priority'] ?? 'medium';
                  foreach ($priorities as $pv => $pd): ?>
                  <div class="col-md-4 mb-2">
                    <div class="priority-btn <?= $cur_pri === $pv ? 'active-'.$pv : '' ?>"
                         onclick="selectPriority('<?= $pv ?>')" id="pri_<?= $pv ?>">
                      <div style="font-size:18px;color:<?= $pd['color'] ?>;"><i class="fa fa-circle"></i></div>
                      <div style="font-weight:700;font-size:14px;"><?= $pd['label'] ?></div>
                      <div style="font-size:11px;color:#6c757d;"><?= $pd['desc'] ?></div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

          </div>

          <!-- Right: Settings & Actions -->
          <div class="col-xl-4 col-lg-5">

            <!-- Live Preview -->
            <div class="card">
              <div class="card-header">
                <h4 class="card-title"><i class="fa fa-mobile mr-2" style="color:#10d596;"></i>Live Preview</h4>
              </div>
              <div class="card-body">
                <div class="preview-box" id="previewBox">
                  <div style="font-size:10px;color:#10d596;font-weight:700;text-transform:uppercase;margin-bottom:4px;">
                    <i class="fa fa-bell mr-1"></i><span id="previewType">General</span>
                  </div>
                  <div style="font-weight:700;font-size:14px;" id="previewTitle">Your notification title</div>
                  <div style="font-size:12px;color:#555;margin-top:4px;" id="previewMsg">Your message will appear here...</div>
                  <div style="font-size:10px;color:#aaa;margin-top:6px;"><i class="fa fa-clock-o mr-1"></i>Just now</div>
                </div>
              </div>
            </div>

            <!-- Audience -->
            <div class="card">
              <div class="card-header">
                <h4 class="card-title"><i class="fa fa-users mr-2" style="color:#10d596;"></i>Audience</h4>
              </div>
              <div class="card-body">
                <div class="form-group mb-2">
                  <select name="target" class="form-control" id="targetSelect"
                    onchange="toggleTargetEmail(this.value)">
                    <option value="all" <?= ($edit_data['target']??'all')==='all'?'selected':'' ?>>
                      All Users (<?= number_format($user_count) ?> registered)
                    </option>
                    <option value="specific" <?= ($edit_data['target']??'')==='specific'?'selected':'' ?>>
                      Specific User
                    </option>
                  </select>
                </div>
                <div class="form-group mb-0" id="specificEmailDiv"
                  style="display:<?= ($edit_data['target']??'')==='specific'?'block':'none' ?>;">
                  <label style="font-size:12px;" class="font-w600">User Email / Phone</label>
                  <input type="text" name="target_email" class="form-control" placeholder="user@example.com"
                    value="<?= htmlspecialchars($edit_data['target_email'] ?? '') ?>">
                </div>
              </div>
            </div>

            <!-- Schedule -->
            <div class="card">
              <div class="card-header">
                <h4 class="card-title"><i class="fa fa-calendar mr-2" style="color:#10d596;"></i>Schedule (Optional)</h4>
              </div>
              <div class="card-body">
                <div class="form-group mb-2">
                  <label style="font-size:12px;" class="font-w600">Scheduled Date & Time</label>
                  <input type="datetime-local" name="scheduled_at" class="form-control"
                    value="<?= $edit_data['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($edit_data['scheduled_at'])) : '' ?>">
                  <small class="text-muted">Leave blank to send immediately</small>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="card">
              <div class="card-body">
                <div class="d-grid" style="display:flex;flex-direction:column;gap:10px;">

                  <button type="submit" name="form_action" value="send_now" class="btn btn-lg"
                    style="background:#10d596;border-color:#10d596;color:#fff;font-weight:700;"
                    onclick="return confirmSend()">
                    <i class="fa fa-paper-plane mr-2"></i> Send Now
                    <small class="d-block" style="font-size:11px;font-weight:400;">Instantly to all users</small>
                  </button>

                  <button type="submit" name="form_action" value="schedule" class="btn btn-warning"
                    onclick="return validateSchedule()">
                    <i class="fa fa-clock-o mr-2"></i> Schedule Send
                    <small class="d-block" style="font-size:11px;font-weight:400;">Send at selected date/time</small>
                  </button>

                  <button type="submit" name="form_action" value="draft" class="btn btn-outline-secondary">
                    <i class="fa fa-floppy-o mr-2"></i> Save as Draft
                  </button>

                  <a href="admin-notifications" class="btn btn-link text-muted text-center">
                    Cancel
                  </a>
                </div>
              </div>
            </div>

            <!-- Info Box -->
            <div class="card" style="border-left:4px solid #17a2b8;">
              <div class="card-body py-3">
                <h6 class="text-info mb-2"><i class="fa fa-info-circle mr-1"></i> How it works</h6>
                <ul style="font-size:12px;color:#555;padding-left:16px;margin:0;">
                  <li><strong>Send Now</strong> — delivers instantly to all users</li>
                  <li><strong>Schedule</strong> — queued & auto-sent at selected time</li>
                  <li><strong>Draft</strong> — saved, not sent yet</li>
                  <li>All deliveries are tracked per user</li>
                </ul>
              </div>
            </div>

          </div>
        </div>
      </form>

    </div>
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
  document.querySelectorAll('.priority-btn').forEach(b => {
    b.className = 'priority-btn';
  });
  document.getElementById('pri_' + val).classList.add('active-' + val);
}

/* ── Audience toggle ── */
function toggleTargetEmail(v) {
  document.getElementById('specificEmailDiv').style.display = v === 'specific' ? 'block' : 'none';
}

/* ── Live preview ── */
document.getElementById('notifTitle').addEventListener('input', function(){
  var v = this.value.trim() || 'Your notification title';
  document.getElementById('previewTitle').textContent = v;
  document.getElementById('titleCount').textContent = this.value.length + '/200';
});
document.getElementById('notifMessage').addEventListener('input', function(){
  var v = this.value.trim() || 'Your message will appear here...';
  document.getElementById('previewMsg').textContent = v.substring(0, 120) + (v.length > 120 ? '...' : '');
  document.getElementById('msgCount').textContent = this.value.length + '/2000';
});

/* Init counts */
(function(){
  var t = document.getElementById('notifTitle');
  var m = document.getElementById('notifMessage');
  document.getElementById('titleCount').textContent = t.value.length + '/200';
  document.getElementById('msgCount').textContent   = m.value.length + '/2000';
  if (t.value) document.getElementById('previewTitle').textContent = t.value;
  if (m.value) document.getElementById('previewMsg').textContent   = m.value.substring(0,120);
})();

/* ── Confirm send ── */
function confirmSend() {
  var title = document.getElementById('notifTitle').value.trim();
  var msg   = document.getElementById('notifMessage').value.trim();
  if (!title || !msg) { alert('Please fill in title and message.'); return false; }
  var target = document.getElementById('targetSelect').value;
  var audience = target === 'all' ? '<?= number_format($user_count) ?> users' : 'specific user';
  return confirm('Send "' + title + '" to ' + audience + ' right now?');
}

/* ── Validate schedule ── */
function validateSchedule() {
  var dt = document.querySelector('[name="scheduled_at"]').value;
  if (!dt) { alert('Please select a scheduled date and time.'); return false; }
  var sel = new Date(dt);
  if (sel <= new Date()) { alert('Scheduled time must be in the future.'); return false; }
  return confirm('Schedule this notification for ' + sel.toLocaleString() + '?');
}
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
