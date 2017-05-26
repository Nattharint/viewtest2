<?php

/**
 *	Class DeliveryCointries
 *  --------------
 *	Description : encapsulates delivery countries methods and properties
 *	Written by  : ApPHP
 *  Updated	    : 23.07.2011
 *  Usage       : Shopping Cart ONLY
 *
 *	PUBLIC				  	STATIC				 			PRIVATE
 * 	------------------	  	---------------     			---------------
 *	__construct             GetDeliveryType
 *	__destruct              
 *	
 **/


class DeliveryCountries extends MicroGrid {
	
	protected $debug = false;
	
	// #001 private $arrTranslations = '';

	//==========================================================================
    // Class Constructor
	//==========================================================================
	function __construct($delivery_id = '0')
	{		
		parent::__construct();

		$this->params = array();
		
		## for standard fields
		if(isset($_POST['delivery_id'])) $this->params['delivery_id'] = (int)$_POST['delivery_id'];
		if(isset($_POST['country_id']))  $this->params['country_id'] = prepare_input($_POST['country_id']);
		if(isset($_POST['price']))  	 $this->params['price'] = prepare_input($_POST['price']);
		
		//$this->uPrefix 		= 'prefix_';
		
		$this->primaryKey 	= 'id';
		$this->tableName 	= TABLE_DELIVERY_COUNTRIES;
		$this->dataSet 		= array();
		$this->error 		= '';
		$this->formActionURL = 'index.php?admin=mod_shop_delivery_countries&delivery_id='.(int)$delivery_id;
		$this->actions      = array('add'=>true, 'edit'=>true, 'details'=>false, 'delete'=>true);
		$this->actionIcons  = true;
		$this->allowRefresh = true;
		$this->allowTopButtons = false;

		$this->allowLanguages = false;
		///$this->languageId  	= ($this->params['language_id'] != '') ? $this->params['language_id'] : Languages::GetDefaultLang();
		$this->WHERE_CLAUSE = 'WHERE delivery_id = \''.(int)$delivery_id.'\'';				
		$this->ORDER_CLAUSE = 'ORDER BY country_name ASC';
		
		$this->isAlterColorsAllowed = true;

		$this->isPagingAllowed = true;
		$this->pageSize = 20;

		$this->isSortingAllowed = true;

		$this->isFilteringAllowed = false;
		// define filtering fields
		$this->arrFilteringFields = array();

		// prepare countries array		
		$total_countries = Countries::GetAllCountries('name ASC', ' AND id NOT IN(SELECT country_id FROM '.TABLE_DELIVERY_COUNTRIES.' WHERE delivery_id = '.(int)$delivery_id.')');
		$arr_countries = array();
		foreach($total_countries[0] as $key => $val){
			$arr_countries[$val['id']] = $val['name'];
		}

		$currency_format = get_currency_format();
		$pre_currency_symbol = ((Application::Get('currency_symbol_place') == 'left') ? Application::Get('currency_symbol') : '');
		$post_currency_symbol = ((Application::Get('currency_symbol_place') == 'right') ? Application::Get('currency_symbol') : '');

		//---------------------------------------------------------------------- 
		// VIEW MODE
		//---------------------------------------------------------------------- 
		$this->VIEW_MODE_SQL = 'SELECT '.$this->tableName.'.'.$this->primaryKey.',
									'.$this->tableName.'.delivery_id,
									'.$this->tableName.'.country_id,
									'.$this->tableName.'.price,
									'.TABLE_DELIVERIES.'.name as delivery_name,
									'.TABLE_COUNTRIES.'.name as country_name
								FROM '.$this->tableName.'
									INNER JOIN '.TABLE_DELIVERIES.' ON '.$this->tableName.'.delivery_id = '.TABLE_DELIVERIES.'.id
									INNER JOIN '.TABLE_COUNTRIES.' ON '.$this->tableName.'.country_id = '.TABLE_COUNTRIES.'.id AND '.TABLE_COUNTRIES.'.is_active = 1';		
		// define view mode fields
		$this->arrViewModeFields = array(
			'delivery_name' => array('title'=>_DELIVERY_TYPE, 'type'=>'label', 'align'=>'left', 'width'=>'18%', 'sortable'=>true, 'nowrap'=>'', 'visible'=>'', 'tooltip'=>'', 'maxlength'=>'', 'format'=>'', 'format_parameter'=>''),
			'country_name'  => array('title'=>_COUNTRY, 'type'=>'label', 'align'=>'left', 'width'=>'', 'sortable'=>true, 'nowrap'=>'', 'visible'=>'', 'tooltip'=>'', 'maxlength'=>'', 'format'=>'', 'format_parameter'=>''),
			'price'         => array('title'=>_PRICE, 'type'=>'label', 'align'=>'right', 'width'=>'100px', 'maxlength'=>'', 'format'=>'currency', 'format_parameter'=>$currency_format.'|2', 'pre_html'=>$pre_currency_symbol, 'post_html'=>$post_currency_symbol),
			'empty_column'  => array('title'=>'', 'type'=>'label', 'align'=>'right', 'width'=>'20px'),
		);
		
		//---------------------------------------------------------------------- 
		// ADD MODE
		//---------------------------------------------------------------------- 
		// define add mode fields
		$this->arrAddModeFields = array(		    
			'delivery_id'  => array('title'=>'', 'type'=>'hidden', 'required'=>true, 'readonly'=>false, 'default'=>(int)$delivery_id),
			'country_id'   => array('title'=>_COUNTRY, 'type'=>'enum', 'required'=>true, 'width'=>'210px', 'readonly'=>false, 'default'=>'', 'source'=>$arr_countries, 'unique'=>false, 'javascript_event'=>''),
			'price'        => array('title'=>_PRICE, 'type'=>'textbox',  'width'=>'85px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'11', 'validation_type'=>'float|positive', 'validation_maximum'=>'100000000', 'pre_html'=>$pre_currency_symbol.' ', 'post_html'=>$post_currency_symbol),
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
									'.$this->tableName.'.country_id,
									'.$this->tableName.'.delivery_id,
									'.$this->tableName.'.price,
									'.TABLE_DELIVERIES.'.name as delivery_name,
									'.TABLE_COUNTRIES.'.name as country_name
							FROM '.$this->tableName.'
								INNER JOIN '.TABLE_DELIVERIES.' ON '.$this->tableName.'.delivery_id = '.TABLE_DELIVERIES.'.id
								INNER JOIN '.TABLE_COUNTRIES.' ON '.$this->tableName.'.country_id = '.TABLE_COUNTRIES.'.id AND '.TABLE_COUNTRIES.'.is_active = 1
							WHERE '.$this->tableName.'.'.$this->primaryKey.' = _RID_';		
		// define edit mode fields
		$this->arrEditModeFields = array(
			'delivery_name' => array('title'=>_DELIVERY_TYPE, 'type'=>'label'),
			'country_name'  => array('title'=>_COUNTRY, 'type'=>'label'),
			'price'         => array('title'=>_PRICE, 'type'=>'textbox',  'width'=>'85px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'11', 'validation_type'=>'float|positive', 'validation_maximum'=>'100000000', 'pre_html'=>$pre_currency_symbol.' ', 'post_html'=>$post_currency_symbol),
		);

		//---------------------------------------------------------------------- 
		// DETAILS MODE
		//----------------------------------------------------------------------
		$this->DETAILS_MODE_SQL = $this->EDIT_MODE_SQL;
		$this->arrDetailsModeFields = array(
		);

	}
	
	//==========================================================================
    // Class Destructor
	//==========================================================================
    function __destruct()
	{
		// echo 'this object has been destroyed';
    }

}
?>