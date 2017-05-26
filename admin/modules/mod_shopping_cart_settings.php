<?php
/**
* @project ApPHP Shopping Cart
* @copyright (c) 2012 ApPHP
* @author ApPHP <info@apphp.com>
* @license http://www.gnu.org/licenses/
*/

// *** Make sure the file isn't accessed directly
defined('APPHP_EXEC') or die('Restricted Access');
//--------------------------------------------------------------------------

if($objLogin->IsLoggedInAs('owner','mainadmin') && Modules::IsModuleInstalled('shopping_cart')) {	

	$action = MicroGrid::GetParameter('action');
	$rid    = MicroGrid::GetParameter('rid');
	$settings_key    = MicroGrid::GetParameter('settings_key', false);
	$settings_value  = MicroGrid::GetParameter('settings_value', false);
	$mode   = 'view';
	$msg    = '';
	
	$objCartSettings = new ModulesSettings('shopping_cart');
	
	if($action=='add'){		
		$mode = 'add';
	}else if($action=='create'){
		if($objCartSettings->AddRecord()){
			$msg = draw_success_message(_ADDING_OPERATION_COMPLETED, false);
			$mode = 'view';
		}else{
			$msg = draw_important_message($objCartSettings->error, false);
			$mode = 'add';
		}
	}else if($action=='edit'){
		$mode = 'edit';
	}else if($action=='update'){
		$can_update = true;
		if($settings_key == 'is_active' && $settings_value == 'yes'){
			$can_update = $objCartSettings->CanActivateModule();			
		}
		if($can_update){
			if($objCartSettings->UpdateRecord($rid)){
				if($settings_key == 'shipping_fee' || $settings_key == 'vat_value'){
					$objCountries = new Countries();
					if($settings_key == 'vat_value') $objCountries->UpdateVatValue($settings_value);
				}
				$msg = draw_success_message(_UPDATING_OPERATION_COMPLETED, false);
				$mode = 'view';
			}else{
				$msg = draw_important_message($objCartSettings->error, false);
				$mode = 'edit';
			}
		}else{
			$msg = draw_important_message($objCartSettings->error, false);
			$mode = 'edit';			
		}		
	}else if($action=='delete'){
		if($objCartSettings->DeleteRecord($rid)){
			$msg = draw_success_message(_DELETING_OPERATION_COMPLETED, false);
		}else{
			$msg = draw_important_message($objCartSettings->error, false);
		}
		$mode = 'view';
	}else if($action=='details'){		
		$mode = 'details';		
	}else if($action=='cancel_add'){		
		$mode = 'view';		
	}else if($action=='cancel_edit'){				
		$mode = 'view';
	}
	
	// Start main content
	draw_title_bar(prepare_breadcrumbs(array(_MODULES=>'',_SHOPPING_CART_SETTINGS=>'',ucfirst($action)=>'')));
    echo '<br />';
	
	echo $msg;

	draw_content_start();
	if($mode == 'view'){		
		$objCartSettings->DrawViewMode();
		
		echo '<br /><br />
		<script type="text/javascript">
			var arrTabs = ["Online","PayPal","2CO","AuthorizeNet"];
		</script>
		<fieldset class="instructions">
		<legend>
			<b>INSTRUCTIONS:
				&nbsp;<a id="tabOnline" style="font-weight:bold" href="javascript:void(\'Online\')" onclick="javascript:appToggleTabs(\'Online\', arrTabs)">[ On-Line Order ]</a>
				&nbsp;<a id="tabPayPal" href="javascript:void(\'PayPal\')" onclick="javascript:appToggleTabs(\'PayPal\', arrTabs)">[ PayPal ]</a>
				&nbsp;<a id="tab2CO" href="javascript:void(\'2CO\')" onclick="javascript:appToggleTabs(\'2CO\', arrTabs)">[ 2CO ]</a>
				&nbsp;<a id="tabAuthorizeNet" href="javascript:void(\'AuthorizeNet\')" onclick="javascript:appToggleTabs(\'AuthorizeNet\', arrTabs)">[ Authorize.Net ]</a>
			</b>
		</legend>

		<div id="contentOnline" style="display:;padding:10px;">
			\'On-line Order\' is designed to allow the customer to place order on the site without any advance payment.<br />
			The administrator receives a notification about placing the order and can complete the order by himself.
			<br /><br />
			IMPORTANT:
			<ol>
				<li>Administrator can view orders '.prepare_permanent_link('index.php?admin=mod_shop_orders', 'here').'.</li>
				<li>
					To activate \'On-line Order\' Payments go back to <b>Administrator Panel -> Modules -> <a href="index.php?admin=mod_shopping_cart_settings">Shopping Cart Settings</a></b>, <br>
					where activate <b>\'On-line Order\' Payment Method</b> and collecting of <b>Credit Cards for \'On-line Orders\'</b> (if needed).<br /><br />
				</li>
			</ol>
		</div>
		
		<div id="contentPayPal" style="display:none;padding:10px;">
		To make PayPal processing system works on your site you have to perform the following steps:<br/><br/>
		<ol>
			<li>Create an account on PayPal: <a href="https://www.paypal.com" target="_new">https://www.paypal.com</a></li>
			<li>After account is created, log into and select from the top menu: <b>My Account -> Profile</b></li>
			<li>On <b>Profile Summary</b> page select from the <b>Selling Preferences</b> column: <b>Instant Payment Notification (IPN) Preferences</b>.  </li>
			<li>Turn \'On\' IPN by selecting <b>Receive IPN messages (Enabled)</b> and write into <b>Notification URL</b>: {site}/index.php?page=order_notify_paypal, where {site} is a full path to your site.<br /><br />
				<span class="code">
			    For example: <b>http://your_domain.com/index.php?page=order_notify_paypal</b> or <br /><b>http://your_domain.com/new_site/index.php?page=order_notify_paypal</b>
				</span>
			</li>
			<li>
				Then go to <b>My Account -> Profile -> Website Payment Preferences</b>, turn <b>Auto Return</b> \'On\' and write into <b>Return URL</b>: {site}/index.php?page=order_return, where {site} is a full path to your site.<br /><br />
				<span class="code">
			    For example: <b>http://your_domain.com/index.php?page=order_return</b><br />
				</span>
			</li>
			<li>
				Then go back to <b>Administrator Panel -> Modules -> <a href="index.php?admin=mod_shopping_cart_settings">Shopping Cart Settings</a></b>, <br>where activate <b>PayPal Payment Method</b> and enter <b>PayPal Email</b>.<br /><br />
			</li>
		</ol>
		</div>

		<div id="content2CO" style="display:none;padding:10px;">
		To make 2CO processing system works on your site you have to perform the following steps:<br/><br/>
		<ol>
			<li>Create an account on 2Checkout: <a href="http://www.2checkout.com" target="_new">http://www.2checkout.com</a></li>
			<li>After account is created, <a href="https://www.2checkout.com/2co/login" target="_new">log into</a> and select from the top menu: <b>Notifications -> Settings</b></li>
			<li>On <b>Instant Notification Settings</b> page enter into <b>Global URL</b> textbox: {site}/index.php?page=order_notify_2co, where {site} is a full path to your site. <br /><br />
				<span class="code">
			    For example: <b>http://your_domain.com/index.php?page=order_notify_2co</b> or <br /><b>http://your_domain.com/new_site/index.php?page=order_notify_2co</b>
				</span>	<br /><br />
				Then click on <b>Enable All Notifications</b> and <b>Save Settings</b> buttons.
			</li>			
			<li>
				Go to <b>Account -> Site Management</b>, set <b>Demo Setting</b> on \'Off\' and enter into <b>Approved URL</b> textbox: {site}/index.php?page=order_return, where {site} is a full path to your site.<br /><br />
				<span class="code">
			    For example: <b>http://your_domain.com/index.php?page=order_return</b> or <br /><b>http://your_domain.com/new_site/index.php?page=order_return</b>
				</span><br />
			</li>
			<li>
				Then go back to <b>Administrator Panel -> Modules -> <a href="index.php?admin=mod_shopping_cart_settings">Shopping Cart Settings</a></b>, <br>where activate <b>2CO Payment Method</b> and enter <b>2CO Vendor ID</b><br /><br />
			</li>
		</ol>
		</div>

		<div id="contentAuthorizeNet" style="display:none;padding:10px;">
		To make Authorize.Net processing system works on your site you have to perform the following steps:<br/><br/>
		<ol>
			<li>Create an account on Authorize.Net: <a href="http://www.authorize.net/solutions/merchantsolutions/" target="_new">http://www.authorize.net</a><br /></li>
			<li>After account is created, <a href="https://account.authorize.net/" target="_new">log into</a> and obtain <b>API Login ID</b> and <b>Transaction Key</b>. Find here how to do this: <a href="http://developer.authorize.net/faqs/" target="_new">Authorize.Net FAQ</a><br /></li>
			<li>
				Then go back to <b>Administrator Panel -> Modules -> <a href="index.php?admin=mod_shopping_cart_settings">Shopping Cart Settings</a></b>, <br>where activate <b>Authorize.Net Payment Method</b>, enter <b>API Login ID</b> and <b>Transaction Key</b>.<br /><br />
			</li>
		</ol>
		</div>
		</fieldset>';
		
	}else if($mode == 'add'){		
		$objCartSettings->DrawAddMode();		
	}else if($mode == 'edit'){		
		$objCartSettings->DrawEditMode($rid);		
	}else if($mode == 'details'){ 
		$objCartSettings->DrawDetailsMode($rid);		
	}
	draw_content_end();

}else{
	draw_title_bar(_ADMIN);
	draw_important_message(_NOT_AUTHORIZED);
}
?>