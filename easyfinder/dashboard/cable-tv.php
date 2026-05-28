<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE = 'Cable TV Subscription';
$URL_NAME = 'dashboard/cable-tv';

$CABLES = ["dstv" => ["name" => "DSTV", "variation" => "api/service-variations?serviceID=dstv", "iuc_verify" => "api/merchant-verify", "pay" => "api/pay"], "gotv" => ["name" => "GOTV", "variation" => "api/service-variations?serviceID=gotv",  "iuc_verify" => "api/merchant-verify", "pay" => "api/pay"], "startimes" => ["name" => "Startimes", "variation" => "api/service-variations?serviceID=startimes",  "iuc_verify" => "api/merchant-verify", "pay" => "api/pay"], "showmax" => ["name" => "ShowMax", "variation" => "api/service-variations?serviceID=showmax",  "iuc_verify" => null, "pay" => "api/pay"]];

require_once '../inc/accessbility_controller.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
  $post_content = file_get_contents('php://input');
  $post = json_decode($post_content, true);

  if (isset($post["pay"]) && $post["pay"] == true && isset($post["pin"]) && !empty($post["pin"]) && isset($post["id"]) && !empty($post["id"]) && isset($post["plan"]) && !empty($post["plan"]) && isset($post["number"]) && !empty($post["number"]) && isset($post["amount"]) && !empty($post["amount"])){
    $pin = md5($post["pin"]);
    $id = $post["id"];
    $plan = $post["plan"];
    $number = $post["number"];
    $trans_id = trim($WalletController->Generate_Trans_id());
    $amount = $post["amount"];

    if ($pin != $Auth->pin){
        echo json_encode(["status" => "failed", "msg" => "incorrect pin"]);
        die();
    }

    if (!$WalletController->Check_Available_Balance_From_Wallet_To_Make_Transaction($amount, $Auth->email)) {
        echo json_encode(["status" => "failed", "msg" => "insufficient wallet balance"]);
        die();
    }

    if (!isset($CABLES[$id])){
        echo json_encode(["status" => "failed", "msg" => "unknown cable service", "data" => []]);
        die();
    }

    $cable = $CABLES[$id];

    $url = VTPASS_LINK.$cable["pay"];

    if (strtolower($id) == "showmax"){
        $params = [
            "request_id" => $trans_id,
            "billersCode" => $number,
            "serviceID" => $id,
            "variation_code" => $plan,
            "phone" =>  $Auth->phone,
        ];
    }else{
        $params = [
            "request_id" => $trans_id,
            "billersCode" => $number,
            "serviceID" => $id,
            "variation_code" => $plan,
            "subscription_type" => "change",
            "phone" => $Auth->phone,
        ];
    }

    if ($WalletController->Make_Tansaction_From_My_Wallet($trans_id, $amount, $Auth->email)){

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, CURL_SSL_VERIFY);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . VTPASS_AUTH,
    ]);

    $res = curl_exec($ch);

    if (curl_errno($ch)) {
        $WalletController->Update_Refund_failed_Wallet_Money_Trans_Status($trans_id, $Auth->email, $amount);
        echo json_encode(["status" => "failed", "msg" => "cURL Error: " . curl_error($ch), "data" => []]);
    }

    $json = json_decode($res, true);

    if (!$json["code"] || $json["code"] != "000" || $json["content"]["error"]){
        $WalletController->Update_Refund_failed_Wallet_Money_Trans_Status($trans_id, $Auth->email, $amount);
        echo json_encode(["status" => "failed", "msg" => "failed trying to subscribe cable TV"]);
        die();
    }

    $data = $json["content"];
    if (strtolower($data["transactions"]["status"]) == "delivered"){
        $WalletController->Update_Successful_Remove_Wallet_Money_Trans_Status('cable-tv', $amount, $trans_id, $Auth->email);
        echo json_encode(["status" => "success", "msg" => "cable subscribed successfully"]);
    }else{
        $WalletController->Update_Refund_failed_Wallet_Money_Trans_Status($trans_id, $Auth->email, $amount);
        echo json_encode(["status" => "failed", "msg" => "something went wrong"]);
    }
    }
   }

  if (isset($post["verify"]) && isset($post["iuc_number"]) && !empty($post["iuc_number"]) && isset($post["id"]) && !empty($post["id"])){
    $iuc = $post["iuc_number"];
    $id = $post["id"];

    if (!isset($CABLES[$id])){
        echo json_encode(["status" => "failed", "msg" => "unknown cable service", "data" => []]);
        die();
    }

    $cable = $CABLES[$id];

    if ($cable["iuc_verify"] == null){
        echo json_encode(["status" => "failed", "msg" => "{$id} does not require IUC verification"]);
        die();
    }

    $url = VTPASS_LINK.$cable["iuc_verify"];

    $params = [
    'billersCode' => $iuc,
    'serviceID' => $id,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, CURL_SSL_VERIFY);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . VTPASS_AUTH,
    ]);

    $res = curl_exec($ch);

    if (curl_errno($ch)) {
        echo json_encode(["status" => "failed", "error" => "cURL Error: " . curl_error($ch), "data" => []]);
    }

    $json = json_decode($res, true);

    if (!$json["code"] || $json["code"] != "000" || $json["content"]["error"]){
        echo json_encode(["status" => "failed", "msg" => "failed trying to verifying IUC number"]);
        die();
    }

    $data = $json["content"];
    if ($id == "startimes"){
        $array = [
        "name" => $data["Customer_Name"],
        "status" => "Active",
        "number" => $data["Smartcard_Number"],
        "service" => "Startimes",
       ];
    }else{
        $array = [
        "name" => $data["Customer_Name"],
        "status" => ucwords(strtolower($data["Status"])),
        "number" => $data["Customer_Number"],
        "service" => $data["Customer_Type"],
        ];
    }
    echo json_encode(["status" => "success", "data" => $array]);
 }

if (isset($post["variation"]) && isset($post["id"]) && !empty($post["id"])){
    $id = $post["id"];
    if (!isset($CABLES[$id])){
        echo json_encode(["status" => "failed", "msg" => "unknown cable service", "data" => []]);
        die();
    }

    $cable = $CABLES[$id];
    $url = VTPASS_LINK.$cable["variation"];
    $res = file_get_contents($url);
    $json = json_decode($res, true);
    if (!isset($json["response_description"]) || $json["response_description"] != "000" || $json["content"]["error"]){
        echo json_encode(["status" => "failed", "msg" => "failed trying to fetch variations", "data" => []]);
        die();
    }
    $variations = $json["content"]["variations"];
    if (!$variations){
        echo json_encode(["status" => "failed", "msg" => "service variations not available", "data" => []]);
        die();
    }
    $data = [];
    foreach ($variations as $item){
            $c_fee = $json["content"]["convinience_fee"];
            $amount = (int) $item["variation_amount"];
            if (strpos($c_fee, "%") != FALSE){
                $num = (int) str_replace("%", "", $c_fee);
                $fees = ($num/100) * $amount;
                $amount += $fees;
            }else if (strpos($c_fee, "N") != FALSE || strpos($c_fee, "n") != FALSE){
                $fees = (int) str_replace("n", "", str_replace("N", "", $c_fee));
                $amount += $fees;
            }

            $data[] = [
                "id" => $item["variation_code"],
                "name" => $item["name"]." + ".$json["content"]["convinience_fee"],
                "amount" => $amount,
            ];
    }
    echo json_encode(["status" => "success", "data" => $data]);
}
exit();
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
                        <div class="card  text-white bg-secondary">

                        <div class="card-header">
                            <h4 class="card-title text-white"><?= $PAGE_TITLE; ?></h4>
                        </div>
                        <div class="card-body">
                              <div class="form-group">
                                    <label class="text-label">Cable Type : </label>
                                    <div class="input-group">
                                    	<select onchange="toggleBtns('verify_iuc'); getVariations(this); showMaxActions(this)" name="cable_type" id="cable_type" required="">
                                    		<?php foreach ($CABLES as $key => $cable):?>
                                    		<option value="<?= $key; ?>"><?= $cable['name']; ?></option>
                                    	<?php endforeach; ?>
                                    	</select>
                                    </div>
                                </div>
                                <div class="form-group d-none">
                                    <label class="text-label">Choose Plan : </label>
                                    <div id="cable-plans" class="input-group">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label id="iuc_number_label" class="text-label">IUC Number : </label>
                                    <div class="input-group">
                                        <input onchange="toggleBtns('verify_iuc'); showMaxActions(document.querySelector('#cable_type'));" type="number" name="iuc_number" id="iuc_number" required="" class=""
                                            autocomplete="off" placeholder="Eg: 0011223344">
                                    </div>
                                    <div class="mt-1"><small id="iuc_details"></small></div>
                                </div>

                                <button id="verify_iuc" onclick="verifyIUC(this)" class="btn action-btns btn-danger">Verify IUC</button>
                                <button id="pay_now" onclick="payNow(this)" class="btn action-btns btn-success d-none">Pay Now</button>

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
    <!-- Modal -->
    <div class="modal fade" id="exampleModalpopover">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Authentication PIN</h5>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="mb-1"><strong>Enter Your PIN : </strong></label>
                        <div class="input-group">
                            <input type="password" name="pin" id="pin" value="" required="" class="form-control" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger light" data-dismiss="modal">Close</button>
                    <a href="#" class="btn btn-primary" onclick="processPay()" id="btn-submit" data-dismiss="modal">Continue</a>
                </div>

            </div>
        </div>


    </div>
    <?php
    require_once 'layout/footer.inc.php';
    require_once 'layout/footer-propt.inc.php';
    ?>



    <script type="text/javascript">
        var bURL = "<?= SITE_URL."easyfinder/".$URL_NAME ?>";

        function toggleBtns(id){
            document.querySelectorAll(".action-btns").forEach(function(btn){
                if (btn.id == id){
                    btn.classList.remove("d-none");
                }else{
                    btn.classList.add("d-none");
                }
            })
        }

        function showMaxActions(el){
            let iuc = document.querySelector("#iuc_number"), label = document.querySelector("#iuc_number_label");
            if (el.value == "showmax"){
                toggleBtns("pay_now");
                label.innerText = "Phone Number :";
                iuc.placeholder = "Eg: 090XXXXXXXX";
            }else{
                label.innerText = "IUC Number :";
                iuc.placeholder = "Eg: 0011223344";
            }
        }

        function processPay(){
            let pin = document.querySelector("#pin").value;
            if (!pin){
                return;
            }
            let cable = document.querySelector("#cable_type").value, plan = document.querySelector("#cable_plans").value, number = document.querySelector("#iuc_number").value;

            plan = plan.split("{BRK}");

            const parameters = {
                pay: true,
                pin: pin,
                id: cable,
                plan: plan[0],
                amount: plan[1],
                number: number,
            };
            fetch(bURL, {
                method: 'POST',
                headers: {'Content-Type': 'application/json',},
                body: JSON.stringify(parameters),
            }).then(res => res.json()).then(data => {
                if (!data.status){
                    toastr.error("Something went wrong. Please refresh and try again", "Error Occurs!", {
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
                    });
                }else{
                    if (data.status == "success"){
                        toastr.success(data.msg, "Success!", {
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
                    });
                    }else{
                        toastr.error(data.msg, "Error Occurs!", {
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
                    });
                    }
                }
                if (cable.toLowerCase() == "showmax"){
                    toggleBtns("pay_now");
                }else{
                    toggleBtns("verify_iuc");
                }
            });
        }

        function payNow(){
            const myModalElement = document.getElementById('exampleModalpopover');
            const myModal = new bootstrap.Modal(myModalElement);
            myModal.show();
        }

        function verifyIUC(btn){
            let iuc_number = document.querySelector("#iuc_number").value, cable = document.querySelector("#cable_type").value, iuc_d = document.querySelector("#iuc_details");

            iuc_d.innerHTML = "";

            if (!iuc_number){
                iuc_d.innerText = "Enter your cable IUC number";
                return;
            }

            if (!cable){
                iuc_d.innerText = "Ooops... Refresh and try again";
                return; 
            }

            parameters = {
                verify:true,
                iuc_number: iuc_number,
                id: cable,
            };
            fetch(bURL, {
                method: 'POST',
                headers: {'Content-Type': 'application/json',},
                body: JSON.stringify(parameters),
            }).then(res => res.json()).then(data => {
                if (!data.status || data.status != "success"){
                    iuc_d.innerText = "Failed to verify IUC number";
                    return;
                }
                data = data.data;
                iuc_d.innerHTML = `<strong>${data.service} (${data.status})</strong> - ${data.name} (${data.number})`;
                toggleBtns('pay_now');
            });
        }

        function getVariations(el){
            if (!el){
                el = document.querySelector("#cable_type");
            }
            let serviceId = el.value;
            let cable_plans = document.querySelector("#cable-plans");
            document.querySelector("#iuc_details").innerHTML = "";
            cable_plans.innerHTML = "";
            cable_plans.parentNode.classList.add("d-none");
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
                        
                        cable_plans.innerHTML = `<select name="cable_plans" id="cable_plans" required="">${html}</select>`;
                        cable_plans.parentNode.classList.remove("d-none");
                });
            }
        }

        $(document).ready(function(){
            getVariations();
        });
    </script>



</body>

</html>