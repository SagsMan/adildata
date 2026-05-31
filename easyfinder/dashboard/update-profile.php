<?php
require_once '../inc/user_session.inc.php';

// ── Direct BVN/NIN update (separate save, before full profile validation) ──────
if (isset($_POST['save_identity'])) {
    $conn_id = mysqli_connect('localhost','adiliqgs_adildata','adildata2026','adiliqgs_adildata');
    $es = mysqli_real_escape_string($conn_id, $Auth->email);
    $id_saved = false;
    if (!empty(trim($_POST['bvn'] ?? ''))) {
        $bvn_c = preg_replace('/\D/','',trim($_POST['bvn']));
        if (strlen($bvn_c) === 11) {
            $bvs = mysqli_real_escape_string($conn_id, $bvn_c);
            mysqli_query($conn_id, "UPDATE users_tbl SET bvn='$bvs', means_of_id='$bvs', means_of_id_type='bvn' WHERE email='$es'");
            $id_saved = true;
        } else {
            array_push($SITE_ERRORS, 'BVN must be exactly 11 digits.');
        }
    } elseif (!empty(trim($_POST['nin'] ?? ''))) {
        $nin_c = preg_replace('/\D/','',trim($_POST['nin']));
        if (strlen($nin_c) === 11) {
            $nis = mysqli_real_escape_string($conn_id, $nin_c);
            mysqli_query($conn_id, "UPDATE users_tbl SET nin='$nis', means_of_id='$nis', means_of_id_type='nin' WHERE email='$es'");
            $id_saved = true;
        } else {
            array_push($SITE_ERRORS, 'NIN must be exactly 11 digits.');
        }
    }
    if ($id_saved) {
        $Auth = $UserAuth->GetUserId($Auth->email);
        array_push($SITE_SUCCESS, 'Identity number updated successfully.');
    }
    mysqli_close($conn_id);
}

if (isset($_POST['change'])) {

    $rules = [
        'sname' => [
            'required',
            'alpha'
        ],
        'oname' => [
            'required',
            'alpha'
        ],
        'phone' => [
            'required',
            'numeric'
        ],
        'means_of_id_type' => [
            'required',
        ],
        'means_of_id' => [
            'required',
        ]
    ];

    $validation_result = SimpleValidator\Validator::validate($_POST, $rules);
    if ($validation_result->isSuccess()) {

        if ($AdminTask->update_profile($_POST, $Auth->email)) {
            array_push($SITE_SUCCESS, 'Your Profile is Succesful updated');
        }
    } else {
        array_push($SITE_ERRORS, $validation_result->getErrors());
    }
}



$PAGE_TITLE   = 'Update Profile';
$URL_NAME     = 'dashboard/change-password';
require_once("../inc/accessbility_controller.inc.php");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    require_once 'layout/header-propt.inc.php';
    ?>

    <title><?= $PAGE_TITLE . " | " . SITE_TITLE ?> </title>
</head>

<body>

    <?php require_once 'layout/preloader.inc.php'; ?>

    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">


        <?php
        require_once 'layout/header.inc.php';
        require_once 'layout/sidebar.inc.php';
        ?>






        <!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body">
            <?php include('layout/minor-top-navbar.inc.php'); ?>
            <div class="container-fluid">
                <div class="row page-titles mx-0">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4 style="color: #003366; font-size: 20px"><?= $PAGE_TITLE ?></h4>

                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0)"><?= SITE_TITLE ?></a></li>
                            <li class="breadcrumb-item active"><a href="javascript:void(0)"><?= $PAGE_TITLE ?></a></li>
                        </ol>
                    </div>
                </div>







                <!-- row -->
                <div class="row">

                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><?= $PAGE_TITLE  ?></h4>
                            </div>
                            <div class="card-body">
                                <div class="basic-form">


                                    <form action="" method="POST" class="form-valide-with-icon">
                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                <label class="mb-1"><strong>Surname </strong></label>
                                                <div class="input-group">
                                                    <input type="text" name="sname" value="<?= $Auth->sname ?>" required="" class="form-control">
                                                </div>
                                            </div>

                                            <div class="form-group col-md-6">
                                                <label class="mb-1"><strong>OtherNames </strong></label>
                                                <div class="input-group">
                                                    <input type="text" name="oname" value="<?= $Auth->oname ?>" required="" class="form-control">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ── BVN / NIN section (separate save form) ─────────── -->
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <div class="card border" style="border-radius:8px;">
                                                    <div class="card-header" style="background:#f0fdf8;border-bottom:1px solid #10d596;">
                                                        <h6 class="mb-0" style="color:#10d596;">
                                                            <i class="fa fa-id-card mr-2"></i>Identity Verification (BVN / NIN)
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <!-- Current status badges -->
                                                        <div class="mb-3 d-flex flex-wrap" style="gap:10px;">
                                                            <?php if (!empty($Auth->bvn)): ?>
                                                            <span class="badge badge-success px-3 py-2" style="font-size:13px;">
                                                                <i class="fa fa-check-circle mr-1"></i>
                                                                BVN saved: <?= substr($Auth->bvn,0,3) ?>****<?= substr($Auth->bvn,-3) ?>
                                                            </span>
                                                            <?php else: ?>
                                                            <span class="badge badge-secondary px-3 py-2" style="font-size:13px;">
                                                                <i class="fa fa-times-circle mr-1"></i> BVN not set
                                                            </span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($Auth->nin)): ?>
                                                            <span class="badge badge-success px-3 py-2" style="font-size:13px;">
                                                                <i class="fa fa-check-circle mr-1"></i>
                                                                NIN saved: <?= substr($Auth->nin,0,3) ?>****<?= substr($Auth->nin,-3) ?>
                                                            </span>
                                                            <?php else: ?>
                                                            <span class="badge badge-secondary px-3 py-2" style="font-size:13px;">
                                                                <i class="fa fa-times-circle mr-1"></i> NIN not set
                                                            </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="text-muted" style="font-size:13px;">
                                                            Required for Monnify bank account generation. Enter whichever you have.
                                                        </p>
                                                        <!-- Separate mini-form for identity -->
                                                        <form method="POST" action="" class="mt-2">
                                                            <div class="mb-2">
                                                                <div class="btn-group btn-group-sm" role="group">
                                                                    <button type="button" class="btn btn-success active" id="ptab-bvn"
                                                                        onclick="pSwitchTab('bvn')"
                                                                        style="background:#10d596;border-color:#10d596;">
                                                                        BVN
                                                                    </button>
                                                                    <button type="button" class="btn btn-outline-success" id="ptab-nin"
                                                                        onclick="pSwitchTab('nin')"
                                                                        style="color:#10d596;border-color:#10d596;">
                                                                        NIN
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div id="pfield-bvn" class="form-group mb-2">
                                                                <input type="text" name="bvn" id="pinput-bvn"
                                                                    class="form-control form-control-sm" maxlength="11"
                                                                    pattern="\d{11}" placeholder="11-digit BVN"
                                                                    value="<?= htmlspecialchars($Auth->bvn ?? '') ?>"
                                                                    style="max-width:240px;">
                                                                <small class="text-muted">Dial *565*0# to get your BVN</small>
                                                            </div>
                                                            <div id="pfield-nin" class="form-group mb-2" style="display:none;">
                                                                <input type="text" name="nin" id="pinput-nin"
                                                                    class="form-control form-control-sm" maxlength="11"
                                                                    pattern="\d{11}" placeholder="11-digit NIN"
                                                                    value="<?= htmlspecialchars($Auth->nin ?? '') ?>"
                                                                    style="max-width:240px;">
                                                                <small class="text-muted">Dial *346# to get your NIN</small>
                                                            </div>
                                                            <button type="submit" name="save_identity" value="1"
                                                                class="btn btn-success btn-sm"
                                                                style="background:#10d596;border-color:#10d596;">
                                                                <i class="fa fa-save mr-1"></i> Save Identity
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- ── Hidden fields to keep means_of_id in sync for main form ── -->
                                        <input type="hidden" name="means_of_id_type" value="<?= htmlspecialchars($Auth->means_of_id_type ?: ($Auth->bvn ? 'bvn' : 'nin')) ?>">
                                        <input type="hidden" name="means_of_id" value="<?= htmlspecialchars($Auth->means_of_id ?: ($Auth->bvn ?: $Auth->nin)) ?>">


                                        <div class="row">

                                            <div class="form-group col-md-6">
                                                <label class="mb-1"><strong>Phone Number </strong></label>
                                                <div class="input-group">
                                                    <input type="tel" name="phone" value="<?= $Auth->phone ?>" required="" class="form-control">
                                                </div>
                                            </div>

                                            <div class="form-group col-md-6">

                                                <label class="mb-1"><strong>Email</strong></label>
                                                <div class="input-group">
                                                    <input type="email" readonly="" required="" value="<?= $Auth->email ?>" class="form-control">
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row">

                                            <div class="form-group col-md-6">
                                                <label class="mb-1"><strong> State Of Origin </strong></label>
                                                <div class="input-group">
                                                    <select class="form-control selectpicker" name="state" required="" value="<?= $Auth->state ?>">

                                                        <option value="Abia">Abia</option>
                                                        <option value="Adamawa">Adamawa</option>
                                                        <option value="Akwa Ibom">Akwa Ibom</option>
                                                        <option value="Anambra">Anambra</option>
                                                        <option value="Bauchi">Bauchi</option>
                                                        <option value="Bayelsa">Bayelsa</option>
                                                        <option value="Benue">Benue</option>
                                                        <option value="Borno">Borno</option>
                                                        <option value="Cross River">Cross River</option>
                                                        <option value="Delta">Delta</option>
                                                        <option value="Ebonyi">Ebonyi</option>
                                                        <option value="Edo">Edo</option>
                                                        <option value="Ekiti">Ekiti</option>
                                                        <option value="Enugu">Enugu</option>
                                                        <option value="Federal Capital Territory">Federal Capital Territory</option>
                                                        <option value="Gombe">Gombe</option>
                                                        <option value="Imo">Imo</option>
                                                        <option value="Jigawa">Jigawa</option>
                                                        <option value="Kaduna">Kaduna</option>
                                                        <option value="Kano">Kano</option>
                                                        <option value="Katsina">Katsina</option>
                                                        <option value="Kebbi">Kebbi</option>
                                                        <option value="Kogi">Kogi</option>
                                                        <option value="Kwara">Kwara</option>
                                                        <option value="Lagos">Lagos</option>
                                                        <option value="Nasarawa">Nasarawa</option>
                                                        <option value="Niger">Niger</option>
                                                        <option value="Ogun">Ogun</option>
                                                        <option value="Ondo">Ondo</option>
                                                        <option value="Osun">Osun</option>
                                                        <option value="Oyo">Oyo</option>
                                                        <option value="Plateau">Plateau</option>
                                                        <option value="Rivers">Rivers</option>
                                                        <option value="Sokoto">Sokoto</option>
                                                        <option value="Taraba">Taraba</option>
                                                        <option value="Yobe">Yobe</option>
                                                        <option value="Zamfara">Zamfara</option>
                                                    </select>

                                                </div>
                                            </div>

                                            <div class="form-group col-md-6">

                                                <label class="mb-1"><strong>Town </strong></label>
                                                <div class="input-group">
                                                    <input type="text" name="town" value="<?= $Auth->town ?>" required="" class="form-control">
                                                </div>
                                            </div>

                                        </div>



                                        <div class="form-group ">
                                            <label class="mb-1"><strong> Your Company Name</strong></label>
                                            <div class="input-group">
                                                <input type="text" placeholder="Eg: Utiplus Digital Solution" name="school" value="<?= $Auth->school ?>" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group ">
                                            <label class="mb-1"><strong> Your Company Address : </strong></label>
                                            <div class="input-group">
                                                <textarea name="address" required="" class="form-control"> <?= $Auth->address ?> </textarea>
                                            </div>
                                        </div>



                                        <div class="text-center mt-4">
                                            <button name="change" type="submit" value="Profile" class="btn btn-primary ">Update now</button>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>






            </div>
        </div>
        <!--**********************************
            Content body end
        ***********************************-->

    </div>

    <?php
    require_once 'layout/footer.inc.php';
    require_once 'layout/footer-propt.inc.php';
    ?>

<script>
function pSwitchTab(type) {
    var bF = document.getElementById('pfield-bvn');
    var nF = document.getElementById('pfield-nin');
    var bI = document.getElementById('pinput-bvn');
    var nI = document.getElementById('pinput-nin');
    var tB = document.getElementById('ptab-bvn');
    var tN = document.getElementById('ptab-nin');
    if (type === 'bvn') {
        bF.style.display = ''; nF.style.display = 'none';
        bI.required = true; nI.required = false; nI.value = '';
        tB.className='btn btn-success active'; tB.style.cssText='background:#10d596;border-color:#10d596;';
        tN.className='btn btn-outline-success'; tN.style.cssText='color:#10d596;border-color:#10d596;';
    } else {
        nF.style.display = ''; bF.style.display = 'none';
        nI.required = true; bI.required = false; bI.value = '';
        tN.className='btn btn-success active'; tN.style.cssText='background:#10d596;border-color:#10d596;';
        tB.className='btn btn-outline-success'; tB.style.cssText='color:#10d596;border-color:#10d596;';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($Auth->nin) && empty($Auth->bvn)): ?>
    pSwitchTab('nin');
    <?php else: ?>
    document.getElementById('pinput-bvn').required = true;
    <?php endif; ?>
});
</script>
</body>

</html>