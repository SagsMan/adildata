 
 <meta http-equiv="X-UA-Compatible" content="IE=edge">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <base href="<?= SITE_URL ?>easyfinder/dashboard/">
 <!-- Favicon icon -->
 <link rel="icon" type="image/png" sizes="16x16" href="images/<?= SITE_LOGO ?>">
 <link href="vendor/jqvmap/css/jqvmap.min.css" rel="stylesheet" media="screen">
 <link rel="stylesheet" href="vendor/chartist/css/chartist.min.css" media="screen">
 <link href="vendor/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet" media="screen">
 <link href="./css/dashboard-style.css" rel="stylesheet" media="screen">
 <link href="css/style.css?<?php echo time() ?>" rel="stylesheet" media="screen">
 <link href="https://cdn.lineicons.com/2.0/LineIcons.css" rel="stylesheet">
 <!-- Toastr -->
 <link rel="stylesheet" href="./vendor/toastr/css/toastr.min.css" media="screen">
 <!-- Datatable -->
 <link href="vendor/datatables/css/jquery.dataTables.min.css" rel="stylesheet" media="screen">
 <!-- Custom Stylesheet -->
 <link href="vendor/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet" media="screen">
 <link href="vendor/summernote/summernote.css" rel="stylesheet" media="screen">
 <link href="vendor/jquery-steps/css/jquery.steps.css" rel="stylesheet" media="screen">
 <link href="vendor/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet" media="screen">



 <!--Start of Tawk.to Script-->
 <!-- <script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/61d70c18f7cf527e84d0c492/1foo0eg5i';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script> -->
 <!--End of Tawk.to Script-->






 <script type="text/javascript">
function copyToClipboard(element) {
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val($(element).text()).select();
    if (document.execCommand("copy")) {
        toastr.success("Your Referal Link Copied !", "success!", {
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
        //alert("Your Referal Link Copied !");
    }
    $temp.remove();
}
 </script>