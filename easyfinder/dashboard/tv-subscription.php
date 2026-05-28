<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE   = strtoupper($_GET['type']).' Bill Payment';
$URL_NAME     = 'dashboard/subscription/'.$_GET['type'];
require_once("../inc/accessbility_controller.inc.php"); 

$type = $_GET['type'];

?>
<!DOCTYPE html>
<html lang="en">

<head><meta charset="utf-8">
   <?php
   require_once 'layout/header-propt.inc.php';
   ?>

<title><?= $PAGE_TITLE." | ".SITE_TITLE ?> </title>
</head>
<body>

   <?php  require_once 'layout/preloader.inc.php'; ?>

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
          <?php  include('layout/minor-top-navbar.inc.php'); ?>
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
                        <div class="card text-white bg-secondary">
                         <div class="card-body ">
      <?php

switch ($type) {
  case 'dstv':
    $products_img = 'Pay-DSTV-Subscription.jpg';
    break;
     case 'showmax':
    $products_img = 'ShowMax.jpg';
    break;
     case 'startimes':
    $products_img = 'Startimes-Subscription.jpg';
    break;
     case 'gotv':
    $products_img = 'Gotv-Payment.jpg';
    break;
    
}



?>

                              <div class="bootstrap-media">
                                    <div class="media">
                                        <img src="https://vtpass.com/resources/products/200X200/<?= $products_img ?>" alt="<?=strtoupper($_GET['type']) ?>" class="img-responsive" style="max-width: 100px">
                                        <div class="media-body">
                                            <h3 class="text-white" style="padding: 10px;"><?= strtoupper($_GET['type']).' Subscription'; ?></h3>
                                           <p style="margin-left: 1.5%">Choose from a range of <?=strtoupper($_GET['type']) ?> bouquets for your entertainment. Easy payment, quick value delivery.</p>
                                        </div>
                                    </div>
                                </div>
                                <hr style="">



  
  <div id="card_verify_response"> </div>


 <form action="<?=SITE_URL?>easyfinder/dashboard/view-payment-summary" method="POST" accept-charset="UTF-8" autocomplete="off" enctype="multipart/form-data">
                                      

                            <div class="row">

                      <?php
                      if($type != 'showmax'){
                      ?>
                                    <div class="form-group col-md-6">
                                    <label><?=strtoupper($type) ?> Smartcard Number : </label>
                        <input type="text" name="billersCode" class="form-control" placeholder="Enter <?=strtoupper($type) ?> Smartcard Number" autocomplete="off" required="" onchange="getVerify(this.value);">


                          <input type="hidden" value="" name="identifier" id="s_identifier">
                          <input type="hidden" value="" name="var_idx" id="var_idx">

                                    </div>
                        <?php

                      }
                        ?>
                                     <div class="form-group <?= $type == 'showmax' ? 'col-md-12' : 'col-md-6' ?> ">
                                    <label> Bouquet : </label>
                                    <?php
                                    $response =$TopupController->GetTvSubscriptionVariations($_GET['type']);
                                    ?>
                                   <select onchange="update_amount_input(this)" data-shb-product-option="data-shb-product-option" id="s_option_1" data-live-search="true" name="variation_code" class="form-control select select-block select-bordered selectpicker variation-type" required="" >
                                    <option value="">Choose Option</option>
                                    <?php
                                    if ($response){
                                    $Variations = $response;
                                    $Variation_js = $response;
                                foreach ($Variations as $value) {
                                 
                                    ?>
                          <option value="<?= $value['id']."{BRK}".$value["amount"] ?>" style="max-width: 300px"><?= $value['name'] ?></option>
                                    <?php
                                  }
                                }

                                    ?>
                                  </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                    <label>Amount : </label>
                                    <input type="Number"  class="form-control" placeholder="Enter Amount" id="s_amount" name="amount" readonly="" required="">
                                    </div>
                                     <div class="form-group col-md-6">
                                    <label>Number of months : </label>
                                    <input type="Number" name="quantity" class="form-control" placeholder="" required="" value="1">
                                    </div>
                                </div>

                                 <div class="row">
                                    <div class="form-group col-md-6">
                                    <label>Phone Number : </label>
                                    <input type="Number" name="phone" class="form-control" placeholder="" required="" value="<?= $Auth->phone?>">
                                    </div>
                                     <div class="form-group col-md-6">
                                    <label>Email Address : </label>
                                    <input type="email" name="p_demo" class="form-control" placeholder="" required="" value="<?= $Auth->email?>" readonly="">
                                    </div>
                                    <input type="hidden" name="subscription_type" id="sub_type" value="Change">
                                    <input type="hidden" name="serviceID" value="<?= $type ?>">
                                </div>
                               
                               
                            <div class="form-group">
                            <input name="continue_sub" type="submit" class="btn btn-primary pull-right" value="Add Now" id="continue_sub">                                            
                            </div>



                                    </form>




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
  



<script type="text/javascript">
  var mainVariation = <?= json_encode($Variation_js->content->variations) ?>;
          
           
            $(document).on('change',function(e){
                if(e.target.dataset.shbProductOption != undefined) {
                    var varx ;
                    var options = document.querySelectorAll("[data-shb-product-option]");
                    for(var i = 0; i < options.length; i++) {
                        if (options[i].value == '') {
                            varx = "";
                        }else{
                            varx = options[i].value;
                        }
                        
                    }
                    var stock = -1; // default stock value
                    // check the mainProduct object if the selection is in stock
                    for(var i = 0; i < mainVariation.length; i++) {
                        var code = mainVariation[i].variation_code; 
                        //alert(JSON.stringify(code));           
                        if(JSON.stringify(code) == JSON.stringify(varx)) {
                            
                            var amount = mainVariation[i].variation_amount;
                            var identifier = mainVariation[i].variation_code;      
                            var var_idx = mainVariation[i].variation_code;
                            var sub_type = mainVariation[i].name;
                        }
                    }

                    if ($('#s_option_1').val() != '') {
                        $('#otherDetails').css('display','block');
                        $('#price-per-qty').html('');
                        if (amount == undefined) {
                            $('#s_amount').attr('value','0');
                            $('#s_amount').attr('readonly');
                        }else if(amount == 0){
                            $('#s_amount').attr('value','0');
                            $('#s_amount').removeAttr('readonly');                      
                        }
                        var p_amount_edit = 1;
                        if ((amount > 1) && (p_amount_edit ==  0) ) {
                            $('#s_amount').attr('readonly','readonly');
                        }
                        $('#s_amount').attr('value',amount);
                        $('#s_amount').val(amount);
                         $('#sub_type').attr('value',sub_type);
                        $('#sub_type').val(sub_type);
                        $('#price-per-qty').html('(N'+Math.round(amount).toFixed(0)+' per month)');
                        
                        if (identifier != undefined) {
                            $('#s_identifier').attr('value',identifier);
                        }           
                        $('#var_idx').attr('value',var_idx);
                    }else{
                        $('#otherDetails').css('display','none');
                    }    
                }
            });
            $('.selectpicker').selectpicker({
                style: 'btn-info',
                size: 4
            });
</script> 





<script type="text/javascript">
const service = `<?= $type ?>`;

const update_amount_input = (el) => {
    document.querySelector("#s_amount").value = el.value.split("{BRK}")[1];
};

if (service != 'showmax'){
$('#continue_sub').prop('disabled',true);
}

function getVerify(code) {
  $('#card_verify_response').html("<div class='alert alert-primary' style='text-align:center'>Please wait while verifying your Smartcard Number...</div>");

  var queryString = "";
  queryString = 'verify_tv_card_num='+code+'&serviceID=<?= $_GET['type'] ?>'; 
 
  jQuery.ajax({
  url: "<?=SITE_URL?>easyfinder/inc/get-data-ajax.inc",
  data:queryString,
  type: "POST",
  success:function(data){
    if(data != 0){
    toastr.success('Your Smartcard/Decoder Number is verified!');
     var myObj = JSON.parse(data);
     $('#continue_sub').prop('disabled',false);
$('#card_verify_response').html("<div class='alert alert-success' style='text-align:center'><h4>"+(myObj.content.Customer_Name?"Decoder Owner : "+myObj.content.Customer_Name:"")+(myObj.content.Status?", Status : "+myObj.content.Status:"")+(myObj.content.Customer_Number?", Customer Number: "+myObj.content.Customer_Number:"")+(myObj.content.Customer_Type?", Decoder Type: "+myObj.content.Customer_Type:"")+"</h4></div>");
  
    }else{
toastr.error('The Smartcard/Decoder Number you entered may be invalid, Please check and only proceed if you are sure it is valid.');
$('#card_verify_response').html("<div class='alert alert-danger' style='text-align:center'>The Smartcard/Decoder Number you entered may be invalid, Please check and only proceed if you are sure it is valid.</div>");
$('#continue_sub').prop('disabled',true);

    }
  },
  error:function (){
    toastr.error('Network failed. Please try agian !');
//    $("#addc_"+code).val('Save changes');
  }
  });
}



</script>
</body>
</html>

