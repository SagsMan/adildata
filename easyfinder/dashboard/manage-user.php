<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE   = 'Manage Users';
$URL_NAME     = 'dashboard/manage-user';
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





        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h4 class="card-title"><?= $PAGE_TITLE ?></h4>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table id="example5" class="display" style="min-width: 845px">
                    <thead>
                      <tr>

                        <th>Id</th>

                        <th>User FullName</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>User Type</th>
                        <th>Status</th>
                        <th> Date Joined</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $sn = 0;
                      if ($Users = $AdminTask->Get_All_Users()) {
                        foreach ($Users as $User) {

                          $sn++;

                      ?>
                          <tr>
                            <td><?= $sn ?></td>
                            <td><?= $User->sname . " " . $User->oname  ?></td>
                            <td><?= $User->email  ?></td>
                            <td><?= $User->phone ?></td>
                            <td><?= $User->role_name ?></td>
                            <td><?= $User->status == 1 ? 'Active' : "Inactive" ?></td>
                            <td><?= $User->date_join ?></td>


                            <td>
                              <div class="dropdown ml-auto text-right">
                                <div class="btn-link" data-toggle="dropdown">
                                  <svg width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                      <rect x="0" y="0" width="24" height="24"></rect>
                                      <circle fill="#000000" cx="5" cy="12" r="2"></circle>
                                      <circle fill="#000000" cx="12" cy="12" r="2"></circle>
                                      <circle fill="#000000" cx="19" cy="12" r="2"></circle>
                                    </g>
                                  </svg>
                                </div>


                                <div class="dropdown-menu dropdown-menu-right">
                                  <?php
                                  if (in_array(1, explode(',', $Auth->admin_role))) {
                                  ?>
                                    <a class="dropdown-item" data-toggle="modal" data-target="#modal-default_<?= $User->id ?>">Edit</a>
                                    <a class="dropdown-item" data-toggle="modal" data-target="#modal-Wallet_<?= $User->id ?>">Fund User Wallet</a>
                                  <?php
                                  }
                                  ?>
                                  <a class="dropdown-item" href="#" id="delete_<?php echo $User->id; ?>" onClick="if(confirm('Are you sure want to change status this?')){edit_data_ajax('disabled_data', 'users_tbl', '<?php echo $User->id; ?>'); }"><?= $User->status == 1 ? 'Disable' : 'Active' ?></a>

                                </div>
                              </div>
                            </td>
                          </tr>






                          <div class="modal fade" id="modal-default_<?= $User->id ?>">
                            <div class="modal-dialog">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h4 class="modal-title">Manage : <?= $User->sname . " " . $User->oname  ?></h4>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form id="frmCart">
                                  <div class="modal-body">





                                    <div class="form-group">
                                      <label>Identification ID:</label>
                                      <input class="form-control" type="text" id="means_of_id_<?= $User->id ?>" value="<?= $User->means_of_id ?>">
                                    </div>
                                    <div class="form-group">
                                      <label>Enter Company Name:</label>
                                      <input class="form-control" type="text" id="company_name_<?= $User->id ?>" value="<?= $User->school ?>">
                                      <input type="hidden" id="id_<?= $User->id ?>" value="<?= $User->id ?>">
                                    </div>

                                    <div class="form-group">
                                      <label>Enter Agent Town:</label>
                                      <input class="form-control" type="text" id="town_<?= $User->id ?>" value="<?= $User->town ?>">
                                    </div>

                                    <div class="form-group">
                                      <label>Enter Agent State:</label>
                                      <input class="form-control" type="text" id="state_<?= $User->id ?>" value="<?= $User->state ?>">
                                    </div>

                                    <div class="form-group">
                                      <label>Address:</label>
                                      <input class="form-control" type="text" id="address_<?= $User->id ?>" value="<?= $User->address ?>">
                                    </div>


                                    <div class="form-group">
                                      <label>Change User Role:</label>
                                      <select class="form-control" name="" id="role_<?= $User->id ?>">
                                        <option value=""><-- Choose role--> </option>
                                        <?php
                                        if ($user_roles = $AdminTask->Get_User_Role()) {
                                          foreach ($user_roles as $user_role) {
                                        ?>
                                            <option value="<?= $user_role->role  ?>" <?= $User->admin_role == $user_role->role ? 'selected' : '' ?>><?= $user_role->role_name ?> </option>

                                        <?php
                                          }
                                        }
                                        ?>
                                      </select>
                                    </div>







                                  </div>
                                  <div class="modal-footer justify-content-between">
                                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                                    <input type="button" value="Save changes" id="addc_<?php echo $User->id; ?>" onClick="edit_data_ajax('edit_client', 'table', '<?php echo $User->id; ?>')" class="btn btn-primary">
                                  </div>
                                </form>
                              </div>
                              <!-- /.modal-content -->
                            </div>
                            <!-- /.modal-dialog -->
                          </div>
                          <!-- /.modal -->




                          <div class="modal fade" id="modal-Wallet_<?= $User->id ?>">
                            <div class="modal-dialog">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h4 class="modal-title">Fund : <?= $User->sname . " " . $User->oname  ?></h4>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form id="frmCart">
                                  <div class="modal-body">

                                    <div class="form-group">
                                      <label>Enter Amount :</label>
                                      <input required="" class="form-control" type="number" id="amount_<?= $User->id ?>" value="">

                                    </div>

                                    <div class="form-group">
                                      <label>User Email :</label>
                                      <input required="" class="form-control" type="email" readonly="" id="email_<?= $User->id ?>" value="<?= $User->email ?>">

                                      <input required="" class="form-control" type="hidden" readonly="" id="trans_id_<?= $User->id ?>" value="<?= $WalletController->Generate_Trans_id()  ?>">

                                    </div>

                                  </div>
                                  <div class="modal-footer justify-content-between">
                                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                                    <input type="button" value="Credit Wallet" id="addc_<?php echo $User->id; ?>" onClick="if(confirm('Are you sure want to credit this user?')){edit_data_ajax('fund_user_wallet', 'table', '<?php echo $User->id; ?>') }" class="btn btn-primary">
                                  </div>
                                </form>
                              </div>
                              <!-- /.modal-content -->
                            </div>
                            <!-- /.modal-dialog -->
                          </div>
                          <!-- /.modal -->





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
  require_once 'layout/footer-propt.inc.php';
  ?>






  <script>
    function edit_data_ajax(action, table, code) {

      var queryString = "";
      if (action != "") {
        switch (action) {

          case "edit_client":
            if (valid(code)) {
              queryString = 'action=' + action + '&company_name=' + $("#company_name_" + code).val() + '&address=' + $("#address_" + code).val() + '&id=' + $("#id_" + code).val() + '&town=' + $("#town_" + code).val() + '&state=' + $("#state_" + code).val() + '&means_of_id=' + $("#means_of_id_" + code).val() + '&role=' + $("#role_" + code).val();
              $("#addc_" + code).val('Saving...');
            }
            break;

          case "fund_user_wallet":
            queryString = 'action=' + action + '&amount=' + $("#amount_" + code).val() + '&email=' + $("#email_" + code).val() + '&trans_id=' + $("#trans_id_" + code).val();
            $("#addc_" + code).val('Saving...');
            break;

          case "disabled_data":
            queryString = 'action=' + action + '&table=' + table + '&id=' + $("#id_" + code).val();
            $("#delete_" + code).hide();
            $("#addc_" + code).hide();
            break;

        }
      }
      jQuery.ajax({
        url: "../inc/get-data-ajax.inc",
        data: queryString,
        type: "POST",
        success: function(data) {
          if (data == 1) {
            toastr.success("Your data changed successfully");
          } else {
            toastr.error(data);
          }
          $("#addc_" + code).val('Saved');
        },
        error: function() {
          toastr.error('Network failed. Please try agian !');
          $("#addc_" + code).val('Save changes');
        }
      });
    }




    function valid(code) {
      var valid = true;
      $(".demoInputBox").css('background-color', '');
      $(".info").html('');

      if ($("#company_name_" + code).val().length < 3) {
        toastr.error('Please Enter Company Name!');
        $("#company_name_" + code).css('background-color', '#FFF0DF');
        valid = false;
      }

      if ($("#means_of_id_" + code).val().length < 3) {
        toastr.error('Please Enter Means of ID!');
        $("#means_of_id_" + code).css('background-color', '#FFF0DF');
        valid = false;
      }

      if ($("#town_" + code).val().length < 3) {
        toastr.error('Please Enter town !');
        $("#town_" + code).css('background-color', '#FFF0DF');
        valid = false;
      }

      if ($("#state_" + code).val().length < 3) {
        toastr.error('Please Enter State Of Origin!');
        $("#state_" + code).css('background-color', '#FFF0DF');
        valid = false;
      }

      return valid;
    }
  </script>





</body>

</html>