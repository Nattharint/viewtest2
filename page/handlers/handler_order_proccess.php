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

if(!$objLogin->IsLoggedIn()){
	$objSession->SetMessage('notice', _MUST_BE_LOGGED);
	header('location: index.php?customer=login');
	exit;
}else{

	$task = isset($_POST['task']) ? prepare_input($_POST['task']) : '';
	$order_proccess_text = '';
	$payment_type = '';

	$objCart = new ShoppingCart();
	
    // redirect if shopping cart is empty
	if($objCart->IsCartEmpty()){
        header('location: index.php?page=shopping_cart');
        echo '<p>if your browser doesn\'t support redirection please click <a href="index.php?page=shopping_cart">here</a>.</p>';        
        exit;		
	}

	// redirect if checkout params are empty
	if(empty($task)){
		header('location: index.php?page=checkout');
		exit;
	}

	if(Modules::IsModuleInstalled('shopping_cart')){
		if(ModulesSettings::Get('shopping_cart', 'is_active') == 'yes'){				
	
			$collect_credit_card = ModulesSettings::Get('shopping_cart', 'online_credit_card_required');	
	
			// handle order
			$payment_type = isset($_POST['payment_type']) ? prepare_input($_POST['payment_type']) : '';
			$additional_info = isset($_POST['additional_info']) ? prepare_input($_POST['additional_info']) : '';		
	
			$cc_params = array();
			$cc_params['cc_type'] 	       = isset($_POST['cc_type']) ? prepare_input($_POST['cc_type']) : '';
			$cc_params['cc_holder_name']   = isset($_POST['cc_holder_name']) ? prepare_input($_POST['cc_holder_name']) : '';
			$cc_params['cc_number'] 	   = isset($_POST['cc_number']) ? prepare_input($_POST['cc_number']) : '';
			$cc_params['cc_expires_month'] = isset($_POST['cc_expires_month']) ? prepare_input($_POST['cc_expires_month']) : '';
			$cc_params['cc_expires_year']  = isset($_POST['cc_expires_year']) ? prepare_input($_POST['cc_expires_year']) : '';
			$cc_params['cc_cvv_code']      = isset($_POST['cc_cvv_code']) ? prepare_input($_POST['cc_cvv_code']) : '';
			
			// test mode alert
			if(Modules::IsModuleInstalled('shopping_cart')){
				if(ModulesSettings::Get('shopping_cart', 'mode') == 'TEST MODE'){
					$order_proccess_text .= draw_message(_TEST_MODE_ALERT_SHORT, false, true);
				}
			}
	
			$objCart->AddAdditionalInfo($additional_info);			
			
			if($task == 'do_order'){
				$result = $objCart->DoOrder($payment_type);				
				if($result == true){
					$order_proccess_text .= draw_content_start(false);
					$order_proccess_text .= $objCart->DrawOrder($payment_type, false);
					$order_proccess_text .= draw_content_end(false);	
				}else{
					$order_proccess_text .= $objCart->error.'<br />';				
				}
			}else if($task == 'place_order'){
				$result = check_credit_card($cc_params);
				if($collect_credit_card == 'yes' && $result != ''){
					$order_proccess_text .= draw_important_message($result, false);
					$order_proccess_text .= draw_content_start(false);
					$order_proccess_text .= $objCart->DrawOrder($payment_type, false);
					$order_proccess_text .= draw_content_end(false);		
				}else{
					$objCart->PlaceOrder($cc_params);	
					$order_proccess_text .= $objCart->message.'<br />';
				}			
			}else{
				$order_proccess_text .= draw_important_message(_WRONG_PARAMETER_PASSED, false);
			}
		}
	}
	
}
?>