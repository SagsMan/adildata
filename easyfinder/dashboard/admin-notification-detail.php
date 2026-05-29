<?php
require_once '../inc/user_session.inc.php';
$PAGE_TITLE = 'Notification Details';
$URL_NAME   = 'admin-notification-detail';

if (!in_array(1, explode(',', $Auth->admin_role)) && !in_array(2, explode(',', $Auth->admin_role))) {
    header('Location: ./'); exit;
}

$notif_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($notif_id < 1) { header('Location: admin-notifications'); exit; }

$conn = mysqli_connect('localhost','adiliqgs_adildata','adildata2026','adiliqgs_adildata');

/* ── Load notification ───────────────────────────────────────────────────── */
$nr = mysqli_query($conn, "SELECT * FROM admin_notifications_tbl WHERE id=$notif_id LIMIT 1");
if (!$nr || !($notif = mysqli_fetch_assoc($nr))) {
    header('Location: admin-notifications'); exit;
}
$PAGE_TITLE = 'Notification: ' . htmlspecialchars($notif['title']);

/* ── Sync read status from legacy table ──────────────────────────────────── */
if (!empty($notif['legacy_notif_id'])) {
    $lid = intval($notif['legacy_notif_id']);
    $lr  = mysqli_query($conn, "SELECT is_read_by FROM notifications_tbl WHERE id=$lid");
    if ($lr && $lrow = mysqli_fetch_assoc($lr)) {
        $readers = json_decode($lrow['is_read_by'] ?: '[]', true);
        if (is_array($readers)) {
            foreach ($readers as $re) {
                $re_esc = mysqli_real_escape_string($conn, $re);
                mysqli_query($conn, "UPDATE admin_notif_delivery_tbl SET delivery_status='read', read_at=NOW()
                    WHERE notification_id=$notif_id AND user_email='$re_esc' AND delivery_status!='read'");
            }
        }
    }
    /* Recount */
    $rc_r = mysqli_query($conn, "SELECT COUNT(*) FROM admin_notif_delivery_tbl WHERE notification_id=$notif_id AND delivery_status='read'");
    $rc_v = $rc_r ? intval(mysqli_fetch_row($rc_r)[0]) : 0;
    $dc_r = mysqli_query($conn, "SELECT COUNT(*) FROM admin_notif_delivery_tbl WHERE notification_id=$notif_id AND delivery_status IN('delivered','read')");
    $dc_v = $dc_r ? intval(mysqli_fetch_row($dc_r)[0]) : 0;
    mysqli_query($conn, "UPDATE admin_notifications_tbl SET read_count=$rc_v, delivered_count=$dc_v WHERE id=$notif_id");
    $notif['read_count']      = $rc_v;
    $notif['delivered_count'] = $dc_v;
}

/* ── Delivery list filters ───────────────────────────────────────────────── */
$page    = max(1, intval($_GET['page'] ?? 1));
$perpage = 20;
$offset  = ($page - 1) * $perpage;

$dsearch = trim($_GET['dsearch'] ?? '');
$dstatus = $_GET['dstatus'] ?? '';

$dwhere = ["notification_id=$notif_id"];
if ($dsearch !== '') {
    $ds = mysqli_real_escape_string($conn, $dsearch);
    $dwhere[] = "(user_name LIKE '%$ds%' OR user_email LIKE '%$ds%' OR user_phone LIKE '%$ds%')";
}
if (in_array($dstatus, ['pending','sent','delivered','failed','read'])) {
    $dwhere[] = "delivery_status='$dstatus'";
}
$dwhere_sql = 'WHERE ' . implode(' AND ', $dwhere);

$total_delivery_r = mysqli_query($conn, "SELECT COUNT(*) FROM admin_notif_delivery_tbl $dwhere_sql");
$total_delivery   = $total_delivery_r ? intval(mysqli_fetch_row($total_delivery_r)[0]) : 0;
$total_pages      = max(1, ceil($total_delivery / $perpage));

$delivery_r = mysqli_query($conn, "SELECT * FROM admin_notif_delivery_tbl $dwhere_sql ORDER BY id ASC LIMIT $perpage OFFSET $offset");
$deliveries = [];
if ($delivery_r) { while ($drow = mysqli_fetch_assoc($delivery_r)) $deliveries[] = $drow; }

/* ── Per-status counts ───────────────────────────────────────────────────── */
$count_by_status = [];
foreach (['sent','delivered','failed','read','pending'] as $ds_v) {
    $csr = mysqli_query($conn, "SELECT COUNT(*) FROM admin_notif_delivery_tbl WHERE notification_id=$notif_id AND delivery_status='$ds_v'");
    $count_by_status[$ds_v] = $csr ? intval(mysqli_fetch_row($csr)[0]) : 0;
}

$delivery_rate = $notif['total_recipients'] > 0
    ? round(($notif['delivered_count'] / $notif['total_recipients']) * 100, 1) : 0;
$read_rate = $notif['total_recipients'] > 0
    ? round(($notif['read_count'] / $notif['total_recipients']) * 100, 1) : 0;

mysqli_close($conn);

/* ── Badge / color maps ──────────────────────────────────────────────────── */
$type_badge = ['important'=>'warning','update'=>'info','promotion'=>'primary','system_alert'=>'danger','general'=>'secondary'];
$type_label = ['important'=>'Important','update'=>'Update','promotion'=>'Promotion','system_alert'=>'System Alert','general'=>'General'];
$pri_colors = ['low'=>'#6c757d','medium'=>'#ffc107','high'=>'#dc3545'];
$status_badge_map = ['draft'=>'secondary','pending'=>'warning','sent'=>'success','failed'=>'danger'];
$ds_badge = ['pending'=>'warning','sent'=>'info','delivered'=>'success','failed'=>'danger','read'=>'primary'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <?php require_once 'layout/header-propt.inc.php'; ?>
  <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?></title>
  <style>
    .info-label { font-size:11px;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.5px; }
    .info-value { font-size:14px;font-weight:600;color:#222; }
    .stat-mini { text-align:center;padding:14px;border-radius:8px; }
    .stat-mini .num { font-size:24px;font-weight:800;line-height:1; }
    .stat-mini .lbl { font-size:11px;color:#6c757d;margin-top:3px; }
    .message-body { background:#f8fff8;border-left:4px solid #10d596;border-radius:4px;padding:16px;font-size:14px;line-height:1.6;white-space:pre-wrap;word-break:break-word; }
    .delivery-status-tabs .btn { font-size:12px;padding:5px 12px; }
  </style>
</head>
<body>
<?php require_once 'layout/preloader.inc.php'; ?>
<div id="main-wrapper">
  <?php require_once 'layout/header.inc.php'; require_once 'layout/sidebar.inc.php'; ?>

  <div class="content-body">
    <?php include 'layout/minor-top-navbar.inc.php'; ?>
    <div class="container-fluid">

      <!-- Header -->
      <div class="row page-titles mx-0 mb-3">
        <div class="col-sm-7 p-md-0">
          <h4 style="color:#10d596;font-weight:700;">
            <i class="fa fa-bell mr-2"></i>
            <?= htmlspecialchars($notif['title']) ?>
          </h4>
          <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
            <span class="badge badge-<?= $type_badge[$notif['notif_type']] ?? 'secondary' ?>" style="font-size:12px;padding:5px 10px;">
              <?= $type_label[$notif['notif_type']] ?? 'General' ?>
            </span>
            <span class="badge badge-<?= $status_badge_map[$notif['status']] ?? 'secondary' ?>" style="font-size:12px;padding:5px 10px;">
              <?= ucfirst($notif['status']) ?>
            </span>
            <span style="font-size:12px;color:#6c757d;">
              <span style="width:10px;height:10px;border-radius:50%;background:<?= $pri_colors[$notif['priority']] ?? '#ccc' ?>;display:inline-block;"></span>
              <?= ucfirst($notif['priority']) ?> Priority
            </span>
          </div>
        </div>
        <div class="col-sm-5 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex align-items-center flex-wrap" style="gap:6px;">
          <a href="admin-notifications" class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-arrow-left mr-1"></i> Back
          </a>
          <?php if ($notif['status'] === 'draft' || $notif['status'] === 'failed'): ?>
          <a href="admin-notification-create?edit=<?= $notif_id ?>" class="btn btn-sm btn-warning">
            <i class="fa fa-edit mr-1"></i> Edit
          </a>
          <?php endif; ?>
          <a href="admin-notification-ajax?action=resend&id=<?= $notif_id ?>" class="btn btn-sm btn-success"
             onclick="return confirm('Resend to all failed/pending users?')">
            <i class="fa fa-refresh mr-1"></i> Resend Failed
          </a>
          <a href="admin-notification-ajax?action=export&id=<?= $notif_id ?>" class="btn btn-sm btn-secondary">
            <i class="fa fa-download mr-1"></i> Export CSV
          </a>
          <a href="admin-notification-ajax?action=delete&id=<?= $notif_id ?>" class="btn btn-sm btn-danger"
             onclick="return confirm('Permanently delete this notification and all delivery records?')">
            <i class="fa fa-trash mr-1"></i> Delete
          </a>
        </div>
      </div>

      <div class="row">
        <!-- Left: Details -->
        <div class="col-xl-4 col-lg-4 mb-4">

          <!-- Notification Info -->
          <div class="card mb-3">
            <div class="card-header">
              <h4 class="card-title"><i class="fa fa-info-circle mr-2" style="color:#10d596;"></i>Details</h4>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <div class="info-label">Created By</div>
                <div class="info-value"><?= htmlspecialchars($notif['created_by'] ?? '—') ?></div>
              </div>
              <div class="mb-3">
                <div class="info-label">Created At</div>
                <div class="info-value"><?= $notif['created_at'] ? date('d M Y, H:i', strtotime($notif['created_at'])) : '—' ?></div>
              </div>
              <div class="mb-3">
                <div class="info-label">Sent At</div>
                <div class="info-value"><?= $notif['sent_at'] ? date('d M Y, H:i', strtotime($notif['sent_at'])) : '—' ?></div>
              </div>
              <?php if ($notif['scheduled_at']): ?>
              <div class="mb-3">
                <div class="info-label">Scheduled At</div>
                <div class="info-value"><?= date('d M Y, H:i', strtotime($notif['scheduled_at'])) ?></div>
              </div>
              <?php endif; ?>
              <div class="mb-3">
                <div class="info-label">Target Audience</div>
                <div class="info-value">
                  <?php if ($notif['target'] === 'all'): ?>
                    <span class="badge badge-info">All Users</span>
                  <?php else: ?>
                    <span class="badge badge-warning"><?= htmlspecialchars($notif['target_email'] ?? 'Specific') ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="mb-0">
                <div class="info-label">Last Updated</div>
                <div class="info-value" style="font-size:12px;"><?= $notif['updated_at'] ? date('d M Y, H:i', strtotime($notif['updated_at'])) : '—' ?></div>
              </div>
            </div>
          </div>

          <!-- Message Body -->
          <div class="card mb-3">
            <div class="card-header">
              <h4 class="card-title"><i class="fa fa-comment mr-2" style="color:#10d596;"></i>Message</h4>
            </div>
            <div class="card-body">
              <div class="message-body"><?= nl2br(htmlspecialchars($notif['message'])) ?></div>
            </div>
          </div>

        </div>

        <!-- Right: Stats + Delivery -->
        <div class="col-xl-8 col-lg-8">

          <!-- Stats Row -->
          <div class="row mb-3">
            <div class="col-6 col-md-3 mb-3">
              <div class="card h-100 mb-0">
                <div class="card-body stat-mini">
                  <div class="num text-dark"><?= number_format($notif['total_recipients']) ?></div>
                  <div class="lbl">Total Recipients</div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
              <div class="card h-100 mb-0">
                <div class="card-body stat-mini">
                  <div class="num" style="color:#10d596;"><?= $delivery_rate ?>%</div>
                  <div class="lbl">Delivery Rate</div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
              <div class="card h-100 mb-0">
                <div class="card-body stat-mini">
                  <div class="num text-primary"><?= $read_rate ?>%</div>
                  <div class="lbl">Read Rate</div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
              <div class="card h-100 mb-0">
                <div class="card-body stat-mini">
                  <div class="num text-danger"><?= number_format($notif['failed_count']) ?></div>
                  <div class="lbl">Failed</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Per-Status Breakdown -->
          <div class="row mb-3">
            <?php
            $st_info = [
              'sent'      => ['label'=>'Sent',      'color'=>'#17a2b8','icon'=>'fa-paper-plane'],
              'delivered' => ['label'=>'Delivered', 'color'=>'#10d596','icon'=>'fa-check'],
              'read'      => ['label'=>'Read',      'color'=>'#6f42c1','icon'=>'fa-eye'],
              'failed'    => ['label'=>'Failed',    'color'=>'#dc3545','icon'=>'fa-times'],
              'pending'   => ['label'=>'Pending',   'color'=>'#ffc107','icon'=>'fa-clock-o'],
            ];
            foreach ($st_info as $sv => $si): ?>
            <div class="col-6 col-md-4 col-lg mb-2">
              <div class="card mb-0 h-100" style="border-left:3px solid <?= $si['color'] ?>;">
                <div class="card-body py-2 px-3">
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <div style="font-size:11px;color:#6c757d;"><?= $si['label'] ?></div>
                      <div style="font-size:20px;font-weight:800;color:<?= $si['color'] ?>;"><?= number_format($count_by_status[$sv]) ?></div>
                    </div>
                    <i class="fa <?= $si['icon'] ?> fa-lg" style="color:<?= $si['color'] ?>;opacity:.5;"></i>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Delivery Progress Bar -->
          <?php if ($notif['total_recipients'] > 0): ?>
          <div class="card mb-3">
            <div class="card-body py-3">
              <div class="d-flex justify-content-between mb-1">
                <small class="font-w600">Delivery Progress</small>
                <small><?= number_format($notif['delivered_count']) ?> / <?= number_format($notif['total_recipients']) ?></small>
              </div>
              <div class="progress mb-2" style="height:8px;">
                <div class="progress-bar bg-success" style="width:<?= $delivery_rate ?>%;background:#10d596!important;"></div>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <small class="font-w600">Read Progress</small>
                <small><?= number_format($notif['read_count']) ?> / <?= number_format($notif['total_recipients']) ?></small>
              </div>
              <div class="progress" style="height:8px;">
                <div class="progress-bar" style="width:<?= $read_rate ?>%;background:#6f42c1;"></div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Delivery List -->
          <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="gap:8px;">
              <h4 class="card-title mb-0"><i class="fa fa-list mr-2" style="color:#10d596;"></i>User Delivery Tracking</h4>
              <form method="GET" class="d-flex align-items-center flex-wrap" style="gap:6px;">
                <input type="hidden" name="id" value="<?= $notif_id ?>">
                <input type="text" name="dsearch" class="form-control form-control-sm" placeholder="Search name/email/phone..." style="width:180px;" value="<?= htmlspecialchars($dsearch) ?>">
                <select name="dstatus" class="form-control form-control-sm" style="width:120px;">
                  <option value="">All Status</option>
                  <?php foreach ($st_info as $sv => $si): ?>
                  <option value="<?= $sv ?>" <?= $dstatus===$sv?'selected':'' ?>><?= $si['label'] ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary" style="background:#10d596;border-color:#10d596;">
                  <i class="fa fa-search"></i>
                </button>
                <?php if ($dsearch || $dstatus): ?>
                <a href="admin-notification-detail?id=<?= $notif_id ?>" class="btn btn-sm btn-outline-secondary">
                  <i class="fa fa-times"></i>
                </a>
                <?php endif; ?>
              </form>
            </div>
            <div class="card-body p-0">
              <?php if (empty($deliveries)): ?>
              <div class="text-center py-5">
                <i class="fa fa-users fa-3x text-muted mb-3 d-block"></i>
                <p class="text-muted">
                  <?= $notif['status'] === 'sent' ? 'No delivery records match your filter.' : 'No delivery records yet. Send this notification first.' ?>
                </p>
              </div>
              <?php else: ?>
              <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0" style="font-size:13px;">
                  <thead style="background:#f8f9fa;">
                    <tr>
                      <th>#</th>
                      <th>User Name</th>
                      <th>Email / Phone</th>
                      <th>Status</th>
                      <th>Sent At</th>
                      <th>Delivered At</th>
                      <th>Read At</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($deliveries as $i => $d): ?>
                  <tr>
                    <td><?= $offset + $i + 1 ?></td>
                    <td><?= htmlspecialchars($d['user_name'] ?: '—') ?></td>
                    <td>
                      <?php if ($d['user_email']): ?>
                        <small><?= htmlspecialchars($d['user_email']) ?></small>
                      <?php endif; ?>
                      <?php if ($d['user_phone']): ?>
                        <br><small class="text-muted"><?= htmlspecialchars($d['user_phone']) ?></small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge badge-<?= $ds_badge[$d['delivery_status']] ?? 'secondary' ?>">
                        <?= ucfirst($d['delivery_status']) ?>
                      </span>
                    </td>
                    <td><small><?= $d['sent_at'] ? date('d M H:i', strtotime($d['sent_at'])) : '—' ?></small></td>
                    <td><small><?= $d['delivered_at'] ? date('d M H:i', strtotime($d['delivered_at'])) : '—' ?></small></td>
                    <td>
                      <?php if ($d['read_at']): ?>
                        <small class="text-success"><i class="fa fa-check-circle mr-1"></i><?= date('d M H:i', strtotime($d['read_at'])) ?></small>
                      <?php else: ?>
                        <small class="text-muted">—</small>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <?php if ($total_pages > 1): ?>
              <div class="px-3 py-2">
                <nav>
                  <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php
                    $qs2 = http_build_query(array_filter(['id'=>$notif_id,'dsearch'=>$dsearch,'dstatus'=>$dstatus]));
                    for ($p = 1; $p <= min($total_pages, 20); $p++):
                      $active = $p === $page ? 'active' : '';
                    ?>
                    <li class="page-item <?= $active ?>">
                      <a class="page-link" href="admin-notification-detail?<?= $qs2 ?>&page=<?= $p ?>"
                         style="<?= $active ? 'background:#10d596;border-color:#10d596;color:#fff;' : '' ?>">
                        <?= $p ?>
                      </a>
                    </li>
                    <?php endfor; ?>
                  </ul>
                </nav>
                <p class="text-center text-muted mt-1 mb-0" style="font-size:11px;">
                  Showing <?= number_format($total_delivery) ?> user(s)
                </p>
              </div>
              <?php endif; ?>
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
