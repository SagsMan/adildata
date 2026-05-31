<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE = 'Monnify Account Manager';
$URL_NAME   = 'dashboard/admin-monnify-users';
require_once('../inc/accessbility_controller.inc.php');

// Admin-only guard
if (!in_array(1, explode(',', $Auth->admin_role))) {
    header('Location: ' . SITE_URL . 'easyfinder/dashboard/');
    exit;
}

$conn = mysqli_connect('localhost', 'adiliqgs_adildata', 'adildata2026', 'adiliqgs_adildata');

// ── Handle "Generate Monnify" for a specific user ─────────────────────────────
$action_msg = '';
$action_err = '';
if (isset($_POST['gen_monnify_for']) && !empty($_POST['gen_monnify_for'])) {
    $target_email = trim($_POST['gen_monnify_for']);
    $target_user  = $UserAuth->GetUserId($target_email);
    if ($target_user && !empty($target_user->email)) {
        if (!empty($target_user->monnify_account_details)) {
            $action_msg = "[$target_email] already has a Monnify account: " . $target_user->monnify_account_details;
        } else {
            $result = $UserAuth->createMonnifyAccount($target_user);
            if ($result['success']) {
                $action_msg = "✓ Monnify account created for $target_email — " . $result['account_details'];
            } else {
                $action_err = "✗ Failed for $target_email: " . ($result['message'] ?? 'Unknown error');
            }
        }
    } else {
        $action_err = "User not found: $target_email";
    }
}

// ── Handle "Generate ALL missing" ─────────────────────────────────────────────
if (isset($_POST['gen_all_missing'])) {
    $qAll = mysqli_query($conn,
        "SELECT email FROM users_tbl
         WHERE (monnify_account_details IS NULL OR monnify_account_details='')
           AND (bvn!='' AND bvn IS NOT NULL OR nin!='' AND nin IS NOT NULL)
         LIMIT 20"
    );
    $gen_ok = 0; $gen_fail = 0;
    while ($row = mysqli_fetch_assoc($qAll)) {
        $tu = $UserAuth->GetUserId($row['email']);
        if ($tu) {
            $r = $UserAuth->createMonnifyAccount($tu);
            if ($r['success']) $gen_ok++; else $gen_fail++;
        }
    }
    $action_msg = "Bulk generate complete: $gen_ok succeeded, $gen_fail failed (processed users with BVN/NIN only).";
}

// ── Filters ───────────────────────────────────────────────────────────────────
$filter  = $_GET['filter'] ?? 'all';
$search  = trim($_GET['q'] ?? '');
$sSafe   = mysqli_real_escape_string($conn, $search);

$where = "1=1";
if ($filter === 'has_monnify')   $where .= " AND monnify_account_details != '' AND monnify_account_details IS NOT NULL";
if ($filter === 'no_monnify')    $where .= " AND (monnify_account_details IS NULL OR monnify_account_details='')";
if ($filter === 'missing_id')    $where .= " AND (bvn IS NULL OR bvn='') AND (nin IS NULL OR nin='')";
if ($filter === 'has_id_no_acc') $where .= " AND (bvn!='' AND bvn IS NOT NULL OR nin!='' AND nin IS NOT NULL) AND (monnify_account_details IS NULL OR monnify_account_details='')";
if ($search) $where .= " AND (email LIKE '%$sSafe%' OR sname LIKE '%$sSafe%' OR oname LIKE '%$sSafe%' OR phone LIKE '%$sSafe%')";

// Stats
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
       COUNT(*) as total,
       SUM(CASE WHEN bvn!='' AND bvn IS NOT NULL THEN 1 ELSE 0 END) as has_bvn,
       SUM(CASE WHEN nin!='' AND nin IS NOT NULL THEN 1 ELSE 0 END) as has_nin,
       SUM(CASE WHEN (bvn IS NULL OR bvn='') AND (nin IS NULL OR nin='') THEN 1 ELSE 0 END) as has_neither,
       SUM(CASE WHEN monnify_account_details IS NOT NULL AND monnify_account_details!='' THEN 1 ELSE 0 END) as has_monnify,
       SUM(CASE WHEN (bvn!='' AND bvn IS NOT NULL OR nin!='' AND nin IS NOT NULL) AND (monnify_account_details IS NULL OR monnify_account_details='') THEN 1 ELSE 0 END) as needs_gen
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
    .filter-btn.active { background:#10d596!important; border-color:#10d596!important; color:#fff!important; }
    .action-row td { vertical-align:middle; }
    .masked { font-family:monospace; letter-spacing:1px; }
    </style>
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
            <div class="row page-titles mx-0 mb-3">
                <div class="col-sm-6 p-md-0">
                    <h4 style="color:#10d596;font-weight:bold;"><?= $PAGE_TITLE ?></h4>
                    <p class="mb-0 text-muted">View all users — BVN/NIN status, Monnify accounts, and generate accounts manually.</p>
                </div>
            </div>

            <?php if ($action_msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fa fa-check-circle mr-2"></i><?= htmlspecialchars($action_msg) ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php endif; ?>
            <?php if ($action_err): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa fa-exclamation-circle mr-2"></i><?= htmlspecialchars($action_err) ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php endif; ?>

            <!-- ── Stats Row ───────────────────────────────────────────────── -->
            <div class="row mb-4">
                <div class="col-xl col-sm-4 mb-3">
                    <div class="stat-box" style="background:#003366;">
                        <p class="mb-1" style="font-size:11px;opacity:.7;">TOTAL USERS</p>
                        <h2 class="mb-0 font-weight-bold"><?= $stats['total'] ?></h2>
                    </div>
                </div>
                <div class="col-xl col-sm-4 mb-3">
                    <div class="stat-box" style="background:#10d596;">
                        <p class="mb-1" style="font-size:11px;opacity:.8;">HAVE BVN</p>
                        <h2 class="mb-0 font-weight-bold"><?= $stats['has_bvn'] ?></h2>
                    </div>
                </div>
                <div class="col-xl col-sm-4 mb-3">
                    <div class="stat-box" style="background:#0b9e72;">
                        <p class="mb-1" style="font-size:11px;opacity:.8;">HAVE NIN</p>
                        <h2 class="mb-0 font-weight-bold"><?= $stats['has_nin'] ?></h2>
                    </div>
                </div>
                <div class="col-xl col-sm-4 mb-3">
                    <div class="stat-box" style="background:#dc3545;">
                        <p class="mb-1" style="font-size:11px;opacity:.8;">NO BVN/NIN</p>
                        <h2 class="mb-0 font-weight-bold"><?= $stats['has_neither'] ?></h2>
                    </div>
                </div>
                <div class="col-xl col-sm-4 mb-3">
                    <div class="stat-box" style="background:#007bff;">
                        <p class="mb-1" style="font-size:11px;opacity:.8;">MONNIFY ACTIVE</p>
                        <h2 class="mb-0 font-weight-bold"><?= $stats['has_monnify'] ?></h2>
                    </div>
                </div>
                <div class="col-xl col-sm-4 mb-3">
                    <div class="stat-box" style="background:#fd7e14;">
                        <p class="mb-1" style="font-size:11px;opacity:.8;">NEEDS GENERATION</p>
                        <h2 class="mb-0 font-weight-bold"><?= $stats['needs_gen'] ?></h2>
                        <small style="opacity:.8;">Has ID, no account yet</small>
                    </div>
                </div>
            </div>

            <!-- ── Controls ────────────────────────────────────────────────── -->
            <div class="card mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <!-- Filter buttons -->
                            <div class="btn-group btn-group-sm flex-wrap" role="group">
                                <?php
                                $filters = [
                                    'all'         => 'All Users',
                                    'has_monnify' => 'Has Monnify',
                                    'no_monnify'  => 'No Monnify',
                                    'missing_id'  => 'Missing BVN & NIN',
                                    'has_id_no_acc'=> 'Ready to Generate',
                                ];
                                foreach ($filters as $fk => $fl):
                                    $active = ($filter === $fk) ? 'active btn-success' : 'btn-outline-secondary';
                                ?>
                                <a href="?filter=<?= $fk ?>&q=<?= urlencode($search) ?>"
                                   class="btn <?= $active ?> filter-btn mr-1 mb-1"
                                   style="<?= $filter===$fk ? 'background:#10d596;border-color:#10d596;' : '' ?>">
                                    <?= $fl ?>
                                    <?php if ($fk === 'has_id_no_acc'): ?>
                                    <span class="badge badge-warning ml-1"><?= $stats['needs_gen'] ?></span>
                                    <?php endif; ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-3 mt-2 mt-md-0">
                            <!-- Search -->
                            <form method="GET" action="">
                                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                                        class="form-control" placeholder="Search name/email/phone…">
                                    <div class="input-group-append">
                                        <button class="btn btn-success" type="submit"
                                            style="background:#10d596;border-color:#10d596;">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-2 mt-2 mt-md-0 text-right">
                            <!-- Bulk generate -->
                            <form method="POST" action=""
                                  onsubmit="return confirm('Generate Monnify accounts for ALL users who have BVN/NIN but no account yet? (Max 20 at a time)')">
                                <button type="submit" name="gen_all_missing" value="1"
                                    class="btn btn-sm btn-warning"
                                    title="Generate for all users who have BVN/NIN but no Monnify account">
                                    <i class="fa fa-bolt mr-1"></i> Bulk Generate
                                    <?php if ($stats['needs_gen'] > 0): ?>
                                    <span class="badge badge-dark ml-1"><?= $stats['needs_gen'] ?></span>
                                    <?php endif; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Users Table ─────────────────────────────────────────────── -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?= count($users) ?> user<?= count($users) !== 1 ? 's' : '' ?>
                        <?= $search ? ' matching <em>' . htmlspecialchars($search) . '</em>' : '' ?>
                        — <?= $filters[$filter] ?>
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
                            <tbody style="font-size:13px;">
                            <?php if (empty($users)): ?>
                                <tr><td colspan="9" class="text-center py-4 text-muted">No users match this filter.</td></tr>
                            <?php else: ?>
                            <?php foreach ($users as $i => $u):
                                $hasBvn     = !empty($u['bvn']);
                                $hasNin     = !empty($u['nin']);
                                $hasAcc     = !empty($u['monnify_account_details']);
                                $canGen     = ($hasBvn || $hasNin) && !$hasAcc;
                                $accParts   = $hasAcc ? array_map('trim', explode(',', $u['monnify_account_details'])) : [];
                            ?>
                            <tr class="action-row">
                                <td class="pl-3 text-muted"><?= $i+1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($u['sname'] . ' ' . $u['oname']) ?></strong>
                                    <?php if (in_array(1, explode(',', $u['admin_role'] ?? ''))): ?>
                                    <span class="badge badge-warning ml-1">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:180px;word-break:break-all;">
                                    <a href="mailto:<?= htmlspecialchars($u['email']) ?>"
                                       style="color:#003366;">
                                        <?= htmlspecialchars($u['email']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                                <td>
                                    <?php if ($hasBvn): ?>
                                    <span class="badge badge-bvn masked">
                                        <?= substr($u['bvn'],0,3) ?>****<?= substr($u['bvn'],-3) ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge badge-none">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($hasNin): ?>
                                    <span class="badge badge-nin masked">
                                        <?= substr($u['nin'],0,3) ?>****<?= substr($u['nin'],-3) ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge badge-none">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($hasAcc): ?>
                                    <?php foreach ($accParts as $ap):
                                        $p = explode(' - ', $ap);
                                    ?>
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
                                <td class="text-center">
                                    <?php if ($hasAcc): ?>
                                    <span class="badge badge-acc px-2 py-1">
                                        <i class="fa fa-check mr-1"></i>Active
                                    </span>
                                    <?php elseif ($canGen): ?>
                                    <form method="POST" action="" style="display:inline;"
                                          onsubmit="return confirm('Generate Monnify account for <?= htmlspecialchars(addslashes($u['email'])) ?>?')">
                                        <input type="hidden" name="gen_monnify_for" value="<?= htmlspecialchars($u['email']) ?>">
                                        <button type="submit" class="btn btn-success btn-xs"
                                            style="background:#10d596;border-color:#10d596;font-size:11px;padding:3px 10px;">
                                            <i class="fa fa-university mr-1"></i>Generate
                                        </button>
                                    </form>
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
                    Green badge = BVN/NIN on file (masked). "Generate" only appears when user has an ID but no Monnify account yet.
                    "Bulk Generate" processes up to 20 users per click.
                </div>
            </div>

        </div>
    </div>
    <?php
    require_once 'layout/footer.inc.php';
    require_once 'layout/footer-propt.inc.php';
    ?>
</div>
</body>
</html>
