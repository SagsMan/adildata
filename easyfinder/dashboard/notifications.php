<?php
require_once '../inc/user_session.inc.php';
$PAGE_TITLE = 'Notifications';
$URL_NAME = 'notifications';

// Admin-only guard
if (!in_array(1, explode(',', $Auth->admin_role)) && !in_array(2, explode(',', $Auth->admin_role))) {
    header('Location: ./');
    exit;
}

$conn = mysqli_connect('localhost','adiliqgs_adildata','adildata2026','adiliqgs_adildata');

// ── Auto-create table if not exists ──────────────────────────────────────────
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifications_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','danger') DEFAULT 'info',
    target ENUM('all','specific') DEFAULT 'all',
    target_email VARCHAR(255) NULL,
    created_by VARCHAR(255) NULL,
    is_read_by LONGTEXT NULL DEFAULT '[]',
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Handle Create Notification ────────────────────────────────────────────────
if (isset($_POST['send_notification'])) {
    $title   = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['title'])));
    $message = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['message'])));
    $type    = in_array($_POST['type'],['info','success','warning','danger']) ? $_POST['type'] : 'info';
    $target  = $_POST['target'] === 'specific' ? 'specific' : 'all';
    $target_email = ($target === 'specific' && !empty($_POST['target_email']))
                    ? mysqli_real_escape_string($conn, trim($_POST['target_email'])) : null;
    $admin_email  = mysqli_real_escape_string($conn, $Auth->email);

    $te_sql = $target_email ? "'$target_email'" : 'NULL';
    $ins = mysqli_query($conn,
        "INSERT INTO notifications_tbl(title,message,type,target,target_email,created_by,status)
         VALUES('$title','$message','$type','$target',$te_sql,'$admin_email',1)"
    );
    if ($ins) {
        array_push($SITE_SUCCESS, 'Notification sent successfully!');
    } else {
        array_push($SITE_ERRORS, 'Failed to send notification: ' . mysqli_error($conn));
    }
}

// ── Handle Delete ─────────────────────────────────────────────────────────────
if (isset($_GET['delete_notif']) && is_numeric($_GET['delete_notif'])) {
    mysqli_query($conn, "UPDATE notifications_tbl SET status = 0 WHERE id = " . intval($_GET['delete_notif']));
    header('Location: notifications'); exit;
}

// ── Fetch all notifications ───────────────────────────────────────────────────
$notifs = [];
$r = mysqli_query($conn, "SELECT * FROM notifications_tbl ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($r)) { $notifs[] = $row; }

mysqli_close($conn);
?>
<?php require_once 'layout/header-propt.inc.php'; ?>
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
                        <h4 class="font-w600 mb-0" style="color:#10d596;">Notifications</h4>
                        <p class="mb-0">Send and manage system announcements</p>
                    </div>
                </div>

                <?php foreach ($SITE_ERRORS as $err): ?>
                    <div class="alert alert-danger"><?= $err ?></div>
                <?php endforeach; ?>
                <?php foreach ($SITE_SUCCESS as $ok): ?>
                    <div class="alert alert-success"><?= $ok ?></div>
                <?php endforeach; ?>

                <div class="row">
                    <!-- Send Notification Form -->
                    <div class="col-xl-5 col-lg-5">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Send New Notification</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="title" class="form-control" placeholder="Notification title" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Message</label>
                                        <textarea name="message" class="form-control" rows="4" placeholder="Enter your message..." required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Type</label>
                                        <select name="type" class="form-control">
                                            <option value="info">Info (Blue)</option>
                                            <option value="success">Success (Green)</option>
                                            <option value="warning">Warning (Orange)</option>
                                            <option value="danger">Alert (Red)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Send To</label>
                                        <select name="target" class="form-control" id="targetSelect" onchange="toggleTarget(this)">
                                            <option value="all">All Users</option>
                                            <option value="specific">Specific User</option>
                                        </select>
                                    </div>
                                    <div class="form-group" id="specificEmail" style="display:none;">
                                        <label>User Email</label>
                                        <input type="email" name="target_email" class="form-control" placeholder="user@example.com">
                                    </div>
                                    <button type="submit" name="send_notification" class="btn btn-primary btn-block"
                                            style="background-color:#10d596!important;border-color:#10d596!important;">
                                        <i class="fa fa-bell mr-2"></i> Send Notification
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications List -->
                    <div class="col-xl-7 col-lg-7">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">All Notifications (<?= count($notifs) ?>)</h4>
                            </div>
                            <div class="card-body">
                                <?php if (empty($notifs)): ?>
                                    <p class="text-center text-muted">No notifications sent yet.</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead><tr>
                                        <th>Title</th><th>Type</th><th>Target</th><th>Date</th><th>Status</th><th>Action</th>
                                    </tr></thead>
                                    <tbody>
                                    <?php foreach ($notifs as $n): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($n['title']) ?></strong><br>
                                            <small><?= htmlspecialchars(substr($n['message'],0,60)) ?>...</small></td>
                                        <td><span class="badge badge-<?= $n['type'] ?>"><?= ucfirst($n['type']) ?></span></td>
                                        <td><?= $n['target'] === 'all' ? '<span class="badge badge-info">All Users</span>'
                                               : '<span class="badge badge-warning">' . htmlspecialchars($n['target_email']) . '</span>' ?></td>
                                        <td><?= !empty($n['created_at']) ? date('d M Y H:i', strtotime($n['created_at'])) : (!empty($n['date_created']) ? date('d M Y H:i', strtotime($n['date_created'])) : '—') ?></td>
                                        <td><?= $n['status'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Hidden</span>' ?></td>
                                        <td><a href="?delete_notif=<?= $n['id'] ?>" class="btn btn-xs btn-danger"
                                               onclick="return confirm('Delete this notification?')">Delete</a></td>
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
function toggleTarget(sel){
    document.getElementById('specificEmail').style.display = sel.value === 'specific' ? 'block' : 'none';
}
</script>