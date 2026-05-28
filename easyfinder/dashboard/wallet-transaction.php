<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE   = 'Wallet Trans. History';
$URL_NAME     = 'dashboard/wallet-transaction';
require_once("../inc/accessbility_controller.inc.php");
?>
<!DOCTYPE html>
<html lang="en">

<head><meta charset="utf-8">
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
                  <table id="example" class="display" style="min-width: 845px; border: none;">
                    <thead>
                      <tr>
                        <th></th>


                      </tr>
                    </thead>
                    <tbody>

                      <?php
                      if ($MyWallets = $WalletController->Get_Wallet_Money_Trans($Auth->email, $Auth->admin_role)) {
                        $sn = 0;
                        foreach ($MyWallets as $MyWallet) {
                          $sn++;
                      ?>
                          <tr style="border: none;">
                            <td style="border: none;">


                              <div class="media items-list-2" style="box-shadow: 0 2px 2px 0 rgba(0, 0, 0, 0.2);">
                                <img class="img-fluid rounded mr-3" width="85" src="./images/dish/pic1.jpg" alt="DexignZone">
                                <div class="media-body col-6 px-0">
                                  <h5 class="mt-0 mb-1 text-black">#<?= $MyWallet->trans_id ?></h5>

                                  <?php
                                  if ($MyWallet->status == 1) {
                                    echo '<a href="#"> <small class="text-success font-w500 mb-3"> Success </small></a>';
                                  } else {
                                  ?>

                                    <a href="credit-wallet?trxref=<?= $MyWallet->trans_id ?>&reference=<?= $MyWallet->trans_id ?>"><small class="text-secondary font-w500 mb-3"> Pending </small> </a>
                                  <?php
                                  }
                                  ?>

                                  <span class="text-secondary mr-2 fo"></span>
                                  <ul class="fs-14 list-inline">
                                    <li class="mr-3"><small class="text-info font-w500 mb-3">Trans. Amount : <?= $MyWallet->trans_amount ?></small></li>
                                    <li class="mr-3"><small class="text-warning font-w500 mb-3">Available Amount : <?= $MyWallet->available_balance  ?></small></li>
                                  </ul>

                                  <ul class="fs-14 list-inline">
                                    <li class="mr-3"><small class="text-danger font-w500 mb-3">Trans. Status : <?= $MyWallet->wallet_status ?></small></li>
                                    <li class="mr-3">Date : <?= date('d F, Y', strtotime($MyWallet->trans_date)) ?></li>

                                  </ul>
                                </div>

                              </div>


                            </td>


                          </tr>


                      <?php
                        }
                      }
                      ?>

                    </tbody>

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