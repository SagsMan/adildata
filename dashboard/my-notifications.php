<?php
require_once '../inc/user_session.inc.php';
$PAGE_TITLE = 'My Notifications';
$URL_NAME = 'my-notifications';

$conn = mysqli_connect('localhost','adiliqgs_adildata','adildata2026','adiliqgs_adildata');
$emailSafe = mysqli_real_escape_string($conn, $Auth->email);

// ── Mark as read ──────────────────────────────────────────────────────────────
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $id = intval($_GET['mark_read']);
    $r  = mysqli_query($conn, "SELECT is_read_by FROM notifications_tbl WHERE id = $id LIMIT 1");
    if ($r && mysqli_num_rows($r) > 0) {
        $row = mysqli_fetch_assoc($r);
        $readers = json_decode($row['is_read_by'] ?: '[]', true);
        if (!in_array($Auth->email, $readers)) {
            $readers[] = $Auth->email;
            $readers_json = mysqli_real_escape_string($conn, json_encode($readers));
            mysqli_query($conn, "UPDATE notifications_tbl SET is_read_by = '$readers_json' WHERE id = $id");
        }
    }
    header('Location: my-notifications'); exit;
}

// ── Mark ALL as read ──────────────────────────────────────────────────────────
if (isset($_GET['mark_all_read'])) {
    $all = mysqli_query($conn, "SELECT id, is_read_by FROM notifications_tbl WHERE status = 1 AND (target = 'all' OR target_email = '$emailSafe')");
    while ($row = mysqli_fetch_assoc($all)) {
        $readers = json_decode($row['is_read_by'] ?: '[]', true);
        if (!in_array($Auth->email, $readers)) {
            $readers[] = $Auth->email;
            $rj = mysqli_real_escape_string($conn, json_encode($readers));
            mysqli_query($conn, "UPDATE notifications_tbl SET is_read_by = '$rj' WHERE id = " . $row['id']);
        }
    }
    header('Location: my-notifications'); exit;
}

// ── Fetch notifications for this user ────────────────────────────────────────
$notifs = [];
$r = mysqli_query($conn,
    "SELECT * FROM notifications_tbl
     WHERE status = 1 AND (target = 'all' OR target_email = '$emailSafe')
     ORDER BY id DESC"
);
while ($row = mysqli_fetch_assoc($r)) {
    $readers = json_decode($row['is_read_by'] ?: '[]', true);
    $row['is_read'] = in_array($Auth->email, $readers);
    $notifs[] = $row;
}
$unread_count = count(array_filter($notifs, fn($n) => !$n['is_read']));

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
                        <h4 class="font-w600 mb-0" style="color:#10d596;">My Notifications</h4>
                        <p class="mb-0"><?= $unread_count ?> unread notification<?= $unread_count != 1 ? 's' : '' ?></p>
                    </div>
                    <?php if ($unread_count > 0): ?>
                    <a href="?mark_all_read" class="btn btn-sm btn-success">
                        <i class="fa fa-check-double mr-1"></i> Mark All as Read
                    </a>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <?php if (empty($notifs)): ?>
                                    <div class="text-center py-5">
                                        <i class="fa fa-bell-slash fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No notifications yet.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notifs as $n): ?>
                                    <div class="media pt-3 pb-3 border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>"
                                         style="border-left: 4px solid <?= ['info'=>'#17a2b8','success'=>'#10d596','warning'=>'#ffc107','danger'=>'#dc3545'][$n['type']] ?? '#10d596' ?>;padding-left:12px;">
                                        <div class="media-body">
                                            <div class="d-flex justify-content-between">
                                                <h5 class="mt-0 mb-1" style="<?= !$n['is_read'] ? 'font-weight:bold;' : '' ?>">
                                                    <?php if (!$n['is_read']): ?>
                                                        <span class="badge badge-<?= $n['type'] ?> mr-2">NEW</span>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($n['title']) ?>
                                                </h5>
                                                <small class="text-muted"><?= date('d M Y H:i', strtotime($n['created_at'])) ?></small>
                                            </div>
                                            <p class="mb-1"><?= nl2br(htmlspecialchars($n['message'])) ?></p>
                                            <?php if (!$n['is_read']): ?>
                                            <a href="?mark_read=<?= $n['id'] ?>" class="btn btn-xs btn-outline-success mt-1">
                                                <i class="fa fa-check mr-1"></i>Mark as Read
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
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