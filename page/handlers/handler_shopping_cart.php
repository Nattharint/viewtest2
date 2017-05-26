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
		$act    = isset($_REQUEST['act']) ? prepare_input($_REQUEST['act']) : '';
		$amount = isset($_POST['amount']) ? (int)$_POST['amount'] : '1';
		          if(!is_numeric($amount) || $amount < 0) $amount = '1';
		$submit_count  = isset($_POST['submit_count']) ? prepare_input($_POST['submit_count']) : '';
		$delivery_type = isset($_GET['delivery_type']) ? (int)$_GET['delivery_type'] : '';

		$objCart = new ShoppingCart();
		
		// save selected delivery type
		if($delivery_type != ''){
			$objCart->AddDelivery($delivery_type);
		}
		
		// add/remove product from the shopping cart
		if($act == 'add'){
			$objCart->AddToCart(Application::Get('product_id'), $amount);
		}else if($act == 'remove'){
			$objCart->RemoveFromCart(Application::Get('product_id'));
		}
		
		if($submit_count != ''){
			$newquan = isset($_POST['newquan']) ? prepare_input($_POST['newquan']) : array();
			foreach($newquan as $key => $value) {
				if(!$objCart->UpdateCart($key, $value)) break;
			}
		}			
	}
}
?>