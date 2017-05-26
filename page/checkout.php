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

if(Modules::IsModuleInstalled('shopping_cart')){
	if(ModulesSettings::Get('shopping_cart', 'is_active') == 'yes'){		
		$objCart = new ShoppingCart();
				
		draw_title_bar(prepare_breadcrumbs(array(_CHECKOUT=>'',_ORDER_DETAILS=>'')));

		// test mode alert
		if(Modules::IsModuleInstalled('shopping_cart')){
			if(ModulesSettings::Get('shopping_cart', 'mode') == 'TEST MODE'){
				draw_message(_TEST_MODE_ALERT_SHORT, true, true);
			}
		}
		
		// discount campaign alert
		Campaigns::DrawCampaignBanner();
		
		if($objCart->IsCartEmpty()) draw_message(_CART_IS_EMPTY_ALERT, true, true);
		
		$objCart->ShowCheckout();
	}else{
		draw_important_message(_NOT_AUTHORIZED);
	}	
}else{
    draw_important_message(_NOT_AUTHORIZED);
}
?>