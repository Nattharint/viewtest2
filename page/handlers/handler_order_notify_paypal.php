<?php
/**
* @project ApPHP Shopping Cart
* @copyright (c) 2012 ApPHP
* @author ApPHP <info@apphp.com>
* @license http://www.gnu.org/licenses/
*/

////////////////////////////////////////////////////////////////////////////////
// PayPal Order Notify
// Last modified: 01.06.2011
////////////////////////////////////////////////////////////////////////////////

// *** Make sure the file isn't accessed directly
defined('APPHP_EXEC') or die('Restricted Access');
//--------------------------------------------------------------------------

if(Modules::IsModuleInstalled('shopping_cart')){
	$mode = ModulesSettings::Get('shopping_cart', 'mode');

	if(ModulesSettings::Get('shopping_cart', 'is_active') == 'yes'){		

		//----------------------------------------------------------------------
		define('LOG_MODE', false);
		define('LOG_TO_FILE', false);
		define('LOG_ON_SCREEN', false);
		
		define('TEST_MODE', ($mode == 'TEST MODE') ? true : false);
		$log_data = '';
		$msg      = '';
		$nl       = "\n";

		// --- Get PayPal response
		$objPaymentIPN 		= new PaymentIPN($_REQUEST, 'paypal');
		$status 			= $objPaymentIPN->GetPaymentStatus();
		$order_number 		= $objPaymentIPN->GetParameter('custom');
	    $transaction_number = $objPaymentIPN->GetParameter('txn_id');
		$payer_status		= $objPaymentIPN->GetParameter('payer_status');
		$pp_payment_type    = $objPaymentIPN->GetParameter('payment_type');
		$total				= $objPaymentIPN->GetParameter('mc_gross');		
		
		// Payment Types   : 0 - Online Order, 1 - PayPal, 2 - 2CO, 3 - Authorize.Net	
		// Payment Methods : 0 - Payment Company Account, 1 - Credit Card, 2 - E-Check
		if($status == 'Completed'){
			if($payer_status == 'verified'){
				$payment_method = '0';
			}else{
				$payment_method = '1';
			}			
		}else{
			$payment_method = ($pp_payment_type == 'echeck') ? '2' : '0'; 
		}
		
		if(TEST_MODE){
			$status = 'Completed';
		}
				
		////////////////////////////////////////////////////////////////////////
		if(LOG_MODE){
			if(LOG_TO_FILE){
				$myFile = 'tmp/logs/payment_paypal.log';
				$fh = fopen($myFile, 'a') or die('can\'t open file');				
			}
	  
			$log_data .= $nl.$nl.'=== ['.date('Y-m-d H:i:s').'] ==================='.$nl;
			$log_data .= '<br />---------------<br />'.$nl;
			$log_data .= '<br />POST<br />'.$nl;
			foreach($_POST as $key=>$value) {
				$log_data .= $key.'='.$value.'<br />'.$nl;        
			}
			$log_data .= '<br />---------------<br />'.$nl;
			$log_data .= '<br />GET<br />'.$nl;
			foreach($_GET as $key=>$value) {
				$log_data .= $key.'='.$value.'<br />'.$nl;        
			}        
		}      
		////////////////////////////////////////////////////////////////////////  

		switch($status)    
		{
			// 1 order pending
			case 'Pending':
				$pending_reason = $objPaymentIPN->GetParameter('pending_reason');
				$msg = 'Pending Payment - '.$pending_reason;
				break;
			case 'Completed':
				// 2 order completed					
				$sql = 'SELECT id, order_number, currency, customer_id, is_admin_order, products_amount, order_price, shipping_fee, vat_fee, total_price 
						FROM '.TABLE_ORDERS.'
						WHERE order_number = \''.$order_number.'\' AND status = 0';
				$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
				if($result[1] > 0){
					write_log($sql);
					
					// check for possible problem or hack attack
					if($total <= 1 || abs($total - $result[0]['total_price']) > 1){
						$ip_address = (isset($_SERVER['HTTP_X_FORWARD_FOR']) && $_SERVER['HTTP_X_FORWARD_FOR']) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
						$message  = 'From IP: '.$ip_address.'<br />'.$nl;
						$message .= 'Status: '.$status.'<br />'.$nl;
						$message .= 'Possible Attempt of Hack Attack? <br />'.$nl;
						$message .= 'Please check this order: <br />'.$nl;
						$message .= 'Order Price: '.$result[0]['total_price'].' <br />'.$nl;
						$message .= 'Payment Processing Gross Price: '.$total.' <br />'.$nl;
						write_log($message);
						break;            
					}

					// update customer orders/products amount
					if($result[0]['is_admin_order'] == '0'){
						$sql = 'UPDATE '.TABLE_CUSTOMERS.' SET
									orders_count = orders_count + 1,
									products_count = products_count + '.(int)$result[0]['products_amount'].'
								WHERE id = '.(int)$result[0]['customer_id'];
						database_void_query($sql);
						write_log($sql);
					}
					
					$sql = 'UPDATE '.TABLE_ORDERS.' SET
								status = 2,
								transaction_number  = \''.$transaction_number.'\',
								payment_date = \''.date('Y-m-d H:i:s').'\',
								status_changed = \''.date('Y-m-d H:i:s').'\',
								payment_type = 1,
								payment_method = '.$payment_method.'
							WHERE order_number = \''.$order_number.'\'';
					if(database_void_query($sql)){
						// update units in stock
						Orders::UpdateUnitsInStock($order_number);
						
						$objCart = new ShoppingCart();
						// send email to customer
						if($objCart->SendOrderEmail($order_number, 'completed', $result[0]['customer_id'])){
							write_log($sql, _ORDER_PLACED_MSG);
							$objCart->EmptyCart();
						}else{
							write_log($sql, _ORDER_ERROR);
						}						
					}else{
						write_log($sql, mysql_error());
					}					
				}else{
					write_log($sql, 'Error: no records found. '.mysql_error());
				}				
				break;
			case 'Updated':
				// 3 updated already
				$msg = 'Thank you for your order!<br><br>';
				break;
			case 'Failed':
				// 4 this will only happen in case of echeck.
				$msg = 'Payment Failed';
				break;
			case 'Denied':
				// 5 denied payment by us
				$msg = 'Payment Denied';
				break;
			case 'Refunded':
				// 6 payment refunded by us
				$msg = 'Payment Refunded';			
				break;
			case 'Canceled':
				/* 7 reversal cancelled
				 mark the payment as dispute cancelled */
				$msg = 'Cancelled reversal';
				break;	
			default:
				// 0 order is not good
				$msg = 'Unknown Payment Status - please try again.';
				// . $objPaymentIPN->GetPaymentStatus();
				break;
		}

		////////////////////////////////////////////////////////////////////////
		if(LOG_MODE){
			$log_data .= '<br>'.$nl.$msg.'<br>'.$nl;    
			if(LOG_TO_FILE){
				fwrite($fh, strip_tags($log_data));
				fclose($fh);        				
			}
			if(LOG_ON_SCREEN){
				echo $log_data;
			}
		}
		////////////////////////////////////////////////////////////////////////

		if(TEST_MODE){
			header('location: index.php?page=order_return');
			exit;
		}
	}	
}

function write_log($sql, $msg = ''){
    global $log_data;
	$nl = "\n";
    if(LOG_MODE){
        $log_data .= '<br>'.$nl.$sql;
        if($msg != '') $log_data .= '<br>'.$nl.$msg;
    }    
}

?>