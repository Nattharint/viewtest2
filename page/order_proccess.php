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

		if($payment_type == 'paypal'){
			$title_desc = _PAYPAL_ORDER;
		}else if($payment_type == '2co'){
			$title_desc = _2CO_ORDER;
		}else if($payment_type == 'authorize'){
			$title_desc = _AUTHORIZE_NET_ORDER;
		}else{
			$title_desc = _ONLINE_ORDER;
		}
				
		draw_title_bar(prepare_breadcrumbs(array(_CHECKOUT=>'',$title_desc=>'')));		
		
		echo $order_proccess_text;		
		
	}else{
		draw_important_message(_NOT_AUTHORIZED);
	}	
}else{
    draw_important_message(_NOT_AUTHORIZED);
}
?>