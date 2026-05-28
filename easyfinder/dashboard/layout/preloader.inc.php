 <!--*******************
        Preloader start
    ********************-->
    <div id="preloader">
        <div style="color: #10d596 !important;" class="sk-three-bounce">
            <div style="color: #10d596 !important; background-color:#10d596 !important" class="sk-child sk-bounce1"></div>
            <div style="color: #10d596 !important; background-color:#10d596 !important" class="sk-child sk-bounce2"></div>
            <div style="color: #10d596 !important; background-color:#10d596 !important" class="sk-child sk-bounce3"></div>
        </div>
    </div>
    <script>
    (function(){
      function adilKill(){
        var p = document.getElementById('preloader');
        var m = document.getElementById('main-wrapper');
        if(p){ p.setAttribute('style','display:none!important;visibility:hidden!important;opacity:0!important;');
               if(p.parentNode){ p.parentNode.removeChild(p); } }
        if(m){ m.classList.add('show'); m.setAttribute('style','display:block!important;'); }
      }
      setTimeout(adilKill, 800);
      setTimeout(adilKill, 2500);
      document.addEventListener('DOMContentLoaded', function(){ setTimeout(adilKill, 400); });
    })();
    </script>
    <!--*******************
        Preloader end
    ********************-->