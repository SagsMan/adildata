<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE   = 'Manage Plan';
$URL_NAME     = 'dashboard/manage-plan';
require_once("../inc/accessbility_controller.inc.php");
?>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        if ($AdminTask->DeleteDataPlan($id)) {
            array_push($SITE_SUCCESS, 'Plan has been deleted successfully');
        } else {
            array_push($SITE_ERRORS, 'Failed to delete plan');
        }
    }
}
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


                                <div class="float-end">
                                    <button type="button"
                                        class="btn btn-secondary btn-sm mb-1 mb-sm-0"
                                        data-toggle="modal" data-target="#changeProvider">Change Provider</button>
                                    <a href="add-plan" class="btn btn-primary btn-sm">Add New Plan</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example5" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>

                                                <th>S/N</th>

                                                <th>Plan ID</th>
                                                <th>Plan Type</th>
                                                <th>Validity</th>
                                                <th>Price</th>
                                                <th>API</th>
                                                <th>Reseller</th>
                                                <th>TopUser</th>
                                                <th> Network Type</th>
                                                <th> Network ID</th>
                                                <th> Provider</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sn = 0;
                                            if ($plans = $AdminTask->GetDataPlans()) {
                                                foreach ($plans as $plan) {

                                                    $sn++;

                                            ?>
                                                    <tr>
                                                        <td><?= $sn ?></td>
                                                        <td><?= $plan->plan_id  ?></td>
                                                        <td><?= $plan->plan  ?></td>
                                                        <td><?= $plan->validity ?></td>
                                                        <td><?= $plan->price ?></td>
                                                        <td><?= $plan->api ?></td>
                                                        <td><?= $plan->reseller ?></td>
                                                        <td><?= $plan->topuser ?></td>
                                                        <td><?= $plan->data_type ?></td>
                                                        <td><?= $plan->network_id ?></td>
                                                        <td><?= $plan->provider ?></td>

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
                                                                        <a class="dropdown-item" data-toggle="modal" data-target="#modal-default_<?= $plan->id ?>">Edit</a>
                                                                        <form action="" method="post">
                                                                            <input type="hidden" name="action" value="delete">
                                                                            <input type="hidden" name="id" value="<?= $plan->id ?>">
                                                                            <button class="dropdown-item">
                                                                                Delete
                                                                            </button>
                                                                        </form>
                                                                    <?php
                                                                    }
                                                                    ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>


                                                    <div class="modal fade" id="modal-default_<?= $plan->id ?>">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h4 class="modal-title">Manage : <?= "{$plan->plan}  {$plan->provider}"  ?></h4>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <form id="frmCart<?= $plan->id ?>" onsubmit="edit_data_ajax('edit_plan', 'table', '<?php echo $plan->id; ?>', this)">
                                                                    <div class="modal-body">
                                                                        <div class="form-group row">
                                                                            <label for="plan_type_id" class="col-sm-4 col-form-label">Plan Type <span class="text-danger">*</span></label>
                                                                            <div class="col-sm-8">
                                                                                <select class="form-control" id="plan_type_id<?= $plan->id ?>" name="plan_type_id" required>
                                                                                    <option value="">Select Data Type</option>
                                                                                    <?php foreach ($AdminTask->Get_Plan_Types() as $planType): ?>
                                                                                        <option
                                                                                            value="<?php echo $planType->id ?>"
                                                                                            <?php echo $plan->plan_type_id == $planType->id ? "selected" : "" ?>>
                                                                                            <?php echo $planType->data_type; ?>
                                                                                        </option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>

                                                                        <div class="form-group row">
                                                                            <label for="api_id" class="col-sm-4 col-form-label">API Setting <span class="text-danger">*</span></label>
                                                                            <div class="col-sm-8">
                                                                                <select class="form-control" id="api_id<?= $plan->id ?>" name="api_id" required>
                                                                                    <option value="">Select Provider</option>
                                                                                    <?php foreach ($AdminTask->Get_API_Settings() as $apiSetting): ?>
                                                                                        <option
                                                                                            value="<?php echo $apiSetting->id ?>"
                                                                                            <?php echo $plan->api_id == $apiSetting->id ? "selected" : "" ?>>
                                                                                            <?php echo $apiSetting->api_name; ?>
                                                                                        </option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>

                                                                        <div class="form-group">
                                                                            <label>Plan ID: </label>
                                                                            <input type="number" name="plan_id" class="form-control" placeholder="E.g 211 , 222, 223 etc" value="<?= $plan->plan_id ?>" required="">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Plan:</label>
                                                                            <input type="text" name="plan" class="form-control" placeholder="E.g MTN SME 1.0 GB" value="<?= $plan->plan ?>" required="">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Data Validity:</label>
                                                                            <input type="text" name="validity" class="form-control" placeholder="E.g 30 DAYS" value="<?= $plan->validity ?>" required="">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Plan Price:</label>
                                                                            <input type="number" name="price" class="form-control" placeholder="E.g 230 , 460 etc" value="<?= $plan->price ?>" required="">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Plan API Price:</label>
                                                                            <input type="number" name="api" class="form-control" placeholder="E.g 230 , 460 etc" value="<?= $plan->api ?>" required="">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Plan Reseller Price:</label>
                                                                            <input type="number" name="reseller" class="form-control" placeholder="E.g 230 , 460 etc" value="<?= $plan->reseller ?>" required="">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Plan TopUser Price:</label>
                                                                            <input type="number" name="topuser" class="form-control" placeholder="E.g 230 , 460 etc" value="<?= $plan->topuser ?>" required="">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Network ID:</label>
                                                                            <input type="number" name="network_id" class="form-control" placeholder="eg 1, 2, 3, or 4" value="<?= $plan->network_id ?>" required="">
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer justify-content-between">
                                                                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                                                                        <button type="submit" id="addc_<?php echo $plan->id; ?>" class="btn btn-primary">Save Changes</button>
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

                            <!-- Modal -->
                            <div class="modal fade" id="changeProvider">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Chnage Provider</h5>
                                        </div>
                                        <form id="frmProvider" onsubmit="edit_data_ajax('change_provider', 'table', 'provider', this)">
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <input type="hidden" name="action" value="change_provider">
                                                    <label class="mb-1"><strong>Active Provider : </strong></label>
                                                    <div>
                                                        <select name="provider_id" id="provider_id" class="form-control" required="">
                                                            <?php
                                                            if ($providers = $AdminTask->Get_API_Settings()) {
                                                                foreach ($providers as $provider) {
                                                            ?>
                                                                    <option
                                                                        value="<?= $provider->id ?>"
                                                                        <?= $provider->is_active == 1 ? "selected" : "" ?>>
                                                                        <?= $provider->api_name ?></option>
                                                            <?php
                                                                }
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-danger light" data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-danger" id="addc_provider">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
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
        var table = $('#example5').DataTable();
                
        // Get the selected provider name from the dropdown text
        var providerName = $('#provider_id option:selected').text().trim();

        // Column index 10 = "Provider" (0-based, from your <thead>)
        table.column(10).search(providerName, false, false).draw();
    
        function edit_data_ajax(action, table, code, form) {
            event.preventDefault();
            $('#addc_' + code).attr('disabled', true);
            $("#addc_" + code).text('Processing...');
            let data, modal;

            if (action !== "") {
                switch (action) {
                    case "edit_plan":
                        data = new FormData(form);
                        data.append('plan_type_id', $(`#plan_type_id${code}`).val());
                        data.append('api_id', $(`#api_id${code}`).val());
                        data.append('id', code);
                        data.append('action', action);
                        modal = `#modal-default_${code}`;

                        break;

                    case 'change_provider':
                        data = new FormData(form);
                        modal = '#changeProvider'

                        break;
                }
            }

            jQuery.ajax({
                url: "../inc/get-data-ajax.inc",
                data: data,
                type: "POST",
                contentType: false,
                processData: false,
                // success: function(response) {
                //     $(modal).modal('hide');
                //     if (response == 1) {
                //         toastr.success("Your data was updated successfully");

                //         // $('#example5').DataTable().ajax.reload(null, false); // Reload without resetting pagination
                //     } else {
                //         toastr.error(response);
                //     }

                //     // Reset the button state
                //     $("#addc_" + code).text('Save Changes');
                //     $("#addc_" + code).attr('disabled', false);
                // },
                success: function(response) {
                    $(modal).modal('hide');
                    if (response > 0) { // response is now the provider_id
                        toastr.success("Your data was updated successfully");
                
                        if (action === 'change_provider') {
                            var table = $('#example5').DataTable();
                
                            // Get the selected provider name from the dropdown text
                            var providerName = $('#provider_id option:selected').text().trim();
                
                            // Column index 10 = "Provider" (0-based, from your <thead>)
                            table.column(10).search(providerName, false, false).draw();
                        }
                
                    } else {
                        toastr.error(response);
                    }
                
                    $("#addc_" + code).text('Save Changes');
                    $("#addc_" + code).attr('disabled', false);
                },
                error: function(xhr, status, error) {
                    toastr.error('Network error. Please try again!');
                    $("#addc_" + code).text('Save changes');
                    $("#addc_" + code).attr('disabled', false);
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