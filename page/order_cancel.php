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
		$objCart=new ShoppingCart();
		
		draw_title_bar(prepare_breadcrumbs(array(_CHECKOUT=>'',_ORDER_CANCELED=>'')));
		
		draw_content_start();
			draw_message(_ORDER_WAS_CANCELED_MSG, true, true);
		draw_content_end();		
	}else{
		draw_important_message(_NOT_AUTHORIZED);
	}	
}else{
    draw_important_message(_NOT_AUTHORIZED);
}
?>