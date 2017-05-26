<?php

/***
 *	Class Shopping Cart
 *  -------------- 
 *  Description : encapsulates Shopping Cart properties
 *  Updated	    : 22.09.2011
 *	Written by  : ApPHP
 *	
 *	PUBLIC:				  	STATIC:				 	PRIVATE:
 * 	------------------	  	---------------     	---------------
 *	__construct			    GetDeliveryInfo         CalculateTotalSum 
 *	__destruct              ReCalcualteDelivery     GetOrderProductsList
 *	ShowCart                                        GetVatPercentDecimalPoints 
 *	ShowCheckout                                    GenerateOrderNumber 
 *	UpdateCart
 *	AddToCart
 *	AddDelivery
 *	RemoveFromCart
 *	EmptyCart
 *	GetCartCount
 *	GetCartSum
 *	DoOrder
 *	PlaceOrder
 *	DrawOrder
 *	SendOrderEmail
 *	IsCartEmpty
 *	AddAdditionalInfo
 *	RefreshCartItems
 *	RefreshShippingCost
 *	
 **/

class ShoppingCart {

	public static $arrDelivery;
	private $cartItems;
	public $message;
	
	private $arrCart;
	private $maxAmount;
	private $currentCustomerID;
	private $vatPercent;
	private $discountPercent;
	private $discountCampaignID;
	private $shippingCost;
	private $additionalInfo;
	private $currencyFormat;
	private $paypal_form_type;
	private $paypal_form_fields;
	private $paypal_form_fields_count;

	//==========================================================================
    // Class Constructor
	//==========================================================================
	function __construct()
	{
		global $objLogin;
	    $secure_word = (defined('INSTALLATION_KEY') && INSTALLATION_KEY != '') ? INSTALLATION_KEY : 'SECWRD_';
		$this->arrCart = &$_SESSION[$secure_word]['shopping_cart'];
		self::$arrDelivery = &$_SESSION[$secure_word]['delivery_info'];
		$this->additionalInfo = &$_SESSION[$secure_word]['additional_info'];
		$this->cartItems = 0;
		$this->currencyFormat = get_currency_format();		  
		$this->paypal_form_type = 'multiple'; // single | multiple
		$this->paypal_form_fields = ''; 

		if(!$objLogin->IsLoggedIn()){
			$this->currentCustomerID = '0';
		}else{
			$this->currentCustomerID = $objLogin->GetLoggedID();
		}

		// prepare discount info
		$campaign_info = Campaigns::GetCampaignInfo();
		if($campaign_info['id'] != ''){
			$this->discountCampaignID = $campaign_info['id'];
			$this->discountPercent = $campaign_info['discount_percent'];			
		}else{
			$this->discountCampaignID = '';
			$this->discountPercent = '0';
		}

		// prepare VAT percent
		$sql='SELECT
				c.*,
				cntry.name as country_name,
				cntry.vat_value
		  	  FROM '.TABLE_CUSTOMERS.' c
				INNER JOIN '.TABLE_COUNTRIES.' cntry ON c.b_country = cntry.abbrv
			  WHERE
				cntry.is_active = 1 AND 
			    c.id = '.(int)$this->currentCustomerID;
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[1] > 0){
			$this->vatPercent = isset($result[0]['vat_value']) ? $result[0]['vat_value'] : '0';
		}else{
			$this->vatPercent = ModulesSettings::Get('shopping_cart', 'vat_value');
		}

		if(count($this->arrCart) > 0){
			$paypal_form_fields_count = 0;
			foreach($this->arrCart as $key => $val){
				//echo $val['quantity'];
				$this->cartItems += (isset($val['quantity']) ? $val['quantity'] : 0);
				if($this->paypal_form_type == 'multiple'){
					$this->paypal_form_fields_count++;
					$this->paypal_form_fields .=
						draw_hidden_field('item_name_'.$this->paypal_form_fields_count, _PRODUCT.': '.$val['product_name'], false).
						draw_hidden_field('quantity_'.$this->paypal_form_fields_count, $val['quantity'], false).
						draw_hidden_field('amount_'.$this->paypal_form_fields_count, number_format((($val['price'] * Application::Get('currency_rate'))), '2', '.', ','), false);
				}				
			}
		}
		
		// prepare shipping cost
		if(!isset(self::$arrDelivery) || empty(self::$arrDelivery)) self::$arrDelivery = Deliveries::GetDefaultDelivery();
		$this->shippingCost = $this->cartItems * (isset(self::$arrDelivery['price']) ? self::$arrDelivery['price'] * Application::Get('currency_rate') : '0');

		$this->message = '';
		$this->maxAmount = 9999;		
	}

	//==========================================================================
    // Class Destructor
	//==========================================================================
    function __destruct()
	{
		// echo 'this object has been destroyed';
    }

    /**
	 * Draws Shopping Cart on the screen
	 *
	 */	
	public function ShowCart()
	{
		global $objLogin;
		$output = '';
		$output_header = '';
		$output_middle = '';
		$output_footer = '';

		if(count($this->arrCart) > 0){
			$output_header = '<form action="index.php?page=shopping_cart" method="post">
			'.draw_token_field(false).'			
			<table width="99%" align="center" border="0" cellspacing="0" cellpadding="3">
			<tr>
				<th class="shopping_cart_th" width="25px">&nbsp;</th>
				<th class="shopping_cart_th" width="40px" align="center"><strong>'._QTY.'</strong></th>
				<th class="shopping_cart_th" width="16px">&nbsp;</th>
				<th class="shopping_cart_th" width="30px">&nbsp;</th>
				<th class="shopping_cart_th" width="40%" align="'.Application::Get('defined_left').'"><strong>'._PRODUCT.'</strong></th>
				<th class="shopping_cart_th" width="" align="'.Application::Get('defined_right').'"><strong>'._PRICE.'</strong></th>
				<th class="shopping_cart_th" width="80px" align="'.Application::Get('defined_right').'"><strong>'._AMOUNT.'</strong></th>
			</tr>';

			$order_price = 0;
			foreach($this->arrCart as $key => $val){
				$quantity = isset($val['quantity']) ? $val['quantity'] : 0;
				$sql='SELECT
							p.*,
							pd.name
						FROM '.TABLE_PRODUCTS.' p
							INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON p.id = pd.product_id
						WHERE
							p.id = '.(int)$key.' AND
							pd.language_id = \''.Application::Get('lang').'\'';
							
				$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
				if($result[1] > 0){
					$icon_file = ($result[0]['icon'] != '') ? $result[0]['icon'] : 'no_image.png';
					$output_middle .= '<tr>
							<td><a href="index.php?page=shopping_cart&act=remove&prodid='.$key.'"><img src="images/shopping_cart/delete.gif" width="16" height="16" border="0" title="'._REMOVE_ITEM_FROM_CART.'" alt="" /></a></td>
							<td>
								<input class="newquan" name="newquan['.$key.']" type="text" id="newquan_'.$key.'" value="'.$quantity.'" maxlength="4">
							</td>
							<td>
								<img class="arrow_plus" onclick="appPlusMinus(\'newquan_'.$key.'\',\'+\')" src="images/up.png" title="+">
								<img class="arrow_minus" onclick="appPlusMinus(\'newquan_'.$key.'\',\'-\')" src="images/down.png" title="-">							
							</td>
							<td align="center"><img src="images/products/'.$icon_file.'" alt="" width="22px" height="22px" /></td>							
							<td>'.prepare_link('product', 'prodid', $result[0]['id'], $result[0]['name'], $result[0]['name'], '', _CLICK_TO_SEE_DESCR).'</td>							
							<td align="right">'.Currencies::PriceFormat($result[0]['price'] * Application::Get('currency_rate'), '', '', $this->currencyFormat).'</td>
							<td align="right">'.Currencies::PriceFormat(($result[0]['price'] * $quantity) * Application::Get('currency_rate'), '', '', $this->currencyFormat).'</td>
						</tr>';
					$order_price += (($result[0]['price'] * $quantity) * Application::Get('currency_rate'));					
				}
			}

			$order_price_before_discount = $order_price;
			
			$discount_value = ($order_price * ($this->discountPercent / 100));
			$order_price -= $discount_value;

			$vat_cost  = ($order_price * ($this->vatPercent / 100));
			$cart_total = ($order_price + $vat_cost + $this->shippingCost);

			$output_footer .= '<tr><td>&nbsp;</td><td colspan="6"></td></tr>';
			$output_footer .= '<tr>
				<td colspan="5"><input type="submit" class="form_button" name="submit_count" value="'._BUTTON_UPDATE.'"></td>
				<td><span style="color:#000000">'._SUBTOTAL.':</span></td>
				<td align="right"><b>'.Currencies::PriceFormat($order_price_before_discount, '', '', $this->currencyFormat).'</b></td>
			</tr>';

			if($this->discountCampaignID != ''){
			    $output_footer .= '<tr>
						<td colspan="5"></td>
						<td><span style="color:#000000">'._DISCOUNT.': ('.$this->discountPercent.'%)</span></td>
						<td align="right"><b><span style="color:#a60000">- '.Currencies::PriceFormat($discount_value, '', '', $this->currencyFormat).'</span></b></td>
					</tr>';				
			}
			
			$output_footer .= '<tr>
				<td colspan="5"></td>
				<td><span style="color:#000000">'._VAT.': ('.Currencies::PriceFormat($this->vatPercent, '%', 'right', $this->currencyFormat, $this->GetVatPercentDecimalPoints($this->vatPercent)).')</span></td>
				<td align="right"><b>'.Currencies::PriceFormat($vat_cost, '', '', $this->currencyFormat).'</b></td>
			</tr>			
			<tr>
				<td colspan="5"></td>
				<td>'.Deliveries::DrawDeliveryTypesSelectBox($this->cartItems, false).'</td>
				<td align="right"><b>'.Currencies::PriceFormat($this->shippingCost, '', '', $this->currencyFormat).'</b></td>
			</tr>';
			
			$output_footer .= '<tr style="color:#000000;">
					<td colspan="5"></td>
					<td class="shopping_cart_th border_'.Application::Get('defined_left').'"><b>'._TOTAL.':</b></td>
					<td class="shopping_cart_th border_'.Application::Get('defined_right').'" align="right"><b>'.Currencies::PriceFormat($cart_total, '', '', $this->currencyFormat).'</b></td>
				</tr>
				<tr>
					<td colspan="5"></td>
					<td colspan="2" align="right"><br/>
						<a style="font-size:14px;" href="index.php?page='.(($objLogin->IsLoggedInAsAdmin()) ? 'category' : 'home').'">'._CONTINUE_SHOPPING.'</a>&nbsp;|&nbsp;<a style="font-size:14px;" href="index.php?page=checkout">'._CHECKOUT.'</a>
					</td>
				</tr>
				</table>
				</form>';				
			
			if($order_price){
				$output = $output_header.$output_middle.$output_footer;
				draw_content_start();
				echo $output;
				draw_content_end();	
			}else{
				draw_message(_CART_IS_EMPTY_ALERT, true, true);
			}			
		}else{
			/// draw_message(_CART_IS_EMPTY_ALERT, true, true);
		}
	}

    /**
	 * Draws Checkout form
	 */	
	public function ShowCheckout()
	{			
		$output = '';
		$output_header = '';
		$output_middle = '';
		$output_footer = '';
		
		$default_payment_system = ModulesSettings::Get('shopping_cart', 'default_payment_system');
		$payment_type_online    = ModulesSettings::Get('shopping_cart', 'payment_type_online');
		$payment_type_paypal    = ModulesSettings::Get('shopping_cart', 'payment_type_paypal');
		$payment_type_2co       = ModulesSettings::Get('shopping_cart', 'payment_type_2co');
		$payment_type_authorize = ModulesSettings::Get('shopping_cart', 'payment_type_authorize');
		$payment_type_cnt	    = ($payment_type_online === 'yes')+($payment_type_paypal === 'yes')+($payment_type_2co === 'yes')+($payment_type_authorize === 'yes');

		if($this->cartItems > 0){
			
			$order_price = 0;
			$output_header = '<form action="index.php?page=order_proccess" method="post">
			'.draw_hidden_field('task', 'do_order', false).'			
			'.draw_token_field(false).'			
			<table width="99%" align="center" border="0" cellspacing="0" cellpadding="4">
			<tr>
				<td class="shopping_cart_th border_'.Application::Get('defined_left').'"><b>'._PRODUCT.'</b></td>
				<td class="shopping_cart_th" width="70px" align="center"><b>'._QTY.'</b></td>
				<td class="shopping_cart_th" width="90px" align="right"><b>'._PRICE.'</b></td>
				<td class="shopping_cart_th border_'.Application::Get('defined_right').'" width="100px" align="right"><b>'._AMOUNT.'</b></td>				
			</tr>';
			
			foreach($this->arrCart as $key => $val){
				$quantity = isset($val['quantity']) ? $val['quantity'] : 0;
				$sql='SELECT
							p.*,
							pd.name
						FROM '.TABLE_PRODUCTS.' p
							INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON p.id = pd.product_id
						WHERE
							p.id = \''.$key.'\' AND
							pd.language_id = \''.Application::Get('lang').'\'';

				$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
				if($result[1] > 0){
					$order_price += (($result[0]['price'] * $quantity) * Application::Get('currency_rate'));
					$output_middle .= '<tr>
							<td>'.$result[0]['name'].'</td>
							<td align="center">'.$quantity.'</td>
							<td align="right">'.Currencies::PriceFormat($result[0]['price'] * Application::Get('currency_rate'), '', '', $this->currencyFormat).'</td>
							<td align="right">'.Currencies::PriceFormat(($result[0]['price'] * $quantity) * Application::Get('currency_rate'), '', '', $this->currencyFormat).'</td>
						</tr>';
				}				
			}

			$order_price_before_discount = $order_price;
			
			$discount_value = ($order_price * ($this->discountPercent / 100));
			$order_price -= $discount_value;

			$vat_cost  = ($order_price * ($this->vatPercent / 100));
			$cart_total = ($order_price + $vat_cost + $this->shippingCost);
			
			$output_footer .= '<tr><td colspan="4" style="border-top:0px solid #dddddd;">&nbsp;</td></tr>';			
			$output_footer .= '<tr>
				<td></td>
				<td colspan="2">'._ITEMS.':</td>
				<td align="right"><b>'.$this->cartItems.'&nbsp;'._ITEMS_LC.'</b></td>
			</tr>	
			<tr>
				<td></td>			
				<td colspan="2">'._SUBTOTAL.':</td>
				<td align="right"><b>'.Currencies::PriceFormat($order_price_before_discount, '', '', $this->currencyFormat).'</b></td>
			</tr>';
			
			if($this->discountCampaignID != ''){
			    $output_footer .= '<tr>
						<td></td>
						<td colspan="2">'._DISCOUNT.': ('.$this->discountPercent.'%)</td>
						<td align="right"><b><span style="color:#a60000">- '.Currencies::PriceFormat($discount_value, '', '', $this->currencyFormat).'</span></b></td>
					</tr>';				
			}
			
			$output_footer .= '<tr>
				<td></td>
				<td colspan="2">'._VAT.': ('.Currencies::PriceFormat($this->vatPercent, '%', 'right', $this->currencyFormat, $this->GetVatPercentDecimalPoints($this->vatPercent)).')</td>
				<td align="right"><b>'.Currencies::PriceFormat($vat_cost, '', '', $this->currencyFormat).'</b></td>
			</tr>			
			<tr>
				<td></td>			
				<td colspan="2">'.(isset(self::$arrDelivery['name']) ? self::$arrDelivery['name'] : '--').':</td>
				<td align="right"><b>'.Currencies::PriceFormat($this->shippingCost, '', '', $this->currencyFormat).'</b></td>
			</tr>';
			$output_footer .= '<tr>
					<td></td>
					<td class="shopping_cart_th border_'.Application::Get('defined_left').'" colspan="2" valign="middle"><b>'._TOTAL.':</b></td>
					<td class="shopping_cart_th border_'.Application::Get('defined_right').'" valign="middle" align="right" style="color:#005600;"><b>'.Currencies::PriceFormat($cart_total, '', '', $this->currencyFormat).'</b></td>
				</tr>';			
			$output_footer .= '<tr>
					<td colspan="4">
						<h4 style="cursor:pointer;" onclick="appToggleElement(\'additional_info\')">'._ADDITIONAL_INFO.' +</h4>
						<textarea name="additional_info" id="additional_info" maxlength="1024" style="display:'.(($this->additionalInfo != '') ? '' : 'none').';width:100%;height:75px">'.$this->additionalInfo.'</textarea>
					</td>
				</tr>';
			$output_footer .= '<tr><td colspan="4" style="border-top:0px solid #dddddd;">&nbsp;</td></tr>';
			if($payment_type_cnt >= 1){
				$output_footer .= '<tr>
					<td colspan="3" align="right">'._PAYMENT.': 
						<select name="payment_type">';							
							if($payment_type_online == 'yes') $output_footer .= '<option value="online" '.(($default_payment_system == 'online') ? 'selected="selected"' : '').'>'._ONLINE_ORDER.'</option>';
							if($payment_type_paypal == 'yes') $output_footer .= '<option value="paypal" '.(($default_payment_system == 'paypal') ? 'selected="selected"' : '').'>'._PAYPAL.'</option>';
							if($payment_type_2co == 'yes') $output_footer .= '<option value="2co" '.(($default_payment_system == '2co') ? 'selected="selected"' : '').'>2CO</option>';
							if($payment_type_authorize == 'yes') $output_footer .= '<option value="authorize.net" '.(($default_payment_system == 'authorize.net') ? 'selected="selected"' : '').'>Authorize.Net</option>';
						$output_footer .= '</select>
					</td>
					<td align="right">
						<input type="submit" class="form_button" value="'._ORDER.'" />
					</td>
				</tr>';
			}else{
				$output_footer .= '<tr><td colspan="4">';
				$output_footer .= draw_important_message(_NO_PAYMENT_TYPES_ALERT, false);
				$output_footer .= '</td></tr>';
			}				
			$output_footer .= '</table>';
			$output_footer .= '</form>';
			
			if($order_price){
				$output = $output_header.$output_middle.$output_footer;
				draw_content_start();
				echo $output;
				draw_content_end();	
			}else{
				draw_message(_CART_IS_EMPTY_ALERT, true, true);
			}			
		}else{
			/// draw_message(_CART_IS_EMPTY_ALERT, true, true);
		}
	}	
	
	/**
	 *	Updates Shopping Cart
	 *		@param $product_id
	 *		@param $amount
	 */
	public function UpdateCart($product_id, $amount = '1')
	{
		$amount = ($amount > $this->maxAmount) ? $this->maxAmount : $amount;
		if(validate_is_integer($amount) && $amount > 0){
			if((int)$product_id > 0){

				$sql='SELECT
						p.id,
						p.units,
						pd.name as product_name
					FROM '.TABLE_PRODUCTS.' p
						LEFT OUTER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON p.id = pd.product_id
					WHERE
						p.id = \''.(int)$product_id.'\' AND
						p.units > 0 AND 
						pd.language_id = \''.Application::Get('lang').'\'';
				$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
				if($result[1] == 1){
					if(isset($this->arrCart[$product_id]['quantity']) && $this->arrCart[$product_id]['quantity'] > 0){
						if($amount > 0){
							$this->arrCart[$product_id]['quantity'] = (int)$amount;
							if($this->arrCart[$product_id]['quantity'] > $this->maxAmount){
								$this->arrCart[$product_id]['quantity'] = $this->maxAmount;
								$this->message = draw_important_message(str_replace(array('_PRODUCT_', '_UNITS_'), array($result[0]['product_name'], $this->maxAmount), _PRODUCT_MAXIMUM_UNITS_REACHED), false);
								return false;
							}else if($this->arrCart[$product_id]['quantity'] > $result[0]['units']){
								$this->arrCart[$product_id]['quantity'] = $result[0]['units'];
								$this->message = draw_important_message(str_replace(array('_PRODUCT_', '_UNITS_'), array($result[0]['product_name'], $result[0]['units']), _PRODUCT_MAXIMUM_UNITS_REACHED), false);
								return false;
							}else{
								$this->message = draw_success_message(_CART_WAS_UPDATED, false);	
							}
						}else if($amount == 0){
							unset($this->arrCart[$product_id]);
							$this->message = draw_message(_PRODUCT_WAS_REMOVED, false);							
						}

						$this->RefreshCartItems();
						$this->RefreshShippingCost();
						return true;
					}else{
						$this->message = draw_important_message(_PRODUCT_NOT_FOUND, false);
						return false;
					}					
				}else{
					$this->message = draw_important_message(_PRODUCT_NOT_FOUND, false);
					return false;
				}				
			}else{
				$this->message = draw_important_message(_PRODUCT_NOT_FOUND, false);
				return false;
			}
		}else{
			$this->message = draw_important_message(str_replace('_FIELD_', _QUANTITY, _FIELD_MUST_BE_NUMERIC), false);
			return false;
		}
	}
	
	/**
	 * Adds product to the cart
	 * 		@param $product_id
	 * 		@param $amount
	 */	
	public function AddToCart($product_id, $amount = 1)
	{
		if(!empty($product_id)){
			$sql='SELECT
					p.id,
					p.units,
					p.price, 
					pd.name as product_name
				FROM '.TABLE_PRODUCTS.' p
					LEFT OUTER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON p.id = pd.product_id
				WHERE
					p.id = \''.(int)$product_id.'\' AND
					p.units > 0 AND 
					pd.language_id = \''.Application::Get('lang').'\'';
			$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
			if($result[1] == 1){
				if(!isset($this->arrCart[$product_id])){
					$this->arrCart[$product_id] = array('product_name'=>$result[0]['product_name'], 'quantity'=>(int)$amount, 'price'=>$result[0]['price']);
				}else{
					$this->arrCart[$product_id]['quantity'] += (int)$amount;
				}					

				if($this->arrCart[$product_id]['quantity'] > $this->maxAmount){
					$this->arrCart[$product_id]['quantity'] = $this->maxAmount;
					$this->message = draw_important_message(str_replace(array('_PRODUCT_', '_UNITS_'), array($result[0]['product_name'], $this->maxAmount), _PRODUCT_MAXIMUM_UNITS_REACHED), false);
				}else if($this->arrCart[$product_id]['quantity'] > $result[0]['units']){
					$this->arrCart[$product_id]['quantity'] = $result[0]['units'];
					$this->message = draw_important_message(str_replace(array('_PRODUCT_', '_UNITS_'), array($result[0]['product_name'], $result[0]['units']), _PRODUCT_MAXIMUM_UNITS_REACHED), false);
				}else{
					$this->message = draw_success_message(_PRODUCT_WAS_ADDED, false);					
				}

				$this->RefreshCartItems();
				$this->RefreshShippingCost();
			}else{
				$this->message = draw_important_message(_PRODUCT_OUT_OF_STOCK, false);
			}
		}else{
			$this->message = draw_important_message(_WRONG_PARAMETER_PASSED, false);
		}
	}
	
	/**
	 * Add delivery to the cart
	 * 		@param $delivery_type
	 */
	public function AddDelivery($delivery_type = '')
	{
		global $objLogin;
		
		if(!empty($delivery_type)){			
			self::$arrDelivery['type'] = (int)$delivery_type;
			$sql = 'SELECT name FROM '.TABLE_DELIVERIES.' WHERE id = '.(int)$delivery_type;
			$result = database_query($sql, DATA_ONLY, FIRST_ROW_ONLY);
			self::$arrDelivery['name'] = isset($result['name']) ? $result['name'] : _UNKNOWN;
			$take_default = true;
			
			if($objLogin->IsLoggedInAsCustomer()){
				$take_default = false;
				$sql = 'SELECT cntry.id
						FROM '.TABLE_CUSTOMERS.' cust
							INNER JOIN '.TABLE_COUNTRIES.' cntry ON cust.b_country = cntry.abbrv
						WHERE cust.id = '.(int)$objLogin->GetLoggedID();
				$result = database_query($sql, DATA_ONLY);
				$country_id = isset($result[0]['id']) ? $result[0]['id'] : '0';
				
				$sql = 'SELECT dc.price
						FROM ('.TABLE_DELIVERIES.' d
							LEFT OUTER JOIN '.TABLE_DELIVERY_COUNTRIES.' dc ON d.id = dc.delivery_id AND dc.delivery_id = '.(int)$delivery_type.')
						WHERE dc.country_id = '.(int)$country_id;
						
				$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);		
				if(!$result[1]){
					$take_default = true;
				}
			}
			
			if($take_default){
				$sql = 'SELECT price FROM '.TABLE_DELIVERIES.' WHERE id = '.(int)$delivery_type;
				$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);		
			}
			
			// update shipping cost
			if($result[1]){				
				self::$arrDelivery['price'] = $result[0]['price'];	
			}else{
				self::$arrDelivery['price'] = 0;	
			}
			$this->shippingCost = $this->cartItems * (self::$arrDelivery['price'] * Application::Get('currency_rate'));							
		}
	}
	
	/**
	 * Add additional info to the cart
	 * 		@param $additional_info
	 */
	public function AddAdditionalInfo($additional_info = '')
	{
		global $objLogin;
		
		$additional_info = substr_by_word($additional_info, 1024);		
		if($this->additionalInfo != $additional_info){			
			$this->additionalInfo = $additional_info;
		}
	}	

	/**
	 * Removes product from the cart
	 * 		@param $product_id
	 */
	public function RemoveFromCart($product_id)
	{
		if((int)$product_id > 0){
			if(isset($this->arrCart[$product_id]) && $this->arrCart[$product_id]['quantity'] > 0){
				unset($this->arrCart[$product_id]);
				$this->message = draw_message(_PRODUCT_WAS_REMOVED, false);

				$this->RefreshCartItems();
				$this->RefreshShippingCost();
			}else{
				$this->message = draw_important_message(_PRODUCT_NOT_FOUND, false);
			}
		}else{
			$this->message = draw_important_message(_PRODUCT_NOT_FOUND, false);
		}
	}	
	
	/**
	 * Empty Shopping Cart
	 */
	public function EmptyCart()
	{
		$this->arrCart = array();
		self::$arrDelivery = array();
		$this->additionalInfo = '';
	}
	
	/**
	 * Returns items count in Shopping Cart
	 */
	public function GetCartCount()
	{
		return $this->cartItems;
	}		

	/**
	 * Returns cart sum
	 * 		@param $show_message
	 */
	public function GetCartSum($show_message = false)
	{
		$cart_total = '0';

		if($this->cartItems > 0){
			$order_price=0;
			foreach($this->arrCart as $key => $val){
				$quantity = isset($val['quantity']) ? $val['quantity'] : 0;
				$sql='SELECT
							p.*,
							pd.name
						FROM '.TABLE_PRODUCTS.' p
							INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON p.id = pd.product_id
						WHERE
							p.id = \''.$key.'\' AND
							pd.language_id = \''.Application::Get('lang').'\'';

				$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
				if($result[1] > 0){
					$order_price += (($result[0]['price'] * $quantity) * Application::Get('currency_rate'));
				}				
			}
			$cart_total = $order_price;
		}else{
			if($show_message) draw_message(_CART_IS_EMPTY_ALERT, true, true);
		}

		return Currencies::PriceFormat($cart_total, '', '', $this->currencyFormat);
	}	

	/**
	 * Place order
	 * 		@param $cc_params
	 */
	public function PlaceOrder($cc_params = array())
	{
		global $objLogin;
		
        if(SITE_MODE == 'demo'){
           $this->message = draw_important_message(_OPERATION_BLOCKED, false);
		   return false;
        }
		
		// check if prepared order exists
		if($objLogin->IsLoggedInAsAdmin()){
			$sql='SELECT id, order_number FROM '.TABLE_ORDERS.' WHERE customer_id = '.(int)$this->currentCustomerID.' AND is_admin_order = 1 AND status = 0 ORDER BY id DESC';	
		}else{
			$sql='SELECT id, order_number FROM '.TABLE_ORDERS.' WHERE customer_id = '.(int)$this->currentCustomerID.' AND is_admin_order = 0 AND status = 0 ORDER BY id DESC';				
		}
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[1] > 0){
			$order_number = $result[0]['order_number'];
			
			$sql = 'UPDATE '.TABLE_ORDERS.'
					SET
						status_changed = \''.date('Y-m-d H:i:s').'\',
						cc_type = \''.$cc_params['cc_type'].'\',
						cc_holder_name = \''.$cc_params['cc_holder_name'].'\',
						cc_number = AES_ENCRYPT(\''.$cc_params['cc_number'].'\', \''.PASSWORDS_ENCRYPT_KEY.'\'),
						cc_expires_month = \''.$cc_params['cc_expires_month'].'\',
						cc_expires_year = \''.$cc_params['cc_expires_year'].'\',
						cc_cvv_code = \''.$cc_params['cc_cvv_code'].'\',
						status = \'1\'
					WHERE order_number = \''.$order_number.'\'';
			database_void_query($sql);

			// update customer orders/products amount
			if($objLogin->IsLoggedInAsCustomer()){
				$sql = 'UPDATE '.TABLE_CUSTOMERS.' SET orders_count = orders_count + 1, products_count = products_count + '.(int)$this->cartItems.' WHERE id = '.(int)$objLogin->GetLoggedID();
				database_void_query($sql);
			}

			$this->message = draw_success_message(str_replace('_ORDER_NUMBER_', '<b>'.$order_number.'</b>', _ORDER_PLACED_MSG), false);
			if($this->SendOrderEmail($order_number, 'accepted', $this->currentCustomerID)){
				$this->message .= draw_success_message(_EMAIL_SUCCESSFULLY_SENT, false);
			}else{
				if($objLogin->IsLoggedInAsAdmin()){
					$this->message .= draw_important_message(_EMAIL_SEND_ERROR, false); /* _ORDER_SEND_MAIL_ERROR */					
				}
			}
		}else{
			$this->message = draw_important_message(_EMAIL_SEND_ERROR, false);					
		}

		if(SITE_MODE == 'development' && mysql_error() != '') $this->message .= '<br>'.$sql.'<br>'.mysql_error();

		$this->EmptyCart();		
	}	

	/**
	 * Prepare order
	 * 		@param $payment_type
	 */
	public function DoOrder($payment_type = '')
	{
        if(SITE_MODE == 'demo'){
           $this->message = draw_important_message(_OPERATION_BLOCKED, false);
		   return false;
        }

		global $objSettings;
		global $objLogin;
		
		$order_accepted = false;
		$order_number = '';
		$order_price = $this->CalculateTotalSum();
		
		$discount_value = ($order_price * ($this->discountPercent / 100));
		$order_price -= $discount_value;

		$vat_cost  = ($order_price * ($this->vatPercent / 100));
		$cart_total = ($order_price + $vat_cost + $this->shippingCost);
		
		if($this->cartItems > 0)
		{
            // add order to database
			if(in_array($payment_type, array('online', 'paypal', '2co', 'authorize.net')))
			{				
				if($payment_type == 'paypal'){
					$payed_by = '1';
					$status = '0';									
				}else if($payment_type == '2co'){
					$payed_by = '2';
					$status = '0';
				}else if($payment_type == 'authorize.net'){
					$payed_by = '3';
					$status = '0';				
				}else{
					$payed_by = '0';
					$status = '0';
				}				

				// check if prepared order exists and replace it
				if($objLogin->IsLoggedInAsAdmin()){
					$sql='SELECT id, order_number FROM '.TABLE_ORDERS.' WHERE customer_id = '.(int)$this->currentCustomerID.' AND is_admin_order = 1 AND status = 0 ORDER BY id DESC';	
				}else{
					$sql='SELECT id, order_number FROM '.TABLE_ORDERS.' WHERE customer_id = '.(int)$this->currentCustomerID.' AND is_admin_order = 0 AND status = 0 ORDER BY id DESC';				
				}				
				$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
				if($result[1] > 0){
					$order_number = $result[0]['order_number'];
					// order exists - replace it with new					
					$sql = 'DELETE FROM '.TABLE_ORDERS_DESCRIPTION.' WHERE order_number = \''.$order_number.'\'';		
					if(!database_void_query($sql)){ /* echo 'error!'; */ }	 					

					$sql = 'UPDATE '.TABLE_ORDERS.' SET ';
					$sql_end = ' WHERE order_number = \''.$order_number.'\'';
					$is_new_record = false;
				}else{
					$sql = 'INSERT INTO '.TABLE_ORDERS.' SET order_number = \'\',';
					$sql_end = '';
					$is_new_record = true;
				}
				
				// do order
				$sql .= 'order_description = \''._PRODUCTS_PURCHASING.'\',
						order_price = '.$order_price.',
						shipping_fee = '.$this->shippingCost.',
						delivery_type = \''.(isset(self::$arrDelivery['name']) ? self::$arrDelivery['name'] : '').'\',
						vat_percent = '.$this->vatPercent.',
						vat_fee = '.$vat_cost.',
						total_price = '.($order_price + $vat_cost + $this->shippingCost).',
						currency = \''.Application::Get('currency_code').'\',
						products_amount = '.(int)$this->cartItems.',
						customer_id = '.(int)$objLogin->GetLoggedID().',
						is_admin_order = '.(($objLogin->IsLoggedInAsAdmin()) ? '1' : '0').',
						transaction_number = \'\',
						created_date = \''.date('Y-m-d H:i:s').'\',
						payment_type = '.$payed_by.',
						payment_method = 0,
						coupon_number = \'\',
						discount_campaign_id = '.(int)$this->discountCampaignID.',						
						shipping_provider = \'\',
						shipping_id = \'\',
						shipping_date = \'\',
						received_date = \'\',
						additional_info = \''.$this->additionalInfo.'\',
						cc_type = \'\',
						cc_holder_name = \'\', 
						cc_number = \'\', 
						cc_expires_month = \'\', 
						cc_expires_year = \'\', 
						cc_cvv_code = \'\',
						status = '.(int)$status;
				$sql .= $sql_end;

				if(database_void_query($sql)){
					if($is_new_record){
						$insert_id = mysql_insert_id();
						$order_number = $this->GenerateOrderNumber($insert_id);
						$sql = 'UPDATE '.TABLE_ORDERS.' SET order_number = \''.$order_number.'\' WHERE id = '.(int)$insert_id;
						if(!database_void_query($sql)){
							$this->error = draw_important_message(_ORDER_ERROR, false);
						}
					}					
					
					///echo $sql.mysql_error();
					$sql = 'INSERT INTO '.TABLE_ORDERS_DESCRIPTION.' (id, order_number, product_id, amount, price) VALUES ';
					$items_count = 0;
					foreach($this->arrCart as $key => $val){
						$quantity = isset($val['quantity']) ? $val['quantity'] : 0;
						$item_price = '0';
						$sql_='SELECT * FROM '.TABLE_PRODUCTS.' WHERE id = '.(int)$key;									
						$result = database_query($sql_, DATA_AND_ROWS, FIRST_ROW_ONLY);
						if($result[1] > 0){
							$item_price = ($result[0]['price']) * Application::Get('currency_rate');
						}
						$sql .= ($items_count++ > 0) ? ',' : '';
						$sql .= '(NULL, \''.$order_number.'\', '.(int)$key.', '.(int)$quantity.', '.round($item_price, 2).')';
					}
					$sql .= ';';
					if(database_void_query($sql)){					
						$order_accepted = true;
					}else{
						$this->error = draw_important_message(_ORDER_ERROR, false);
					}
				}else{
					$this->error = draw_important_message(_ORDER_ERROR, false);
				}
			}
		}else{
			$this->error = draw_message(_CART_IS_EMPTY_ALERT, true, true);
		}
		
		if(SITE_MODE == 'development' && !empty($this->message)) $this->error .= '<br>'.$sql.'<br>'.mysql_error();		
		
		return $order_accepted;
	}

	/**
	 * Draw order info
	 * 		@param $payment_type
	 * 		@param $draw
	 */
	public function DrawOrder($payment_type = '', $draw = true)
	{
		global $objLogin;
		
		$output = '';
		$cc_type = isset($_POST['cc_type']) ? prepare_input($_POST['cc_type']) : '';
		$cc_holder_name = isset($_POST['cc_holder_name']) ? prepare_input($_POST['cc_holder_name']) : '';
		$cc_number = isset($_POST['cc_number']) ? prepare_input($_POST['cc_number']) : '';
		$cc_expires_month = isset($_POST['cc_expires_month']) ? prepare_input($_POST['cc_expires_month']) : '1';
		$cc_expires_year = isset($_POST['cc_expires_year']) ? prepare_input($_POST['cc_expires_year']) : date('Y');
		$cc_cvv_code = isset($_POST['cc_cvv_code']) ? prepare_input($_POST['cc_cvv_code']) : '';

		$paypal_email        = ModulesSettings::Get('shopping_cart', 'paypal_email');
		$collect_credit_card = ModulesSettings::Get('shopping_cart', 'online_credit_card_required');
		$two_checkout_vendor = ModulesSettings::Get('shopping_cart', 'two_checkout_vendor');
		$authorize_login_id  = ModulesSettings::Get('shopping_cart', 'authorize_login_id');
		$authorize_transaction_key = ModulesSettings::Get('shopping_cart', 'authorize_transaction_key');
		$mode                = ModulesSettings::Get('shopping_cart', 'mode');
		
		$delivery_type       = (isset(self::$arrDelivery['name']) ? self::$arrDelivery['name'] : _UNKNOWN);

		// prepare clients info 
		$sql='SELECT *
			  FROM '.TABLE_CUSTOMERS.'
			  WHERE id = '.(int)$this->currentCustomerID;
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		$customer_info = array();
		$customer_info['first_name'] = isset($result[0]['first_name']) ? $result[0]['first_name'] : '';
		$customer_info['last_name'] = isset($result[0]['last_name']) ? $result[0]['last_name'] : '';
		$customer_info['address1'] = isset($result[0]['b_address']) ? $result[0]['b_address'] : '';
		$customer_info['address2'] = isset($result[0]['b_address_2']) ? $result[0]['b_address_2'] : '';
		$customer_info['city'] = isset($result[0]['b_city']) ? $result[0]['b_city'] : '';
		$customer_info['zip'] = isset($result[0]['b_zipcode']) ? $result[0]['b_zipcode'] : '';
		$customer_info['country'] = isset($result[0]['b_country']) ? $result[0]['b_country'] : '';
		$customer_info['state'] = isset($result[0]['b_state']) ? $result[0]['b_state'] : '';
		$customer_info['email'] = isset($result[0]['email']) ? $result[0]['email'] : '';
		$customer_info['company'] = isset($result[0]['company']) ? $result[0]['company'] : '';
		$customer_info['phone'] = isset($result[0]['phone']) ? $result[0]['phone'] : '';
		$customer_info['fax'] = isset($result[0]['fax']) ? $result[0]['fax'] : '';

		if($cc_holder_name == ''){
			if($objLogin->IsLoggedIn()){
				$cc_holder_name = $objLogin->GetLoggedFirstName().' '.$objLogin->GetLoggedLastName();
			}else{
				$cc_holder_name = $customer_info['first_name'].' '.$customer_info['last_name'];
			}
		}		

		// check if prepared order exists and retrieve it
		$sql='SELECT id, order_number
			  FROM '.TABLE_ORDERS.'
			  WHERE customer_id = '.(int)$this->currentCustomerID.' AND
					status = 0 AND
					is_admin_order = '.($objLogin->IsLoggedInAsAdmin() ? '1' : '0' ).'
			  ORDER BY id DESC';	
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[1] > 0){
			$order_number = $result[0]['order_number'];		
		}else{
			///echo $sql.mysql_error();
			$output .= draw_important_message(_ORDER_ERROR, false);
		}
		
		$order_price = $this->CalculateTotalSum();

		$order_price_before_discount = $order_price;

		$discount_value = ($order_price * ($this->discountPercent / 100));
		$order_price -= $discount_value;
	
		$vat_cost  = ($order_price * ($this->vatPercent / 100));
		$cart_total = ($order_price + $vat_cost + $this->shippingCost);

		$pp_params = array(
			'api_login'       => '',
			'transaction_key' => '',
			'order_number'    => $order_number,			
			
			'address1'      => $customer_info['address1'],
			'address2'      => $customer_info['address2'],
			'city'          => $customer_info['city'],
			'zip'           => $customer_info['zip'],
			'country'       => $customer_info['country'],
			'state'         => $customer_info['state'],
			'first_name'    => $customer_info['first_name'],
			'last_name'     => $customer_info['last_name'],
			'email'         => $customer_info['email'],
			'company'       => $customer_info['company'],
			'phone'         => $customer_info['phone'],
			'fax'           => $customer_info['fax'],
			
			'notify'        => '',
			'return'        => 'index.php?page=order_return',
			'cancel_return' => 'index.php?page=order_cancel',
						
			'paypal_form_type'   	   => '',
			'paypal_form_fields' 	   => '',
			'paypal_form_fields_count' => '',
			
			'collect_credit_card' => '',
			'cc_type'             => '',
			'cc_holder_name'      => '',
			'cc_number'           => '',
			'cc_cvv_code'         => '',
			'cc_expires_month'    => '',
			'cc_expires_year'     => '',
			
			'currency_code'      => Application::Get('currency_code'),
			'additional_info'    => $this->additionalInfo,
			'discount_value'     => $discount_value,
			'vat_cost'           => $vat_cost,
			'shipping_cost'      => $this->shippingCost,
			'cart_total'         => $cart_total
			
		);

		$fisrt_part = '<table border="0" width="98%" align="center">
			<tr><td width="20%">'._ORDER_DATE.' </td><td width="2%"> : </td><td> <b>'.date('M d, Y g:i A').'</b></td></tr>
			<tr><td>'._CURRENCY.' </td><td> : </td><td> <b>'.Application::Get('currency_code').'</b></td></tr>
			<tr><td>'._PRODUCTS.' </td><td> : </td><td> <b>'.(int)$this->cartItems.'</b></td></tr>
			<tr><td>'._ORDER_PRICE.' </td><td> : </td><td> <b>'.Currencies::PriceFormat($order_price_before_discount, '', '', $this->currencyFormat).'</b></td></tr>';
			if($this->discountCampaignID != ''){
				$fisrt_part .= '<tr><td>'._DISCOUNT.' ('.$this->discountPercent.'%)</td><td> : </td><td> <b><span style="color:#a60000">- '.Currencies::PriceFormat($discount_value, '', '', $this->currencyFormat).'</span></b></td></tr>';
			}
			$fisrt_part .= '<tr><td>'._VAT.' ('.Currencies::PriceFormat($this->vatPercent, '%', 'right', $this->currencyFormat, $this->GetVatPercentDecimalPoints($this->vatPercent)).') </td><td> : </td><td> <b>'.Currencies::PriceFormat($vat_cost, '', '', $this->currencyFormat).'</b></td></tr>
			<tr><td>'._DELIVERY_TYPE.' </td><td> : </td><td> <b>'.$delivery_type.'</b></td></tr>
			<tr><td>'._SHIPPING_FEE.' </td><td> : </td><td> <b>'.Currencies::PriceFormat($this->shippingCost, '', '', $this->currencyFormat).'</b></td></tr>
			<tr><td>'._TOTAL_PRICE.' </td><td> : </td><td> <b>'.Currencies::PriceFormat($cart_total, '', '', $this->currencyFormat).'</b></td></tr>';
			if(!empty($this->additionalInfo)) $fisrt_part .= '<tr><td>'._ADDITIONAL_INFO.' </td><td> : </td><td> '.$this->additionalInfo.'</td></tr>';
			$fisrt_part .= '<tr><td colspan="3"><br />';
		
		$second_part = '</td></tr></table><br /></fieldset>';

		if($payment_type == 'online'){
			
			$output .= '<fieldset><legend><b>'._ONLINE_ORDER.'</b></legend><br />';				
			$output .= $fisrt_part;
				$pp_params['collect_credit_card'] = $collect_credit_card;
				$pp_params['cc_type']             = $cc_type;
				$pp_params['cc_holder_name']      = $cc_holder_name;
				$pp_params['cc_number']           = $cc_number;
				$pp_params['cc_cvv_code']         = $cc_cvv_code;
				$pp_params['cc_expires_month']    = $cc_expires_month;
				$pp_params['cc_expires_year']     = $cc_expires_year;
				$output .= PaymentIPN::DrawPaymentForm('online', $pp_params, (($mode == 'TEST MODE') ? 'test' : 'real'), false);
			$output .= $second_part;			
		
		}else if($payment_type == 'paypal'){

			$output .= '<fieldset><legend><b>PayPal</b></legend><br />';
			$output .= $fisrt_part;
				$pp_params['api_login']                = $paypal_email;
				$pp_params['notify']        		   = 'index.php?page=order_notify_paypal';
				$pp_params['paypal_form_type']   	   = $this->paypal_form_type;
				$pp_params['paypal_form_fields'] 	   = $this->paypal_form_fields;
				$pp_params['paypal_form_fields_count'] = $this->paypal_form_fields_count;						
				$output .= PaymentIPN::DrawPaymentForm('paypal', $pp_params, (($mode == 'TEST MODE') ? 'test' : 'real'), false);
			$output .= $second_part;

		}else if($payment_type == '2co'){				
		
			$output .= '<fieldset><legend><b>2CO</b></legend><br />';
			$output .= $fisrt_part;
				$pp_params['api_login'] = $two_checkout_vendor;			
				$pp_params['notify']    = 'index.php?page=order_notify_2co';
				$output .= PaymentIPN::DrawPaymentForm('2co', $pp_params, (($mode == 'TEST MODE') ? 'test' : 'real'), false);
			$output .= $second_part;
				
		}else if($payment_type == 'authorize.net'){
			
			$output .= '<fieldset><legend><b>Authorize.Net</b></legend><br />';
			$output .= $fisrt_part;
				$pp_params['api_login'] 	  = $authorize_login_id;
				$pp_params['transaction_key'] = $authorize_transaction_key;
				$pp_params['notify']    	  = 'index.php?page=order_notify_autorize_net';
				// authorize.net accepts only USD, so we need to convert the sum into USD
				$pp_params['cart_total']      = number_format((($pp_params['cart_total'] * Application::Get('currency_rate'))), '2', '.', ',');												
				PaymentIPN::DrawPaymentForm('authorize.net', $pp_params, (($mode == 'TEST MODE') ? 'test' : 'real'), false);
			$output .= $second_part;
		}
		
		if($draw) echo $output;
		return $output;
	}
	
	/**
	 * Sends order mail
	 * 		@param $order_number
	 * 		@param $order_type
	 * 		@param $customer_id
	 */
	public function SendOrderEmail($order_number, $order_type = 'accepted', $customer_id = '')
	{		
		global $objSettings;
		
		$order_details = '';
		
		// send email to customer
		$sql = 'SELECT
			o.id,
			o.order_number,
			o.order_description,
			o.order_price,
			o.shipping_fee,
			o.delivery_type,
			o.vat_percent,
			o.vat_fee,
			o.total_price,
			o.currency,
			o.products_amount,
			o.customer_id,
			o.transaction_number,
			o.created_date,
			IF(o.payment_date != \'\' AND o.payment_date != \'0000-00-00 00:00:00\', o.payment_date, \'--\') as payment_date,
			o.payment_type,
			o.payment_method,
			o.status,
			o.email_sent,
			o.additional_info,
			CASE
				WHEN o.payment_type = 0 THEN \''._ONLINE_ORDER.'\'
				WHEN o.payment_type = 1 THEN \''._PAYPAL.'\'
				WHEN o.payment_type = 2 THEN \'2CO\'
				WHEN o.payment_type = 3 THEN \'Authorize.Net\'
				ELSE \''._UNKNOWN.'\'
			END as mod_payment_type,
			CASE
				WHEN o.payment_method = 0 THEN \''._PAYMENT_COMPANY_ACCOUNT.'\'
				WHEN o.payment_method = 1 THEN \''._CREDIT_CARD.'\'
				WHEN o.payment_method = 2 THEN \''._ECHECK.'\'
				ELSE \''._UNKNOWN.'\'
			END as mod_payment_method,			
			CASE
				WHEN o.status = 0 THEN \'<span style=color:#960000>'._PREPARING.'</span>\'
				WHEN o.status = 1 THEN \'<span style=color:#FF9966>'._PENDING.'</span>\'
				WHEN o.status = 2 THEN \'<span style=color:#336699>'._PAID.'</span>\'
				WHEN o.status = 3 THEN \'<span style=color:#99CCCC>'._SHIPPED.'</span>\'
				WHEN o.status = 4 THEN \'<span style=color:#009696>'._RECEIVED.'</span>\'
				WHEN o.status = 5 THEN \'<span style=color:#009600>'._COMPLETED.'</span>\'
				ELSE \''._UNKNOWN.'\'
			END as mod_status,
			c.first_name,
			c.last_name,
			c.user_name as customer_name,
			c.email,
			c.preferred_language,
			c.b_address,
			c.b_address_2,
			c.b_city,
			c.b_state,
			count.name as b_country,
			c.b_zipcode, 
			c.phone,
			c.fax,
			cur.symbol,
			cur.symbol_placement,
			camp.campaign_name,
			camp.discount_percent
		FROM '.TABLE_ORDERS.' o
			INNER JOIN '.TABLE_CURRENCIES.' cur ON o.currency = cur.code
			LEFT OUTER JOIN '.TABLE_CUSTOMERS.' c ON o.customer_id = c.id
			LEFT OUTER JOIN '.TABLE_COUNTRIES.' count ON c.b_country = count.abbrv 
			LEFT OUTER JOIN '.TABLE_CAMPAIGNS.' camp ON o.discount_campaign_id = camp.id			
		WHERE
			o.customer_id = '.(int)$customer_id.' AND
			o.order_number = \''.$order_number.'\'';
		
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[1] > 0){
			
			$preferred_language = $result[0]['preferred_language'];
			
			if(ModulesSettings::Get('shopping_cart', 'mode') == 'TEST MODE'){
				$order_details .= '<div style="text-align:center;padding:10px;color:#a60000;border:1px dashed #a60000;width:100px">TEST MODE!</div><br />';	
			}			

			// Personal Info
			$order_details .= '<b>'._PERSONAL_INFORMATION.':</b><br />';
			$order_details .= _FIRST_NAME.' : '.$result[0]['first_name'].'<br />';
			$order_details .= _LAST_NAME.' : '.$result[0]['last_name'].'<br />';
			$order_details .= _EMAIL_ADDRESS.' : '.$result[0]['email'].'<br />';
			$order_details .= '<br />';

			// Billing Info
			$order_details .= '<b>'._BILLING_INFORMATION.':</b><br />';
			$order_details .= _ADDRESS.': '.$result[0]['b_address'].'<br />';
			$order_details .= _ADDRESS_2.': '.$result[0]['b_address_2'].'<br />';
			$order_details .= _CITY.': '.$result[0]['b_city'].'<br />';
			$order_details .= _STATE_PROVINCE.': '.$result[0]['b_state'].'<br />';						
			$order_details .= _COUNTRY.': '.$result[0]['b_country'].'<br />';
			$order_details .= _ZIP_CODE.': '.$result[0]['b_zipcode'].'<br />';
			if(!empty($result[0]['phone'])) $order_details .= _PHONE.' : '.$result[0]['phone'].'<br />';
			if(!empty($result[0]['fax'])) $order_details .= _FAX.' : '.$result[0]['fax'].'<br />';			
			$order_details .= '<br />';
			
			// Order Details
			$order_details .= '<b>'._ORDER_DETAILS.':</b><br />';
			$order_details .= _ORDER_DESCRIPTION.': '.$result[0]['order_description'].'<br />';
			$order_details .= _CURRENCY.': '.$result[0]['currency'].'<br />';
			$order_details .= _CREATED_DATE.': '.format_datetime($result[0]['created_date']).'<br />';
			$order_details .= _PAYMENT_DATE.': '.format_datetime($result[0]['payment_date']).'<br />';
			$order_details .= _PAYMENT_TYPE.': '.$result[0]['mod_payment_type'].'<br />';
			$order_details .= _PAYMENT_METHOD.': '.$result[0]['mod_payment_method'].'<br />';
			$order_details .= _PRODUCTS_AMOUNT.': '.$result[0]['products_amount'].'<br />';
			$order_details .= (($result[0]['campaign_name'] != '') ? _DISCOUNT_CAMPAIGN.': '.$result[0]['campaign_name'].' ('.$result[0]['discount_percent'].'%)' : '').'<br />';
			$order_details .= _ORDER_PRICE.': '.Currencies::PriceFormat($result[0]['order_price'], $result[0]['symbol'], $result[0]['symbol_placement'], $this->currencyFormat).'<br />';
			$order_details .= _VAT.': '.Currencies::PriceFormat($result[0]['vat_fee'], $result[0]['symbol'], $result[0]['symbol_placement'], $this->currencyFormat).' ('.Currencies::PriceFormat($result[0]['vat_percent'], '%', 'right', $this->currencyFormat, $this->GetVatPercentDecimalPoints($result[0]['vat_percent'])).')<br />';
			$order_details .= _DELIVERY_TYPE.': '.$result[0]['delivery_type'].'<br />';
			$order_details .= _SHIPPING_FEE.': '.Currencies::PriceFormat($result[0]['shipping_fee'], $result[0]['symbol'], $result[0]['symbol_placement'], $this->currencyFormat).'<br />';
			$order_details .= _TOTAL_PRICE.': '.Currencies::PriceFormat($result[0]['total_price'], $result[0]['symbol'], $result[0]['symbol_placement'], $this->currencyFormat).'<br />';
			$order_details .= _ADDITIONAL_INFO.': '.nl2br($result[0]['additional_info']).'<br /><br />';
				
			$order_details .= $this->GetOrderProductsList($order_number, Application::Get('lang'));
			
			$send_order_copy_to_admin = ModulesSettings::Get('shopping_cart', 'send_order_copy_to_admin');

			////////////////////////////////////////////////////////////
			$sender = $objSettings->GetParameter('admin_email');
			$recipient = $result[0]['email'];

			$objEmailTemplates = new EmailTemplates();
			if($order_type == 'completed'){
				// exit if email was already sent
				if($result[0]['email_sent'] == '1') return true;
				$email_template = 'order_paid';
				$admin_copy_subject = 'Customer order has been paid (admin copy)';				
			}else if($order_type == 'refunded'){
				$email_template = 'order_refunded';
				$admin_copy_subject = 'Order has been refunded (admin copy)';
			}else{
				$email_template = 'order_accepted_online';
				$admin_copy_subject = 'Customer has placed online order (admin copy)';
			}
			
			$arr_send_email = array('customer');
			if($send_order_copy_to_admin == 'yes'){
				$arr_send_email[] = 'admin_copy';
			}

			$copy_subject = '';
			foreach($arr_send_email as $key){			
				if($key == 'admin_copy'){
					$preferred_language = Languages::GetDefaultLang();
					$recipient = $sender;
					$copy_subject = $admin_copy_subject;
				}
				send_email(
					$recipient,
					$sender,
					$email_template,
					array(
						'{FIRST NAME}' => $result[0]['first_name'],
						'{LAST NAME}'  => $result[0]['last_name'],
						'{ORDER NUMBER}'  => $order_number,
						'{ORDER DETAILS}' => $order_details,
					),
					$preferred_language,
					'',
					$copy_subject
				);
			}
		
			if($order_type == 'completed'){
				$sql = 'UPDATE '.TABLE_ORDERS.' SET email_sent = 1 WHERE order_number = \''.$order_number.'\'';
				database_void_query($sql);
			}

			////////////////////////////////////////////////////////////
			return true;
		}
		return false;
	}
	
	/**
	 * Checks if cart is empty 
	 */
	public function IsCartEmpty()
	{
		return ($this->cartItems > 0) ? false : true;
	}
	
	/**
	 * Calculate total cart sum
	 */	
	private function CalculateTotalSum()
	{
        $order_price = '0';
		foreach($this->arrCart as $key => $val){
			$quantity = isset($val['quantity']) ? $val['quantity'] : 0;
			$price = isset($val['price']) ? $val['price'] : 0;
			$order_price += (($price * $quantity) * Application::Get('currency_rate'));
		}
	
		return $order_price;
	}
	
	/**
	 * Returns order products list
	 * 		@param $oid
	 * 		@param $language_id
	 */
	private function GetOrderProductsList($oid, $language_id)
	{
		$output = '';
		
        // display list of products in order		
		$sql = 'SELECT
					od.order_number,
					pd.name as product_name,
					od.amount,
					od.price as tp_w_currency,								
					(od.price * od.amount) as tp_w_currency_total					
				FROM '.TABLE_ORDERS_DESCRIPTION.' od
					INNER JOIN '.TABLE_ORDERS.' o ON od.order_number = o.order_number
					INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON od.product_id = pd.product_id
					LEFT OUTER JOIN '.TABLE_CURRENCIES.' cur ON o.currency = cur.code
					LEFT OUTER JOIN '.TABLE_CUSTOMERS.' c ON o.customer_id = c.id
				WHERE
					o.order_number = \''.$oid.'\' AND
					pd.language_id = \''.$language_id.'\' ';

		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);

		if($result[1] > 0){
			$output .= '<table width="100%" border="0" cellspacing="0" cellpadding="3" style="border:1px solid #d1d2d3">';
			$output .= '<tr style="background-color:#e1e2e3;font-weight:bold;font-size:13px;">';
			$output .= '<th align="center"> # </th>';
			$output .= '<th align="left">'._PRODUCT.'</th>';
			$output .= '<th align="right" width="90px"> '._PRICE.' </th>';
			$output .= '<th align="center" width="90px"> '._AMOUNT.' </th>';
			$output .= '<th align="right" width="90px"> '._TOTAL.' </th>';
			$output .= '</tr>';	
			for($i=0; $i < $result[1]; $i++){
				$output .= '<tr>';
				$output .= '<td align="center" width="40px">'.($i+1).'.</td>';
				$output .= '<td align="left">'.$result[0][$i]['product_name'].' </td>';				
				$output .= '<td align="right">'.Currencies::PriceFormat($result[0][$i]['tp_w_currency'], '', '', $this->currencyFormat).'</td>';
				$output .= '<td align="center">'.$result[0][$i]['amount'].'</td>';
				$output .= '<td align="right">'.Currencies::PriceFormat($result[0][$i]['tp_w_currency_total'], '', '', $this->currencyFormat).'</td>';
				$output .= '</tr>';	
			}
			$output .= '</table>';			
		}		
		
		return $output;		
	}
	
	/**
	 * Refresh cart items
	 */
	public function RefreshCartItems()
	{
		$this->cartItems = 0;
		if(count($this->arrCart) > 0){
			foreach($this->arrCart as $key => $val){
				$this->cartItems += (isset($val['quantity']) ? $val['quantity'] : 0);
			}
		}
	}

	/**
	 * Refresh shipping cost
	 */
	public function RefreshShippingCost()
	{
		$this->shippingCost = $this->cartItems * (isset(self::$arrDelivery['price']) ? self::$arrDelivery['price'] * Application::Get('currency_rate') : '0');
	}
	
	
	//==========================================================================
    // Static Methods
	//==========================================================================
	
	/**
	 * Get delivery info
	 * 		@param $param
	 */
	public static function GetDeliveryInfo($param = '')
	{
		return (isset(self::$arrDelivery[$param])) ? self::$arrDelivery[$param] : '';
	}	

	/**
	 * Recalculates Delivery price accourding to customers country
	 * 		@param $logged_as_customer
	 * 		@param $logged_id
	 */
	public static function ReCalcualteDelivery($logged_as_customer = false, $logged_id = 0)
	{
		$secure_word = (defined('INSTALLATION_KEY') && INSTALLATION_KEY != '') ? INSTALLATION_KEY : 'SECWRD_';
		self::$arrDelivery = &$_SESSION[$secure_word]['delivery_info'];
		
		if($logged_as_customer && !empty(self::$arrDelivery['type'])){			

			$take_default = false;
			$sql = 'SELECT cntry.id
					FROM '.TABLE_CUSTOMERS.' cust
						INNER JOIN '.TABLE_COUNTRIES.' cntry ON cust.b_country = cntry.abbrv
					WHERE cust.id = '.(int)$logged_id;
			$result = database_query($sql, DATA_ONLY);
			$country_id = isset($result[0]['id']) ? $result[0]['id'] : '0';
			
			$sql = 'SELECT dc.price
					FROM ('.TABLE_DELIVERIES.' d
						LEFT OUTER JOIN '.TABLE_DELIVERY_COUNTRIES.' dc ON d.id = dc.delivery_id AND dc.delivery_id = '.(int)self::$arrDelivery['type'].')
					WHERE dc.country_id = '.(int)$country_id;

			$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
			if(!$result[1]){
				$take_default = true;
			}
			
			if($take_default){
				$sql = 'SELECT price FROM '.TABLE_DELIVERIES.' WHERE id = '.(int)self::$arrDelivery['type'];
				$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);		
			}
			
			// update shipping cost
			if($result[1]){
				self::$arrDelivery['price'] = $result[0]['price'];	
			}else{
				self::$arrDelivery['price'] = 0;	
			}
		}else{
			self::$arrDelivery = Deliveries::GetDefaultDelivery();
		}
	}

	/**
	 * Get Vat Percent decimal points
	 * 		@param $vat_percent
	 */
	private function GetVatPercentDecimalPoints($vat_percent = '0')
	{
		return (substr($vat_percent, -1) == '0') ? 2 : 3;
	}	

	/**
	 * Generate order number
	 * 		@param $order_id
	 */
	private function GenerateOrderNumber($order_id = '0')
	{
		$order_number_type = ModulesSettings::Get('shopping_cart', 'order_number_type');
		if($order_number_type == 'sequential'){
			return str_pad($order_id, 10, '0', STR_PAD_LEFT);
		}else{
			return strtoupper(get_random_string(10));		
		}		
	}
	
}