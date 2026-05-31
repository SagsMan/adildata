 <!--**********************************
            Sidebar start
        ***********************************-->
 <div class="no-print deznav">
     <div class="deznav-scroll">
         <ul class="metismenu" id="menu">

             <li><a href="<?php echo SITE_URL . 'easyfinder/dashboard/' ?>" class="ai-icon" aria-expanded="false">
                     <i class="flaticon-381-networking"></i>
                     <span class="nav-text">Dashboard</span>
                 </a>
             </li>
             <?php if ($links = $site_settings->url_link($Auth->admin_role)) {

                    // Define the specific links you want to prioritize -- for multiple sorting
                    $specificLinks = ['verifications', 'mobile-topup'];

                    // Extract the specific links in the defined order
                    $prioritizedLinks = array_filter($links, fn($link) => in_array($link->link, $specificLinks));

                    // Sort the prioritized links to match the order in $specificLinks
                    usort($prioritizedLinks, function ($a, $b) use ($specificLinks) {
                        return array_search($a->link, $specificLinks) - array_search($b->link, $specificLinks);
                    });

                    // Get the remaining links
                    $remainingLinks = array_filter($links, fn($link) => !in_array($link->link, $specificLinks));

                    // Combine prioritized links with the remaining links
                    $links = array_merge($prioritizedLinks, $remainingLinks);

                    // Move the specific link to the beginning
                    // $specificLink = array_filter($links, fn($link) => $link->link === 'verifications');
                    // $remainingLinks = array_filter($links, fn($link) => $link->link !== 'verifications');

                    // Combine the specific link with the remaining links
                    // $links = array_merge($specificLink, $remainingLinks);
                    foreach ($links as $link) {
                        if ($link->has_sub == 0) { ?>


                         <li><a href="<?= $link->link ?>" class="ai-icon" aria-expanded="false">
                                 <i class="<?= $link->link_icon ?>"></i>
                                 <span class="nav-text"><?= $link->link_name ?></span>
                             </a>
                         </li>

                     <?php } else { ?>

                         <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                                 <i class="<?= $link->link_icon ?>"></i>
                                 <span class="nav-text"><?= $link->link_name ?></span>
                             </a>
                             <ul aria-expanded="false">
                                 <?php if (
                                        $sub_links = $site_settings->sub_url_link(
                                            $link->id,
                                            $Auth->admin_role
                                        )
                                    ) {
                                        foreach ($sub_links as $sub_link) { ?>
                                         <li><a href="../<?= $sub_link->sub_link ?>"><?= $sub_link->sub_link_name ?></a></li>
                                 <?php }
                                    } ?>
                             </ul>
                         </li>




             <?php }
                    }
                } ?>



             <li><a href="<?php echo SITE_URL . 'easyfinder/dashboard/referral' ?>" class="ai-icon" aria-expanded="false">
                     <i class="flaticon-381-star-1"></i>
                     <span class="nav-text">Referral & Earnings</span>
                 </a>
             </li>
             <li><a href="<?php echo SITE_URL . 'easyfinder/dashboard/verify-monnify' ?>" class="ai-icon" aria-expanded="false">
                     <i class="flaticon-381-check-circle"></i>
                     <span class="nav-text">Verify Payment</span>
                 </a>
             </li>
             <?php if (in_array(1, explode(',', $Auth->admin_role)) || in_array(2, explode(',', $Auth->admin_role))): ?>
             <?php
             $_pn = @mysqli_connect("localhost","adiliqgs_adildata","adildata2026","adiliqgs_adildata");
             $_pnc = 0;
             if ($_pn) {
                 $_pnr = mysqli_query($_pn, "SELECT COUNT(*) FROM admin_notifications_tbl WHERE status='pending'");
                 $_pnc = $_pnr ? intval(mysqli_fetch_row($_pnr)[0]) : 0;
                 mysqli_close($_pn);
             }
             ?>
             <li>
                 <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                     <i class="fa fa-bell" style="font-size:16px;"></i>
                     <span class="nav-text">Notifications
                         <?php if ($_pnc > 0): ?>
                         <span class="badge badge-warning ml-1" style="font-size:9px;vertical-align:middle;"><?= $_pnc ?></span>
                         <?php endif; ?>
                     </span>
                 </a>
                 <ul aria-expanded="false">
                     <li><a href="<?= SITE_URL ?>easyfinder/dashboard/admin-notifications"><i class="fa fa-tachometer mr-1"></i> Dashboard</a></li>
                     <li><a href="<?= SITE_URL ?>easyfinder/dashboard/admin-notification-create"><i class="fa fa-plus mr-1"></i> Create New</a></li>
                     <li><a href="<?= SITE_URL ?>easyfinder/dashboard/admin-notification-settings"><i class="fa fa-cog mr-1"></i> API Settings</a></li>
                 </ul>
             </li>
             <?php endif; ?>
             <?php if (in_array(1, explode(',', $Auth->admin_role ?? ''))): ?>
             <li><a href="<?php echo SITE_URL . 'easyfinder/dashboard/admin-monnify-users' ?>" class="ai-icon" aria-expanded="false">
                     <i class="flaticon-381-settings-2"></i>
                     <span class="nav-text">Monnify Manager</span>
                 </a>
             </li>
             <?php endif; ?>
             <li><a href="<?php echo SITE_URL . 'easyfinder/dashboard/referral' ?>" class="ai-icon" aria-expanded="false">
                     <i class="flaticon-381-star-1"></i>
                     <span class="nav-text">Referral & Earnings</span>
                 </a>
             </li>
             <li><a href="<?php echo SITE_URL . 'easyfinder/dashboard/verify-monnify' ?>" class="ai-icon" aria-expanded="false">
                     <i class="flaticon-381-check-circle"></i>
                     <span class="nav-text">Verify Payment</span>
                 </a>
             </li>
         </ul>

         <div class="add-menu-sidebar">
             <img src="images/icon1.png" alt="" />
             <p>Get Your Own Bill Payment Website</p>
             <a href="javascript:void(0);" class="btn btn-primary btn-block light">+ Create Now</a>
         </div>
         <div class="copyright">
             <p><strong>Azzeetech IT</strong> © 2021 All Rights Reserved</p>

         </div>
     </div>
 </div>
 <!--**********************************
            Sidebar end
        ***********************************-->