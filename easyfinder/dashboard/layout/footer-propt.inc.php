   <!--**********************************
        Main wrapper end
    ***********************************-->

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
 <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/plugins-init/sweetalert.init.js"></script>

    
    <script src="vendor/global/global.min.js"></script>
	<script src="vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
	<script src="vendor/chart.js/Chart.bundle.min.js"></script>
    <script src="js/custom.min.js"></script>
	<script src="js/deznav-init.js"></script>
	
	<!-- Counter Up -->
    <script src="vendor/waypoints/jquery.waypoints.min.js"></script>
    <script src="vendor/jquery.counterup/jquery.counterup.min.js"></script>	
		
	<!-- Apex Chart -->
	<script src="vendor/apexchart/apexchart.js"></script>	
	
	<!-- Chart piety plugin files -->
	<script src="vendor/peity/jquery.peity.min.js"></script>
	
	<!-- Dashboard 1 -->
	<script src="js/dashboard/dashboard-1.js?<?= time() ?>"></script>


	 <!-- Jquery Validation -->
    <script src="vendor/jquery-validation/jquery.validate.min.js"></script>
    <!-- Form validate init -->
    <script src="js/plugins-init/jquery.validate-init.js"></script>


     <!-- Toastr -->
    <script src="vendor/toastr/js/toastr.min.js"></script>

    <!-- All init script -->
    <script src="js/plugins-init/toastr-init.js"></script>

  <!-- Summernote -->
    <script src="vendor/summernote/js/summernote.min.js"></script>
    <!-- Summernote init -->
    <script src="js/plugins-init/summernote-init.js"></script>

    
    <!-- Datatable -->
    <script src="vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="js/plugins-init/datatables.init.js"></script>
    <script src="vendor/jquery-steps/build/jquery.steps.min.js"></script>

    <!-- Form step init -->
    <script src="js/plugins-init/jquery-steps-init.js"></script>
       <script src="js/encrypt_data_mmmmmmmmmmmmmddddddddddddddddddd.js"></script>




  



<?php if (count($SITE_ERRORS) > 0): ?>
        <?php foreach ($SITE_ERRORS as $error): ?>
     
     <script type="text/javascript">
     
                toastr.error("<?= strip_tags($error) ?>", "Error Occurs!", {
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
         
</script>
   
        <?php endforeach ?>
    <?php endif ?>
<?php if (count($SITE_SUCCESS) > 0): ?>
        <?php foreach ($SITE_SUCCESS as $good): ?>
             
  <script type="text/javascript">
     
                toastr.success("<?= strip_tags($good) ?>", "Good Job", {
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
         
</script>
        <?php endforeach ?>
    <?php endif ?>



