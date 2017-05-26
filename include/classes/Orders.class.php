<?php

/**
 *	Orders MicroGrid Class
 *  -------------- 
 *	Written by  : ApPHP
 *  Updated	    : 06.07.2011
 *	Written by  : ApPHP
 *
 *	PUBLIC:				  	STATIC:				 	PRIVATE:
 * 	------------------	  	---------------     	---------------
 *	__construct             UpdateUnitsInStock      GetOrderProductsList
 *	__destruct              ReturnUnitsToStock      GetVatPercentDecimalPoints
 *	DrawOrderDescription    RemoveExpired
 *	DrawOrderInvoice
 *	BeforeDeleteRecord
 *	AfterDeleteRecord
 *	OnItemCreated_ViewMode
 *	CleanCreditCardInfo
 *	UpdatePaymentDate
 *	SendInvoice
 *	
 **/


class Orders extends MicroGrid {
	
	protected $debug = false;
	
    // ----------------------------
	private $page;
	private $customer_id;
	private $order_number;
	private $order_status;
	private $order_products;
	private $currency_format;
	
	private $inventory_control;
	private $collect_credit_card;
	private $sqlFieldDatetimeFormat;
	private $arr_payment_types;
	private $arr_payment_methods;
	private $arr_statuses;
	
	//==========================================================================
    // Class Constructor
	// 		@param $customer_id
	//==========================================================================
	function __construct($customer_id = '')
	{
		global $objLogin;
		
		parent::__construct();

		$this->params = array();		
		if(isset($_POST['status']))   		   $this->params['status'] = prepare_input($_POST['status']);
		if(isset($_POST['status_changed']))    $this->params['status_changed'] = prepare_input($_POST['status_changed']);
		if(isset($_POST['shipping_provider'])) $this->params['shipping_provider'] = prepare_input($_POST['shipping_provider']);
		if(isset($_POST['shipping_id']))   	   $this->params['shipping_id'] = prepare_input($_POST['shipping_id']);
		if(isset($_POST['shipping_date']))     $this->params['shipping_date'] = prepare_input($_POST['shipping_date']);
		if(isset($_POST['received_date']))     $this->params['received_date'] = prepare_input($_POST['received_date']);
		if(isset($_POST['additional_info']))   $this->params['additional_info'] = prepare_input($_POST['additional_info']);
		
		#if(isset($_POST['parameter2']))   $this->params['parameter2'] = $_POST['parameter2'];
		#if(isset($_POST['parameter3']))   $this->params['parameter3'] = $_POST['parameter3'];
		#// for checkboxes 
		#if(isset($_POST['parameter4']))   $this->params['parameter4'] = $_POST['parameter4']; else $this->params['parameter4'] = '0';

		$this->params['language_id'] = MicroGrid::GetParameter('language_id');
		$rid = MicroGrid::GetParameter('rid');
	
		$this->primaryKey 	= 'id';
		$this->tableName 	= TABLE_ORDERS;
		$this->dataSet 		= array();
		$this->error 		= '';
		$this->order_number = '';
		$this->order_status = '';
		$this->order_products = '';
		$arr_statuses          = array('0'=>_PREPARING, '1'=>_PENDING, '2'=>_PAID, '3'=>_SHIPPED, '4'=>_RECEIVED, '5'=>_COMPLETED, '6'=>_REFUNDED);
		$arr_statuses_edit     = array('1'=>_PENDING, '2'=>_PAID, '3'=>_SHIPPED, '4'=>_RECEIVED, '5'=>_COMPLETED, '6'=>_REFUNDED);
		$arr_statuses_edit_cut = array('1'=>_PENDING, '2'=>_PAID, '3'=>_SHIPPED, '4'=>_RECEIVED, '5'=>_COMPLETED);
		$arr_statuses_refund   = array('6'=>_REFUNDED);
		$arr_statuses_customer_edit = array('4'=>_RECEIVED);

		$this->arr_payment_types = array('0'=>_ONLINE_ORDER, '1'=>_PAYPAL, '2'=>'2CO', '3'=>'Authorize.Net', '4'=>_UNKNOWN);
		$this->arr_payment_methods = array('0'=>_PAYMENT_COMPANY_ACCOUNT, '1'=>_CREDIT_CARD, '2'=>_ECHECK, '3'=>_UNKNOWN);
		$this->arr_statuses = array(
			'0' => '<span style="color:#960000">'._PREPARING.'</span>',
			'1' => '<span style="color:#FF9966">'._PENDING.'</span>',
			'2' => '<span style="color:#336699">'._PAID.'</span>',
			'3' => '<span style="color:#99CCCC">'._SHIPPED.'</span>',
			'4' => '<span style="color:#009696">'._RECEIVED.'</span>',
			'5' => '<span style="color:#009600">'._COMPLETED.'</span>',
			'6' => '<span style="color:#969600">'._REFUNDED.'</span>',
			'7' => _UNKNOWN
		);

		if($customer_id != ''){
			$this->customer_id = $customer_id;
			$this->page = 'customer=my_orders';
			$this->actions   = array('add'=>false, 'edit'=>false, 'details'=>false, 'delete'=>false);
		}else{			
			$this->customer_id = '';
			$this->page = 'admin=mod_shop_orders';
			$this->actions   = array('add'=>false, 'edit'=>false, 'details'=>false, 'delete'=>(($objLogin->IsLoggedInAs('owner')) ? true : false));
		}
		$this->actionIcons  = true;
		$this->allowRefresh = true;

		$this->formActionURL = 'index.php?'.$this->page;

		$this->allowLanguages = false;
		$this->languageId  	= ''; // ($this->params['language_id'] != '') ? $this->params['language_id'] : Languages::GetDefaultLang();
		$this->WHERE_CLAUSE = 'WHERE 1 = 1';
		if($this->customer_id != ''){
			$this->WHERE_CLAUSE .= ' AND '.$this->tableName.'.is_admin_order = 0 AND '.$this->tableName.'.customer_id = '.$this->customer_id;
		}
		$this->ORDER_CLAUSE = 'ORDER BY '.$this->tableName.'.id DESC'; // ORDER BY date_created DESC
		
		$this->isAlterColorsAllowed = true;

		$this->isPagingAllowed = true;
		$this->pageSize = 30;

		$this->isSortingAllowed = true;

		if($objLogin->IsLoggedInAsAdmin()){
			$this->isExportingAllowed = true;
			$this->arrExportingTypes = array('csv'=>true);
		}

		$date_format_settings = get_date_format('view', true);
		$this->isFilteringAllowed = true;
		// define filtering fields
		$this->arrFilteringFields = array(
			_ORDER_NUMBER  => array('table'=>$this->tableName, 'field'=>'order_number', 'type'=>'text', 'sign'=>'like%', 'width'=>'70px'),
			_DATE  => array('table'=>$this->tableName, 'field'=>'created_date', 'type'=>'calendar', 'date_format'=>$date_format_settings, 'sign'=>'like%', 'width'=>'80px', 'visible'=>true),
		);
		if($this->customer_id == ''){
			$this->arrFilteringFields[_CUSTOMER] = array('table'=>TABLE_CUSTOMERS, 'field'=>'user_name', 'type'=>'text', 'sign'=>'like%', 'width'=>'70px');
		}
		$this->arrFilteringFields[_STATUS] = array('table'=>$this->tableName, 'field'=>'status', 'type'=>'dropdownlist', 'source'=>$arr_statuses_edit, 'sign'=>'=', 'width'=>'');

		$this->isAggregateAllowed = true;
		// define aggregate fields for View Mode
		$this->arrAggregateFields = array(
			'total_price' => array('function'=>'SUM', 'align'=>'right'),
			'products_amount' => array('function'=>'SUM', 'align'=>'center'),
		///	'field2' => array('function'=>'AVG'),
		);

		global $objSettings;
		$this->default_currency_info = Currencies::GetDefaultCurrencyInfo();
		if($objSettings->GetParameter('date_format') == 'mm/dd/yyyy'){
			$this->sqlFieldDatetimeFormat = '%b %d, %Y %H:%i';
		}else{
			$this->sqlFieldDatetimeFormat = '%d %b, %Y %H:%i';
		}

		$datetime_format = get_datetime_format();
		$date_format = get_date_format();
			
		$this->currency_format = get_currency_format();
		$pre_currency_symbol = ((Application::Get('currency_symbol_place') == 'left') ? Application::Get('currency_symbol') : '');
		$post_currency_symbol = ((Application::Get('currency_symbol_place') == 'right') ? Application::Get('currency_symbol') : '');

		$this->inventory_control = ModulesSettings::Get('shopping_cart', 'inventory_control');
		$this->collect_credit_card = ModulesSettings::Get('shopping_cart', 'online_credit_card_required');

		//---------------------------------------------------------------------- 
		// VIEW MODE
		//---------------------------------------------------------------------- 
        // set locale time names
		$this->SetLocale(Application::Get('lc_time_name'));

		$this->VIEW_MODE_SQL = 'SELECT
								'.$this->tableName.'.'.$this->primaryKey.',
								'.$this->tableName.'.order_number,
								'.$this->tableName.'.order_description,
								'.$this->tableName.'.order_price,
								'.$this->tableName.'.total_price,
								CONCAT('.TABLE_CURRENCIES.'.symbol, "", '.$this->tableName.'.total_price) as mod_total_price,
								'.$this->tableName.'.currency,
								'.$this->tableName.'.products_amount,
								'.$this->tableName.'.customer_id,
								'.$this->tableName.'.transaction_number,
								DATE_FORMAT('.$this->tableName.'.created_date, \''.$this->sqlFieldDatetimeFormat.'\') as mod_created_date,
								'.$this->tableName.'.payment_date,
								'.$this->tableName.'.payment_type,
								'.$this->tableName.'.payment_method,
								'.$this->tableName.'.status,
								'.$this->tableName.'.status_changed,
								IF('.$this->tableName.'.is_admin_order = 0, CONCAT("<a href=\"javascript:void(\'customer|view\')\" onclick=\"open_popup(\'popup.ajax.php\',\'customer\',\'", '.$this->tableName.'.customer_id, "\')\">", '.TABLE_CUSTOMERS.'.user_name, "</a>"), "{administrator}") as customer_name,
								'.TABLE_CURRENCIES.'.symbol,
								CONCAT("<a href=\"javascript:void(\'description\')\" onclick=\"javascript:__mgDoPostBack(\''.$this->tableName.'\', \'description\', \'", '.$this->tableName.'.'.$this->primaryKey.', "\')\">[ ", "'._DESCRIPTION.'", " ]</a>") as link_order_description,
								IF('.$this->tableName.'.status >= 2, CONCAT("<a href=\"javascript:void(\'invoice\')\" onclick=\"javascript:__mgDoPostBack(\''.$this->tableName.'\', \'invoice\', \'", '.$this->tableName.'.'.$this->primaryKey.', "\')\">[ ", "'._INVOICE.'", " ]</a>"), "<span class=lightgray>'._INVOICE.'</span>") as link_order_invoice,
								IF('.$this->tableName.'.status = 0 OR '.$this->tableName.'.status = 1, CONCAT("<a href=\"javascript:void(0);\" title=\"Delete\" onclick=\"javascript:__mgDoPostBack(\''.TABLE_ORDERS.'\', \'delete\', \'", '.$this->tableName.'.'.$this->primaryKey.', "\');\">[ '._DELETE_WORD.' ]</a>"), "<span class=lightgray>'._DELETE_WORD.'</span>") as link_order_delete,								
								IF('.$this->tableName.'.status = 3, CONCAT("<a href=\"javascript:void(0);\" title=\"'._EDIT_WORD.'\" onclick=\"javascript:__mgDoPostBack(\''.TABLE_ORDERS.'\', \'edit\', \'", '.$this->tableName.'.'.$this->primaryKey.', "\');\">[ '._EDIT_WORD.' ]</a>"), "<span class=lightgray>'._EDIT_WORD.'</span>") as link_order_edit,
								IF('.$this->tableName.'.status != 0, CONCAT("<a href=\"javascript:void(0);\" title=\"'._EDIT_WORD.'\" onclick=\"javascript:__mgDoPostBack(\''.TABLE_ORDERS.'\', \'edit\', \'", '.$this->tableName.'.'.$this->primaryKey.', "\');\">[ '._EDIT_WORD.' ]</a>"), "<span class=lightgray>'._EDIT_WORD.'</span>") as link_admin_order_edit,
								'.TABLE_CUSTOMERS.'.b_country
							FROM '.$this->tableName.'
								INNER JOIN '.TABLE_CURRENCIES.' ON '.$this->tableName.'.currency = '.TABLE_CURRENCIES.'.code
								LEFT OUTER JOIN '.TABLE_CUSTOMERS.' ON '.$this->tableName.'.customer_id = '.TABLE_CUSTOMERS.'.id
							';		

		// define view mode fields
		if($this->customer_id != ''){
			$this->arrViewModeFields = array(
				'order_number'     => array('title'=>_ORDER_NUMBER, 'type'=>'label', 'align'=>'left', 'width'=>'', 'height'=>'', 'maxlength'=>''),
				'mod_created_date' => array('title'=>_DATE, 'type'=>'label', 'align'=>'left', 'width'=>'', 'height'=>'', 'maxlength'=>'', 'format'=>'date', 'format_parameter'=>$datetime_format),
				'products_amount'  => array('title'=>'#', 'type'=>'label', 'align'=>'center', 'width'=>'', 'height'=>'', 'maxlength'=>''),
				'mod_total_price'     => array('title'=>_TOTAL_PRICE, 'type'=>'label', 'align'=>'right', 'width'=>'', 'height'=>'', 'maxlength'=>'', 'sort_by'=>'total_price', 'sort_type'=>'numeric', 'format'=>'currency', 'format_parameter'=>$this->currency_format.'|2'),
				//'symbol'          => array('title'=>'', 'type'=>'label', 'align'=>'left', 'width'=>'', 'height'=>'', 'maxlength'=>''),
				'status' 		   => array('title'=>_STATUS, 'type'=>'enum',  'align'=>'center', 'width'=>'', 'sortable'=>true, 'nowrap'=>'', 'visible'=>'', 'source'=>$this->arr_statuses),
				'link_order_description' => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'', 'height'=>'', 'maxlength'=>''),
				'link_order_invoice'     => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'', 'height'=>'', 'maxlength'=>''),
				'link_order_edit'        => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'', 'height'=>'', 'maxlength'=>''),
				'link_order_delete'      => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'', 'height'=>'', 'maxlength'=>''),
			);			
		}else{
			$this->arrViewModeFields = array(
				'order_number'    		=> array('title'=>_ORDER, 'type'=>'label', 'align'=>'left', 'width'=>'', 'height'=>'', 'maxlength'=>''),
				'mod_created_date'		=> array('title'=>_DATE, 'type'=>'label', 'align'=>'left', 'width'=>'', 'height'=>'', 'maxlength'=>'', 'format'=>'date', 'format_parameter'=>$datetime_format),
				'customer_name'   		=> array('title'=>_CUSTOMER, 'type'=>'label', 'align'=>'left', 'width'=>'', 'height'=>'', 'maxlength'=>''),
				'b_country'             => array('title'=>_COUNTRY, 'type'=>'label', 'align'=>'center', 'width'=>'', 'height'=>'', 'maxlength'=>''),
				'products_amount' 		=> array('title'=>_PRODUCTS, 'type'=>'label', 'align'=>'center', 'width'=>'', 'height'=>'', 'maxlength'=>''),
				'payment_type'          => array('title'=>_METHOD, 'type'=>'enum',  'align'=>'center', 'width'=>'', 'sortable'=>true, 'nowrap'=>'', 'visible'=>'', 'source'=>$this->arr_payment_types),
				'total_price'     		=> array('title'=>_TOTAL_PRICE, 'type'=>'label', 'align'=>'right', 'width'=>'', 'height'=>'', 'maxlength'=>'', 'sort_by'=>'total_price', 'sort_type'=>'numeric', 'format'=>'currency', 'format_parameter'=>$this->currency_format.'|2'),
				'symbol'          		=> array('title'=>'', 'type'=>'label', 'align'=>'left', 'width'=>'', 'height'=>'', 'maxlength'=>''),
				'status' 		  		=> array('title'=>_STATUS, 'type'=>'enum',  'align'=>'center', 'width'=>'', 'sortable'=>true, 'nowrap'=>'', 'visible'=>'', 'source'=>$this->arr_statuses),
				'link_order_description' => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'', 'height'=>'', 'maxlength'=>''),
				'link_order_invoice'     => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'', 'height'=>'', 'maxlength'=>''),
				'link_admin_order_edit'  => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'', 'height'=>'', 'maxlength'=>''),
			);						
		}
		
		//---------------------------------------------------------------------- 
		// ADD MODE
		//---------------------------------------------------------------------- 
		// define add mode fields
		$this->arrAddModeFields = array(

		);

		//---------------------------------------------------------------------- 
		// EDIT MODE
		//---------------------------------------------------------------------- 
		$this->EDIT_MODE_SQL = 'SELECT
								'.$this->tableName.'.'.$this->primaryKey.',
								'.$this->tableName.'.order_number,
								'.$this->tableName.'.order_number as order_number_view,
								'.$this->tableName.'.order_description,
								'.$this->tableName.'.order_price,
								'.$this->tableName.'.shipping_fee,
								'.$this->tableName.'.delivery_type,
								'.$this->tableName.'.vat_fee,
								'.$this->tableName.'.total_price,
								'.$this->tableName.'.currency,
								'.$this->tableName.'.products_amount,
								'.$this->tableName.'.customer_id,
								'.$this->tableName.'.cc_type,
								'.$this->tableName.'.cc_holder_name,
								IF(
									LENGTH(AES_DECRYPT('.$this->tableName.'.cc_number, "'.PASSWORDS_ENCRYPT_KEY.'")) = 4,
									CONCAT("...", AES_DECRYPT('.$this->tableName.'.cc_number, "'.PASSWORDS_ENCRYPT_KEY.'"), " ('._CLEANED.')"),
									AES_DECRYPT('.$this->tableName.'.cc_number, "'.PASSWORDS_ENCRYPT_KEY.'")
								) as mod_cc_number,								
								'.$this->tableName.'.cc_cvv_code,
								'.$this->tableName.'.cc_expires_month,
								'.$this->tableName.'.cc_expires_year,
								IF('.$this->tableName.'.cc_expires_month != "", CONCAT('.$this->tableName.'.cc_expires_month, "/", '.$this->tableName.'.cc_expires_year), "") as mod_cc_expires_date,
								'.$this->tableName.'.transaction_number,
								'.$this->tableName.'.payment_date,
								'.$this->tableName.'.payment_type,
								'.$this->tableName.'.payment_method,
								'.$this->tableName.'.status,
								'.$this->tableName.'.status_changed,
								'.$this->tableName.'.shipping_provider,
								'.$this->tableName.'.shipping_id,
								'.$this->tableName.'.shipping_date,
								'.$this->tableName.'.received_date,
								'.$this->tableName.'.units_updated,
								'.$this->tableName.'.additional_info
							FROM '.$this->tableName.'
								INNER JOIN '.TABLE_CURRENCIES.' ON '.$this->tableName.'.currency = '.TABLE_CURRENCIES.'.code
								LEFT OUTER JOIN '.TABLE_CUSTOMERS.' ON '.$this->tableName.'.customer_id = '.TABLE_CUSTOMERS.'.id
							';		

		if($this->customer_id != ''){
			$WHERE_CLAUSE = 'WHERE '.$this->tableName.'.is_admin_order = 0 AND
								   '.$this->tableName.'.status = 3 AND
								   '.$this->tableName.'.customer_id = '.$this->customer_id.' AND
			                       '.$this->tableName.'.id = _RID_';
		}else{
			$WHERE_CLAUSE = 'WHERE '.$this->tableName.'.id = _RID_';
		}
		$this->EDIT_MODE_SQL = $this->EDIT_MODE_SQL.$WHERE_CLAUSE;

		// prepare trigger
		$sql = 'SELECT
		            status,
					IF(TRIM(cc_number) = "" OR LENGTH(AES_DECRYPT(cc_number, "'.PASSWORDS_ENCRYPT_KEY.'")) <= 4, "hide", "show") as cc_number_trigger
				FROM '.$this->tableName.' WHERE id = '.(int)$rid;
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY, FETCH_ASSOC);
		if($result[1] > 0){
			$cc_number_trigger = $result[0]['cc_number_trigger'];
			$status_trigger = $result[0]['status'];
		}else{
			$cc_number_trigger = 'hide';
			$status_trigger = '0';
		}		

		// define edit mode fields
		if($customer_id != ''){
			$this->arrEditModeFields = array(
				'order_number_view' => array('title'=>_ORDER_NUMBER, 'type'=>'label'),
				'status_changed'    => array('title'=>'', 'type'=>'hidden', 'required'=>true, 'readonly'=>false, 'default'=>date('Y-m-d H:i:s')),
				'status'  		    => array('title'=>_STATUS, 'type'=>'enum', 'width'=>'', 'required'=>true, 'readonly'=>false, 'source'=>$arr_statuses_customer_edit),
                'received_date' 	=> array('title'=>_RECEIVED_DATE, 'type'=>'date',     'required'=>true, 'readonly'=>false, 'unique'=>false, 'visible'=>true, 'default'=>date('Y-m-d'), 'validation_type'=>'date', 'format'=>'date', 'format_parameter'=>$date_format, 'min_year'=>'1', 'max_year'=>'1'),
				'order_number'      => array('title'=>'', 'type'=>'hidden',   'required'=>false, 'default'=>''),
				'customer_id'       => array('title'=>'', 'type'=>'hidden',   'required'=>false, 'default'=>''),
				'units_updated'     => array('title'=>'', 'type'=>'hidden',   'required'=>false, 'default'=>''),
			);
		}else{
			$status_readonly = ($status_trigger == '6') ? true : false;
			if($status_trigger >= '2' && $status_trigger <= '6'){
				$ind = $status_trigger;
				while($ind--) unset($arr_statuses_edit[$ind]);
				$status_source = $arr_statuses_edit;
			}else{
				$status_source = $arr_statuses_edit_cut;
			}			
			
			$this->arrEditModeFields = array(
				'order_number_view' => array('title'=>_ORDER_NUMBER, 'type'=>'label'),
				'customer_id'       => array('title'=>'', 'type'=>'hidden',   'required'=>false, 'default'=>''),
				'payment_type'       => array('title'=>_PAYMENT_TYPE, 'type'=>'enum', 'width'=>'', 'required'=>true, 'readonly'=>true, 'default'=>'', 'source'=>$this->arr_payment_types, 'default_option'=>'', 'unique'=>false, 'javascript_event'=>''),
				'payment_method'     => array('title'=>_PAYMENT_METHOD, 'type'=>'enum', 'width'=>'', 'required'=>true, 'readonly'=>true, 'default'=>'', 'source'=>$this->arr_payment_methods, 'default_option'=>'', 'unique'=>false, 'javascript_event'=>''),
				'status_changed'    => array('title'=>'', 'type'=>'hidden', 'required'=>true, 'readonly'=>false, 'default'=>date('Y-m-d H:i:s')),
				'status'  		    => array('title'=>_STATUS, 'type'=>'enum', 'width'=>'', 'required'=>true, 'readonly'=>$status_readonly, 'source'=>$status_source, 'javascript_event'=>'onchange="Status_OnChange(this.value);"'),
				'shipping_provider' => array('title'=>_SHIPPING_PROVIDER, 'type'=>'textbox',  'width'=>'210px', 'required'=>false, 'readonly'=>true, 'validation_type'=>'text'),
				'shipping_id' 		=> array('title'=>_SHIPPING_ID, 'type'=>'textbox',  'width'=>'210px', 'required'=>false, 'readonly'=>true, 'validation_type'=>'text'),
				'shipping_date' 	=> array('title'=>_SHIPPING_DATE, 'type'=>'date',  'required'=>false, 'readonly'=>true, 'unique'=>false, 'visible'=>true, 'default'=>date('Y-m-d'), 'validation_type'=>'date', 'format'=>'date', 'format_parameter'=>$date_format, 'min_year'=>'1', 'max_year'=>'1'),				
				'received_date' 	=> array('title'=>_RECEIVED_DATE, 'type'=>'date',  'required'=>false, 'readonly'=>true, 'unique'=>false, 'visible'=>true, 'default'=>date('Y-m-d'), 'validation_type'=>'date', 'format'=>'date', 'format_parameter'=>$date_format, 'min_year'=>'1', 'max_year'=>'1'),
				'order_number'      => array('title'=>'', 'type'=>'hidden',   'required'=>false, 'default'=>''),
				'units_updated'     => array('title'=>'', 'type'=>'hidden',   'required'=>false, 'default'=>''),

				'cc_type' 			=> array('title'=>_CREDIT_CARD_TYPE, 'type'=>'label'),
				'cc_holder_name'    => array('title'=>_CREDIT_CARD_HOLDER_NAME, 'type'=>'label'),
				'mod_cc_number' 	  => array('title'=>_CREDIT_CARD_NUMBER, 'type'=>'label', 'post_html'=>(($cc_number_trigger == 'show') ? '&nbsp;[ <a href="javascript:void(0);" onclick=\'if(confirm("'._PERFORM_OPERATION_COMMON_ALERT.'")) __mgDoPostBack("'.$this->tableName.'", "clean_credit_card", "'.$rid.'")\'>'._REMOVE.'</a> ]' : '')),
				'mod_cc_expires_date' => array('title'=>_EXPIRES, 'type'=>'label'),
				'cc_cvv_code' 		=> array('title'=>_CVV_CODE, 'type'=>'label'),
				'additional_info' 	=> array('title'=>_ADDITIONAL_INFO, 'type'=>'textarea', 'width'=>'390px', 'height'=>'90px', 'editor_type'=>'simple', 'readonly'=>false, 'default'=>'', 'required'=>false, 'validation_type'=>'', 'maxlength'=>'2048', 'validation_maxlength'=>'2048', 'unique'=>false),
			);
		}
		
		//---------------------------------------------------------------------- 
		// DETAILS MODE
		//----------------------------------------------------------------------		
		$this->DETAILS_MODE_SQL = $this->VIEW_MODE_SQL.$WHERE_CLAUSE;
		$this->arrDetailsModeFields = array(

			'order_number'  	 => array('title'=>_ORDER, 'type'=>'label'),
			'order_description'  => array('title'=>_DESCRIPTION, 'type'=>'label'),
			'order_price'  		 => array('title'=>_ORDER_PRICE, 'type'=>'label'),
			'vat_fee'       	 => array('title'=>_VAT, 'type'=>'label'),
			'delivery_type'      => array('title'=>_DELIVERY_TYPE, 'type'=>'label'),
			'shipping_fee'       => array('title'=>_SHIPPING_FEE, 'type'=>'label'),
			'total_price'  		 => array('title'=>_TOTAL_PRICE, 'type'=>'label'),
			'currency'  		 => array('title'=>_CURRENCY, 'type'=>'label'),
			'products_amount'    => array('title'=>_PRODUCTS, 'type'=>'label'),
			'customer_name'      => array('title'=>_CUSTOMER, 'type'=>'label'),
			'transaction_number' => array('title'=>_TRANSACTION, 'type'=>'label'),
			'payment_date'  	 => array('title'=>_DATE, 'type'=>'label', 'format'=>'date', 'format_parameter'=>$datetime_format),
			'payment_type'       => array('title'=>_PAYMENT_TYPE, 'type'=>'enum', 'source'=>$this->arr_payment_types),
			'payment_method'     => array('title'=>_PAYMENT_METHOD, 'type'=>'enum', 'source'=>$this->arr_payment_methods),
			//'coupon_number'  	 => array('title'=>'', 'type'=>'label'),
			//'discount_campaign_id' => array('title'=>'', 'type'=>'label'),
			'status'  	         => array('title'=>_STATUS, 'type'=>'enum', 'source'=>$this->arr_statuses),
			'status_changed'     => array('title'=>_STATUS_CHANGED, 'type'=>'label', 'format'=>'date', 'format_parameter'=>$datetime_format),
		);
	}
	
	//==========================================================================
    // Class Destructor
	//==========================================================================
    function __destruct()
	{
		// echo 'this object has been destroyed';
    }	
	
	/**
	 *	Draws order invoice
	 * 		@param $rid
	 * 		@param $text_only
	 * 		@param $draw
	 */
	public function DrawOrderInvoice($rid, $text_only = false, $draw = true)
	{
		global $objSiteDescription;
		global $objSettings;
		
		$oid = isset($rid) ? (int)$rid : '0';
		$language_id = Languages::GetDefaultLang();
		$output = '';
		$content = '';
		
		$sql = 'SELECT
					'.$this->tableName.'.*,
					IF('.$this->tableName.'.status_changed = \'0000-00-00 00:00:00\', \'\', '.$this->tableName.'.status_changed) as status_changed,
					IF('.$this->tableName.'.is_admin_order = 0, cust.user_name, \''._ADMIN.'\') as customer_name,
					'.$this->tableName.'.order_price,
					'.$this->tableName.'.vat_fee,
					'.$this->tableName.'.shipping_fee,
					'.$this->tableName.'.delivery_type,
					'.$this->tableName.'.total_price,
					DATE_FORMAT('.$this->tableName.'.created_date, \''.$this->sqlFieldDatetimeFormat.'\') as mod_created_date,
					DATE_FORMAT('.$this->tableName.'.payment_date, \''.$this->sqlFieldDatetimeFormat.'\') as mod_payment_date,
					cur.symbol,
					cur.symbol_placement,
					CONCAT("<a href=\"index.php?'.$this->page.'&mg_action=description&oid=", '.$this->tableName.'.'.$this->primaryKey.', "\">", "'._DESCRIPTION.'", "</a>") as link_order_description,
					cust.first_name,
					cust.last_name,					
					cust.email as customer_email,
					cust.company as customer_company,
					cust.b_address,
					cust.b_address_2,
					cust.b_city,
					cust.b_state,
					cust.b_zipcode, 
					cntr.name as country_name,
					camp.campaign_name,
					camp.discount_percent 
				FROM '.$this->tableName.'
					INNER JOIN '.TABLE_CURRENCIES.' cur ON '.$this->tableName.'.currency = cur.code
					LEFT OUTER JOIN '.TABLE_CUSTOMERS.' cust ON '.$this->tableName.'.customer_id = cust.id
					LEFT OUTER JOIN '.TABLE_COUNTRIES.' cntr ON cust.b_country = cntr.abbrv 
					LEFT OUTER JOIN '.TABLE_CAMPAIGNS.' camp ON '.$this->tableName.'.discount_campaign_id = camp.id
				WHERE
					'.$this->tableName.'.'.$this->primaryKey.' = '.(int)$oid;
				if($this->customer_id != ''){
					$sql .= ' AND '.$this->tableName.'.customer_id = '.(int)$this->customer_id;
				}
					
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY, FETCH_ASSOC);
		if($result[1] > 0){

			$part = '<table width="100%" dir="'.Application::Get('lang_dir').'" border="0">';
			if($text_only && ModulesSettings::Get('shopping_cart', 'mode') == 'TEST MODE'){
				$part .= '<tr><td colspan="2"><div style="text-align:center;padding:10px;color:#a60000;border:1px dashed #a60000;width:100px">TEST MODE!</div></td></tr>';
			}
			$part .= '<tr>';
			$part .= '<td valign="top">';	
			$part .= '<h3>'._CUSTOMER_DETAILS.'</h3><br />';
			$part .= _FIRST_NAME.': '.$result[0]['first_name'].'<br />';
			$part .= _LAST_NAME.': '.$result[0]['last_name'].'<br />';
			$part .= _EMAIL_ADDRESS.': '.$result[0]['customer_email'].'<br />';
			$part .= _COMPANY.': '.$result[0]['customer_company'].'<br />';
			$part .= _ADDRESS.': '.$result[0]['b_address'].' '.$result[0]['b_address_2'].'<br />';
			$part .= $result[0]['b_city'].' '.$result[0]['b_state'].'<br />';
			$part .= $result[0]['country_name'].' '.$result[0]['b_zipcode'];
			$part .= '</td>';
			$part .= '<td valign="top" align="'.Application::Get('defined_right').'">';
			$part .= '<h3>'._COMPANY.': '.$objSiteDescription->GetParameter('header_text').'</h3><br />';
			$part .= _EMAIL_ADDRESS.': '.$objSettings->GetParameter('admin_email').'<br />';
			$part .= _ORDER_DATE.': '.$result[0]['mod_payment_date'].'<br />';
			$part .= '</td>';
			$part .= '</tr>';
			$part .= '</table><br />';

			$part .= '<table width="100%" dir="'.Application::Get('lang_dir').'" border="0" cellspacing="0" cellpadding="3" style="border:1px solid #d1d2d3">';
			$part .= '<tr style="background-color:#e1e2e3;font-weight:bold;font-size:13px;"><th align="left" colspan="2">&nbsp;<b>'._ORDER_DETAILS.'</b></th></tr>';
			$part .= '<tr><td width="25%">'._ORDER.': </td><td>'.$result[0]['order_number'].'</td></tr>';
			$part .= '<tr><td>'._DESCRIPTION.': </td>	<td>'.$result[0]['order_description'].'</td></tr>';
			$part .= '<tr><td>'._TRANSACTION.': </td>	<td>'.$result[0]['transaction_number'].'</td></tr>';
			$part .= '<tr><td>'._DATE_CREATED.': </td>	<td>'.$result[0]['mod_created_date'].'</td></tr>';
			$part .= '<tr><td>'._PAYMENT_DATE.': </td>	<td>'.$result[0]['mod_payment_date'].'</td></tr>';
			$part .= '<tr><td>'._PAYMENT_TYPE.': </td>  <td>'.$this->arr_payment_types[$result[0]['payment_type']].'</td></tr>';
			$part .= '<tr><td>'._PAYMENT_METHOD.': </td><td>'.$this->arr_payment_methods[$result[0]['payment_method']].'</td></tr>';
			$part .= '<tr><td>'._PRODUCTS.': </td>	 	<td>'.$result[0]['products_amount'].'</td></tr>';
			$part .= '<tr><td>'._ORDER_PRICE.': </td>	<td>'.Currencies::PriceFormat($result[0]['order_price'], $result[0]['symbol'], $result[0]['symbol_placement'], $this->currency_format).'</td></tr>';
			$part .= '<tr><td>'._VAT.': </td>		 	<td>'.Currencies::PriceFormat($result[0]['vat_fee'], $result[0]['symbol'], $result[0]['symbol_placement'], $this->currency_format).' ('.Currencies::PriceFormat($result[0]['vat_percent'], '%', 'right', $this->currency_format, $this->GetVatPercentDecimalPoints($result[0]['vat_percent'])).')</td></tr>';
			$part .= '<tr><td>'._DELIVERY_TYPE.': </td>	<td>'.$result[0]['delivery_type'].'</td></tr>';
			$part .= '<tr><td>'._SHIPPING_FEE.': </td>	<td>'.Currencies::PriceFormat($result[0]['shipping_fee'], $result[0]['symbol'], $result[0]['symbol_placement'], $this->currency_format).'</td></tr>';
			$part .= '<tr><td>'._TOTAL.': </td>			<td>'.Currencies::PriceFormat($result[0]['total_price'], $result[0]['symbol'], $result[0]['symbol_placement'], $this->currency_format).'</td></tr>';
			if($result[0]['campaign_name'] != '') $part .= '<tr><td>'._DISCOUNT_CAMPAIGN.': </td><td>'.$result[0]['campaign_name'].' ('.$result[0]['discount_percent'].'%)</td></tr>';
			$part .= '</table><br />';									

			$content = @file_get_contents('html/templates/invoice.tpl');
			if($content){
				$content = str_replace('_TOP_PART_', $part, $content);
				$content = str_replace('_PRODUCTS_LIST_', $this->GetOrderProductsList($oid, $language_id), $content);
				$content = str_replace('_YOUR_COMPANY_NAME_', $objSiteDescription->GetParameter('header_text'), $content);
				$content = str_replace('_ADMIN_EMAIL_', $objSettings->GetParameter('admin_email'), $content);
			}
		}

		$output .= '<div id="divInvoiceContent">'.$content.'</div>';

		if(!$text_only){		
			$output .= '<table width="100%" border="0">';
			$output .= '<tr><td colspan="2">&nbsp;</tr>';
			$output .= '<tr>';
			$output .= '  <td colspan="2" align="left"><input type="button" class="mgrid_button" name="btnBack" value="'._BUTTON_BACK.'" onclick="javascript:appGoTo(\''.$this->page.'\')"></td>';
			$output .= '</tr>';			
			$output .= '</table>';
		}
		
		if($draw) echo $output;
		else return $output;
	}
	
	/**
	 *	Draws order description	
	 * 		@param $rid
	 */
	public function DrawOrderDescription($rid)
	{
		$output = '';
		$content = '';
		$oid = isset($rid) ? (int)$rid : '0';
		$language_id = Languages::GetDefaultLang();
	
		$sql = 'SELECT
					'.$this->tableName.'.'.$this->primaryKey.',
					'.$this->tableName.'.order_number,
					'.$this->tableName.'.order_description,
					'.$this->tableName.'.order_price,
					'.$this->tableName.'.shipping_fee,
					'.$this->tableName.'.delivery_type,
					'.$this->tableName.'.vat_percent,
					'.$this->tableName.'.vat_fee,
					'.$this->tableName.'.total_price,
					'.$this->tableName.'.additional_info,
					'.$this->tableName.'.currency,
					'.$this->tableName.'.products_amount,
					'.$this->tableName.'.customer_id,
					'.$this->tableName.'.cc_type,
					'.$this->tableName.'.cc_holder_name,
					IF(
						LENGTH(AES_DECRYPT('.$this->tableName.'.cc_number, \''.PASSWORDS_ENCRYPT_KEY.'\')) = 4,
						CONCAT(\'...\', AES_DECRYPT('.$this->tableName.'.cc_number, \''.PASSWORDS_ENCRYPT_KEY.'\')),
						AES_DECRYPT('.$this->tableName.'.cc_number, \''.PASSWORDS_ENCRYPT_KEY.'\')
					) as cc_number,								
					CONCAT(\'...\', SUBSTRING(AES_DECRYPT(cc_number, \''.PASSWORDS_ENCRYPT_KEY.'\'), -4)) as cc_number_for_customer,								
					IF(
						LENGTH(AES_DECRYPT('.$this->tableName.'.cc_number, \''.PASSWORDS_ENCRYPT_KEY.'\')) = 4,
						\' ('._CLEANED.')\',
						\'\'
					) as cc_number_cleaned,								
					'.$this->tableName.'.cc_expires_month,
					'.$this->tableName.'.cc_expires_year,
					'.$this->tableName.'.cc_cvv_code, 
					'.$this->tableName.'.transaction_number,
					'.$this->tableName.'.created_date,
					DATE_FORMAT('.$this->tableName.'.created_date, \''.$this->sqlFieldDatetimeFormat.'\') as mod_created_date,
					DATE_FORMAT('.$this->tableName.'.payment_date, \''.$this->sqlFieldDatetimeFormat.'\') as mod_payment_date,
					'.$this->tableName.'.payment_type,
					'.$this->tableName.'.payment_method,
					'.$this->tableName.'.shipping_provider,
					'.$this->tableName.'.shipping_id,
					'.$this->tableName.'.shipping_date,
					'.$this->tableName.'.received_date,
					'.$this->tableName.'.status,
					IF('.$this->tableName.'.status_changed = \'0000-00-00 00:00:00\', \'\', DATE_FORMAT('.$this->tableName.'.status_changed, \''.$this->sqlFieldDatetimeFormat.'\')) as mod_status_changed,
					IF('.$this->tableName.'.is_admin_order = 0, cust.user_name, \'Admin\') as customer_name,
					cur.symbol,
					cur.symbol_placement,
					CONCAT("<a href=\"index.php?'.$this->page.'&mg_action=description&oid=", '.$this->tableName.'.'.$this->primaryKey.', "\">", "'._DESCRIPTION.'", "</a>") as link_order_description,
					camp.campaign_name,
					camp.discount_percent 
				FROM '.$this->tableName.'
					INNER JOIN '.TABLE_CURRENCIES.' cur ON '.$this->tableName.'.currency = cur.code
					LEFT OUTER JOIN '.TABLE_CUSTOMERS.' cust ON '.$this->tableName.'.customer_id = cust.id
					LEFT OUTER JOIN '.TABLE_CAMPAIGNS.' camp ON '.$this->tableName.'.discount_campaign_id = camp.id
				WHERE
					'.$this->tableName.'.'.$this->primaryKey.' = '.(int)$oid;
				if($this->customer_id != ''){
					$sql .=  ' AND '.$this->tableName.'.customer_id = '.(int)$this->customer_id;
				}
					
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY, FETCH_ASSOC);
		if($result[1] > 0){
			$content .= '<table width="100%" dir="'.Application::Get('lang_dir').'" border="0">';
			$content .= '<tr>
							<td width="20%"><b>'._ORDER.': </b></td><td width="30%">'.$result[0]['order_number'].'</td>
							<td><b>'._STATUS.': </b></td><td>'.$this->arr_statuses[$result[0]['status']].'</td>
						</tr>';
			$content .= '<tr>
							<td><b>'._DESCRIPTION.': </b></td><td>'.$result[0]['order_description'].'</td>
							<td><b>'._STATUS_CHANGED.': </b></td><td>'.$result[0]['mod_status_changed'].'</td>
						</tr>';
			$content .= '<tr>
							<td><b>'._CREATED_DATE.': </b></td><td>'.$result[0]['mod_created_date'].'</td>
							<td><b>'._SHIPPING_PROVIDER.': </b></td><td>'.$result[0]['shipping_provider'].'</td>
						</tr>';
			$content .= '<tr>
							<td><b>'._PAYMENT_DATE.': </b></td><td>'.$result[0]['mod_payment_date'].'</td>
							<td><b>'._SHIPPING_ID.': </b></td><td>'.$result[0]['shipping_id'].'</td>
						</tr>';
			$content .= '<tr>
							<td><b>'._TRANSACTION.': </b></td><td>'.$result[0]['transaction_number'].'</td>
							<td><b>'._SHIPPING_DATE.': </b></td><td>'.$result[0]['shipping_date'].'</td>
						</tr>';
			$content .= '<tr>
							<td><b>'._PAYMENT_TYPE.': </b></td><td>'.$this->arr_payment_types[$result[0]['payment_type']].'</td>
							<td><b>'._RECEIVED_DATE.': </b></td><td>'.$result[0]['received_date'].'</td>
						</tr>';
			$content .= '<tr>
							<td><b>'._PAYMENT_METHOD.': </b></td><td>'.$this->arr_payment_methods[$result[0]['payment_method']].'</td>
							<td></td><td></td>
						</tr>';
			$content .= '<tr>
							<td><b>'._PRODUCTS.': </b></td><td>'.$result[0]['products_amount'].'</td>
						</tr>';
			$content .= '<tr>
							<td><b>'._ORDER_PRICE.': </b></td><td>'.Currencies::PriceFormat($result[0]['order_price'], $result[0]['symbol'], $result[0]['symbol_placement'], $this->currency_format).'</td>
							<td colspan="2" rowspan="5" valign="top">
								<b>'._ADDITIONAL_INFO.'</b>:<br />
								'.(($result[0]['additional_info'] != '') ? $result[0]['additional_info'] : '--').'
							</td>							
						</tr>';
			$content .= '<tr><td><b>'._VAT.': </b></td><td>'.Currencies::PriceFormat($result[0]['vat_fee'], $result[0]['symbol'], $result[0]['symbol_placement'], $this->currency_format).' ('.Currencies::PriceFormat($result[0]['vat_percent'], '%', 'right', $this->currency_format, $this->GetVatPercentDecimalPoints($result[0]['vat_percent'])).')</td></tr>';
			$content .= '<tr><td><b>'._DELIVERY_TYPE.': </b></td><td>'.$result[0]['delivery_type'].'</td></tr>';
			$content .= '<tr><td><b>'._SHIPPING_FEE.': </b></td><td>'.Currencies::PriceFormat($result[0]['shipping_fee'], $result[0]['symbol'], $result[0]['symbol_placement'], $this->currency_format).'</td></tr>';
			$content .= '<tr><td><b>'._TOTAL_PRICE.': </b></td><td>'.Currencies::PriceFormat($result[0]['total_price'], $result[0]['symbol'], $result[0]['symbol_placement'], $this->currency_format).'</td></tr>';
			if($result[0]['campaign_name'] != '') $content .= '<tr><td><b>'._DISCOUNT_CAMPAIGN.': </b></td><td>'.$result[0]['campaign_name'].' ('.$result[0]['discount_percent'].'%)</td><td colspan="2"></td></tr>';
			if($this->customer_id == '') $content .= '<tr><td><b>'._CUSTOMER.': </b></td><td>'.$result[0]['customer_name'].'</td><td colspan="2"></td></tr>';
			if($result[0]['payment_type'] == '0'){
				// always show cc info, even if collecting is not requieed
				// $this->collect_credit_card == 'yes' 
				$content .= '<tr><td><b>'._CREDIT_CARD_TYPE.': </b></td><td>'.$result[0]['cc_type'].'</td></tr>';
				$content .= '<tr><td><b>'._CREDIT_CARD_HOLDER_NAME.': </b></td><td>'.$result[0]['cc_holder_name'].'</td></tr>';
				if($this->customer_id == ''){
					$content .= '<tr><td><b>'._CREDIT_CARD_NUMBER.': </b></td><td>'.$result[0]['cc_number'].$result[0]['cc_number_cleaned'].'</td></tr>';
					$content .= '<tr><td><b>'._EXPIRES.': </b></td><td>'.(($result[0]['cc_expires_month'] != '') ? $result[0]['cc_expires_month'].'/'.$result[0]['cc_expires_year'] : '').'</td></tr>';
					$content .= '<tr><td><b>'._CVV_CODE.': </b></td><td>'.$result[0]['cc_cvv_code'].'</td></tr>';				
				}else{
					$content .= '<tr><td><b>'._CREDIT_CARD_NUMBER.': </b></td><td>'.$result[0]['cc_number_for_customer'].'</td></tr>';
				}
			}
			$content .= '<tr><td colspan="4">&nbsp;</tr>';
			$content .= '</table>';			
		}
		$content .= $this->GetOrderProductsList($oid, $language_id);

		$output .= '<div id="divDescriptionContent">'.$content.'</div>';		
		$output .= '<div>';
		$output .= '<br /><input type="button" class="mgrid_button" name="btnBack" value="'._BUTTON_BACK.'" onclick="javascript:appGoTo(\''.$this->page.'\')">';
		$output .= '</div>';			

		echo $output;
	}

	/**
	 *	Before-Deleting record
	 */
	public function BeforeDeleteRecord()
	{
	   // update products count field
	   $oid = MicroGrid::GetParameter('rid');
	   $sql = 'SELECT order_number, status, products_amount FROM '.TABLE_ORDERS.' WHERE id = '.(int)$oid;		
	   $result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY, FETCH_ASSOC);
	   if($result[1] > 0){
		   $this->order_number = $result[0]['order_number'];
		   $this->order_status = $result[0]['status'];
		   $this->order_products = $result[0]['products_amount'];
		   return true;
	   }
	   return false;
	}
	
	/**
	 *	After-Deleting record
	 */
	public function AfterDeleteRecord()
	{
		global $objLogin;
		
		// update products count field
		$sql = 'DELETE FROM '.TABLE_ORDERS_DESCRIPTION.' WHERE order_number = \''.$this->order_number.'\'';		
		if(!database_void_query($sql)){ /* echo 'error!'; */ }
	   
		// update customer orders/products amount
		if($objLogin->IsLoggedInAsCustomer() && $this->order_status > 0){
			$sql = 'UPDATE '.TABLE_CUSTOMERS.' SET orders_count = orders_count - 1, products_count = products_count - '.(int)$this->order_products.' WHERE id = '.(int)$objLogin->GetLoggedID();
			database_void_query($sql);
		}
	}
	
	/**
	 * Trigger method - allows to work with View Mode items
	 */
	protected function OnItemCreated_ViewMode($field_name, &$field_value)
	{
		if($field_name == 'customer_name' && $field_value == '{administrator}'){
			$field_value = _ADMIN;			
		}
    }
	
	/**
	 *	Draws Products of customer
	 */
	public function DrawMyProducts()
	{
		global $objLogin;
		
		$sql = 'SELECT
				ord.order_number,
				DATE_FORMAT(ord.created_date, \''.$this->sqlFieldDatetimeFormat.'\') as mod_created_date,
				DATE_FORMAT(ord.payment_date, \''.$this->sqlFieldDatetimeFormat.'\') as mod_payment_date,
				ord.currency,
				ordd.price,
				prod.id as product_id,
				prodd.name as product_name,
				prod.product_type,
				TIMESTAMPDIFF(HOUR, ord.payment_date, \''.date('Y-m-d H:i:s').'\') as time_diff,
				CASE
					WHEN prod.product_type = 0 THEN \''._GOODS.'\'
					ELSE \''._DIGITAL.'\'
				END as mod_product_type				 
			FROM '.TABLE_ORDERS.' ord
				INNER JOIN '.TABLE_ORDERS_DESCRIPTION.' ordd ON ord.order_number = ordd.order_number
				INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' prodd ON ordd.product_id = prodd.product_id AND prodd.language_id = \''.Application::Get('lang').'\'
				INNER JOIN '.TABLE_PRODUCTS.' prod ON prodd.product_id = prod.id AND prodd.language_id = \''.Application::Get('lang').'\'
			WHERE
				ord.customer_id = '.(int)$this->customer_id.' AND
				(ord.status = 2 OR ord.status = 5)
			ORDER BY ord.id DESC';
				
		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);

		echo '<table width="100%" border="0" cellspacing="0" cellpadding="2" class="mgrid_table">
		<tr>
			<th align="'.Application::Get('defined_left').'">'._PRODUCT.'</th>
			<th align="center">'._ORDER_DATE.'</th>
			<th align="center">'._ORDER_NUMBER.'</th>
			<th align="center">'._PRICE.'</th>
			<th align="center">'._TYPE.'</th>
			<th></th>
		</tr>';

		if($result[1] <= 0){
			echo '<tr>';
			echo '<td colspan="6">'.draw_message(_NO_RECORDS_FOUND, false, true).'</td>';
			echo '</tr>';
		}else{
			for($i=0; $i<$result[1]; $i++){
				echo '<tr>';
				echo '<td>'.$result[0][$i]['product_name'].'</td>';
				echo '<td align="center">'.$result[0][$i]['mod_created_date'].'</td>';
				echo '<td align="center">'.$result[0][$i]['order_number'].'</td>';
				echo '<td align="center">'.$result[0][$i]['currency'].' '.$result[0][$i]['price'].'</td>';
				echo '<td align="center">'.$result[0][$i]['mod_product_type'].'</td>';
				echo '<td align="center">';
				
				if($result[0][$i]['product_type'] == '1'){
					echo '<img src="images/download.png" style="margin-bottom:-5px" width="16px" /> ';
					if($result[0][$i]['time_diff']/24 < DIGITAL_PRODUCT_DOWNLOAD_EXPIRE){
						$download_code = base64_encode(serialize(array('customer'=>$objLogin->GetLoggedID(), 'order'=>$result[0][$i]['order_number'], 'product'=>$result[0][$i]['product_id'])));					
						echo '<a href="javascript:void(0);" onclick="appGoToPage(\'download.php\', \'?dc='.$download_code.'&token='.Application::Get('token').'\')">[ '._DOWNLOAD.' ]</a>';
					}else{
						echo '<span class="no">'._EXPIRED.'</span>';
					}
				}
				echo '</td>';
				echo '</tr>';
			}			
		}
				
		echo '</table>';
	}
	
	/**
	 * Updates units in stock
	 * 		@param $order_number
	 */
	public static function UpdateUnitsInStock($order_number)
	{
		$sql = 'SELECT
					ordd.id,
					ordd.order_number,
					ordd.product_id,
					ordd.amount,
					ordd.price 
				FROM '.TABLE_ORDERS.' ord
					INNER JOIN '.TABLE_ORDERS_DESCRIPTION.' ordd ON ord.order_number = ordd.order_number
				WHERE
					ord.units_updated = 0 AND
					ord.order_number = \''.$order_number.'\'';
		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS);
		if($result[1] > 0){
			for($i=0; $i < $result[1]; $i++){
				$sql = 'UPDATE '.TABLE_PRODUCTS.' SET units = IF(units >= '.(int)$result[0][$i]['amount'].', units - '.(int)$result[0][$i]['amount'].', units) WHERE id='.(int)$result[0][$i]['product_id'];
				database_void_query($sql);
			}					
			$sql = 'UPDATE '.TABLE_ORDERS.' SET units_updated = 1 WHERE order_number = \''.$order_number.'\'';
			database_void_query($sql);
		}
	}
	
	/**
	 * Return units to stock
	 * 		@param $order_number
	 */
	public static function ReturnUnitsToStock($order_number)
	{
		$sql = 'SELECT
					ordd.id,
					ordd.order_number,
					ordd.product_id,
					ordd.amount,
					ordd.price 
				FROM '.TABLE_ORDERS.' ord
					INNER JOIN '.TABLE_ORDERS_DESCRIPTION.' ordd ON ord.order_number = ordd.order_number
				WHERE
					ord.order_number = \''.$order_number.'\'';
		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS);
		if($result[1] > 0){
			for($i=0; $i < $result[1]; $i++){
				$sql = 'UPDATE '.TABLE_PRODUCTS.' SET units = units + '.(int)$result[0][$i]['amount'].' WHERE id='.(int)$result[0][$i]['product_id'];
				database_void_query($sql);
			}					
		}
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
					ordd.order_number,
					prodd.name as product_name,
					ordd.amount,
					ordd.price as unit_price,								
					(ordd.price * ordd.amount) as units_total_price
				FROM '.TABLE_ORDERS_DESCRIPTION.' ordd
					INNER JOIN '.$this->tableName.' ON ordd.order_number = '.$this->tableName.'.order_number
					INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' prodd ON ordd.product_id = prodd.product_id
					LEFT OUTER JOIN '.TABLE_CURRENCIES.' cur ON '.$this->tableName.'.currency = cur.code
					LEFT OUTER JOIN '.TABLE_CUSTOMERS.' cust ON '.$this->tableName.'.customer_id = cust.id
				WHERE
					'.$this->tableName.'.'.$this->primaryKey.' = '.(int)$oid.' AND
					prodd.language_id = \''.$language_id.'\' ';
				if($this->customer_id != ''){
					$sql .= ' AND cust.id = '.(int)$this->customer_id;
				}

		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);

		if($result[1] > 0){
			$output .= '<table width="100%" dir="'.Application::Get('lang_dir').'" border="0" cellspacing="0" cellpadding="3" style="border:1px solid #d1d2d3">';
			$output .= '<tr style="background-color:#e1e2e3;font-weight:bold;font-size:13px;">
				<th align="center"> # </th>
				<th align="left">'._PRODUCT.'</th>				
				<th align="center" width="90px"> '._UNIT_PRICE.' </th>
				<th align="center" width="90px"> '._AMOUNT.' </th>
				<th align="right" width="90px"> '._TOTAL.' </th>
			</tr>';	
			for($i=0; $i < $result[1]; $i++){
				$output .= '<tr>';
				$output .= ' <td align="center" width="40px">'.($i+1).'.</td>';
				$output .= ' <td align="left">'.$result[0][$i]['product_name'].' </td>';				
				$output .= ' <td align="center">'.Currencies::PriceFormat($result[0][$i]['unit_price'], '', '', $this->currency_format).'</td>';
				$output .= ' <td align="center">'.$result[0][$i]['amount'].'</td>';
				$output .= ' <td align="right">'.Currencies::PriceFormat($result[0][$i]['units_total_price'], '', '', $this->currency_format).'</td>';
				$output .= '</tr>';	
			}
			$output .= '</table>';			
		}		
		
		return $output;		
	}	
	
	/**
	 *	Cleans credit card info
	 * 		@param $rid
	 */
	public function CleanCreditCardInfo($rid)
	{
		$sql = 'UPDATE '.$this->tableName.'
				SET
					cc_number = AES_ENCRYPT(SUBSTRING(AES_DECRYPT(cc_number, \''.PASSWORDS_ENCRYPT_KEY.'\'), -4), \''.PASSWORDS_ENCRYPT_KEY.'\'),
					cc_cvv_code = \'\',
					cc_expires_month = \'\',
					cc_expires_year = \'\'
				WHERE '.$this->primaryKey.'='.(int)$rid;
		return database_void_query($sql);		
	}
	
	/**
	 *	Update Payment Date
	 * 		@param $rid
	 */
	public function UpdatePaymentDate($rid)
	{
		$sql = 'UPDATE '.$this->tableName.'
				SET payment_date = \''.date('Y-m-d H:i:s').'\'
				WHERE
					'.$this->primaryKey.'='.(int)$rid.' AND 
					(status = 2 OR status = 4 OR status = 5) AND
					(payment_date = \'\' OR payment_date = \'0000-00-00\')';
		database_void_query($sql);		
	}
	
	/**
	 * Send invoice to customer
	 * 		@param $rid
	 */
	public function SendInvoice($rid)
	{
		global $objSettings;
		
		if(strtolower(SITE_MODE) == 'demo'){
			$this->error = _OPERATION_BLOCKED;
			return false;
		}

		$sql = 'SELECT
					IF(is_admin_order = 1, a.email, c.email) as email,
					IF(is_admin_order = 1, a.preferred_language, c.preferred_language) as preferred_language
				FROM '.TABLE_ORDERS.' o
					LEFT OUTER JOIN '.TABLE_CUSTOMERS.' c ON o.customer_id = c.id
					LEFT OUTER JOIN '.TABLE_ACCOUNTS.' a ON o.customer_id = a.id
				WHERE o.id = '.(int)$rid;		
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[1] > 0){

			$recipient = $result[0]['email'];
			$sender    = $objSettings->GetParameter('admin_email');
			$subject   = 'Invoice #'.$rid;
			$body      = $this->DrawOrderInvoice($rid, true, false);
			$preferred_language = $result[0]['preferred_language'];
			
			send_email_wo_template(
				$recipient,
				$sender,
				$subject,
				$body,
				$preferred_language
			);

			return true;
		}
		
		$this->error = _EMAILS_SENT_ERROR;
		return false;		
	}


	//==========================================================================
    // Static Methods
	//==========================================================================
	/**
	 * Remove expired 'Preparing' orders
	 */
	public static function RemoveExpired()
	{
		$preparing_orders_timeout = (int)ModulesSettings::Get('shopping_cart', 'preparing_orders_timeout');

		if($preparing_orders_timeout > 0){
			$sql = 'DELETE FROM '.TABLE_ORDERS.'
					WHERE status = 0 AND
						  TIMESTAMPDIFF(HOUR, created_date, \''.date('Y-m-d H:i:s').'\') > '.(int)$preparing_orders_timeout;
			return database_void_query($sql);
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
	
}
?>