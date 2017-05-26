<?php

/**
 *	Class Countries (for Shopping Cart ONLY)
 *  -------------- 
 *  Description : encapsulates countries properties
 *  Updated	    : 26.10.2010
 *	Written by  : ApPHP
 *
 *	PUBLIC:				  	STATIC:				 	PRIVATE:              PROTECTED
 * 	------------------	  	---------------     	---------------       ---------------
 *	__construct				GetAllCountries                               OnItemCreated_ViewMode
 *	__destruct              DrawAllCountries                              OnItemCreated_DetailsMode
 *	UpdateVatValue
 *	BeforeDeleteRecord
 *	AfterInsertRecord
 *	AfterUpdateRecord
 *	AfterDeleteRecord
 *	
 **/


class Countries extends MicroGrid{
	
	protected $debug = false;
	
	protected $countries;
	private $id;
	private $currency_format;
	
	//==========================================================================
    // Class Constructor
	//		@param $id
	//==========================================================================
	function __construct($id = '')
	{
		parent::__construct();

		$this->params = array();
		
		## for standard fields
		if(isset($_POST['name']))           $this->params['name'] = prepare_input($_POST['name']);
		if(isset($_POST['abbrv']))          $this->params['abbrv'] = prepare_input($_POST['abbrv']);
		if(isset($_POST['vat_value']))      $this->params['vat_value'] = prepare_input($_POST['vat_value']);
		if(isset($_POST['is_active']))      $this->params['is_active'] = (int)$_POST['is_active'];
		if(isset($_POST['is_default']))     $this->params['is_default'] = (int)$_POST['is_default'];
		if(isset($_POST['priority_order'])) $this->params['priority_order'] = prepare_input($_POST['priority_order']);

		$this->id = $id;
		if($this->id != ''){
			$sql = 'SELECT
						id, abbrv, name, is_active, is_default, vat_value, priority_order 
					FROM '.TABLE_COUNTRIES.'
					WHERE id = \''.intval($this->id).'\'';
			$this->countries = database_query($sql, DATA_ONLY, FIRST_ROW_ONLY);
		}else{
			$this->countries['id'] = '';
			$this->countries['abbrv'] = '';
			$this->countries['name'] = '';
			$this->countries['vat_value'] = '';
			$this->countries['is_active'] = '';
			$this->countries['is_default'] = '';
			$this->countries['priority_order'] = '';
		}
		
		$default_vat_value = '0';
		$shopping_cart_installed = false;
		if(Modules::IsModuleInstalled('shopping_cart')){
			$shopping_cart_installed = true;
			$vat_value = ModulesSettings::Get('shopping_cart', 'vat_value');
		}

		## for checkboxes 
		//if(isset($_POST['parameter4']))   $this->params['parameter4'] = $_POST['parameter4']; else $this->params['parameter4'] = '0';

		## for images
		//if(isset($_POST['icon'])){
		//	$this->params['icon'] = $_POST['icon'];
		//}else if(isset($_FILES['icon']['name']) && $_FILES['icon']['name'] != ''){
		//	// nothing 			
		//}else if (self::GetParameter('action') == 'create'){
		//	$this->params['icon'] = '';
		//}

		// $this->params['language_id'] 	  = MicroGrid::GetParameter('language_id');
	
		$this->primaryKey 	= 'id';
		$this->tableName 	= TABLE_COUNTRIES;
		$this->dataSet 		= array();
		$this->error 		= '';
		$this->formActionURL = 'index.php?admin=countries_management';
		$this->actions      = array('add'=>true, 'edit'=>true, 'details'=>true, 'delete'=>true);
		$this->actionIcons  = true;
		$this->allowRefresh = true;

		$this->allowLanguages = false;
		//$this->languageId  	= ($this->params['language_id'] != '') ? $this->params['language_id'] : Languages::GetDefaultLang();
		$this->WHERE_CLAUSE = ''; // WHERE .... / 'WHERE language_id = \''.$this->languageId.'\'';				
		$this->ORDER_CLAUSE = 'ORDER BY priority_order DESC, name ASC'; // ORDER BY '.$this->tableName.'.date_created DESC
		
		$this->isAlterColorsAllowed = true;

		$this->isPagingAllowed = true;
		$this->pageSize = 20;

		$this->isSortingAllowed = true;

		$this->isFilteringAllowed = true;

		$arr_activity_types_vm = array('0'=>'<span class=no>'._NO.'</span>', '1'=>'<span class=yes>'._YES.'</span>');
		$arr_default_types_vm = array('0'=>'<span class=gray>'._NO.'</span>', '1'=>'<span class=yes>'._YES.'</span>');

		$arr_default_types = array('0'=>_NO, '1'=>_YES);		
		$arr_activity_types = array('0'=>_NO, '1'=>_YES);				
		// define filtering fields
		$this->arrFilteringFields = array(
			_NAME  => array('table'=>$this->tableName, 'field'=>'name', 'type'=>'text', 'sign'=>'like%', 'width'=>'120px'),
			_ACTIVE => array('table'=>$this->tableName, 'field'=>'is_active', 'type'=>'dropdownlist', 'source'=>$arr_activity_types, 'sign'=>'=', 'width'=>'90px', 'visible'=>true),
		);

		$this->currency_format = get_currency_format();
		$pre_currency_symbol = ((Application::Get('currency_symbol_place') == 'left') ? Application::Get('currency_symbol') : '');
		$post_currency_symbol = ((Application::Get('currency_symbol_place') == 'right') ? Application::Get('currency_symbol') : '');

		//---------------------------------------------------------------------- 
		// VIEW MODE
		//---------------------------------------------------------------------- 
		$this->VIEW_MODE_SQL = 'SELECT '.$this->primaryKey.',
									abbrv,
									name,
									vat_value,
									is_default,
									is_active,
									priority_order
								FROM '.$this->tableName;		
		// define view mode fields
		$this->arrViewModeFields = array(
			'name'  		 => array('title'=>_NAME, 'type'=>'label', 'align'=>'left', 'width'=>'', 'height'=>'', 'maxlength'=>''),
			'abbrv'  		 => array('title'=>_ABBREVIATION, 'type'=>'label', 'align'=>'center', 'width'=>'100px', 'height'=>'', 'maxlength'=>''),
			'is_default'     => array('title'=>_DEFAULT, 'type'=>'enum',  'align'=>'center', 'width'=>'80px', 'sortable'=>true, 'nowrap'=>'', 'visible'=>true, 'source'=>$arr_default_types_vm),
			'is_active'      => array('title'=>_ACTIVE, 'type'=>'enum',  'align'=>'center', 'width'=>'80px', 'sortable'=>true, 'nowrap'=>'', 'visible'=>true, 'source'=>$arr_activity_types_vm),
			'vat_value'      => array('title'=>_VAT, 'type'=>'label', 'align'=>'center', 'width'=>'100px', 'height'=>'', 'maxlength'=>''),
			'priority_order' => array('title'=>_ORDER, 'type'=>'label', 'align'=>'center', 'width'=>'90px', 'height'=>'', 'maxlength'=>''),
		);
		
		//---------------------------------------------------------------------- 
		// ADD MODE
		// Validation Type: alpha|numeric|float|alpha_numeric|text|email
		// Validation Sub-Type: positive (for numeric and float)
		// Ex.: 'validation_type'=>'numeric', 'validation_type'=>'numeric|positive'
		//---------------------------------------------------------------------- 
		// define add mode fields
		$this->arrAddModeFields = array(		    
			'name'  	     => array('title'=>_NAME, 'type'=>'textbox',  'width'=>'210px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'70', 'default'=>'', 'validation_type'=>'text'),
			'abbrv'  	     => array('title'=>_ABBREVIATION, 'type'=>'textbox',  'width'=>'40px', 'required'=>true, 'readonly'=>false, 'unique'=>true, 'maxlength'=>'2', 'default'=>'', 'validation_type'=>'alpha'),
			'is_default'     => array('title'=>_DEFAULT,      'type'=>'enum',     'required'=>true, 'width'=>'90px', 'readonly'=>false, 'default'=>'0', 'source'=>$arr_default_types, 'unique'=>false, 'javascript_event'=>''),
			'is_active'      => array('title'=>_ACTIVE,       'type'=>'enum',     'required'=>true, 'width'=>'90px', 'readonly'=>false, 'default'=>'1', 'source'=>$arr_activity_types, 'unique'=>false, 'javascript_event'=>''),
			'priority_order' => array('title'=>_ORDER, 'type'=>'textbox',  'width'=>'50px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'4', 'default'=>'0', 'validation_type'=>'numeric'),
		);
		if($shopping_cart_installed){
			$this->arrAddModeFields['vat_value'] = array('title'=>_VAT, 'type'=>'textbox', 'width'=>'50px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'5', 'default'=>$default_vat_value, 'validation_type'=>'float|positive', 'validation_maximum'=>'99', 'post_html'=>' %');
		}

		//---------------------------------------------------------------------- 
		// EDIT MODE
		// Validation Type: alpha|numeric|float|alpha_numeric|text|email
		// Validation Sub-Type: positive (for numeric and float)
		// Ex.: 'validation_type'=>'numeric', 'validation_type'=>'numeric|positive'
		//---------------------------------------------------------------------- 
		$this->EDIT_MODE_SQL = 'SELECT
								'.$this->tableName.'.'.$this->primaryKey.',
								'.$this->tableName.'.name,
								'.$this->tableName.'.abbrv,
								'.$this->tableName.'.is_active,
								'.$this->tableName.'.is_default,
								'.$this->tableName.'.vat_value,
								'.$this->tableName.'.priority_order
							FROM '.$this->tableName.'
							WHERE '.$this->tableName.'.'.$this->primaryKey.' = _RID_';		
		// define edit mode fields
		$this->arrEditModeFields = array(		
			'name'  	     => array('title'=>_NAME, 'type'=>'textbox',  'width'=>'210px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'70', 'default'=>'', 'validation_type'=>'text'),
			'abbrv'  	     => array('title'=>_ABBREVIATION, 'type'=>'textbox',  'width'=>'40px', 'required'=>true, 'readonly'=>false, 'unique'=>true, 'maxlength'=>'2', 'default'=>'', 'validation_type'=>'alpha'),
			'is_default'     => array('title'=>_DEFAULT,      'type'=>'enum',     'required'=>true, 'width'=>'90px', 'readonly'=>false, 'default'=>'1', 'source'=>$arr_default_types, 'unique'=>false, 'javascript_event'=>''),
			'is_active'      => array('title'=>_ACTIVE,       'type'=>'enum',     'required'=>true, 'width'=>'90px', 'readonly'=>false, 'default'=>'1', 'source'=>$arr_activity_types, 'unique'=>false, 'javascript_event'=>''),
			'priority_order' => array('title'=>_ORDER, 'type'=>'textbox',  'width'=>'50px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'4', 'default'=>'0', 'validation_type'=>'numeric'),
		);
		if($shopping_cart_installed){
			$this->arrEditModeFields['vat_value'] = array('title'=>_VAT, 'type'=>'textbox',  'width'=>'50px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'5', 'default'=>$default_vat_value, 'validation_type'=>'float|positive', 'validation_maximum'=>'99', 'post_html'=>' %');
		}
		
		//---------------------------------------------------------------------- 
		// DETAILS MODE
		//----------------------------------------------------------------------
		$this->DETAILS_MODE_SQL = $this->EDIT_MODE_SQL;
		$this->arrDetailsModeFields = array(
			'name'  	=> array('title'=>_NAME, 'type'=>'label'),
			'abbrv'  	=> array('title'=>_ABBREVIATION, 'type'=>'label'),
			'is_default' => array('title'=>_DEFAULT, 'type'=>'enum', 'source'=>$arr_default_types_vm),
			'is_active'  => array('title'=>_ACTIVE, 'type'=>'enum', 'source'=>$arr_activity_types_vm),
			'priority_order'  => array('title'=>_ORDER, 'type'=>'label'),
		);
		if($shopping_cart_installed){
			$this->arrDetailsModeFields['vat_value'] = array('title'=>_VAT, 'type'=>'label');
		}
	}
	
	//==========================================================================
    // Class Destructor
	//==========================================================================
    function __destruct()
	{
		// echo 'this object has been destroyed';
    }

	//==========================================================================
    // MicroGrid Methods
	//==========================================================================	
	/**
	 *	Before record deleting
	 */	
	public function BeforeDeleteRecord()
	{
		$record_info = $this->GetInfoByID($this->curRecordId);
		if(isset($record_info['is_active']) && $record_info['is_active'] == 1){
			$sql = 'SELECT COUNT(*) as cnt FROM '.TABLE_COUNTRIES.' WHERE is_active = 1';
			$result = database_query($sql, DATA_ONLY, FIRST_ROW_ONLY);
			if(isset($result['cnt']) && $result['cnt'] <= 1){
				$this->error = _REMOVE_LAST_COUNTRY_ALERT;				
				return false;
			}
		}	
		return true;
	}

    /**
	 * After-Insertion Record
	 */
	public function AfterInsertRecord()
	{
		$is_default = MicroGrid::GetParameter('is_default', false);
		if($is_default == '1'){
			$sql = 'UPDATE '.TABLE_COUNTRIES.' SET is_default = \'0\' WHERE id != '.(int)$this->lastInsertId;
			database_void_query($sql);
			return true;
		}		
	}

    /**
	 * After-Updating Record
	 */
	public function AfterUpdateRecord()
	{
		$sql = 'SELECT id, is_active, is_default FROM '.TABLE_COUNTRIES;
		if($result = database_query($sql, DATA_AND_ROWS, ALL_ROWS)){
			if((int)$result[1] == 1){
				// make last country always be default
				$sql = 'UPDATE '.TABLE_COUNTRIES.' SET is_default = \'1\', is_active = \'1\' WHERE id = '.(int)$result[0][0]['id'];
				database_void_query($sql);
				return true;	
			}else{
				// save all other countries to be not default
				$rid = MicroGrid::GetParameter('rid');
				$is_default = MicroGrid::GetParameter('is_default', false);
				if($is_default == '1'){
					$sql = 'UPDATE '.TABLE_COUNTRIES.' SET is_active = \'1\'  WHERE id = '.(int)$rid;
					database_void_query($sql);
					
					$sql = 'UPDATE '.TABLE_COUNTRIES.' SET is_default = \'0\' WHERE id != '.(int)$rid;
					database_void_query($sql);
					return true;
				}
			}
		}
	    return true;	
	}
	
    /**
	 * After-Deleting Record
	 */
	public function AfterDeleteRecord()
	{
		$sql = 'SELECT id, is_active FROM '.TABLE_COUNTRIES;
		if($result = database_query($sql, DATA_AND_ROWS, ALL_ROWS)){
			if((int)$result[1] == 1){
				// make last country always default and active
				$sql = 'UPDATE '.TABLE_COUNTRIES.' SET is_default = \'1\', is_active = \'1\' WHERE id = '.(int)$result[0][0]['id'];
				database_void_query($sql);
				return true;	
			}
		}
	    return true;	
	}

	/**
	 *	Updates VAT value for all countries 
	 *		@param $value
	 */
	public function UpdateVatValue($value = '0')
	{
		$sql = 'UPDATE '.TABLE_COUNTRIES.' SET vat_value = '.number_format($value, 3, '.', '');
		if(database_void_query($sql)){
			return true;
		}else{
			$this->error = _TRY_LATER;
			return false;
		}				
	}

	/**
	 *	Get all languages array
	 *		@param $order - order clause
	 *      @param $where_clause - where clause
	 */
	public static function GetAllCountries($order = ' priority_order DESC, name ASC', $where_clause = '')
	{
		// Build ORDER BY CLAUSE
		$order_clause = (!empty($order)) ? 'ORDER BY '.$order : '';

		$sql = 'SELECT id, abbrv, name, is_active, is_default, priority_order
				FROM '.TABLE_COUNTRIES.'
				WHERE is_active = 1 '.
				$where_clause.' '.
				$order_clause;			
		
		return database_query($sql, DATA_AND_ROWS);
	}

	/**
	 *	Draw all languages array
	 *		@param $tag_name
	 *		@param $selected_value
	 *		@param $select_default
	 *		@param $draw
	 */
	public static function DrawAllCountries($tag_name = 'b_country', $selected_value = '', $select_default = true, $draw = true)
	{	
		$output  = '<select name="'.$tag_name.'" id="'.$tag_name.'">';
		$output .= '<option value="">-- '._SELECT.' --</option>';		
		$countries = Countries::GetAllCountries('priority_order DESC, name ASC');
		for($i=0; $i < $countries[1]; $i++){
			if($select_default && $countries[0][$i]['is_default'] && empty($selected_value)){
				$selected_state = 'selected="selected"';
			}else if($selected_value == $countries[0][$i]['abbrv']){
				$selected_state = 'selected="selected"';
			}else{
				$selected_state = '';
			}			
			$output .= '<option '.$selected_state.' value="'.$countries[0][$i]['abbrv'].'">'.$countries[0][$i]['name'].'</option>';
		}
		$output .= '</select>';
		
		if($draw) echo $output;
		else return $output;		
	}

	/**
	 * Trigger method - allows to work with View Mode items
	 * 		@param $field_name
	 * 		@param &$field_value
	*/
	protected function OnItemCreated_ViewMode($field_name, &$field_value)
	{
		if($field_name == 'vat_value'){
			if(substr($field_value, -1) == '0') $field_value = number_format($field_value, 2);
			if($field_value == '0'){
				$field_value = '<span class=gray>'.$field_value.'%</span>';
			}else{
				$field_value = $field_value.'%';
			}
		}
	}	

	/**
	 * Trigger method - allows to work with View Mode items
	 * 		@param $field_name
	 * 		@param &$field_value
	*/
	protected function OnItemCreated_DetailsMode($field_name, &$field_value)
	{
		if($field_name == 'vat_value'){
			if(substr($field_value, -1) == '0') $field_value = number_format($field_value, 2);
			if($field_value == '0'){
				$field_value = '<span class=gray>'.$field_value.'%</span>';
			}else{
				$field_value = $field_value.'%';
			}
		}
	}	

}
?>