<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE = 'Monnify Account Manager';
$URL_NAME   = 'dashboard/admin-monnify-users';
require_once('../inc/accessbility_controller.inc.php');

// Admin-only guard
$adminRoles = array_map('trim', explode(',', $Auth->admin_role ?? ''));
if (!in_array('1', $adminRoles) && !in_array(1, $adminRoles)) {
    header('Location: ' . SITE_URL . 'easyfinder/dashboard/');
    exit;
}

$conn = mysqli_connect('localhost', 'adiliqgs_adildata', 'adildata2026', 'adiliqgs_adildata');

// ── Filters ───────────────────────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$sSafe  = mysqli_real_escape_string($conn, $search);

$where = "1=1";
if ($filter === 'has_monnify')    $where .= " AND monnify_account_details != '' AND monnify_account_details IS NOT NULL";
if ($filter === 'no_monnify')     $where .= " AND (monnify_account_details IS NULL OR monnify_account_details='')";
if ($filter === 'missing_id')     $where .= " AND (bvn IS NULL OR bvn='') AND (nin IS NULL OR nin='')";
if ($filter === 'has_id_no_acc')  $where .= " AND ((bvn IS NOT NULL AND bvn!='') OR (nin IS NOT NULL AND nin!='')) AND (monnify_account_details IS NULL OR monnify_account_details='')";
if ($search) $where .= " AND (email LIKE '%$sSafe%' OR sname LIKE '%$sSafe%' OR oname LIKE '%$sSafe%' OR phone LIKE '%$sSafe%')";

// Stats
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
       COUNT(*) as total,
       SUM(CASE WHEN bvn IS NOT NULL AND bvn!='' THEN 1 ELSE 0 END) as has_bvn,
       SUM(CASE WHEN nin IS NOT NULL AND nin!='' THEN 1 ELSE 0 END) as has_nin,
       SUM(CASE WHEN (bvn IS NULL OR bvn='') AND (nin IS NULL OR nin='') THEN 1 ELSE 0 END) as has_neither,
       SUM(CASE WHEN monnify_account_details IS NOT NULL AND monnify_account_details!='' THEN 1 ELSE 0 END) as has_monnify,
       SUM(CASE WHEN ((bvn IS NOT NULL AND bvn!='') OR (nin IS NOT NULL AND nin!=''))
                 AND (monnify_account_details IS NULL OR monnify_account_details='') THEN 1 ELSE 0 END) as needs_gen
     FROM users_tbl"
));

// Users list
$qUsers = mysqli_query($conn,
    "SELECT id, sname, oname, email, phone, bvn, nin, monnify_account_details, admin_role, date_join
     FROM users_tbl WHERE $where ORDER BY id DESC"
);
$users = [];
while ($r = mysqli_fetch_assoc($qUsers)) $users[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once 'layout/header-propt.inc.php'; ?>
    <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?></title>
    <style>
    .stat-box { border-radius:10px; padding:20px; color:#fff; }
    .badge-bvn  { background:#10d596; color:#fff; }
    .badge-nin  { background:#0b9e72; color:#fff; }
    .badge-none { background:#dc3545; color:#fff; }
    .badge-acc  { background:#007bff; color:#fff; }
    .masked { font-family:monospace; letter-spacing:1px; }

    /* Toast notification */
    #toast-container {
        position: fixed; top: 80px; right: 20px; z-index: 99999;
        display: flex; flex-direction: column; gap: 8px;
    }
    .toast-msg {
        min-width: 280px; max-width: 420px;
        padding: 14px 18px; border-radius: 8px;
        font-size: 14px; color: #fff;
        box-shadow: 0 4px 16px rgba(0,0,0,.25);
        display: flex; align-items: flex-start; gap: 10px;
        animation: slideIn .3s ease;
    }
    .toast-msg.success { background: #10d596; }
    .toast-msg.error   { background: #dc3545; }
    .toast-msg.info    { background: #007bff; }
    .toast-msg i { margin-top: 2px; flex-shrink: 0; }
    .toast-msg .toast-close { margin-left:auto; cursor:pointer; opacity:.8; }
    @keyframes slideIn { from { transform:translateX(120%); opacity:0; } to { transform:none; opacity:1; } }
    @keyframes slideOut { to { transform:translateX(120%); opacity:0; } }

    .btn-gen { background:#10d596; border-color:#10d596; font-size:11px; padding:3px 10px; }
    .btn-gen:hover { background:#0db882; border-color:#0db882; }
    </style>
</head>
<body>
<?php require_once 'layout/preloader.inc.php'; ?>

<!-- Toast container -->
<div id="toast-container"></div>

<div id="main-wrapper">
    <?php
    require_once 'layout/header.inc.php';
    require_once 'layout/sidebar.inc.php';
    ?>
    <div class="content-body">
        <?php include 'layout/minor-top-navbar.inc.php'; ?>
        <div class="container-fluid">

            <!-- Page title -->
            <div class="row page-titles mx-0 mb-3">
                <div class="col-sm-8 p-md-0">
                    <h4 style="color:#10d596;font-weight:bold;"><?= $PAGE_TITLE ?></h4>
                    <p class="mb-0 text-muted">View all users — BVN/NIN status, Monnify accounts, and generate accounts manually.</p>
                </div>
                <div class="col-sm-4 p-md-0 text-right">
                    <!-- Bulk generate -->
                    <button id="bulk-gen-btn" class="btn btn-warning btn-sm mt-1"
                            title="Generate for all users who have BVN/NIN but no Monnify account">
                        <i class="fa fa-bolt mr-1"></i> Bulk Generate
                        <?php if ($stats['needs_gen'] > 0): ?>
                        <span class="badge badge-dark ml-1" id="needs-gen-badge"><?= $stats['needs_gen'] ?></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>

            <!-- ── Stats Row ─────────────────────────────────────────────── -->
            <div class="row mb-4">
                <?php
                $statCards = [
                    ['Total Users',       $stats['total'],      '#003366'],
                    ['Have BVN',          $stats['has_bvn'],    '#10d596'],
                    ['Have NIN',          $stats['has_nin'],    '#0b9e72'],
                    ['No BVN/NIN',        $stats['has_neither'],'#dc3545'],
                    ['Monnify Active',    $stats['has_monnify'],'#007bff'],
                    ['Needs Generation',  $stats['needs_gen'],  '#fd7e14'],
                ];
                foreach ($statCards as [$label, $val, $col]): ?>
                <div class="col-xl col-sm-4 mb-3">
                    <div class="stat-box" style="background:<?= $col ?>;">
                        <p class="mb-1" style="font-size:11px;opacity:.8;"><?= strtoupper($label) ?></p>
                        <h2 class="mb-0 font-weight-bold"><?= $val ?></h2>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ── Filter & Search ───────────────────────────────────────── -->
            <div class="card mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <?php
                            $filters = [
                                'all'          => ['All Users',           null],
                                'has_monnify'  => ['Has Monnify',         null],
                                'no_monnify'   => ['No Monnify',          null],
                                'missing_id'   => ['Missing BVN & NIN',   null],
                                'has_id_no_acc'=> ['Ready to Generate',   $stats['needs_gen']],
                            ];
                            foreach ($filters as $fk => [$fl, $cnt]):
                                $active = ($filter === $fk);
                            ?>
                            <a href="?filter=<?= $fk ?>&q=<?= urlencode($search) ?>"
                               class="btn btn-sm mr-1 mb-1 <?= $active ? 'btn-success' : 'btn-outline-secondary' ?>"
                               style="<?= $active ? 'background:#10d596;border-color:#10d596;' : '' ?>">
                                <?= $fl ?>
                                <?php if ($cnt !== null): ?>
                                <span class="badge badge-warning ml-1"><?= $cnt ?></span>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-4 mt-2 mt-md-0">
                            <form method="GET">
                                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                                           class="form-control" placeholder="Search name / email / phone…">
                                    <div class="input-group-append">
                                        <button class="btn btn-success" style="background:#10d596;border-color:#10d596;">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Users Table ───────────────────────────────────────────── -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?= count($users) ?> user<?= count($users) !== 1 ? 's' : '' ?>
                        <?= $search ? ' matching <em>' . htmlspecialchars($search) . '</em>' : '' ?>
                        — <?= $filters[$filter][0] ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead style="background:#003366;color:#fff;font-size:12px;">
                                <tr>
                                    <th class="pl-3">#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>BVN</th>
                                    <th>NIN</th>
                                    <th>Monnify Account</th>
                                    <th>Joined</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody style="font-size:13px;" id="users-tbody">
                            <?php if (empty($users)): ?>
                                <tr><td colspan="9" class="text-center py-4 text-muted">No users match this filter.</td></tr>
                            <?php else: ?>
                            <?php foreach ($users as $i => $u):
                                $hasBvn   = !empty($u['bvn']);
                                $hasNin   = !empty($u['nin']);
                                $hasAcc   = !empty($u['monnify_account_details']);
                                $canGen   = ($hasBvn || $hasNin) && !$hasAcc;
                                $accParts = $hasAcc ? array_map('trim', explode(',', $u['monnify_account_details'])) : [];
                                $isAdmin  = in_array('1', array_map('trim', explode(',', $u['admin_role'] ?? '')));
                            ?>
                            <tr id="user-row-<?= $u['id'] ?>">
                                <td class="pl-3 text-muted"><?= $i+1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($u['sname'] . ' ' . $u['oname']) ?></strong>
                                    <?php if ($isAdmin): ?>
                                    <span class="badge badge-warning ml-1" style="font-size:9px;">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:180px;word-break:break-all;">
                                    <a href="mailto:<?= htmlspecialchars($u['email']) ?>" style="color:#003366;">
                                        <?= htmlspecialchars($u['email']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                                <td>
                                    <?php if ($hasBvn): ?>
                                    <span class="badge badge-bvn masked"><?= substr($u['bvn'],0,3) ?>****<?= substr($u['bvn'],-3) ?></span>
                                    <?php else: ?><span class="badge badge-none">Not set</span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($hasNin): ?>
                                    <span class="badge badge-nin masked"><?= substr($u['nin'],0,3) ?>****<?= substr($u['nin'],-3) ?></span>
                                    <?php else: ?><span class="badge badge-none">Not set</span><?php endif; ?>
                                </td>
                                <td id="acc-cell-<?= $u['id'] ?>">
                                    <?php if ($hasAcc): ?>
                                    <?php foreach ($accParts as $ap):
                                        $p = explode(' - ', $ap); ?>
                                    <div style="font-size:11px;">
                                        <span class="text-success">&#10003;</span>
                                        <strong><?= htmlspecialchars($p[1] ?? '') ?></strong>
                                        <span class="text-muted"><?= htmlspecialchars($p[0] ?? '') ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <span class="text-muted" style="font-size:12px;">— none —</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:11px;color:#888;">
                                    <?= $u['date_join'] ? date('d M Y', strtotime($u['date_join'])) : '—' ?>
                                </td>
                                <td class="text-center" id="action-cell-<?= $u['id'] ?>">
                                    <?php if ($hasAcc): ?>
                                    <span class="badge badge-acc px-2 py-1"><i class="fa fa-check mr-1"></i>Active</span>
                                    <?php elseif ($canGen): ?>
                                    <button class="btn btn-success btn-xs btn-gen"
                                            onclick="generateOne('<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>', <?= $u['id'] ?>, this)">
                                        <i class="fa fa-university mr-1"></i>Generate
                                    </button>
                                    <?php else: ?>
                                    <span class="badge badge-none" title="No BVN or NIN on file">
                                        <i class="fa fa-times mr-1"></i>Needs ID
                                    </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted" style="font-size:12px;">
                    <i class="fa fa-info-circle mr-1"></i>
                    Green = BVN on file (masked). Teal = NIN. "Generate" only shows when user has ID but no account.
                    Bulk Generate processes up to 20 users at once.
                </div>
            </div>

        </div>
    </div>
    <?php
    require_once 'layout/footer.inc.php';
    require_once 'layout/footer-propt.inc.php';
    ?>
</div>

<script>
const ACTION_URL = '<?= SITE_URL ?>easyfinder/dashboard/admin-monnify-action';

/* ── Toast helper ──────────────────────────────────────────────────────────── */
function showToast(msg, type = 'success', duration = 6000) {
    const container = document.getElementById('toast-container');
    const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
    const t = document.createElement('div');
    t.className = `toast-msg ${type}`;
    t.innerHTML = `<i class="fa ${icons[type] || icons.info}"></i>
                   <span style="flex:1">${msg}</span>
                   <span class="toast-close" onclick="this.parentElement.remove()">&times;</span>`;
    container.appendChild(t);
    if (duration > 0) setTimeout(() => {
        t.style.animation = 'slideOut .3s ease forwards';
        setTimeout(() => t.remove(), 320);
    }, duration);
    return t;
}

/* ── Generate for ONE user ─────────────────────────────────────────────────── */
function generateOne(email, userId, btn) {
    if (!confirm('Generate Monnify account for ' + email + '?')) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i>Working…';

    const fd = new FormData();
    fd.append('action', 'gen_one');
    fd.append('email', email);

    fetch(ACTION_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('✓ ' + data.message, 'success');
                // Update action cell — mark as active
                const cell = document.getElementById('action-cell-' + userId);
                if (cell) cell.innerHTML = '<span class="badge badge-acc px-2 py-1"><i class="fa fa-check mr-1"></i>Active</span>';
                // Update account cell if details returned
                if (data.details) {
                    const acc = document.getElementById('acc-cell-' + userId);
                    if (acc) acc.innerHTML = '<div style="font-size:11px;"><span class="text-success">&#10003;</span> <strong>' + data.details + '</strong></div>';
                }
            } else {
                showToast('✗ ' + data.message, 'error', 10000);
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-university mr-1"></i>Retry';
            }
        })
        .catch(err => {
            showToast('Network error — please try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-university mr-1"></i>Retry';
        });
}

/* ── Bulk generate ─────────────────────────────────────────────────────────── */
document.getElementById('bulk-gen-btn').addEventListener('click', function() {
    const badge = document.getElementById('needs-gen-badge');
    const count = badge ? badge.textContent : '?';
    if (!confirm('Generate Monnify accounts for up to 20 users who have BVN/NIN but no account yet?\n\nThis cannot be undone.')) return;

    this.disabled = true;
    this.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Processing…';
    const self = this;

    const toast = showToast('<i class="fa fa-spinner fa-spin mr-2"></i> Running bulk generation, please wait…', 'info', 0);

    const fd = new FormData();
    fd.append('action', 'gen_all');

    fetch(ACTION_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            toast.remove();
            if (data.ok > 0) {
                showToast('✓ Bulk complete: ' + data.ok + ' account(s) created' +
                    (data.fail > 0 ? ', ' + data.fail + ' failed.' : '.'), 'success', 12000);
                if (badge) badge.textContent = Math.max(0, parseInt(badge.textContent) - data.ok);
            } else if (data.fail > 0) {
                showToast('✗ Bulk done — 0 succeeded, ' + data.fail + ' failed. Check if Monnify API is working.', 'error', 12000);
            } else {
                showToast('ℹ No eligible users found — everyone either already has an account or is missing BVN/NIN.', 'info');
            }
            if (data.errors && data.errors.length > 0) {
                console.log('Bulk errors:', data.errors);
            }
            self.disabled = false;
            self.innerHTML = '<i class="fa fa-bolt mr-1"></i> Bulk Generate';
        })
        .catch(err => {
            toast.remove();
            showToast('Network error during bulk generation.', 'error');
            self.disabled = false;
            self.innerHTML = '<i class="fa fa-bolt mr-1"></i> Bulk Generate';
        });
});
</script>
</body>
</html>
