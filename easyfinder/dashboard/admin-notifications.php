<?php
require_once '../inc/user_session.inc.php';
$PAGE_TITLE = 'Notifications Management';
$URL_NAME   = 'admin-notifications';

if (!in_array(1, explode(',', $Auth->admin_role)) && !in_array(2, explode(',', $Auth->admin_role))) {
    header('Location: ./'); exit;
}

$conn = mysqli_connect('localhost','adiliqgs_adildata','adildata2026','adiliqgs_adildata');

/* ── Create tables if not exist ───────────────────────────────────────────── */
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin_notifications_tbl (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255) NOT NULL,
    message         TEXT NOT NULL,
    notif_type      ENUM('important','update','promotion','system_alert','general') DEFAULT 'general',
    priority        ENUM('low','medium','high') DEFAULT 'medium',
    target          ENUM('all','specific') DEFAULT 'all',
    target_email    VARCHAR(255) NULL,
    status          ENUM('draft','pending','sent','failed') DEFAULT 'draft',
    scheduled_at    DATETIME NULL,
    sent_at         DATETIME NULL,
    created_by      VARCHAR(255) NULL,
    total_recipients INT DEFAULT 0,
    delivered_count INT DEFAULT 0,
    read_count      INT DEFAULT 0,
    failed_count    INT DEFAULT 0,
    legacy_notif_id INT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin_notif_delivery_tbl (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    user_id         INT NULL,
    user_name       VARCHAR(255) NULL,
    user_email      VARCHAR(255) NULL,
    user_phone      VARCHAR(50) NULL,
    delivery_status ENUM('pending','sent','delivered','failed','read') DEFAULT 'sent',
    sent_at         DATETIME NULL,
    delivered_at    DATETIME NULL,
    read_at         DATETIME NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_id (notification_id),
    INDEX idx_user_email (user_email),
    INDEX idx_delivery_status (delivery_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── Sync read status from legacy notifications_tbl ──────────────────────── */
$sync_r = mysqli_query($conn, "SELECT ant.id, ant.legacy_notif_id FROM admin_notifications_tbl ant WHERE ant.legacy_notif_id IS NOT NULL AND ant.status='sent'");
if ($sync_r) {
    while ($srow = mysqli_fetch_assoc($sync_r)) {
        $lid = intval($srow['legacy_notif_id']);
        $aid = intval($srow['id']);
        $lr  = mysqli_query($conn, "SELECT is_read_by FROM notifications_tbl WHERE id=$lid");
        if ($lr && $lrow = mysqli_fetch_assoc($lr)) {
            $readers = json_decode($lrow['is_read_by'] ?: '[]', true);
            if (is_array($readers) && count($readers) > 0) {
                foreach ($readers as $re) {
                    $re_esc = mysqli_real_escape_string($conn, $re);
                    mysqli_query($conn, "UPDATE admin_notif_delivery_tbl SET delivery_status='read', read_at=NOW() WHERE notification_id=$aid AND user_email='$re_esc' AND delivery_status!='read'");
                }
                $rc = count($readers);
                mysqli_query($conn, "UPDATE admin_notifications_tbl SET read_count=(SELECT COUNT(*) FROM admin_notif_delivery_tbl WHERE notification_id=$aid AND delivery_status='read'), delivered_count=(SELECT COUNT(*) FROM admin_notif_delivery_tbl WHERE notification_id=$aid AND delivery_status IN('delivered','read')) WHERE id=$aid");
            }
        }
    }
}

/* ── Auto-send scheduled notifications that are due ─────────────────────── */
$sched = mysqli_query($conn, "SELECT * FROM admin_notifications_tbl WHERE status='pending' AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()");
if ($sched) {
    while ($sn = mysqli_fetch_assoc($sched)) {
        $sid = intval($sn['id']);
        $users_r = mysqli_query($conn, "SELECT id, fname, lname, email, phone FROM users_tbl");
        $total = 0;
        if ($users_r) {
            while ($u = mysqli_fetch_assoc($users_r)) {
                $ue = mysqli_real_escape_string($conn, $u['email']);
                $un = mysqli_real_escape_string($conn, trim($u['fname'].' '.$u['lname']));
                $up = mysqli_real_escape_string($conn, $u['phone'] ?? '');
                mysqli_query($conn, "INSERT INTO admin_notif_delivery_tbl (notification_id,user_id,user_name,user_email,user_phone,delivery_status,sent_at) VALUES($sid,{$u['id']},'$un','$ue','$up','sent',NOW())");
                $total++;
            }
        }
        $map = ['important'=>'warning','update'=>'info','promotion'=>'success','system_alert'=>'danger','general'=>'info'];
        $legacy_type = $map[$sn['notif_type']] ?? 'info';
        $lt = mysqli_real_escape_string($conn, $sn['title']);
        $lm = mysqli_real_escape_string($conn, $sn['message']);
        $ins = mysqli_query($conn, "INSERT INTO notifications_tbl(title,message,type,target,created_by,status) VALUES('$lt','$lm','$legacy_type','all','{$sn['created_by']}',1)");
        $legacy_id = $ins ? mysqli_insert_id($conn) : null;
        $lid_sql = $legacy_id ? $legacy_id : 'NULL';
        mysqli_query($conn, "UPDATE admin_notifications_tbl SET status='sent', sent_at=NOW(), total_recipients=$total, delivered_count=$total, legacy_notif_id=$lid_sql WHERE id=$sid");
    }
}

/* ── Stats ───────────────────────────────────────────────────────────────── */
$stat = function($sql) use ($conn) {
    $r = mysqli_query($conn, $sql);
    if (!$r) return 0;
    $row = mysqli_fetch_row($r);
    return intval($row[0] ?? 0);
};

$total_notifs     = $stat("SELECT COUNT(*) FROM admin_notifications_tbl");
$sent_notifs      = $stat("SELECT COUNT(*) FROM admin_notifications_tbl WHERE status='sent'");
$pending_notifs   = $stat("SELECT COUNT(*) FROM admin_notifications_tbl WHERE status='pending'");
$failed_notifs    = $stat("SELECT COUNT(*) FROM admin_notifications_tbl WHERE status='failed'");
$draft_notifs     = $stat("SELECT COUNT(*) FROM admin_notifications_tbl WHERE status='draft'");
$users_reached    = $stat("SELECT SUM(total_recipients) FROM admin_notifications_tbl WHERE status='sent'");
$total_delivered  = $stat("SELECT SUM(delivered_count) FROM admin_notifications_tbl WHERE status='sent'");
$delivery_rate    = $users_reached > 0 ? round(($total_delivered / $users_reached) * 100, 1) : 0;
$total_read       = $stat("SELECT COUNT(*) FROM admin_notif_delivery_tbl WHERE delivery_status='read'");
$total_users      = $stat("SELECT COUNT(*) FROM users_tbl");

/* ── Recent notifications (for activity table) ───────────────────────────── */
$page    = max(1, intval($_GET['page'] ?? 1));
$perpage = 10;
$offset  = ($page - 1) * $perpage;

$where_clauses = [];
$search = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? '';
$filter_type   = $_GET['type'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to   = $_GET['date_to'] ?? '';

if ($search !== '') {
    $se = mysqli_real_escape_string($conn, $search);
    $where_clauses[] = "(title LIKE '%$se%' OR message LIKE '%$se%' OR created_by LIKE '%$se%')";
}
if (in_array($filter_status, ['draft','pending','sent','failed'])) {
    $where_clauses[] = "status='$filter_status'";
}
if (in_array($filter_type, ['important','update','promotion','system_alert','general'])) {
    $where_clauses[] = "notif_type='$filter_type'";
}
if ($filter_date_from !== '') {
    $fd = mysqli_real_escape_string($conn, $filter_date_from);
    $where_clauses[] = "DATE(created_at) >= '$fd'";
}
if ($filter_date_to !== '') {
    $td = mysqli_real_escape_string($conn, $filter_date_to);
    $where_clauses[] = "DATE(created_at) <= '$td'";
}

$where_sql = count($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
$total_filtered = $stat("SELECT COUNT(*) FROM admin_notifications_tbl $where_sql");
$total_pages    = max(1, ceil($total_filtered / $perpage));

$notifs_r = mysqli_query($conn, "SELECT * FROM admin_notifications_tbl $where_sql ORDER BY id DESC LIMIT $perpage OFFSET $offset");
$notifs   = [];
if ($notifs_r) { while ($row = mysqli_fetch_assoc($notifs_r)) $notifs[] = $row; }

/* ── Chart data: last 14 days ────────────────────────────────────────────── */
$chart_labels = $chart_sent = $chart_read = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($d));
    $cs = $stat("SELECT COUNT(*) FROM admin_notifications_tbl WHERE DATE(created_at)='$d'");
    $cr = $stat("SELECT COUNT(*) FROM admin_notif_delivery_tbl WHERE DATE(read_at)='$d' AND delivery_status='read'");
    $chart_sent[] = $cs;
    $chart_read[] = $cr;
}

/* ── Hourly distribution for donut chart ────────────────────────────────── */
$type_counts = [];
foreach (['general','update','important','promotion','system_alert'] as $nt) {
    $type_counts[$nt] = $stat("SELECT COUNT(*) FROM admin_notifications_tbl WHERE notif_type='$nt'");
}

/* ── Bulk SMS Nigeria wallet balance ─────────────────────────────────────── */
$sms_wallet_balance = null;
$sms_wallet_error   = null;
$bsn_r = mysqli_query($conn, "SELECT setting_value FROM admin_notif_api_settings WHERE setting_key='bulksms_api_token' LIMIT 1");
if ($bsn_r && $bsn_row = mysqli_fetch_assoc($bsn_r)) {
    $bsn_token = trim($bsn_row['setting_value'] ?? '');
    if (!empty($bsn_token)) {
        $wch = curl_init('https://www.bulksmsnigeria.com/api/v1/wallet?api_token=' . urlencode($bsn_token));
        curl_setopt_array($wch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $wresp = curl_exec($wch);
        $wcode = curl_getinfo($wch, CURLINFO_HTTP_CODE);
        curl_close($wch);
        $wres = json_decode($wresp, true);
        if ($wcode === 200 && isset($wres['data']['wallet_balance'])) {
            $sms_wallet_balance = number_format(floatval($wres['data']['wallet_balance']), 2);
        } else {
            $sms_wallet_error = 'Check API Settings';
        }
    }
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
    .stat-card { border-left: 4px solid #10d596; transition: transform .2s; }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-card.danger  { border-left-color: #dc3545; }
    .stat-card.warning { border-left-color: #ffc107; }
    .stat-card.info    { border-left-color: #17a2b8; }
    .stat-card.purple  { border-left-color: #6f42c1; }
    .stat-icon { width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px; }
    .notif-type-badge { font-size:11px;padding:3px 8px;border-radius:12px; }
    .priority-dot { width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:5px; }
    .chart-card { min-height: 320px; }
    .filter-row .form-control { height: 36px; font-size: 13px; }
    .action-btn { font-size:11px;padding:3px 8px; }
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
      <div class="row page-titles mx-0">
        <div class="col-sm-6 p-md-0">
          <div class="welcome-text">
            <h4 style="color:#10d596;font-weight:700;"><i class="fa fa-bell mr-2"></i><?= $PAGE_TITLE ?></h4>
            <p class="mb-0 text-muted">Manage and track all notifications sent to users</p>
          </div>
        </div>
        <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>easyfinder/dashboard/"><?= SITE_TITLE ?></a></li>
            <li class="breadcrumb-item active">Notifications</li>
          </ol>
        </div>
      </div>

      <!-- Quick Action Buttons -->
      <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap gap-2">
          <a href="admin-notification-create" class="btn btn-primary mr-2" style="background:#10d596;border-color:#10d596;">
            <i class="fa fa-plus mr-1"></i> Create Notification
          </a>
          <a href="admin-notifications?status=pending" class="btn btn-warning mr-2">
            <i class="fa fa-clock-o mr-1"></i> Pending (<?= $pending_notifs ?>)
          </a>
          <a href="admin-notifications?status=failed" class="btn btn-danger mr-2">
            <i class="fa fa-exclamation-circle mr-1"></i> Failed (<?= $failed_notifs ?>)
          </a>
          <a href="admin-notification-ajax?action=export_all" class="btn btn-secondary mr-2">
            <i class="fa fa-download mr-1"></i> Export Report
          </a>
        </div>
      </div>

      <!-- Stats Row 1 -->
      <div class="row">
        <div class="col-xl-2 col-lg-4 col-sm-6 mb-3">
          <div class="card stat-card h-100 mb-0">
            <div class="card-body py-3 px-3">
              <div class="d-flex align-items-center">
                <div class="stat-icon bg-light mr-3" style="background:#e8fff6!important;">
                  <i class="fa fa-bell" style="color:#10d596;"></i>
                </div>
                <div>
                  <p class="mb-1 text-muted" style="font-size:11px;">TOTAL</p>
                  <h4 class="mb-0 font-w700"><?= number_format($total_notifs) ?></h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-sm-6 mb-3">
          <div class="card stat-card h-100 mb-0">
            <div class="card-body py-3 px-3">
              <div class="d-flex align-items-center">
                <div class="stat-icon mr-3" style="background:#e8fff6;">
                  <i class="fa fa-check-circle" style="color:#10d596;"></i>
                </div>
                <div>
                  <p class="mb-1 text-muted" style="font-size:11px;">SENT</p>
                  <h4 class="mb-0 font-w700 text-success"><?= number_format($sent_notifs) ?></h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-sm-6 mb-3">
          <div class="card stat-card warning h-100 mb-0">
            <div class="card-body py-3 px-3">
              <div class="d-flex align-items-center">
                <div class="stat-icon mr-3" style="background:#fff8e1;">
                  <i class="fa fa-clock-o" style="color:#ffc107;"></i>
                </div>
                <div>
                  <p class="mb-1 text-muted" style="font-size:11px;">PENDING</p>
                  <h4 class="mb-0 font-w700 text-warning"><?= number_format($pending_notifs) ?></h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-sm-6 mb-3">
          <div class="card stat-card danger h-100 mb-0">
            <div class="card-body py-3 px-3">
              <div class="d-flex align-items-center">
                <div class="stat-icon mr-3" style="background:#fdecea;">
                  <i class="fa fa-times-circle" style="color:#dc3545;"></i>
                </div>
                <div>
                  <p class="mb-1 text-muted" style="font-size:11px;">FAILED</p>
                  <h4 class="mb-0 font-w700 text-danger"><?= number_format($failed_notifs) ?></h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-sm-6 mb-3">
          <div class="card stat-card info h-100 mb-0">
            <div class="card-body py-3 px-3">
              <div class="d-flex align-items-center">
                <div class="stat-icon mr-3" style="background:#e3f6fd;">
                  <i class="fa fa-users" style="color:#17a2b8;"></i>
                </div>
                <div>
                  <p class="mb-1 text-muted" style="font-size:11px;">USERS REACHED</p>
                  <h4 class="mb-0 font-w700 text-info"><?= number_format($users_reached) ?></h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-sm-6 mb-3">
          <div class="card stat-card purple h-100 mb-0">
            <div class="card-body py-3 px-3">
              <div class="d-flex align-items-center">
                <div class="stat-icon mr-3" style="background:#f3eeff;">
                  <i class="fa fa-bar-chart" style="color:#6f42c1;"></i>
                </div>
                <div>
                  <p class="mb-1 text-muted" style="font-size:11px;">DELIVERY RATE</p>
                  <h4 class="mb-0 font-w700" style="color:#6f42c1;"><?= $delivery_rate ?>%</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Row 2 -->
      <div class="row mb-3">
        <div class="col-md-3 col-sm-6 mb-3">
          <div class="card mb-0 h-100">
            <div class="card-body py-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <p class="mb-1 text-muted" style="font-size:11px;">DRAFT</p>
                  <h5 class="mb-0 text-secondary font-w700"><?= number_format($draft_notifs) ?></h5>
                </div>
                <i class="fa fa-file-text-o fa-2x text-secondary"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
          <div class="card mb-0 h-100">
            <div class="card-body py-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <p class="mb-1 text-muted" style="font-size:11px;">TOTAL READS</p>
                  <h5 class="mb-0 font-w700" style="color:#10d596;"><?= number_format($total_read) ?></h5>
                </div>
                <i class="fa fa-eye fa-2x" style="color:#10d596;"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
          <div class="card mb-0 h-100">
            <div class="card-body py-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <p class="mb-1 text-muted" style="font-size:11px;">REGISTERED USERS</p>
                  <h5 class="mb-0 font-w700 text-info"><?= number_format($total_users) ?></h5>
                </div>
                <i class="fa fa-user-circle fa-2x text-info"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
          <?php if ($sms_wallet_balance !== null): ?>
          <div class="card mb-0 h-100" style="background:linear-gradient(135deg,#6f42c1,#9b59b6);color:#fff;border:none;">
            <div class="card-body py-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <p class="mb-1" style="font-size:10px;opacity:.8;letter-spacing:.5px;">SMS WALLET BALANCE</p>
                  <h5 class="mb-0 font-w700" style="color:#fff;">₦<?= $sms_wallet_balance ?></h5>
                  <small style="opacity:.7;font-size:10px;">Bulk SMS Nigeria</small>
                </div>
                <div>
                  <i class="fa fa-mobile fa-2x" style="opacity:.4;"></i><br>
                  <a href="admin-notification-settings" style="font-size:9px;color:rgba(255,255,255,.7);">Top up ↗</a>
                </div>
              </div>
            </div>
          </div>
          <?php elseif ($sms_wallet_error !== null): ?>
          <div class="card mb-0 h-100" style="border-left:4px solid #6f42c1;">
            <div class="card-body py-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <p class="mb-1 text-muted" style="font-size:10px;letter-spacing:.5px;">SMS WALLET</p>
                  <h6 class="mb-0 text-muted font-w700" style="font-size:12px;"><?= htmlspecialchars($sms_wallet_error) ?></h6>
                  <a href="admin-notification-settings" style="font-size:10px;color:#6f42c1;">Configure API →</a>
                </div>
                <i class="fa fa-mobile fa-2x" style="color:#6f42c1;opacity:.4;"></i>
              </div>
            </div>
          </div>
          <?php else: ?>
          <div class="card mb-0 h-100" style="border:2px dashed #dee2e6;">
            <div class="card-body py-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <p class="mb-1 text-muted" style="font-size:10px;letter-spacing:.5px;">SMS WALLET</p>
                  <p class="mb-0 text-muted" style="font-size:12px;">Not configured</p>
                  <a href="admin-notification-settings" style="font-size:10px;color:#6f42c1;">Setup Bulk SMS →</a>
                </div>
                <i class="fa fa-mobile fa-2x text-muted" style="opacity:.3;"></i>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 d-none"><!-- placeholder to maintain layout -->
          <div class="card mb-0 h-100">
            <div class="card-body py-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <p class="mb-1 text-muted" style="font-size:11px;">DELIVERED</p>
                  <h5 class="mb-0 font-w700 text-success"><?= number_format($total_delivered) ?></h5>
                </div>
                <i class="fa fa-paper-plane fa-2x text-success"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="row">
        <div class="col-xl-8 col-lg-8 mb-4">
          <div class="card chart-card">
            <div class="card-header">
              <h4 class="card-title"><i class="fa fa-line-chart mr-2" style="color:#10d596;"></i>Notification Activity (Last 14 Days)</h4>
            </div>
            <div class="card-body">
              <div id="notifActivityChart"></div>
            </div>
          </div>
        </div>
        <div class="col-xl-4 col-lg-4 mb-4">
          <div class="card chart-card">
            <div class="card-header">
              <h4 class="card-title"><i class="fa fa-pie-chart mr-2" style="color:#10d596;"></i>By Type</h4>
            </div>
            <div class="card-body">
              <div id="notifTypeChart"></div>
              <div class="mt-3">
                <?php
                $type_labels = ['general'=>'General','update'=>'Update','important'=>'Important','promotion'=>'Promotion','system_alert'=>'System Alert'];
                $type_colors = ['general'=>'#17a2b8','update'=>'#10d596','important'=>'#ffc107','promotion'=>'#6f42c1','system_alert'=>'#dc3545'];
                foreach ($type_labels as $k => $v): ?>
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <div class="d-flex align-items-center">
                    <span style="width:12px;height:12px;border-radius:50%;background:<?= $type_colors[$k] ?>;display:inline-block;margin-right:8px;"></span>
                    <small><?= $v ?></small>
                  </div>
                  <small class="font-w600"><?= $type_counts[$k] ?></small>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter & Search -->
      <div class="card mb-3">
        <div class="card-body py-3">
          <form method="GET" action="">
            <div class="row filter-row align-items-end">
              <div class="col-md-3 col-sm-6 mb-2">
                <label class="mb-1" style="font-size:12px;font-weight:600;">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Title, message, creator..." value="<?= htmlspecialchars($search) ?>">
              </div>
              <div class="col-md-2 col-sm-6 mb-2">
                <label class="mb-1" style="font-size:12px;font-weight:600;">Status</label>
                <select name="status" class="form-control">
                  <option value="">All Status</option>
                  <option value="sent" <?= $filter_status==='sent'?'selected':'' ?>>Sent</option>
                  <option value="pending" <?= $filter_status==='pending'?'selected':'' ?>>Pending</option>
                  <option value="failed" <?= $filter_status==='failed'?'selected':'' ?>>Failed</option>
                  <option value="draft" <?= $filter_status==='draft'?'selected':'' ?>>Draft</option>
                </select>
              </div>
              <div class="col-md-2 col-sm-6 mb-2">
                <label class="mb-1" style="font-size:12px;font-weight:600;">Type</label>
                <select name="type" class="form-control">
                  <option value="">All Types</option>
                  <option value="general" <?= $filter_type==='general'?'selected':'' ?>>General</option>
                  <option value="update" <?= $filter_type==='update'?'selected':'' ?>>Update</option>
                  <option value="important" <?= $filter_type==='important'?'selected':'' ?>>Important</option>
                  <option value="promotion" <?= $filter_type==='promotion'?'selected':'' ?>>Promotion</option>
                  <option value="system_alert" <?= $filter_type==='system_alert'?'selected':'' ?>>System Alert</option>
                </select>
              </div>
              <div class="col-md-2 col-sm-6 mb-2">
                <label class="mb-1" style="font-size:12px;font-weight:600;">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filter_date_from) ?>">
              </div>
              <div class="col-md-2 col-sm-6 mb-2">
                <label class="mb-1" style="font-size:12px;font-weight:600;">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filter_date_to) ?>">
              </div>
              <div class="col-md-1 col-sm-6 mb-2">
                <button type="submit" class="btn btn-primary btn-block" style="background:#10d596;border-color:#10d596;height:36px;">
                  <i class="fa fa-search"></i>
                </button>
              </div>
            </div>
            <?php if ($search || $filter_status || $filter_type || $filter_date_from || $filter_date_to): ?>
            <div class="mt-1">
              <a href="admin-notifications" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-times mr-1"></i>Clear Filters
              </a>
              <small class="text-muted ml-2"><?= number_format($total_filtered) ?> result(s) found</small>
            </div>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <!-- Notifications Table -->
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h4 class="card-title mb-0"><i class="fa fa-list mr-2" style="color:#10d596;"></i>Recent Notification Activity</h4>
          <a href="admin-notification-create" class="btn btn-sm btn-primary" style="background:#10d596;border-color:#10d596;">
            <i class="fa fa-plus mr-1"></i> New
          </a>
        </div>
        <div class="card-body">
          <?php if (empty($notifs)): ?>
          <div class="text-center py-5">
            <i class="fa fa-bell-slash fa-4x text-muted mb-3 d-block"></i>
            <h5 class="text-muted">No notifications found</h5>
            <p class="text-muted">Create your first notification to get started.</p>
            <a href="admin-notification-create" class="btn btn-primary mt-2" style="background:#10d596;border-color:#10d596;">
              <i class="fa fa-plus mr-1"></i> Create Notification
            </a>
          </div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0" style="font-size:13px;">
              <thead style="background:#f8f9fa;">
                <tr>
                  <th>#</th>
                  <th>Title / Message</th>
                  <th>Type</th>
                  <th>Priority</th>
                  <th>Status</th>
                  <th>Recipients</th>
                  <th>Delivery</th>
                  <th>Read</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php
              $type_badge_map = ['important'=>'warning','update'=>'info','promotion'=>'primary','system_alert'=>'danger','general'=>'secondary'];
              $priority_colors = ['low'=>'#6c757d','medium'=>'#ffc107','high'=>'#dc3545'];
              $status_badge = ['draft'=>'secondary','pending'=>'warning','sent'=>'success','failed'=>'danger'];
              foreach ($notifs as $n):
                $badge = $type_badge_map[$n['notif_type']] ?? 'secondary';
                $pcolor = $priority_colors[$n['priority']] ?? '#ccc';
                $sbadge = $status_badge[$n['status']] ?? 'secondary';
                $delivery_pct = $n['total_recipients'] > 0 ? round(($n['delivered_count']/$n['total_recipients'])*100) : 0;
              ?>
              <tr>
                <td><?= $n['id'] ?></td>
                <td>
                  <strong><?= htmlspecialchars($n['title']) ?></strong><br>
                  <small class="text-muted"><?= htmlspecialchars(mb_substr(strip_tags($n['message']),0,60)) ?>...</small>
                </td>
                <td><span class="badge badge-<?= $badge ?> notif-type-badge"><?= ucfirst(str_replace('_',' ',$n['notif_type'])) ?></span></td>
                <td>
                  <span class="priority-dot" style="background:<?= $pcolor ?>; vertical-align:middle;"></span>
                  <?= ucfirst($n['priority']) ?>
                </td>
                <td><span class="badge badge-<?= $sbadge ?>"><?= ucfirst($n['status']) ?></span></td>
                <td><?= number_format($n['total_recipients']) ?></td>
                <td>
                  <?php if ($n['total_recipients'] > 0): ?>
                  <div class="d-flex align-items-center">
                    <div class="progress mr-1" style="width:50px;height:6px;">
                      <div class="progress-bar bg-success" style="width:<?= $delivery_pct ?>%"></div>
                    </div>
                    <small><?= $delivery_pct ?>%</small>
                  </div>
                  <?php else: ?>
                  <small class="text-muted">—</small>
                  <?php endif; ?>
                </td>
                <td>
                  <?= $n['read_count'] > 0 ? '<span class="text-success font-w600">'.number_format($n['read_count']).'</span>' : '<small class="text-muted">0</small>' ?>
                </td>
                <td><small><?= $n['created_at'] ? date('d M Y H:i', strtotime($n['created_at'])) : '—' ?></small></td>
                <td>
                  <div class="d-flex flex-wrap" style="gap:3px;">
                    <a href="admin-notification-detail?id=<?= $n['id'] ?>" class="btn btn-xs btn-info action-btn" title="View Details">
                      <i class="fa fa-eye"></i>
                    </a>
                    <?php if ($n['status'] === 'draft' || $n['status'] === 'failed'): ?>
                    <a href="admin-notification-create?edit=<?= $n['id'] ?>" class="btn btn-xs btn-warning action-btn" title="Edit">
                      <i class="fa fa-edit"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($n['status'] === 'failed' || ($n['status'] === 'sent' && $n['failed_count'] > 0)): ?>
                    <a href="admin-notification-ajax?action=resend&id=<?= $n['id'] ?>" class="btn btn-xs btn-success action-btn" title="Resend Failed">
                      <i class="fa fa-refresh"></i>
                    </a>
                    <?php endif; ?>
                    <a href="admin-notification-ajax?action=export&id=<?= $n['id'] ?>" class="btn btn-xs btn-secondary action-btn" title="Export">
                      <i class="fa fa-download"></i>
                    </a>
                    <a href="admin-notification-ajax?action=delete&id=<?= $n['id'] ?>" class="btn btn-xs btn-danger action-btn" title="Delete"
                       onclick="return confirm('Delete this notification and all delivery records?')">
                      <i class="fa fa-trash"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
          <nav class="mt-3">
            <ul class="pagination pagination-sm justify-content-center mb-0">
              <?php
              $qs = http_build_query(array_filter(['search'=>$search,'status'=>$filter_status,'type'=>$filter_type,'date_from'=>$filter_date_from,'date_to'=>$filter_date_to]));
              for ($p = 1; $p <= $total_pages; $p++):
                $active = $p === $page ? 'active' : '';
                $link = 'admin-notifications?' . ($qs ? $qs.'&' : '') . 'page='.$p;
              ?>
              <li class="page-item <?= $active ?>">
                <a class="page-link <?= $active ? '' : '' ?>" href="<?= $link ?>" style="<?= $active ? 'background:#10d596;border-color:#10d596;color:#fff;' : '' ?>"><?= $p ?></a>
              </li>
              <?php endfor; ?>
            </ul>
          </nav>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
  <?php require_once 'layout/footer-propt.inc.php'; ?>
</div>

<script>
/* Activity Line Chart */
var activityOptions = {
  series: [
    { name: 'Notifications Sent', data: <?= json_encode($chart_sent) ?> },
    { name: 'Reads', data: <?= json_encode($chart_read) ?> }
  ],
  chart: { type: 'area', height: 280, toolbar: { show: false }, zoom: { enabled: false } },
  colors: ['#10d596', '#6f42c1'],
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  xaxis: { categories: <?= json_encode($chart_labels) ?>, labels: { style: { fontSize: '11px' } } },
  yaxis: { labels: { style: { fontSize: '11px' } }, min: 0, forceNiceScale: true },
  legend: { position: 'top', fontSize: '12px' },
  grid: { borderColor: '#f0f0f0' },
  tooltip: { shared: true, intersect: false }
};
new ApexCharts(document.querySelector('#notifActivityChart'), activityOptions).render();

/* Type Donut Chart */
var typeOptions = {
  series: [<?= implode(',', array_values($type_counts)) ?>],
  chart: { type: 'donut', height: 200 },
  labels: ['General', 'Update', 'Important', 'Promotion', 'System Alert'],
  colors: ['#17a2b8', '#10d596', '#ffc107', '#6f42c1', '#dc3545'],
  legend: { show: false },
  plotOptions: { pie: { donut: { size: '65%' } } },
  dataLabels: { enabled: false },
  tooltip: { y: { formatter: v => v + ' notifications' } }
};
new ApexCharts(document.querySelector('#notifTypeChart'), typeOptions).render();

/* Auto-refresh stats every 60s */
setInterval(function(){
  fetch('admin-notification-ajax?action=stats')
    .then(r => r.json())
    .then(function(d){
      if(d.success) {
        console.log('Stats refreshed');
      }
    }).catch(function(){});
}, 60000);
</script>
</body>
</html>
