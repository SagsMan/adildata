<style type="text/css">
.mini_scroll_menu{
  overflow: auto;
  white-space: nowrap;
}

.mini_scroll_menu a {
  display: inline-block;
  color: rgb(16, 213, 150);
  text-align: center;
  padding: 14px;
  text-decoration: none;
  font-size: 14px;
}

.mini_scroll_menu a:hover {
     border-bottom: solid rgb(16, 213, 150)
}
</style>



<div class="mini_scroll_menu nav-scroller bg-body shadow-sm">
 <a style="color: #10d596" class="nav-link " aria-current="page" href="verifications" style="<?= $URL_NAME == 'verifications' ? 'border-bottom: solid rgb(16, 213, 150)' : '' ?>">Verifications</a>
 <a style="color: #10d596" class="nav-link" href="cheap-data" style="<?= $URL_NAME == 'cheap-data' ? 'border-bottom: solid rgb(16, 213, 150)' : '' ?>" >Buy Data</a>
  <a style="color: #10d596" class="nav-link" href="credit-wallet" style="<?= $URL_NAME == 'credit-wallet' ? 'border-bottom: solid rgb(16, 213, 150)' : '' ?>" >Recharge Wallet</a>
    <a style="color: #10d596" class="nav-link" href="#" onclick="copyToClipboard('#p1')">Refer Friends And Earn</a>
    <a style="color: #10d596" class="nav-link" href="my-trans-history" style="<?= $URL_NAME == 'my-trans-history' ? 'border-bottom: solid rgb(16, 213, 150)' : '' ?>" >
      Transaction
      <span class="badge bg-danger text-white rounded-pill align-text-bottom" style="padding: 2px;"><?= $AdminTask->Get_User_Payment_History($Auth->email,$Auth->admin_role) != false ? count($AdminTask->Get_User_Payment_History($Auth->email,$Auth->admin_role)) : 0 ?></span>
    </a>
   
</div>


<p id="p1" style="display: none;">register?join_with_referal=<?= $Auth->referal_token ?></p>


