<?php

/**
 *	Class PaymentIPN
 *  -------------- 
 *  Description : encapsulates Payment IPN properties
 *	Written by  : ApPHP
 *	Version     : 1.0.2
 *  Updated	    : 01.10.2012
 *	Usage       : HotelSite, ShoppingCart 
 *
 *	PUBLIC:				  	STATIC:				 	PRIVATE:
 * 	------------------	  	---------------     	---------------
 *	__construct             DrawPaymentForm         
 *	__destruct              DrawOnlineForm  
 *  GetPaymentStatus        DrawPayPalPaymentForm  
 *  GetParameter            Draw2COPaymentForm
 *                          DrawAuthorizeNetPaymentForm
 *  
 *  1.0.2
 *  	- added 2co type
 *  	- added online type
 *  	-
 *  	-
 *  	-
 **/

class PaymentIPN {
	
	private $post_vars;
	private $response;
	private $timeout;

	private $error_email;
	private $pp_type;
	
	//==========================================================================
    // Class Constructor
	// 		@param $post_vars
	//==========================================================================
	function __construct($post_vars, $pp_type)
	{
		$this->post_vars = $post_vars;
		$this->pp_type = $pp_type;
		$this->timeout = 120;
	}

	//==========================================================================
    // Class Destructor
	//==========================================================================
    function __destruct()
	{
		// echo 'this object has been destroyed';
    }
	
	/**
	 *	Returns payment status
	 */
	public function GetPaymentStatus()
	{
		$payment_status = '';
		
		if($this->pp_type == 'paypal'){
			$payment_status = isset($this->post_vars['payment_status']) ? $this->post_vars['payment_status'] : '';
		}else if($this->pp_type == '2co'){
			$payment_status = isset($this->post_vars['invoice_status']) ? $this->post_vars['invoice_status'] : '';
		}else if($this->pp_type == 'authorize.net'){
			$payment_status = isset($this->post_vars['x_response_code']) ? $this->post_vars['x_response_code'] : '';
		}
		return $payment_status;
	}

	/**
	 *	Returns parameter
	 *		@param $param
	 */
	public function GetParameter($param)
	{
		$param_value = '';
		
		if($this->pp_type == 'paypal'){
			$param_value = isset($this->post_vars[$param]) ? $this->post_vars[$param] : '';
		}else if($this->pp_type == '2co'){
			$param_value = isset($this->post_vars[$param]) ? $this->post_vars[$param] : '';
		}else if($this->pp_type == 'authorize.net'){
			$param_value = isset($this->post_vars[$param]) ? $this->post_vars[$param] : '';
		}		
		return $param_value;
	}
	
	////////////////////////////////////////////////////////////////////////////
	/**
	 *	Draws Payment Form
	 *		@param $pp_type
	 *		@param $pp_params
	 *		@param $mode
	 *		@param $draw
	 */
	public static function DrawPaymentForm($pp_type, $pp_params = array(), $mode = 'real', $draw = true)
	{
		$output = '';
		if($pp_type == 'online'){
			$output = self::DrawOnlineForm($pp_params, $mode);
		}else if($pp_type == 'paypal'){
			$output = self::DrawPayPalPaymentForm($pp_params, $mode);
		}else if($pp_type == '2co'){
			$output = self::Draw2COPaymentForm($pp_params, $mode);
		}else if($pp_type == 'authorize.net'){
			$output = self::DrawAuthorizeNetPaymentForm($pp_params, $mode);
		}
		if($draw) echo $output;
		else return $output;		
	}
	
	/**
	 *	Draws Payment Form for Online
	 *		@param $pp_params
	 *		@param $mode
	 */
	public static function DrawOnlineForm($pp_params, $mode = 'real')
	{
		$nl = "\n";		
		$output  = $nl.'<form action="index.php?page=order_proccess" method="post">';
		$output .= $nl.draw_hidden_field('task', 'place_order', false);
		$output .= $nl.draw_hidden_field('payment_type', 'online', false);
		$output .= $nl.draw_hidden_field('additional_info', $pp_params['additional_info'], false);
		$output .= $nl.draw_token_field(false);
		
		$req_mark = ($pp_params['collect_credit_card'] == 'yes') ? '<span style="color:darkred">*</span>' : '';

		$output .= $nl.'<table border="0" width="100%" align="center">';
		if($pp_params['collect_credit_card'] == 'yes'){
			$cc_type     = $pp_params['cc_type'];
			$cc_holder_name = $pp_params['cc_holder_name'];
			$cc_number   = $pp_params['cc_number'];
			$cc_cvv_code = $pp_params['cc_cvv_code'];
			$cc_expires_month = $pp_params['cc_expires_month'];
			$cc_expires_year = $pp_params['cc_expires_year'];

			$output .= $nl.'<tr><td colspan="3"><h4>'._CREDIT_CARD.'</h4></td></tr>';
			$output .= $nl.'<tr>';
			$output .= $nl.'	<td width="20%">'._CREDIT_CARD_TYPE.' '.$req_mark.'</td>';
			$output .= $nl.'	<td width="2%"> : </td>';
			$output .= $nl.'	<td>';
			$output .= $nl.'		<select name="cc_type">';
			$output .= $nl.'		<option value="Visa" '.(($cc_type == 'Visa') ? 'selected="selected"' : '').'>Visa</option>';
			$output .= $nl.'		<option value="MasterCard" '.(($cc_type == 'MasterCard') ? 'selected="selected"' : '').'>MasterCard</option>';
			$output .= $nl.'		<option value="American Express" '.(($cc_type == 'American Express') ? 'selected="selected"' : '').'>American Express</option>';
			$output .= $nl.'		<option value="Discover" '.(($cc_type == 'Discover') ? 'selected="selected"' : '').'>Discover</option>';
			$output .= $nl.'		</select>';
			$output .= $nl.'	</td>';
			$output .= $nl.'</tr>';
			$output .= $nl.'<tr>';
			$output .= $nl.'	<td>'._CREDIT_CARD_HOLDER_NAME.' '.$req_mark.'</td>';
			$output .= $nl.'	<td> : </td>';
			$output .= $nl.'	<td><input type="text" name="cc_holder_name" size="20" maxlength="50" value="'.$cc_holder_name.'" autocomplete="off" /></td>';
			$output .= $nl.'</tr>';
			$output .= $nl.'<tr>';
			$output .= $nl.'	<td>'._CREDIT_CARD_NUMBER.' '.$req_mark.'</td>';
			$output .= $nl.'	<td> : </td>';
			$output .= $nl.'	<td><input type="text" name="cc_number" size="20" maxlength="20" value="'.$cc_number.'" autocomplete="off" /></td>';
			$output .= $nl.'</tr>';
			$output .= $nl.'<tr>';
			$output .= $nl.'	<td>'._CREDIT_CARD_EXPIRES.' '.$req_mark.'</td>';
			$output .= $nl.'	<td> : </td>';
			$output .= $nl.'	<td>';
			$output .= $nl.draw_months_select_box('cc_expires_month', $cc_expires_month, 'cc_month', false, false);
			$output .= $nl.draw_years_select_box('cc_expires_year', $cc_expires_year, 'cc_year', false);
			$output .= $nl.'	</td>';
			$output .= $nl.'</tr>';
			$output .= $nl.'<tr><td>'._CVV_CODE.' '.$req_mark.'</td>';
			$output .= $nl.'	<td> : </td>';
			$output .= $nl.'	<td><input type="text" name="cc_cvv_code" size="4" maxlength="4" value="'.$cc_cvv_code.'" autocomplete="off" /> &nbsp;<a href="javascript:void(0)" onclick="javascript:appOpenPopup(\'html/cvv_description.html\')">'._WHAT_IS_CVV.' [?]</a></td>';
			$output .= $nl.'</tr>';
			$output .= $nl.'<tr><td colspan="3" nowrap="nowrap" height="10px"></td></tr>';
		}

		$output .= $nl.'<tr>';
		$output .= $nl.'<td colspan="3">';
		$output .= $nl.'<input type="submit" class="form_button" value="'._PLACE_ORDER.'" name="btnSubmit" />';
		$output .= $nl.'&nbsp; - '._OR.' - &nbsp;';
		$output .= $nl.'<a href="javascript:void(0);" onclick="javascript:appGoTo(\'page=checkout\')">'._BUTTON_CANCEL.'</a>';
		$output .= $nl.'</td>';
		$output .= $nl.'</tr>';
		$output .= $nl.'</table>';							
		$output .= $nl.'</form>';
		
		return $output;		
	}	
	
	/**
	 *	Draws Payment Form for PayPal
	 *		@param $pp_params
	 *		@param $mode
	 */
	public static function DrawPayPalPaymentForm($pp_params, $mode = 'real')
	{
		$nl = "\n";
		if($mode == 'test'){
			$output  = $nl.'<form action="index.php?page=order_notify_paypal" method="post" name="payform">';
			$output .= $nl.draw_hidden_field('txn_id', 'TEST_'.get_random_string(8), false);
			$output .= $nl.draw_hidden_field('payer_status', 'verified', false);
			$output .= $nl.draw_hidden_field('mc_gross', round($pp_params['cart_total'], 2), false);
			$output .= $nl.draw_hidden_field('custom', $pp_params['order_number'], false);
			$output .= $nl.draw_token_field(false);
			
			$output .= $nl._PAYPAL_NOTICE.'<br /><br />';
			$output .= $nl.'<table width="99%" border="0">';
			$output .= $nl.'<tr>';
			$output .= $nl.'<td align="left">';
			$output .= $nl.'<input type="image" style="border:0px" src="images/ppc_icons/btn_pp_buynow.gif" title="'._BUY_NOW.'" value="Go To Payment" name="btnSubmit" />';
			$output .= $nl.'&nbsp; - '._OR.' - &nbsp;';
			$output .= $nl.'<a href="javascript:void(0);" onclick="javascript:appGoTo(\'page=checkout\')">'._BUTTON_CANCEL.'</a>';
			$output .= $nl.'</td>';
			$output .= $nl.'</tr>';
			$output .= $nl.'</table>';
			$output .= $nl.'</form>';
		}else{
			$output  = $nl.'<form action="https://www.paypal.com/cgi-bin/webscr" method="post" name="payform">';
			$output .= $nl.draw_hidden_field('business', $pp_params['api_login'], false);
	
			if($pp_params['paypal_form_type'] == 'multiple' && !$pp_params['discount_value']){
				$output .= $nl.draw_hidden_field('cmd', '_cart', false);
				$output .= $nl.draw_hidden_field('upload', '1', false);
				$output .= $pp_params['paypal_form_fields'];
				if($pp_params['vat_cost'] > 0){
					$pp_params['paypal_form_fields_count']++;
					$output .= $nl.draw_hidden_field('item_name_'.$pp_params['paypal_form_fields_count'], _VAT, false);
					$output .= $nl.draw_hidden_field('quantity_'.$pp_params['paypal_form_fields_count'], '1', false);
					$output .= $nl.draw_hidden_field('amount_'.$pp_params['paypal_form_fields_count'], number_format($pp_params['vat_cost'], '2', '.', ','), false);
				}									
				if($pp_params['shipping_cost'] > 0){
					$pp_params['paypal_form_fields_count']++;
					$output .= $nl.draw_hidden_field('item_name_'.$pp_params['paypal_form_fields_count'], _SHIPPING_FEE, false);
					$output .= $nl.draw_hidden_field('quantity_'.$pp_params['paypal_form_fields_count'], '1', false);
					$output .= $nl.draw_hidden_field('amount_'.$pp_params['paypal_form_fields_count'], number_format($pp_params['shipping_cost'], '2', '.', ','), false);
				}									
			}else{				
				$output .= $nl.draw_hidden_field('cmd', '_xclick', false);
				$output .= $nl.draw_hidden_field('item_name', 'Products Purchasing', false);
				$output .= $nl.draw_hidden_field('item_number', '002', false);
				$output .= $nl.draw_hidden_field('amount', round($pp_params['cart_total'], 2), false);
			}
			$output .= $nl.draw_hidden_field('custom', $pp_params['order_number'], false);
			$output .= $nl.draw_hidden_field('lc', 'US', false);
			$output .= $nl.draw_hidden_field('currency_code', $pp_params['currency_code'], false);
			$output .= $nl.draw_hidden_field('cn', '', false);
			$output .= $nl.draw_hidden_field('no_shipping', '1', false);
			$output .= $nl.draw_hidden_field('rm', '1', false);

			$output .= $nl.draw_hidden_field('address_override', '0', false);
			$output .= $nl.draw_hidden_field('address1', $pp_params['address1'], false);
			$output .= $nl.draw_hidden_field('address2', $pp_params['address2'], false);
			$output .= $nl.draw_hidden_field('city', $pp_params['city'], false);
			$output .= $nl.draw_hidden_field('zip', $pp_params['zip'], false);
			$output .= $nl.draw_hidden_field('country', $pp_params['country'], false);			
			$output .= $nl.draw_hidden_field('state', $pp_params['state'], false);
			$output .= $nl.draw_hidden_field('first_name', $pp_params['first_name'], false);
			$output .= $nl.draw_hidden_field('last_name', $pp_params['last_name'], false);
			$output .= $nl.draw_hidden_field('email', $pp_params['email'], false);

			$phone_parts = explode('-', $pp_params['phone']);
			if(isset($phone_parts[0])) $output .= $nl.draw_hidden_field('night_phone_a', $phone_parts[0], false);
			if(isset($phone_parts[1])) $output .= $nl.draw_hidden_field('night_phone_b', $phone_parts[1], false);
			if(isset($phone_parts[2])) $output .= $nl.draw_hidden_field('night_phone_c', $phone_parts[2], false);
	
			$output .= $nl.draw_hidden_field('notify', APPHP_BASE.$pp_params['notify'], false);
			$output .= $nl.draw_hidden_field('return', APPHP_BASE.$pp_params['return'], false);
			$output .= $nl.draw_hidden_field('cancel_return', APPHP_BASE.$pp_params['cancel_return'], false);
			$output .= $nl.draw_hidden_field('bn', 'PP-BuyNowBF', false);
			
			$output .= $nl._PAYPAL_NOTICE.'<br /><br />';
			$output .= $nl.'<input type="image" style="border:0px" src="images/ppc_icons/btn_pp_buynow.gif" title="'._BUY_NOW.'" value="Go To Payment" name="btnSubmit" />';
			$output .= $nl.'&nbsp; - '._OR.' - &nbsp;';
			$output .= $nl.'<a href="javascript:void(0);" onclick="javascript:appGoTo(\'page=checkout\')">'._BUTTON_CANCEL.'</a>';
			$output .= $nl.'</form>';			
		}		
		return $output;
	}
	
	/**
	 *	Draws Payment Form for 2CO
	 *		@param $pp_params
	 *		@param $mode
	 */
	public static function Draw2COPaymentForm($pp_params, $mode = 'real')
	{
		$nl = "\n";
		if($mode == 'test'){
			$output  = $nl.'<form action="index.php?page=order_notify_2co" method="post">';
			$output .= $nl.draw_hidden_field('order_number', 'TEST_'.get_random_string(8), false);
			$output .= $nl.draw_hidden_field('pay_method', '2CO', false);
			$output .= $nl.draw_hidden_field('total', round($pp_params['cart_total'], 2), false);
			$output .= $nl.draw_hidden_field('custom', $pp_params['order_number'], false);
			$output .= $nl.draw_token_field(false);
			$output .= $nl._2CO_NOTICE.'<br /><br />';
			$output .= $nl.'<table width="99%" border="0">';
			$output .= $nl.'<tr>';
			$output .= $nl.'<td>';
			$output .= $nl.'<input type="image" style="border:0px" src="images/ppc_icons/btn_2co_buynow.jpg" title="'._BUY_NOW.'" value="Go To Payment" name="btnSubmit" />';
			$output .= $nl.'&nbsp; - '._OR.' - &nbsp;';
			$output .= $nl.'<a href="javascript:void(0);" onclick="javascript:appGoTo(\'page=checkout\')">'._BUTTON_CANCEL.'</a>';
			$output .= $nl.'</td>';
			$output .= $nl.'</tr>';
			$output .= $nl.'</table>';
			$output .= $nl.'</form>';
		}else{
			$output  = $nl.'<form action="https://www.2checkout.com/checkout/purchase" method="post">';
			$output .= $nl.draw_hidden_field('sid', $pp_params['api_login'], false);
			$output .= $nl.draw_hidden_field('cart_order_id', 'Products Purchasing', false);
			$output .= $nl.draw_hidden_field('total', (round($pp_params['cart_total'] * Application::Get('currency_rate'), 2)), false);
			$output .= $nl.draw_hidden_field('x_Receipt_Link_URL', APPHP_BASE.$pp_params['notify'], false);
			$output .= $nl.draw_hidden_field('return_url', APPHP_BASE.$pp_params['return'], false);
			$output .= $nl.draw_hidden_field('tco_currency', Application::Get('currency_code'), false);
			$output .= $nl.draw_hidden_field('custom', $pp_params['order_number'], false);
			$output .= $nl._2CO_NOTICE.'<br /><br />';
			$output .= $nl.'<input type="image" style="border:0px" src="images/ppc_icons/btn_2co_buynow.jpg" title="'._BUY_NOW.'" value="Go To Payment" name="btnSubmit" />';
			$output .= $nl.'&nbsp; - '._OR.' - &nbsp;';
			$output .= $nl.'<a href="javascript:void(0);" onclick="javascript:appGoTo(\'page=checkout\')">'._BUTTON_CANCEL.'</a>';
			$output .= $nl.'</form>';
		}		
		return $output;
	}
	
	/**
	 *	Draws Payment Form for Authorize.Net
	 *		@param $pp_params
	 *		@param $mode
	 */
	public static function DrawAuthorizeNetPaymentForm($pp_params, $mode = 'real')
	{
		$nl = "\n";
		if($mode == 'test'){
			$output  = $nl.'<form action="index.php?page=order_notify_autorize_net" method="post">';
			$output .= $nl.draw_hidden_field('x_trans_id', 'TEST_'.get_random_string(8), false);
			$output .= $nl.draw_hidden_field('x_method', '1', false);
			$output .= $nl.draw_hidden_field('x_amount', round($pp_params['cart_total'], 2), false);
			$output .= $nl.draw_hidden_field('custom', $pp_params['order_number'], false);
			$output .= $nl.draw_token_field(false);
			$output .= $nl._AUTHORIZE_NET_NOTICE.'<br /><br />';
			$output .= $nl.'<table width="99%" border="0">';
			$output .= $nl.'<tr>';
			$output .= $nl.'<td>';
			$output .= $nl.'<input type="image" style="border:0px" src="images/ppc_icons/btn_authorize_buynow.gif" title="'._SUBMIT_PAYMENT.'" value="Go To Payment" name="btnSubmit" />';
			$output .= $nl.'&nbsp; - '._OR.' - &nbsp;';
			$output .= $nl.'<a href="javascript:void(0);" onclick="javascript:appGoTo(\'page=checkout\')">'._BUTTON_CANCEL.'</a>';
			$output .= $nl.'</td>';
			$output .= $nl.'</tr>';
			$output .= $nl.'</table>';
			$output .= $nl.'</form>';
		}else{
			// <!-- Create the HTML form containing necessary SIM post values -->
			// <!--  Additional fields can be added here as outlined in the SIM integration guide at: http://developer.authorize.net -->
			//$url = 'https://test.authorize.net/gateway/transact.dll';
			$url = 'https://secure.authorize.net/gateway/transact.dll';			
			
			$testMode   = 'false';
			// an invoice is generated using the date and time
			$invoice	= date('YmdHis');
			// a sequence number is randomly generated
			$sequence	= rand(1, 1000);
			// a timestamp is generated
			$timeStamp	= time();
			// The following lines generate the SIM fingerprint.  PHP versions 5.1.2 and
			// newer have the necessary hmac function built in.  For older versions, it
			// will try to use the mhash library.
			if( phpversion() >= '5.1.2' ){
				$fingerprint = hash_hmac('md5', $pp_params['api_login'] . '^' . $sequence . '^' . $timeStamp . '^' . round($pp_params['cart_total'], 2) . '^', $pp_params['transaction_key']);
			}else{
				$fingerprint = bin2hex(mhash(MHASH_MD5, $pp_params['api_login'] . '^' . $sequence . '^' . $timeStamp . '^' . round($pp_params['cart_total'], 2) . '^', $pp_params['transaction_key']));
			}

			$output  = $nl.'<form method="post" action="'.$url.'">';
			$output .= $nl.draw_hidden_field('x_login', $pp_params['api_login'], false);
			$output .= $nl.draw_hidden_field('x_amount', round($pp_params['cart_total'], 2), false);
			$output .= $nl.draw_hidden_field('x_description', 'Products Purchasing', false);
			$output .= $nl.draw_hidden_field('x_invoice_num', $invoice, false);
			$output .= $nl.draw_hidden_field('x_fp_sequence', $sequence, false);
			$output .= $nl.draw_hidden_field('x_fp_timestamp', $timeStamp, false);
			$output .= $nl.draw_hidden_field('x_fp_hash', $fingerprint, false);
			$output .= $nl.draw_hidden_field('x_test_request', $testMode, false);
			$output .= $nl.draw_hidden_field('x_relay_response', 'TRUE', false);
			$output .= $nl.draw_hidden_field('x_relay_url', APPHP_BASE.$pp_params['notify'], false);
			$output .= $nl.draw_hidden_field('x_show_form', 'PAYMENT_FORM', false);
			$output .= $nl.draw_hidden_field('custom', $pp_params['order_number'], false);

			$output .= $nl.draw_hidden_field('x_first_name', $pp_params['first_name'], false);
			$output .= $nl.draw_hidden_field('x_last_name', $pp_params['last_name'], false);
			$output .= $nl.draw_hidden_field('x_company', $pp_params['company'], false);
			$output .= $nl.draw_hidden_field('x_phone', $pp_params['phone'], false);
			$output .= $nl.draw_hidden_field('x_fax', $pp_params['fax'], false);
			$output .= $nl.draw_hidden_field('x_email', $pp_params['email'], false);
			$output .= $nl.draw_hidden_field('x_address', $pp_params['address1'].' '.$pp_params['address2'], false);
			$output .= $nl.draw_hidden_field('x_city', $pp_params['city'], false);
			$output .= $nl.draw_hidden_field('x_zip', $pp_params['zip'], false);
			$output .= $nl.draw_hidden_field('x_country', $pp_params['country'], false);
			$output .= $nl.draw_hidden_field('x_state', $pp_params['state'], false);

			$output .= $nl._AUTHORIZE_NET_NOTICE.'<br /><br />';
			$output .= $nl.'<input type="image" style="border:0px" src="images/ppc_icons/btn_authorize_buynow.gif" title="'._SUBMIT_PAYMENT.'" value="Go To Payment" name="btnSubmit" />';
			$output .= $nl.'&nbsp; - '._OR.' - &nbsp;';
			$output .= $nl.'<a href="javascript:void(0);" onclick="javascript:appGoTo(\'page=checkout\')">'._BUTTON_CANCEL.'</a>';
			$output .= $nl.'</form>';
			
		}
		return $output;
	}
	
} 

?>