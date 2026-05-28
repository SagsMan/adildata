<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE = 'Transaction History';
$URL_NAME = 'dashboard/my-trans-history';
require_once '../inc/accessbility_controller.inc.php';
?>
<!DOCTYPE html>
<html lang="en">

<head><meta charset="utf-8">
    <?php require_once 'layout/header-propt.inc.php'; ?>

    <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?> </title>
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
            <?php include 'layout/minor-top-navbar.inc.php'; ?>
            <div class="container-fluid">
                <div class="row page-titles mx-0">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4 style="color: #003366; font-size: 20px"><?= $PAGE_TITLE ?></h4>

                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0)"><?= SITE_TITLE ?> </a></li>
                            <li class="breadcrumb-item active"><a href="javascript:void(0)"><?= $PAGE_TITLE ?></a></li>
                        </ol>
                    </div>
                </div>





                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><?= $PAGE_TITLE ?></h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>#</th>

                                                <th>Trans ID</th>
                                                <th>Amount</th>
                                                <th>Product Name</th>
                                                <th>Element ID</th>
                                                <th>Status</th>
                                                <th>Trans Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <?php if (
                                                $MyTrans = $AdminTask->Get_User_Payment_History(
                                                    $Auth->email,
                                                    $Auth->admin_role
                                                )
                                            ) {
                                                $sn = 0;
                                                foreach ($MyTrans as $MyTran) {
                                                    $sn++; ?>
                                            <tr>
                                                <td><?= $sn ?></td>
                                                <td><a href="#"
                                                        style="color: #003366"><?= $MyTran->request_id ?></a>
                                                </td>
                                                <td>N<?= $MyTran->amount ?></td>
                                                <td><?= is_numeric($MyTran->product_name)
                                        ? 'Cheap Data'
                                        : strtoupper(
                                            $MyTran->product_name
                                        ) ?></td>
                                                <td><?= strtoupper(
                                        $MyTran->unique_element
                                    ) ?></td>

                                                <td><?php if ($MyTran->status == 1) { ?>
                                                    <span class="badge light badge-success">Successful</span>
                                                    <?php } elseif ($MyTran->status == 2) { ?>
                                                    <span
                                                        class='badge light badge-danger'><?= $MyTran->response_description ?></span>

                                                    <?php } else { ?>
                                                    <a href="#" class='badge light badge-info'>Pending</a>
                                                    <?php } ?>
                                                </td>
                                                <td><?= $MyTran->transaction_date ?></td>

                                            </tr>


                                            <?php
                                                }
                                            } ?>

                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>#</th>

                                                <th>Trans_ID</th>
                                                <th>Amount</th>
                                                <th>Product Name</th>
                                                <th>Element ID</th>
                                                <th>Status</th>
                                                <th>Trans Date</th>
                                            </tr>
                                        </tfoot>
                                    </table>
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

</body>

</html>