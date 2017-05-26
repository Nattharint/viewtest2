<?php

/**
 *	Class Delivery
 *  --------------
 *	Description : encapsulates delivery methods and properties
 *	Written by  : ApPHP
 *  Updated	    : 01.05.2012
 *  Usage       : Shopping Cart ONLY
 *
 *	PUBLIC				  	STATIC				 			PRIVATE
 * 	------------------	  	---------------     			---------------
 *	__construct             DrawDeliveryTypesSelectBox
 *	__destruct              GetDeliveryType
 *	                        GetDefaultDelivery
 *	
 **/


class Deliveries extends MicroGrid {
	
	protected $debug = false;
	
	// #001 private $arrTranslations = '';

	//==========================================================================
    // Class Constructor
	//==========================================================================
	function __construct()
	{		
		parent::__construct();

		$this->params = array();
		
		## for standard fields
		if(isset($_POST['name']))   $this->params['name'] = prepare_input($_POST['name']);
		if(isset($_POST['description'])) $this->params['description'] = prepare_input($_POST['description']);
		if(isset($_POST['price']))   $this->params['price'] = prepare_input($_POST['price']);
		if(isset($_POST['priority_order'])) $this->params['priority_order'] = prepare_input($_POST['priority_order']);		
		
		//$this->uPrefix 		= 'prefix_';
		
		$this->primaryKey 	= 'id';
		$this->tableName 	= TABLE_DELIVERIES;
		$this->dataSet 		= array();
		$this->error 		= '';
		$this->formActionURL = 'index.php?admin=mod_shop_delivery_settings';
		$this->actions      = array('add'=>true, 'edit'=>true, 'details'=>true, 'delete'=>true);
		$this->actionIcons  = true;
		$this->allowRefresh = true;
		$this->allowTopButtons = false;

		$this->allowLanguages = false;
		///$this->languageId  	= ($this->params['language_id'] != '') ? $this->params['language_id'] : Languages::GetDefaultLang();
		$this->WHERE_CLAUSE = ''; // WHERE .... / 'WHERE language_id = \''.$this->languageId.'\'';				
		$this->ORDER_CLAUSE = 'ORDER BY priority_order ASC';
		
		$this->isAlterColorsAllowed = true;

		$this->isPagingAllowed = true;
		$this->pageSize = 20;

		$this->isSortingAllowed = true;

		$this->isFilteringAllowed = false;
		// define filtering fields
		$this->arrFilteringFields = array();

		$currency_format = get_currency_format();
		$pre_currency_symbol = ((Application::Get('currency_symbol_place') == 'left') ? Application::Get('currency_symbol') : '');
		$post_currency_symbol = ((Application::Get('currency_symbol_place') == 'right') ? Application::Get('currency_symbol') : '');

		//---------------------------------------------------------------------- 
		// VIEW MODE
		//---------------------------------------------------------------------- 
		$this->VIEW_MODE_SQL = 'SELECT '.$this->primaryKey.',
									name,
									description,
									price,
									priority_order,
									"[ Set Prices ]" as link_countries
								FROM '.$this->tableName;		
		// define view mode fields
		$this->arrViewModeFields = array(
			'name'        	 => array('title'=>_NAME, 'type'=>'label', 'align'=>'left', 'width'=>'20%', 'sortable'=>true, 'nowrap'=>'', 'visible'=>'', 'tooltip'=>'', 'maxlength'=>'30', 'format'=>'', 'format_parameter'=>''),
			'description' 	 => array('title'=>_DESCRIPTION, 'type'=>'label', 'align'=>'left', 'width'=>'', 'sortable'=>true, 'nowrap'=>'', 'visible'=>'', 'tooltip'=>'', 'maxlength'=>'40', 'format'=>'', 'format_parameter'=>''),
			'price'       	 => array('title'=>_DEFAULT_PRICE, 'type'=>'label', 'align'=>'right', 'width'=>'120px', 'sortable'=>true, 'nowrap'=>'', 'visible'=>'', 'tooltip'=>'', 'maxlength'=>'', 'format_parameter'=>'', 'format'=>'currency', 'format_parameter'=>$currency_format.'|2', 'pre_html'=>$pre_currency_symbol, 'post_html'=>$post_currency_symbol),
			'priority_order' => array('title'=>_ORDER, 'type'=>'label', 'align'=>'center', 'width'=>'100px', 'sortable'=>true, 'nowrap'=>'', 'movable'=>true),
			'link_countries' => array('title'=>_COUNTRIES, 'type'=>'link',  'align'=>'center', 'width'=>'110px', 'sortable'=>true, 'nowrap'=>'', 'visible'=>'', 'tooltip'=>'', 'maxlength'=>'', 'format'=>'', 'format_parameter'=>'', 'href'=>'index.php?admin=mod_shop_delivery_countries&delivery_id={id}', 'target'=>''),
		);
		
		//---------------------------------------------------------------------- 
		// ADD MODE
		//---------------------------------------------------------------------- 
		// define add mode fields
		$this->arrAddModeFields = array(		    
			'name' 		     => array('title'=>_NAME, 'type'=>'textbox',  'required'=>true, 'width'=>'210px', 'readonly'=>false, 'maxlength'=>'70', 'default'=>'', 'validation_type'=>'', 'unique'=>false, 'visible'=>true),
			'description'    => array('title'=>_DESCRIPTION, 'type'=>'textarea', 'required'=>false, 'width'=>'310px', 'height'=>'90px', 'editor_type'=>'simple', 'readonly'=>false, 'default'=>'', 'validation_type'=>'', 'maxlength'=>'255', 'validation_maxlength'=>'255', 'unique'=>false),
			'price'          => array('title'=>_DEFAULT_PRICE_PER_ITEM, 'type'=>'textbox',  'width'=>'85px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'11', 'validation_type'=>'float|positive', 'validation_maximum'=>'100000000', 'pre_html'=>$pre_currency_symbol.' ', 'post_html'=>$post_currency_symbol),
			'priority_order' => array('title'=>_ORDER, 'type'=>'textbox',  'width'=>'50px', 'maxlength'=>'2', 'required'=>true, 'readonly'=>false, 'default'=>'0', 'validation_type'=>'numeric|positive'),
		);

		//---------------------------------------------------------------------- 
		// EDIT MODE
		// - Validation Type: alpha|numeric|float|alpha_numeric|text|email|ip_address|password|date
		//   Validation Sub-Type: positive (for numeric and float)
		//   Ex.: 'validation_type'=>'numeric', 'validation_type'=>'numeric|positive'
		// - Validation Max Length: 12, 255... Ex.: 'validation_maxlength'=>'255'
		// - Validation Min Length: 4, 6... Ex.: 'validation_minlength'=>'4'
		// - Validation Max Value: 12, 255... Ex.: 'validation_maximum'=>'99.99'
		//---------------------------------------------------------------------- 
		$this->EDIT_MODE_SQL = 'SELECT
									name,
									description,
									price,
									priority_order
							FROM '.$this->tableName.'
							WHERE '.$this->tableName.'.'.$this->primaryKey.' = _RID_';		
		// define edit mode fields
		$this->arrEditModeFields = array(
			'name' 		     => array('title'=>_NAME, 'type'=>'textbox',  'required'=>true, 'width'=>'210px', 'readonly'=>false, 'maxlength'=>'70', 'default'=>'', 'validation_type'=>'', 'unique'=>false, 'visible'=>true),
			'description'    => array('title'=>_DESCRIPTION, 'type'=>'textarea', 'required'=>false, 'width'=>'310px', 'height'=>'90px', 'editor_type'=>'simple', 'readonly'=>false, 'default'=>'', 'validation_type'=>'', 'maxlength'=>'255', 'validation_maxlength'=>'255', 'unique'=>false),
			'price'          => array('title'=>_DEFAULT_PRICE_PER_ITEM, 'type'=>'textbox',  'width'=>'85px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'11', 'validation_type'=>'float|positive', 'validation_maximum'=>'100000000', 'pre_html'=>$pre_currency_symbol.' ', 'post_html'=>$post_currency_symbol),
			'priority_order' => array('title'=>_ORDER, 'type'=>'textbox',  'width'=>'50px', 'maxlength'=>'2', 'required'=>true, 'readonly'=>false, 'default'=>'0', 'validation_type'=>'numeric|positive'),
		);

		//---------------------------------------------------------------------- 
		// DETAILS MODE
		//----------------------------------------------------------------------
		$this->DETAILS_MODE_SQL = $this->EDIT_MODE_SQL;
		$this->arrDetailsModeFields = array(
			'name' 		   => array('title'=>_NAME, 'type'=>'label'),
			'description'  => array('title'=>_DESCRIPTION, 'type'=>'label', 'format'=>'nl2br'),
			'price'        => array('title'=>_DEFAULT_PRICE_PER_ITEM, 'type'=>'label', 'format'=>'currency', 'format_parameter'=>$currency_format.'|2', 'pre_html'=>$pre_currency_symbol, 'post_html'=>$post_currency_symbol),
			'priority_order' => array('title'=>_ORDER, 'type'=>'label'),
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
	 * Draws delivery types select box
	 * 		@param $items_count
	 * 		@param $draw
	 */
	public static function DrawDeliveryTypesSelectBox($items_count = 0, $draw = true)
	{
		global $objLogin, $objCart;
		
		$currency_format = get_currency_format();		
		$delivery_type = ShoppingCart::GetDeliveryInfo('type');
		
		if($objLogin->IsLoggedInAsCustomer()){				
			$sql = 'SELECT cntry.id
					FROM '.TABLE_CUSTOMERS.' cust
						INNER JOIN '.TABLE_COUNTRIES.' cntry ON cust.b_country = cntry.abbrv AND cntry.is_active = 1
					WHERE cust.id = '.(int)$objLogin->GetLoggedID();
			$result = database_query($sql, DATA_ONLY);
			$country_id = isset($result[0]['id']) ? $result[0]['id'] : '0';
			
			$sql = 'SELECT
						d.id,
						d.name,
						d.description,
						IF(dc.price IS NOT NULL, dc.price, d.price) as delivery_price
					FROM ('.TABLE_DELIVERIES.' d
						LEFT OUTER JOIN '.TABLE_DELIVERY_COUNTRIES.' dc ON d.id = dc.delivery_id AND dc.country_id = '.(int)$country_id.') 
					ORDER BY priority_order ASC';
		}else{
			// prepare default prices
			$sql = 'SELECT
						d.id,
						d.name,
						d.description,
						d.price as delivery_price
					FROM '.TABLE_DELIVERIES.' d
					ORDER BY priority_order ASC';
		}
		
		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS);

		$output = '<select name="delivery_type" onchange="javascript:appGoTo(\'page=shopping_cart\',\'&delivery_type=\'+this.value)">';
		//$options = '<option value="">-- '._DELIVERY_TYPE.' --</option>';
		$options = '';
		for($i = 0; $i < $result[1]; $i++) {
			$options .= '<option value="'.$result[0][$i]['id'].'"';
			if($delivery_type == $result[0][$i]['id']) $options .= ' selected="selected"';
			$options .= '>'.$result[0][$i]['name'].' - '.Currencies::PriceFormat($result[0][$i]['delivery_price'] * Application::Get('currency_rate'), '', '', $currency_format).(($items_count > 1) ? ' x '.$items_count : '').'</option>';
		}
		if($options == '') $options .= '';
		$output .= $options.'</select>';

		if(!$draw) return $output;
		else echo $output;		
	}

	/**
	 * Returns delivery type
	 */
	public static function GetDeliveryType($dtype = '0')
	{
		$sql = 'SELECT id, name, description FROM '.TABLE_DELIVERIES.' WHERE id = '.(int)$dtype;
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[1] == '1') return $result[0]['name'];
		else return _UNKNOWN;
	}
	
	/**
	 * Returns default delivery
	 */
	public static function GetDefaultDelivery()
	{
		$arr_delivery = array('price'=>0, 'type'=>0, 'name'=>'');
		
		$sql = 'SELECT id, price, name, description FROM '.TABLE_DELIVERIES.' ORDER BY priority_order ASC LIMIT 0, 1';
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[1] == '1'){
			$arr_delivery['price'] = $result[0]['price'];
			$arr_delivery['type']  = $result[0]['id'];
			$arr_delivery['name']  = $result[0]['name'];			
		}
		
		return $arr_delivery; 
	}
	
}
?>