<?php

/**
 *	CategoriesDescription
 *  -------------- 
 *	Written by  : ApPHP
 *  Updated	    : 08.10.2010
 *	Written by  : ApPHP
 *
 *	PUBLIC:				  	STATIC:				 	PRIVATE:
 * 	------------------	  	---------------     	---------------
 *	__construct
 *	__destruct
 *	
 **/


class CategoriesDescription extends MicroGrid {
	
	protected $debug = false;
	
	//==========================================================================
    // Class Constructor
	//==========================================================================
	function __construct()
	{		
		parent::__construct();
		
		$cid = isset($_GET['cid']) ? (int)$_GET['cid'] : '0';
		$cdid = isset($_GET['cdid']) ? (int)$_GET['cdid'] : '0';
		
		$this->params = array();		
		if(isset($_POST['name']))        $this->params['name'] = prepare_input($_POST['name']);
		if(isset($_POST['description'])) $this->params['description'] = prepare_input($_POST['description']);

		//$default_lang = Languages::GetDefaultLang();
		//$default_currency = Currencies::GetDefaultCurrency();	
		
		
		// for checkboxes
		/// if(isset($_POST['parameter4']))   $this->params['parameter4'] = $_POST['parameter4']; else $this->params['parameter4'] = '0';
		
		//$this->params['language_id'] 	  = MicroGrid::GetParameter('language_id');
	
		$this->primaryKey 	= 'id';
		$this->tableName 	= TABLE_CATEGORIES_DESCRIPTION;
		$this->dataSet 		= array();
		$this->error 		= '';
		$this->formActionURL = 'index.php?admin=mod_catalog_category_description&cid='.(int)$cid.'&cdid='.(int)$cdid;
		$this->actions      = array('add'=>false, 'edit'=>true, 'details'=>true, 'delete'=>false);
		$this->actionIcons  = true;
		$this->isHtmlEncoding = true; 
		
		$this->allowLanguages = false;
		$this->languageId  	= ''; //($this->params['language_id'] != '') ? $this->params['language_id'] : Languages::GetDefaultLang();
		$this->WHERE_CLAUSE = 'WHERE '.$this->tableName.'.category_id = \''.$cdid.'\'';
		$this->ORDER_CLAUSE = 'ORDER BY '.$this->tableName.'.id ASC';
		
		$this->isAlterColorsAllowed = true;
        
		$this->isPagingAllowed = false;
		$this->pageSize = 100;
        
		$this->isSortingAllowed = true;
        
		$this->isFilteringAllowed = false;
		// define filtering fields
		$this->arrFilteringFields = array(
			'price' => array('title'=>_CATEGORY, 'type'=>'text', 'sign'=>'like%', 'width'=>'80px'),
		);
		
		// prepare languages array
		//$total_languages = Languages::GetAllActive();
		//$arr_languages      = array();
		//foreach($total_languages[0] as $key => $val){
		//	$arr_languages[$val['abbreviation']] = $val['lang_name'];
		//}
		

		//---------------------------------------------------------------------- 
		// VIEW MODE
		//---------------------------------------------------------------------- 
		$this->VIEW_MODE_SQL = 'SELECT '.$this->tableName.'.'.$this->primaryKey.',
									'.$this->tableName.'.category_id,
									'.$this->tableName.'.language_id,
									'.$this->tableName.'.name,									
									'.$this->tableName.'.description,
									'.TABLE_LANGUAGES.'.lang_name  
								FROM '.$this->tableName.'
									INNER JOIN '.TABLE_CATEGORIES.' ON '.$this->tableName.'.category_id = '.TABLE_CATEGORIES.'.id
									INNER JOIN '.TABLE_LANGUAGES.' ON '.$this->tableName.'.language_id = '.TABLE_LANGUAGES.'.abbreviation AND '.TABLE_LANGUAGES.'.is_active = 1
								';

		// define view mode fields
		$this->arrViewModeFields = array(
			'name'  	   => array('title'=>_NAME, 'type'=>'label', 'align'=>'left', 'width'=>'200px', 'maxlength'=>''),
			'description'  => array('title'=>_DESCRIPTION, 'type'=>'label', 'align'=>'left', 'width'=>'', 'maxlength'=>'50'),
			'lang_name'    => array('title'=>_LANGUAGE, 'type'=>'label', 'align'=>'center', 'width'=>'120px', 'maxlength'=>''),
		);
		
		//---------------------------------------------------------------------- 
		// ADD MODE
		//---------------------------------------------------------------------- 
		// define add mode fields
		$this->arrAddModeFields = array(
		
		);

		//---------------------------------------------------------------------- 
		// EDIT MODE
		//---------------------------------------------------------------------- 
		$this->EDIT_MODE_SQL = 'SELECT '.$this->tableName.'.'.$this->primaryKey.',
									'.$this->tableName.'.category_id,
									'.$this->tableName.'.language_id,
									'.$this->tableName.'.name,									
									'.$this->tableName.'.description,
									'.TABLE_LANGUAGES.'.lang_name  
								FROM '.$this->tableName.'
									INNER JOIN '.TABLE_CATEGORIES.' ON '.$this->tableName.'.category_id = '.TABLE_CATEGORIES.'.id
									INNER JOIN '.TABLE_LANGUAGES.' ON '.$this->tableName.'.language_id = '.TABLE_LANGUAGES.'.abbreviation AND '.TABLE_LANGUAGES.'.is_active = 1
								WHERE '.$this->tableName.'.'.$this->primaryKey.' = _RID_';		

		// define edit mode fields
		$this->arrEditModeFields = array(
			'lang_name'   => array('title'=>_LANGUAGE, 'type'=>'label'),
			'name' 		  => array('title'=>_NAME, 'type'=>'textbox', 'width'=>'310px', 'required'=>true, 'readonly'=>false, 'default'=>'', 'validation_type'=>'text', 'maxlength'=>'50'),
			'description' => array('title'=>_DESCRIPTION, 'type'=>'textarea', 'width'=>'400px', 'height'=>'100px', 'required'=>false, 'readonly'=>false, 'default'=>'', 'validation_type'=>'text', 'maxlength'=>'255', 'validation_maxlength'=>'255'),
		);

		//---------------------------------------------------------------------- 
		// DETAILS MODE
		//----------------------------------------------------------------------
		$this->DETAILS_MODE_SQL = $this->EDIT_MODE_SQL;
		$this->arrDetailsModeFields = array(
			'lang_name'   => array('title'=>_LANGUAGE, 'type'=>'label'),
			'name'        => array('title'=>_NAME, 'type'=>'label'),
			'description' => array('title'=>_DESCRIPTION, 'type'=>'label'),
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