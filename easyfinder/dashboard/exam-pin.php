<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE = 'Examination PIN';
if (!isset($_GET['exam_type'])) {
    $_GET['exam_type'] = "waec";
}
$URL_NAME = 'dashboard/exam-pin?exam_type=' .$_GET['exam_type'];
require_once '../inc/accessbility_controller.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
  $post_content = file_get_contents('php://input');
  $post = json_decode($post_content, true);

  if (isset($post["variation"]) && isset($post["id"]) && !empty($post["id"])){
    $id = $post["id"];
    echo json_encode(["status" => "success", "data" => $TopupController->getExamTypeVariation($id)]);
    exit();
}

if (isset($post["verify"]) && isset($post["id_number"]) && !empty($post["id_number"]) && isset($post["id"]) && !empty($post["id"]) && isset($post["type"]) && !empty($post["type"])){
    $id_num = $post["id_number"];
    $id = $post["id"];
    $type = $post["type"];
    $TopupController->verifyJambId($id, $id_num, $type);
    exit();
 }
}

if ($exam_type_info = $TopupController->GetSingleExamType($_GET['exam_type'])) {
    }
?>
<!DOCTYPE html>
<html lang="en">

<head><meta charset="utf-8">
    <?php require_once 'layout/header-propt.inc.php'; ?>

    <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?> </title>
    <style type="text/css">
    table {
        width: 100%
    }

    #table th,
    #table td {
        border: none;
        padding: 7px;
    }
    </style>
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





                <div class="row ">
                    <div class="col-12">
                        <div class="card ">
                            <div class="card-body ">
                                <?php if (
          isset($_POST['buy_exam_pin']) &&
          !empty($_POST['amount'])
      ) {
          $trans_id = trim($_POST['trans_id']);
          if (
              !$WalletController->Check_If_My_Transaction_Id_Exist(
                  $trans_id,
                  'transactions_tbl'
              )
          ) {
              $amount = $_POST['amount'] * $_POST['qty'];
              if (
                  $TopupController->Store_My_Trans(
                      $trans_id,
                      $amount,
                      $_POST['amount'],
                      $Auth->email,
                      $Auth->phone,
                      $Auth->phone,
                      strtoupper($_POST['exam_type']),
                      0
                  )
              ) {
                  if (
                      $WalletController->Check_Available_Balance_From_Wallet_To_Make_Transaction(
                          $amount,
                          $Auth->email
                      )
                  ) {
                      if (
                          $WalletController->Make_Tansaction_From_My_Wallet(
                              $trans_id,
                              $amount,
                              $Auth->email
                          )
                      ) {
                          if (
                              $Airtime_result = $TopupController->BuyExamPin(
                                  $_POST
                              )
                          ) {
                            ?>



                                <?php if (isset($Airtime_result->success) && strlen($Airtime_result->success)) {
    if (
        $Airtime_result->success == 'true' &&
        $Airtime_result->status === 'Successful'
    ) {
        $status = 1;
        if (
            $WalletController->Update_Successful_Remove_Wallet_Money_Trans_Status(
                'result-pin-buy',
                $amount,
                $trans_id,
                $Auth->email
            )
        ) {
            if (
                $TopupController->Confirm_My_Trans(
                    $Airtime_result->status,
                    $trans_id,
                    $Airtime_result->reference_no,
                    $status
                )
            ) {
                $result_rsult_pin_bought = $Airtime_result->data;
                if (
                    $trans_info = $TopupController->Get_Trans_Info($trans_id)
                ) { ?>

                                <div class="col-xl-12">
                                    <div class="card text-dark">
                                        <div class="card-header">
                                            <h5 class="card-title ">Transaction Successful :
                                                <?= $Airtime_result->status ?> </h5>
                                        </div>
                                        <div class="card-body mb-0">




                                            <div class="row">


                                                <?php if (isset($result_rsult_pin_bought)) {
    foreach ($result_rsult_pin_bought as $pin) {
        $TopupController->Store_Buy_Token_OR_Pin(
            $pin[0],
            $Auth->email,
            $trans_id
        ); ?>

                                                <div class="col-sm-4 col-print-3" style="border: solid #000;">
                                                    <div class="panel-body well">
                                                        <h4 style="color: #000; text-align: center;"><?= strtoupper(
    $_POST['exam_type']
) ?> PIN</h4>
                                                        <p>PIN : <strong><?= $pin[0] ?></strong> <br>
                                                        <?php 
                                                        if (isset($pin[1]) && !empty($pin[1])){
                                                            ?>
                                                         <p>Serial Number : <strong><?= $pin[1] ?></strong> <br>
                                                            <?php
                                                        }
                                                        ?>
                                                        </p>


                                                    </div>
                                                </div>

                                                <?php
    }
} ?>



                                            </div>










                                        </div>
                                    </div>
                                </div>


                                <?php }
            }
        }
    } else {
        $status = 2;

        if ($trans_info = $TopupController->Get_Trans_Info($trans_id)) {
            if (
                $TopupController->Confirm_My_Trans(
                    'Transaction Failed',
                    $trans_info->request_id,
                    $trans_info->request_id,
                    $status
                )
            ) {
                if (
                    $WalletController->Update_Refund_failed_Wallet_Money_Trans_Status(
                        $trans_info->request_id,
                        $Auth->email,
                        $trans_info->amount
                    )
                ) {
                    array_push($SITE_ERRORS, 'TRANSACTION FAILED'); ?>

                                <div class="col-xl-12">
                                    <div class="card text-white bg-danger">
                                        <div class="card-header">
                                            <h5 class="card-title text-white"><?= strtoupper(
                                    $trans_info->product_name
                                ) ?> : Transaction Failed </h5>
                                        </div>
                                        <div class="card-body mb-0">


                                            <div class="table-responsive">
                                                <table class="table text-white" id="table">

                                                    <tr>
                                                        <th>Product Name</th>
                                                        <td><?= $trans_info->product_name ?>
                                                        <td>
                                                    </tr>

                                                    <tr>
                                                        <th>Phone Number</th>
                                                        <td><?= $trans_info->unique_element ?>
                                                        <td>
                                                    </tr>

                                                    <tr>
                                                        <th>Amount</th>
                                                        <td><?= $trans_info->amount ?>
                                                        <td>
                                                    </tr>
                                                    <tr>
                                                        <th>Quatity</th>
                                                        <td><?= $_POST['qty'] ?>
                                                        <td>
                                                    </tr>
                                                    <tr>
                                                        <th>Email : </th>
                                                        <td><?= $trans_info->email ?>
                                                        <td>
                                                    </tr>

                                                    <tr>
                                                        <th>Transaction ID</th>
                                                        <td><?= $trans_info->transaction_id ?>
                                                        <td>
                                                    </tr>

                                                    <tr>
                                                        <th>Request ID</th>
                                                        <td><?= $trans_info->request_id ?>
                                                        <td>
                                                    </tr>


                                                    <tr>
                                                        <th>Status</th>
                                                        <td>Failed
                                                        <td>
                                                    </tr>
                                                </table>


                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <?php
                }
            }
        }
    }
}}
                      }
                  } else {
                      if (
                          $TopupController->Confirm_My_Trans(
                              'Insuficient Balance',
                              $trans_id
                          )
                      ) {
                          array_push(
                              $SITE_ERRORS,
                              'Insuficient Balance. Please fund your wallet and try again!'
                          );
                      } ?>
                                <div class="alert alert-danger" style="text-align:center">Insuficient Balance. Please <a
                                        href="<?= SITE_URL ?>easyfinder/dashboard/credit-wallet">Click Here</a> To Fund Your
                                    Wallet</div>
                                <a href="<?= SITE_URL ?>easyfinder/dashboard/topup" class="btn btn-primary">Re-try again</a>

                                <?php
                  }
              }
          } else {
              array_push($SITE_ERRORS, 'Duplicate Transaction Id or Key!'); ?>

                                <div class="alert alert-danger" style="text-align:center">Duplicate Transaction Id or
                                    Key</div>
                                <a href="<?= SITE_URL ?>easyfinder/dashboard/resultchecker/<?= $_GET[
    'exam_type'
] ?>" class="btn btn-primary">Re-try again</a>

                                <?php
          }
      } else {

          $trans_id = $WalletController->Generate_Trans_id();
          $exam_type = strip_tags(htmlspecialchars($_GET['exam_type']));
          ?>

                                <div class="card-header">
                                    <h5 class="card-title text-white">Buy Examination PIN </h5>
                                </div>
                                <div class="card-body mb-0">

                                    <hr style="">

                                    <form class="form-valide-with-icon" method="POST" action="">
                                        <div class="form-group col-md-12 ">
                                            <label> Select Exam Type: </label>
                                            <?php
                                            $exam_types =  $TopupController->GetExamTypes();
                                            ?>
                                            <select onchange="getVariations(this)" data-shb-product-option="data-shb-product-option"
                                                data-live-search="true" id="exam-type"
                                                class="form-control select select-block select-bordered selectpicker"
                                                required="">
                                            <option value="">Choose Option</option>
                                            <?php
                                            foreach ($exam_types as $key => $e_type){
                                                ?>
                                                <option value="<?= $key ?>" <?= (($exam_type_info) && is_array($exam_type_info) && count($exam_type_info) > 0 && $exam_type_info["id"] == $key?"selected":"") ?>><?= $e_type["name"] ?></option>
                                                <
                                                <?php
                                            }
                                            ?>
                                            </select>
                                        </div>
                                        <div class="form-group d-none col-md-12 ">
                                            <label> Select Exam Option: </label>
                                            <?php
                                            $exam_types =  $TopupController->GetExamTypes();
                                            ?>
                                            <div id="exam-plans"></div>
                                        </div>
                                        <div class="col-md-12 form-group">
                                            <label>Amount : </label>
                                            <div class="input-group">
                                                <input type="Number" class="form-control" placeholder="Enter Amount"
                                                    id="s_amount" name="amount" readonly="" required=""
                                                    value="">
                                            </div>
                                        </div>




                                        <div class="form-group col-md-12">
                                            <label>How Many Do Want To Buy</label>
                                            <select data-live-search="true" name="qty"
                                                class="form-control select-block select-bordered selectpicker"
                                                required="" id="qty" disabled>

                                                <option style="max-width: 300px">1</option>
                                                <option style="max-width: 300px">2</option>
                                                <option style="max-width: 300px">3</option>
                                                <option style="max-width: 300px">4</option>
                                                <option style="max-width: 300px">5</option>
                                                <option style="max-width: 300px">7</option>
                                                <option style="max-width: 300px">8</option>
                                                <option style="max-width: 300px">9</option>
                                                <option style="max-width: 300px">10</option>

                                            </select>
                                        </div>
                                    <div id="verify-tab" class="d-none form-group col-md-12">
                                    <div class="form-group">
                                    <label id="id_number_label" class="text-label">Profile ID : </label>
                                    <div class="input-group">
                                        <input class="form-control" type="number" name="id_number" id="id_number" required="" class=""
                                            autocomplete="off" placeholder="Eg: 0123456789">
                                    </div>
                                    <div class="mt-1"><small id="id_details"></small></div>
                                    </div>
                                    </div>
                                    <a href="<?= SITE_URL ?>easyfinder/dashboard" class="btn btn-danger btn-sm pull-left light">Cancel</a>
                                    <span id="verify_id" onclick="verifyID(this)" class="btn action-btns btn-danger pull-right d-none">Verify ID</span>
                                    <span class="btn btn-primary btn-sm action-btns pull-right d-none" data-toggle="modal" data-target="#exampleModalpopover" id="btn-continue">Buy Now</span>
                                    </form>




                                </div>


                                <?php
      } ?>



                            </div>
                        </div>

                    </div>








                </div>
            </div>
            <!--**********************************
            Content body end
        ***********************************-->

        </div>
        <!-- Modal -->
        <div class="modal fade" id="exampleModalpopover">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Authentication PIN</h5>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="mb-1"><strong>Enter Your Pass PIN : </strong></label>
                            <div class="input-group">
                                <input type="password" name="pass" value="" required="" id="ss_amount"
                                    class="form-control" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger light" data-dismiss="modal">Close</button>
                        <a href="#" class="btn btn-primary" id="btn-submit" data-dismiss="modal">Continue</a>
                    </div>

                </div>
            </div>


        </div>
        <?php
  require_once 'layout/footer.inc.php';
  require_once 'layout/footer-propt.inc.php';
  ?>



        <script type="text/javascript">
        let bURL = "<?= SITE_URL."easyfinder/".$URL_NAME ?>";
        function toggleBtns(id){
            document.querySelectorAll(".action-btns").forEach(function(btn){
                if (btn.id == id){
                    btn.classList.remove("d-none");
                }else{
                    btn.classList.add("d-none");
                }
            })
        }

        function JambActions(value){
            if (!value){
                value = document.querySelector("#exam-type");
            }
            let tab = document.querySelector("#verify-tab");
            if (value == "jamb"){
                toggleBtns("verify_id");
                tab.classList.remove("d-none");
            }else{
                tab.classList.add("d-none");
                toggleBtns("btn-continue");
            }
        }

        function verifyID(btn){
            plan = document.querySelector("#the-exam-plan").value;
            plan = plan.split("{BRK}")[0];

            if (!plan){
                return;
            }

            let id_number = document.querySelector("#id_number").value, exam = document.querySelector("#exam-type").value, id_d = document.querySelector("#id_details");

            id_d.innerHTML = "";

            if (!id_number){
                id_d.innerText = "Enter your exam profile ID number";
                return;
            }

            if (!exam){
                id_d.innerText = "Ooops... Refresh and try again";
                return; 
            }

            parameters = {
                verify:true,
                id_number: id_number,
                type: plan,
                id: exam,
            };
            fetch(bURL, {
                method: 'POST',
                headers: {'Content-Type': 'application/json',},
                body: JSON.stringify(parameters),
            }).then(res => res.json()).then(data => {
                if (!data.status || data.status != "success"){
                    id_d.innerText = "Failed to verify ID number";
                    return;
                }
                data = data.data;
                id_d.innerHTML = `<strong>Name: </strong> - ${data.name}`;
                toggleBtns('btn-continue');
            });
        }

        function getVariations(el){
            if (!el){
                el = document.querySelector("#exam-type");
            }
            let serviceId = el.value;
            let exam_plans = document.querySelector("#exam-plans");
            document.querySelector("#id_details").innerHTML = "";
            exam_plans.innerHTML = "";
            exam_plans.parentNode.classList.add("d-none");
            if (serviceId != ""){
                parameters = {
                    "variation": true,
                    "id": serviceId,
                };
                fetch(bURL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json',},
                    body: JSON.stringify(parameters),
                }).then(res => res.json()).then(data => {
                    if (!data.status || data.status != "success"){
                        return;
                    }

                        data = data.data;
                        let html = "";
                        data.forEach(function(item){
                            html += `<option value="${item.id}{BRK}${item.amount}">${item.name}</option>`;
                        });
                        
                        exam_plans.innerHTML = `<select onchange="updateAmount()" class="form-control select select-block select-bordered variation-type" id="the-exam-plan">${html}</select>`;
                        exam_plans.parentNode.classList.remove("d-none");
                        updateAmount();
                        JambActions(serviceId);
                });
            }
        }

        function updateAmount(){
            let el = document.getElementById("s_amount"), amount = 0, exam_plan = document.getElementById("the-exam-plan").value;
            if (exam_plan != ""){
                amount = exam_plan.split("{BRK}")[1];
                el.value = amount;
            }
        }

        function getV(id){
            return document.querySelector(id);
        }

        $(document).ready(function(){
            getVariations();

            $('#btn-submit').on('click', function(e) {
            e.preventDefault();
            var send_to_confirm = "<?= $Auth->pin ?>";
            var send_to_confirm_entered = $('#ss_amount').val();
            var ss_amount = md5(send_to_confirm_entered);
            if (send_to_confirm === ss_amount) {
                swal.fire({
                    title: "<br><span style='font-size: 20px; color:red'>Please confirm your request? </span> <br> <p style='font-size:18px; font-weight:1px'>Exam Type : " +
                        document.getElementById('exam-type').value.toUpperCase() + " <br> Quantity : " + document
                        .getElementById('qty').value + " <br> Amount : " + (document.getElementById(
                            's_amount').value * document.getElementById('qty').value) + "</p>",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#003366",
                    confirmButtonText: "Confirm",
                }).then(function(result) {
                    if (result.value === true) {
                        let exam_type = getV("#exam-type").value, exam_plan = getV("#the-exam-plan").value, quantity = getV("#qty").value, amount = getV("#s_amount").value, profile_id = getV("#id_number").value;
                        params = {
                            buy_exam_pin: true,
                            exam_type: exam_type,
                            exam_plan: exam_plan.split("{BRK}")[0],
                            qty: quantity,
                            amount: amount,
                            trans_id: "<?= $trans_id ?>",
                            profile_id: profile_id,
                        };
                const form = document.createElement("form");
                form.method = "POST";
                form.action = bURL;

               for (const key in params) {
                if (Object.prototype.hasOwnProperty.call(params, key)) {
                  const hiddenField = document.createElement("input");
                  hiddenField.type = "hidden";
                  hiddenField.name = key;
                  hiddenField.value = params[key];
                  form.appendChild(hiddenField);
                }
               }

               document.body.appendChild(form);
               form.submit();
                    }
                });
            } else {
                toastr.error("Invalid Pass Pin. Please try again !", "Error Occurs!", {
                    positionClass: "toast-top-right",
                    timeOut: 5e3,
                    closeButton: !0,
                    debug: !1,
                    newestOnTop: !0,
                    progressBar: !0,
                    preventDuplicates: !0,
                    onclick: null,
                    showDuration: "300",
                    hideDuration: "1000",
                    extendedTimeOut: "1000",
                    showEasing: "swing",
                    hideEasing: "linear",
                    showMethod: "fadeIn",
                    hideMethod: "fadeOut",
                    tapToDismiss: !1
                })
            }
        });
        });
        </script>



</body>

</html>