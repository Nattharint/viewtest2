<?php

/**
 *	Class Products
 *  -------------- 
 *  Description : encapsulates products properties
 *  Updated	    : 01.09.2011
 *  Written by  : ApPHP
 *
 *	PUBLIC:				  	STATIC:				 	PRIVATE:
 * 	------------------	  	---------------     	---------------
 *	__construct				DrawNewProductsBlock	 
 *	__destruct              DrawProducts
 *	BeforeInsertRecord      DrawAllProducts
 *  AfterInsertRecord       DrawFeaturedBlock
 *	BeforeDeleteRecord      DrawFeaturedAll
 *	BeforeUpdateRecord      GetAllProducts  
 *	AfterDeleteRecord       DrawCatalogStatistics
 *	AfterUpdateRecord       DrawProductBlock (private) 
 *	GetInfoByID
 *	DrawProductDescription
 *	
 **/


class Products extends MicroGrid {
	
	protected $debug = false;
	
	// --------------------------------
	private $selected_category_id;
	private $selected_is_active;
	private $currency_format;
	private $inventory_control;	
	private $sqlFieldDateFormat = '';
	
	//==========================================================================
    // Class Constructor
	//==========================================================================
	function __construct()
	{		
		parent::__construct();
		
		global $objSettings;

		$this->params = array();
		if(isset($_POST['sku']))            $this->params['sku'] = prepare_input($_POST['sku']);
		if(isset($_POST['category_id']))    $this->params['category_id'] = prepare_input($_POST['category_id']);
		if(isset($_POST['manufacturer_id']))$this->params['manufacturer_id'] = (int)$_POST['manufacturer_id'];
		if(isset($_POST['product_type']))   $this->params['product_type'] = prepare_input($_POST['product_type']);
		if(isset($_POST['price']))  	    $this->params['price'] = prepare_input($_POST['price']);
		if(isset($_POST['list_price']))  	$this->params['list_price'] = (float)$_POST['list_price'];
		if(isset($_POST['units']))  	    $this->params['units'] = prepare_input($_POST['units']);
		if(isset($_POST['weight']))  	    $this->params['weight'] = prepare_input($_POST['weight']);
		if(isset($_POST['dimensions']))  	$this->params['dimensions'] = prepare_input($_POST['dimensions']);
		if(isset($_POST['is_taxable']))  	$this->params['is_taxable'] = (int)$_POST['is_taxable'];
		if(isset($_POST['is_featured']))  	$this->params['is_featured'] = (int)$_POST['is_featured'];
		if(isset($_POST['date_added']))  	$this->params['date_added'] = prepare_input($_POST['date_added']);
		if(isset($_POST['priority_order'])) $this->params['priority_order'] = prepare_input($_POST['priority_order']);
		$this->params['is_active']          = isset($_POST['is_active']) ? prepare_input($_POST['is_active']) : '0';

		$name = (isset($_POST['descr_name'])) ? prepare_input($_POST['descr_name']) : '';
		$description = (isset($_POST['descr_description'])) ? prepare_input($_POST['descr_description']) : '';
		$default_lang = Languages::GetDefaultLang();
		$default_currency = Currencies::GetDefaultCurrency();
		$this->selected_category_id = '0';
		$this->selected_is_active = '0';
		
		// for checkboxes
		/// if(isset($_POST['parameter4']))   $this->params['parameter4'] = $_POST['parameter4']; else $this->params['parameter4'] = '0';
		
		$this->params['language_id'] 	  = MicroGrid::GetParameter('language_id');
	
		$this->primaryKey 	= 'id';
		$this->tableName 	= TABLE_PRODUCTS;
		$this->dataSet 		= array();
		$this->error 		= '';
		$this->formActionURL = 'index.php?admin=mod_catalog_products';
		$this->actions      = array('add'=>true, 'edit'=>true, 'details'=>true, 'delete'=>true);
		$this->actionIcons  = true;
		$this->allowRefresh = true;
		$this->allowTopButtons = true;
		$this->isHtmlEncoding = true; 		
				
		$this->allowLanguages = false;
		$this->languageId  	= ''; //($this->params['language_id'] != '') ? $this->params['language_id'] : Languages::GetDefaultLang();
		$this->WHERE_CLAUSE = 'WHERE catd.language_id = \''.$default_lang.'\' AND 
									 pd.language_id = \''.$default_lang.'\'';
							
		$this->ORDER_CLAUSE = 'ORDER BY p.priority_order ASC';
		
		$this->isAlterColorsAllowed = true;
        
		$this->isPagingAllowed = true;
		$this->pageSize = 20;
        
		$this->isSortingAllowed = true;
        
		$this->isExportingAllowed = true;
		$this->arrExportingTypes = array('csv'=>true);

		// prepare categories array
		$total_categories = Categories::GetAllExistingCategories();
		$arr_categories = array();
		foreach($total_categories as $key => $val){
			if($val['level'] == '1'){
				$arr_categories[$val['id']] = $val['name'];
			}else if($val['level'] == '2'){
				$arr_categories[$val['id']] = '&nbsp;&nbsp;&bull; '.$val['name'];
			}else if($val['level'] == '3'){
				$arr_categories[$val['id']] = '&nbsp;&nbsp;&nbsp;&nbsp;:: '.$val['name'];
			}
		}

		// prepare manufacturers array
		$objManufacturers = new Manufacturers();
		$total_manufacturers = $objManufacturers->GetAll();
		$arr_manufacturers = array();
		foreach($total_manufacturers[0] as $key => $val){
			$arr_manufacturers[$val['id']] = $val['name'];
		}
		
		// prepare product types array
		$arr_product_types = array('0'=>_TANGIBLE.' (physical)', '1'=>_DIGITAL.' (downloadable)');
		$arr_product_types_view = array('0'=>_TANGIBLE, '1'=>_DIGITAL);
		// prepare taxibility array
		$arr_taxable = array('0'=>_NO, '1'=>_YES);
		$arr_taxable_vm = array('0'=>'<span class=no>'._NO.'</span>', '1'=>'<span class=yes>'._YES.'</span>');
		$arr_featured = array('0'=>_NO, '1'=>_YES);
		$arr_featured_vm = array('0'=>'<span class=no>'._NO.'</span>', '1'=>'<span class=yes>'._YES.'</span>');
		$arr_activity = array('0'=>'<span class=no>'._NO.'</span>', '1'=>'<span class=yes>'._YES.'</span>');

		$this->isFilteringAllowed = true;
		// define filtering fields
		$this->arrFilteringFields = array(
			_PRODUCT => array('table'=>'pd', 'field'=>'name', 'type'=>'text', 'sign'=>'like%', 'width'=>'80px'),
			_CATEGORY => array('table'=>'cat', 'field'=>'id', 'type'=>'dropdownlist', 'source'=>$arr_categories, 'sign'=>'=', 'width'=>'130px'),
			_PRODUCT_TYPE => array('table'=>'p', 'field'=>'product_type', 'type'=>'dropdownlist', 'source'=>$arr_product_types, 'sign'=>'=', 'width'=>''),
			_SKU => array('table'=>'p', 'field'=>'sku', 'type'=>'text', 'sign'=>'like%', 'width'=>'80px'),
		);
		
		$this->currency_format = get_currency_format();
		$pre_currency_symbol = ((Application::Get('currency_symbol_place') == 'left') ? Application::Get('currency_symbol') : '');
		$post_currency_symbol = ((Application::Get('currency_symbol_place') == 'right') ? Application::Get('currency_symbol') : '');
		
		$this->inventory_control = ModulesSettings::Get('shopping_cart', 'inventory_control');
		
		$date_format = get_date_format();

		if($objSettings->GetParameter('date_format') == 'mm/dd/yyyy'){
			$this->sqlFieldDateFormat = '%b %d, %Y';
		}else{
			$this->sqlFieldDateFormat = '%d %b, %Y';
		}
		$this->SetLocale(Application::Get('lc_time_name'));

		//---------------------------------------------------------------------- 
		// VIEW MODE
		//---------------------------------------------------------------------- 
		$this->VIEW_MODE_SQL = 'SELECT p.id,
									p.sku,
									p.category_id,
									p.icon,
									p.icon_thumb,
									p.image1,
									p.image1_thumb,
									p.image2,
									p.image2_thumb,
									p.image3,
									p.image3_thumb,
									p.units,
									p.price,
									p.icon,									
									p.priority_order,
									p.product_type,
									p.is_active,
									catd.name as category_name,
									pd.language_id,
									pd.name,									
									pd.description,
									m.name as manufacturer_name,
									CONCAT("<a href=index.php?admin=mod_catalog_product_description&prodid=", p.id, ">[ ", "'._DESCRIPTION.'", " ]</a>") as link_product_description
								FROM '.$this->tableName.' p
									LEFT OUTER JOIN '.TABLE_CATEGORIES.' cat ON p.category_id = cat.id
									INNER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' catd ON cat.id = catd.category_id
									INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON p.id = pd.product_id
									LEFT OUTER JOIN '.TABLE_MANUFACTURERS.' m ON p.manufacturer_id = m.id
								';

		// define view mode fields
		$this->arrViewModeFields = array(
			'icon_thumb'     => array('title'=>_IMAGE, 'type'=>'image', 'align'=>'left', 'width'=>'44px', 'image_width'=>'40px', 'image_height'=>'30px', 'target'=>'images/products/', 'no_image'=>'no_image.png'),
			'name'  		 => array('title'=>_NAME, 'type'=>'label', 'align'=>'left', 'width'=>'', 'maxlength'=>''),
			'sku'  		     => array('title'=>_SKU, 'type'=>'label', 'align'=>'left', 'width'=>'50px', 'maxlength'=>''),
			'category_name'  => array('title'=>_CATEGORY, 'type'=>'label', 'align'=>'center', 'width'=>'130px', 'maxlength'=>'30'),
			//'manufacturer_name' => array('title'=>_MANUFACTURER, 'type'=>'label', 'align'=>'center', 'width'=>'130px', 'maxlength'=>'30'),
			'product_type'   => array('title'=>_TYPE, 'type'=>'enum',  'align'=>'center', 'width'=>'', 'sortable'=>true, 'nowrap'=>'', 'visible'=>true, 'source'=>$arr_product_types_view),
			'units' 		 => array('title'=>_UNITS, 'type'=>'label', 'align'=>'center', 'width'=>'70px', 'maxlength'=>'30', 'visible'=>(($this->inventory_control == 'yes') ? true : false)),
			'price'          => array('title'=>_PRICE, 'type'=>'label', 'align'=>'right', 'width'=>'80px', 'maxlength'=>'', 'format'=>'currency', 'format_parameter'=>$this->currency_format.'|2', 'pre_html'=>$pre_currency_symbol, 'post_html'=>$post_currency_symbol),
			'priority_order' => array('title'=>_ORDER, 'type'=>'label', 'align'=>'center', 'width'=>'65px', 'maxlength'=>'', 'movable'=>true),
			'is_active'      => array('title'=>_ACTIVE, 'type'=>'enum',  'align'=>'center', 'width'=>'60px', 'sortable'=>true, 'nowrap'=>'', 'visible'=>true, 'source'=>$arr_activity),
			'link_product_description' => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'90px', 'maxlength'=>''),
		);
		
		//---------------------------------------------------------------------- 
		// ADD MODE
		//---------------------------------------------------------------------- 
		// define add mode fields
		$this->arrAddModeFields = array(				
			'separator_general'  => array(
				'separator_info' => array('legend'=>_GENERAL_INFO, 'columns'=>'1'),
				'product_type'      => array('title'=>_PRODUCT_TYPE, 'type'=>'enum',     'required'=>true, 'readonly'=>false, 'width'=>'', 'source'=>$arr_product_types, 'javascript_event'=>'onchange="product_type_OnChange(this.value)"'),
				'product_file'    	=> array('title'=>_PRODUCT_FILE, 'type'=>'file',    'width'=>'210px', 'required'=>false, 'target'=>'downloads/', 'no_image'=>'', 'random_name'=>'false', 'unique'=>false),
				'category_id'       => array('title'=>_CATEGORY, 'type'=>'enum',     'required'=>true, 'readonly'=>false, 'width'=>'210px', 'source'=>$arr_categories),
				'manufacturer_id'   => array('title'=>_MANUFACTURER, 'type'=>'enum',     'required'=>false, 'readonly'=>false, 'width'=>'210px', 'source'=>$arr_manufacturers),
				'price'             => array('title'=>_PRICE, 'type'=>'textbox',  'width'=>'80px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'9', 'validation_type'=>'float|positive', 'pre_html'=>$pre_currency_symbol.' ', 'post_html'=>$post_currency_symbol),
				'list_price'        => array('title'=>_MARKET_PRICE, 'header_tooltip'=>_MARKET_PRICE_TOOLTIP, 'type'=>'textbox',  'width'=>'80px', 'required'=>false, 'readonly'=>false, 'maxlength'=>'9', 'validation_type'=>'float|positive', 'pre_html'=>$pre_currency_symbol.' ', 'post_html'=>$post_currency_symbol),
				'units'  			=> array('title'=>_UNITS_IN_STOCK, 'type'=>'textbox',  'width'=>'70px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'4', 'default'=>'1', 'validation_type'=>'numeric|positive', 'unique'=>false, 'visible'=>(($this->inventory_control == 'yes') ? true : false)),
				'is_featured'       => array('title'=>_FEATURED, 'type'=>'enum', 'required'=>false, 'readonly'=>false, 'width'=>'', 'default'=>'0', 'source'=>$arr_featured),
				//'is_taxable'      => array('title'=>_TAXABLE, 'type'=>'enum', 'required'=>false, 'readonly'=>false, 'width'=>'', 'default'=>'1', 'source'=>$arr_taxable),
				'is_taxable'        => array('title'=>'', 'type'=>'hidden', 'required'=>false, 'readonly'=>false, 'default'=>'1'),
				'is_active'         => array('title'=>_ACTIVE, 'type'=>'checkbox', 'readonly'=>false, 'default'=>'1', 'true_value'=>'1', 'false_value'=>'0', 'unique'=>false),
				'date_added'        => array('title'=>'', 'type'=>'hidden', 'required'=>false, 'readonly'=>false, 'default'=>date('Y-m-d')),
				'priority_order'    => array('title'=>_ORDER, 'type'=>'textbox',  'width'=>'60px', 'maxlength'=>'3', 'required'=>true, 'readonly'=>false, 'default'=>'0', 'validation_type'=>'numeric|positive'),
			),			
			'separator_details'  => array(
				'separator_info' => array('legend'=>_DESCRIPTION, 'columns'=>'1'),
				'sku' 		     => array('title'=>_SKU, 'type'=>'textbox',  'width'=>'110px', 'required'=>false, 'readonly'=>false, 'default'=>'', 'validation_type'=>'text', 'maxlength'=>'40'),
				'descr_name' 	 => array('title'=>_NAME, 'type'=>'textbox',  'width'=>'310px', 'required'=>true, 'readonly'=>false, 'default'=>$name, 'validation_type'=>'text', 'maxlength'=>'50'),
				'descr_description' => array('title'=>_DESCRIPTION, 'type'=>'textarea', 'editor_type'=>'wysiwyg', 'width'=>'480px', 'height'=>'120px', 'required'=>true, 'readonly'=>false, 'default'=>$description, 'maxlength'=>'2048', 'validation_type'=>'text'),
				'weight' 		 => array('title'=>_WEIGHT_IN_LBS, 'type'=>'textbox',  'width'=>'110px', 'required'=>false, 'readonly'=>false, 'default'=>'', 'validation_type'=>'text', 'maxlength'=>'50'),
				'dimensions'     => array('title'=>_DIMENSIONS, 'type'=>'textbox',  'width'=>'170px', 'required'=>false, 'readonly'=>false, 'default'=>'', 'validation_type'=>'text', 'maxlength'=>'50'),
			),
			'separator_images'   => array(
				'separator_info' => array('legend'=>_IMAGES, 'columns'=>'2'),
				'icon'  		 => array('title'=>_ICON_IMAGE,  'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/products/', 'random_name'=>'true', 'thumbnail_create'=>true, 'thumbnail_field'=>'icon_thumb', 'thumbnail_width'=>'115px', 'thumbnail_height'=>'', 'file_maxsize'=>'200k'),
				'image1'  		 => array('title'=>_IMAGE.' #1', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/products/', 'no_image'=>'no_image.png', 'random_name'=>'true', 'unique'=>false, 'image_width'=>'120px', 'image_height'=>'90px', 'thumbnail_create'=>true, 'thumbnail_field'=>'image1_thumb', 'thumbnail_width'=>'115px', 'thumbnail_height'=>'', 'file_maxsize'=>'400k'),
				'image2'  		 => array('title'=>_IMAGE.' #2', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/products/', 'no_image'=>'no_image.png', 'random_name'=>'true', 'unique'=>false, 'image_width'=>'120px', 'image_height'=>'90px', 'thumbnail_create'=>true, 'thumbnail_field'=>'image2_thumb', 'thumbnail_width'=>'115px', 'thumbnail_height'=>'', 'file_maxsize'=>'400k'),
				'image3'  		 => array('title'=>_IMAGE.' #3', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/products/', 'no_image'=>'no_image.png', 'random_name'=>'true', 'unique'=>false, 'image_width'=>'120px', 'image_height'=>'90px', 'thumbnail_create'=>true, 'thumbnail_field'=>'image3_thumb', 'thumbnail_width'=>'115px', 'thumbnail_height'=>'', 'file_maxsize'=>'400k'),
			)
		);

		//---------------------------------------------------------------------- 
		// EDIT MODE
		//---------------------------------------------------------------------- 
		$this->EDIT_MODE_SQL = 'SELECT p.id,
									p.sku,
									p.category_id,
									p.product_type,
									p.price,
									IF(p.list_price = 0, \'\', p.list_price) as list_price,
									p.weight,
									p.dimensions,
									p.is_taxable,
									p.is_featured,
									p.icon,
									p.icon_thumb,
									p.image1,
									p.image1_thumb,
									p.image2,
									p.image2_thumb,
									p.image3,
									p.image3_thumb,
									p.product_file,
									p.priority_order,
									p.units,
									DATE_FORMAT(p.date_added, \''.$this->sqlFieldDateFormat.'\') as date_added,
									p.is_active,
									catd.name as category_name,
									pd.language_id,
									pd.name,
									pd.description,
									m.id as manufacturer_id,
									m.name as manufacturer_name
								FROM '.$this->tableName.' p
									INNER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' catd ON p.category_id = catd.category_id
									INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON p.id = pd.product_id
									LEFT OUTER JOIN '.TABLE_MANUFACTURERS.' m ON p.manufacturer_id = m.id
								WHERE
									pd.language_id = \''.$default_lang.'\' AND
									pd.language_id = \''.$default_lang.'\' AND 
									p.'.$this->primaryKey.' = _RID_';		
							    
		// define edit mode fields
		$this->arrEditModeFields = array(			
			'separator_general'  => array(
				'separator_info' => array('legend'=>_GENERAL_INFO, 'columns'=>'1'),
				'category_id'     => array('title'=>_CATEGORY, 'type'=>'enum', 'required'=>true, 'readonly'=>false, 'width'=>'210px', 'source'=>$arr_categories),
				'manufacturer_id' => array('title'=>_MANUFACTURER, 'type'=>'enum', 'required'=>false, 'readonly'=>false, 'width'=>'210px', 'source'=>$arr_manufacturers),
				'product_type'    => array('title'=>_PRODUCT_TYPE, 'type'=>'enum', 'required'=>true, 'readonly'=>false, 'width'=>'', 'source'=>$arr_product_types, 'javascript_event'=>'onchange="product_type_OnChange(this.value)"'),
				'product_file'    => array('title'=>_PRODUCT_FILE, 'type'=>'file', 'width'=>'210px', 'required'=>false, 'target'=>'downloads/', 'no_image'=>'', 'random_name'=>'false', 'unique'=>false),
				'price'           => array('title'=>_PRICE, 'type'=>'textbox',  'width'=>'80px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'9', 'validation_type'=>'float|positive', 'pre_html'=>$pre_currency_symbol.' ', 'post_html'=>$post_currency_symbol),
				'list_price'      => array('title'=>_MARKET_PRICE, 'header_tooltip'=>_MARKET_PRICE_TOOLTIP, 'type'=>'textbox',  'width'=>'80px', 'required'=>false, 'readonly'=>false, 'maxlength'=>'9', 'validation_type'=>'float|positive', 'pre_html'=>$pre_currency_symbol.' ', 'post_html'=>$post_currency_symbol),
				'units'  		  => array('title'=>_UNITS_IN_STOCK, 'type'=>'textbox',  'width'=>'70px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'4', 'default'=>'1', 'validation_type'=>'integer|positive', 'unique'=>false, 'visible'=>(($this->inventory_control == 'yes') ? true : false)),
				'is_featured'     => array('title'=>_FEATURED, 'type'=>'enum', 'required'=>false, 'readonly'=>false, 'width'=>'', 'default'=>'0', 'source'=>$arr_featured),
				//'is_taxable'    => array('title'=>_TAXABLE, 'type'=>'enum', 'required'=>false, 'readonly'=>false, 'width'=>'', 'source'=>$arr_taxable),
				'is_taxable'      => array('title'=>'', 'type'=>'hidden', 'required'=>false, 'readonly'=>false, 'default'=>'1'),			
				'priority_order'  => array('title'=>_ORDER, 'type'=>'textbox',  'width'=>'60px', 'maxlength'=>'3', 'required'=>true, 'readonly'=>false, 'validation_type'=>'numeric|positive'),
				'is_active'       => array('title'=>_ACTIVE, 'type'=>'checkbox', 'readonly'=>false, 'default'=>'1', 'true_value'=>'1', 'false_value'=>'0', 'unique'=>false),
				'date_added'      => array('title'=>_ADDED_TO_CATALOG, 'type'=>'label'),
			),
			'separator_details'  => array(
				'separator_info' => array('legend'=>_DESCRIPTION, 'columns'=>'1'),
				'sku' 		     => array('title'=>_SKU, 'type'=>'textbox',  'width'=>'110px', 'required'=>false, 'readonly'=>false, 'default'=>'', 'validation_type'=>'text', 'maxlength'=>'40'),
				'name'           => array('title'=>_PRODUCT, 'type'=>'label'),
				'description'    => array('title'=>_DESCRIPTION, 'type'=>'label'),			
				'weight' 		 => array('title'=>_WEIGHT_IN_LBS, 'type'=>'textbox',  'width'=>'110px', 'required'=>false, 'readonly'=>false, 'default'=>'', 'validation_type'=>'text', 'maxlength'=>'50'),
				'dimensions'     => array('title'=>_DIMENSIONS, 'type'=>'textbox',  'width'=>'170px', 'required'=>false, 'readonly'=>false, 'default'=>'', 'validation_type'=>'text', 'maxlength'=>'50'),
			),
			'separator_images'   => array(
				'separator_info' => array('legend'=>_IMAGES, 'columns'=>'2'),
				'icon'  		 => array('title'=>_ICON_IMAGE,  'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/products/', 'random_name'=>'true', 'thumbnail_create'=>true, 'thumbnail_field'=>'icon_thumb', 'thumbnail_width'=>'115px', 'thumbnail_height'=>'', 'file_maxsize'=>'200k'),
				'image1'  		 => array('title'=>_IMAGE.' #1', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/products/', 'no_image'=>'no_image.png', 'random_name'=>'true', 'unique'=>false, 'image_width'=>'120px', 'image_height'=>'90px', 'thumbnail_create'=>true, 'thumbnail_field'=>'image1_thumb', 'thumbnail_width'=>'115px', 'thumbnail_height'=>'', 'file_maxsize'=>'400k'),
				'image2'  		 => array('title'=>_IMAGE.' #2', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/products/', 'no_image'=>'no_image.png', 'random_name'=>'true', 'unique'=>false, 'image_width'=>'120px', 'image_height'=>'90px', 'thumbnail_create'=>true, 'thumbnail_field'=>'image2_thumb', 'thumbnail_width'=>'115px', 'thumbnail_height'=>'', 'file_maxsize'=>'400k'),
				'image3'  		 => array('title'=>_IMAGE.' #3', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/products/', 'no_image'=>'no_image.png', 'random_name'=>'true', 'unique'=>false, 'image_width'=>'120px', 'image_height'=>'90px', 'thumbnail_create'=>true, 'thumbnail_field'=>'image3_thumb', 'thumbnail_width'=>'115px', 'thumbnail_height'=>'', 'file_maxsize'=>'400k'),
			)			
		);

		//---------------------------------------------------------------------- 
		// DETAILS MODE
		//----------------------------------------------------------------------
		$this->DETAILS_MODE_SQL = $this->EDIT_MODE_SQL;
		$this->arrDetailsModeFields = array(
			'separator_general'  => array(
				'separator_info' => array('legend'=>_GENERAL_INFO, 'columns'=>'1'),
				'category_name'   => array('title'=>_CATEGORY, 'type'=>'label'),
				'manufacturer_name' => array('title'=>_MANUFACTURER, 'type'=>'label'),
				'product_type'    => array('title'=>_PRODUCT_TYPE, 'type'=>'enum', 'source'=>$arr_product_types),
				'product_file'    => array('title'=>_PRODUCT_FILE, 'type'=>'label'),
				'price'  		  => array('title'=>_PRICE, 'type'=>'label', 'format'=>'currency', 'format_parameter'=>$this->currency_format.'|2', 'pre_html'=>$pre_currency_symbol, 'post_html'=>$post_currency_symbol),			
				'list_price'      => array('title'=>_MARKET_PRICE, 'type'=>'label', 'format'=>'currency', 'format_parameter'=>$this->currency_format.'|2', 'pre_html'=>$pre_currency_symbol, 'post_html'=>$post_currency_symbol),			
				'units'  		  => array('title'=>_UNITS, 'type'=>'label', 'visible'=>(($this->inventory_control == 'yes') ? true : false)),			
				'is_featured'     => array('title'=>_FEATURED, 'type'=>'enum', 'source'=>$arr_featured_vm),
				//'is_taxable'  => array('title'=>_TAXABLE, 'type'=>'enum', 'source'=>$arr_taxable_vm),
				'priority_order'  => array('title'=>_ORDER, 'type'=>'label'),
				'is_active'       => array('title'=>_ACTIVE, 'type'=>'enum', 'source'=>$arr_activity),
				'date_added'      => array('title'=>_ADDED_TO_CATALOG, 'type'=>'label', 'format'=>'date'),
			),
			'separator_details'  => array(
				'separator_info' => array('legend'=>_DESCRIPTION, 'columns'=>'1'),
				'sku' 		     => array('title'=>_SKU, 'type'=>'label'),			
				'name'           => array('title'=>_PRODUCT, 'type'=>'label'),			
				'description'    => array('title'=>_DESCRIPTION, 'type'=>'label'),			
				'weight' 		 => array('title'=>_WEIGHT_IN_LBS, 'type'=>'label'),			
				'dimensions'     => array('title'=>_DIMENSIONS, 'type'=>'label'),			
			),
			'separator_1'   =>array(
				'separator_info' => array('legend'=>_IMAGES, 'columns'=>'2'),
				'icon'  		 => array('title'=>_ICON_IMAGE,  'type'=>'image', 'target'=>'images/products/', 'no_image'=>'no_image.png'),
				'image1'  		 => array('title'=>_IMAGE.' #1', 'type'=>'image', 'target'=>'images/products/', 'no_image'=>'no_image.png'),
				'image2'  		 => array('title'=>_IMAGE.' #2', 'type'=>'image', 'target'=>'images/products/', 'no_image'=>'no_image.png'),
				'image3'  		 => array('title'=>_IMAGE.' #3', 'type'=>'image', 'target'=>'images/products/', 'no_image'=>'no_image.png'),
			)
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
	 *	Before-Insertion operations
	 */
	public function BeforeInsertRecord()
	{
		$name = (isset($_POST['descr_name'])) ? prepare_input($_POST['descr_name']) : '';
		$description = (isset($_POST['descr_description'])) ? prepare_input($_POST['descr_description']) : '';

		if($name == ''){
			$this->error = str_replace('_FIELD_', _NAME, _FIELD_CANNOT_BE_EMPTY);
			$this->errorField = 'descr_name';
			return false;
		}else if($description == ''){
			$this->error = str_replace('_FIELD_', _DESCRIPTION, _FIELD_CANNOT_BE_EMPTY);
			$this->errorField = 'descr_description';
			return false;
		}else if(strlen($description) > 2048){	
			$this->error = str_replace('_FIELD_', '<b>'._DESCRIPTION.'</b>', _FIELD_LENGTH_EXCEEDED);
			$this->error = str_replace('_LENGTH_', 2048, $this->error);
			$this->errorField = 'descr_description';
			return false;
		}
		
		return true;
	}

	/**
	 *	After-Insertion operations
	 */
	public function AfterInsertRecord()
	{
		$name = (isset($_POST['descr_name'])) ? prepare_input($_POST['descr_name']) : '';
		$description = (isset($_POST['descr_description'])) ? prepare_input($_POST['descr_description']) : '';
		$category_id = (isset($_POST['category_id'])) ? (int)$_POST['category_id'] : '0';
	
		// languages array		
		$total_languages = Languages::GetAllActive();
		foreach($total_languages[0] as $key => $val){			
			$sql = 'INSERT INTO '.TABLE_PRODUCTS_DESCRIPTION.'(
						id, product_id, language_id, name, description)
					VALUES(
						NULL, '.$this->lastInsertId.', \''.$val['abbreviation'].'\', \''.$name.'\', \''.mysql_real_escape_string($description).'\'
					)';
			if(!database_void_query($sql)){ /* echo 'error!'; */ }
		}

		Categories::UpdateProductsCount($category_id, '+');
	}

	/**
	 *	Before-Deleting operations
	 */
	public function BeforeDeleteRecord()
	{
		$product_id = MicroGrid::GetParameter('rid');
		
		$sql = 'SELECT category_id FROM '.TABLE_PRODUCTS.' WHERE id = '.(int)$product_id;
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[1] > 0){
			$this->selected_category_id = (int)$result[0]['category_id']; 
		}		
		return true;
	}

	/**
	 *	Post-Deleting operations
	 */
	public function AfterDeleteRecord()
	{
		$rid = MicroGrid::GetParameter('rid');
		$sql = 'DELETE FROM '.TABLE_PRODUCTS_DESCRIPTION.' WHERE product_id = '.(int)$rid;		
		if(database_void_query($sql)){
			// update products count in category
			Categories::UpdateProductsCount($this->selected_category_id, '-');
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 *	Before-Update operations
	 */
	public function BeforeUpdateRecord()
	{
		$product_id = MicroGrid::GetParameter('rid');
		
		$sql = 'SELECT category_id, is_active FROM '.TABLE_PRODUCTS.' WHERE id = '.(int)$product_id;
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[1] > 0){
			$this->selected_category_id = (int)$result[0]['category_id'];
			$this->selected_is_active = (int)$result[0]['is_active'];
		}		
		return true;
	}

	/**
	 * After-Updating operations
	 */
	public function AfterUpdateRecord()
	{
		$category_id = MicroGrid::GetParameter('category_id', false);
		$is_active = MicroGrid::GetParameter('is_active', false);
		
		// update products count in categories
		if($this->selected_category_id != $category_id){
			$remove = $add = true;
			if($this->selected_is_active == '1' && $is_active == ''){
				$add = false;
			}else if($this->selected_is_active == '' && $is_active == '1'){
				$remove = false;
			}else if($this->selected_is_active == '' && $is_active == ''){
				$add = $remove = false;
			}
			if($remove) Categories::UpdateProductsCount($this->selected_category_id, '-');
			if($add) Categories::UpdateProductsCount($category_id, '+');						
		}else if($this->selected_is_active != $is_active){
			Categories::UpdateProductsCount($this->selected_category_id, (($this->selected_is_active == '1' && $is_active == '') ? '-' : '+'));
		}
	}
	
	/**
	 *	Returns info by ID
	 *		@param $product_id
	 */
	public function GetInfoByID($product_id = '')
	{
		$sql = 'SELECT p.id,
				p.category_id,
				p.icon,									
				p.priority_order,									
				catd.name as category_name,
				pd.language_id,
				pd.name,									
				pd.description,
				CONCAT("<a href=\'index.php?admin=mod_catalog_product_description&prodid=", p.id, "\'>", "'._DESCRIPTION.'", "</a>") as link_product_description
			FROM '.$this->tableName.' p
				INNER JOIN '.TABLE_CATEGORIES.' cat ON p.category_id = cat.id
				INNER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' catd ON cat.id = catd.category_id
				INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON p.id = pd.product_id
			WHERE p.id = '.(int)$product_id.'
			LIMIT 0, 1';

		return database_query($sql, DATA_ONLY, FIRST_ROW_ONLY);
	}	
	
	/**
	 *	Draws product description
	 *		@param $product_id
	 */
	public function DrawProductDescription($product_id)
	{		
		if(empty($product_id)) return false;		

		global $objLogin;
		
		$output = '';
		$shopping_cart_installed = false;
		$prices_access_level = 'all';
		$arr_product_types_view = array('0'=>_TANGIBLE_PRODUCTS, '1'=>_DIGITAL_PRODUCTS);

		if(Modules::IsModuleInstalled('shopping_cart')){				
			if(ModulesSettings::Get('shopping_cart', 'is_active') == 'yes') $shopping_cart_installed = true;
			if(ModulesSettings::Get('shopping_cart', 'prices_access_level') == 'registered') $prices_access_level = 'registered';
		}

		//GetAll($order_clause = '', $limit_clause = '') {	
		$sql = 'SELECT p.id,
					p.category_id,
					p.price,
					p.list_price,
					p.units,
					p.weight,
					p.dimensions,
					p.icon,
					p.icon_thumb,
					p.image1,
					p.image1_thumb,
					p.image2,
					p.image2_thumb,
					p.image3,
					p.image3_thumb,					
					p.priority_order,
					p.date_added,
					p.product_type,
					pd.name,									
					pd.description,
					CONCAT("<a href=\'index.php?admin=mod_catalog_product_description&prodid=", p.id, "\'>", "'._DESCRIPTION.'", "</a>") as link_product_description,
					m.id as manufacturer_id,
					m.name as manufacturer_name,
					catd.name as category_name
				FROM '.TABLE_PRODUCTS.' p
					INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON p.id = pd.product_id AND pd.language_id = \''.Application::Get('lang').'\'
					LEFT OUTER JOIN '.TABLE_MANUFACTURERS.' m ON p.manufacturer_id = m.id
					LEFT OUTER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' catd ON catd.category_id = p.category_id AND catd.language_id = \''.Application::Get('lang').'\'
				WHERE
					p.is_active = 1 AND 
					p.id = '.(int)$product_id;

		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);

		if($result[1] > 0){
			$icon_file = ($result[0]['icon'] != '') ? $result[0]['icon'] : 'no_image.png';
			$icon_file_thumb = ($result[0]['icon_thumb'] != '') ? $result[0]['icon_thumb'] : 'no_image.png';			
			
			$output .= '<table class="product_description" border="0" cellpadding="3">
					<tr>
						<td rowspan="2" valign="top">
							<div class="product_description_icon">';
								if($icon_file != 'no_image.png') $output .= '<a href="images/products/'.$icon_file.'" rel="lyteshow_'.$result[0]['id'].'">';
								$output .= '<img align="'.Application::Get('defined_alignment').'" src="images/products/'.$icon_file_thumb.'" width="120px" title="'._CLICK_TO_INCREASE.'" alt="" />';
								if($icon_file != 'no_image.png') $output .= '</a>';
							$output .= '</div>
						</td>
						<td width="90%" valign="top" colspan="2">
							<p><b style="font-size:15px;border-bottom:1px solid #cccccc;">'.$result[0]['name'].'</b></p>						
						</td>
					</tr>
					<tr>						
						<td valign="top">';
							if($result[0]['category_name'] != '') $output .= '<p><b>'._CATEGORY.'</b>: '.prepare_link('category', 'cid', $result[0]['category_id'], '', $result[0]['category_name'], '').'</p>';
							if($result[0]['manufacturer_name'] != '') $output .= '<p><b>'._MANUFACTURER.'</b>: '.prepare_link('manufacturer', 'mid', $result[0]['manufacturer_id'], '', $result[0]['manufacturer_name'], '').'</p>';
							if($result[0]['weight'] != '') $output .= '<p><b>'._WEIGHT_IN_LBS.'</b>: '.$result[0]['weight'].'</p>';
							if($result[0]['dimensions'] != '') $output .= '<p><b>'._DIMENSIONS.'</b>: '.$result[0]['dimensions'].'</p>';
							$output .= '<p><b>'._ADDED_TO_CATALOG.'</b>: '.format_datetime($result[0]['date_added'], get_date_format()).'</p>';
						$output .= '	
						</td>
						<td valign="top" width="30%">';
							if($shopping_cart_installed){
								if($prices_access_level == 'all' || ($prices_access_level == 'registered' && $objLogin->IsLoggedIn())){	
									if($result[0]['list_price'] != 0) $output .= '<p><strike><b>'._MARKET_PRICE.'</b>: '.Currencies::PriceFormat($result[0]['list_price'] * Application::Get('currency_rate'), '', '', $this->currency_format).'</strike></p>';								
									$output .= '<p><b>'._PRICE.'</b>: <span class="price">'.Currencies::PriceFormat($result[0]['price'] * Application::Get('currency_rate'), '', '', $this->currency_format).'</span></p>';
								}
								$output .= '<p><b>'._TYPE.'</b>: '.@$arr_product_types_view[$result[0]['product_type']].'</p>';
								$output .= ($this->inventory_control == 'yes') ? '<p>'.(($result[0]['units'] > 0) ? '<b>'._UNITS_IN_STOCK.'</b>: '.$result[0]['units'] : '<b>'._AVAILABILITY.':</b> <span class=no>'._OUT_OF_STOCK.'</span>').'</p>' : '';
							}
						$output .= '
						</td>
					</tr>
					<tr>
						<td colspan="3">';
							if($result[0]['image1_thumb'] != '' && $result[0]['image1'] != '') $output .= '<a href="images/products/'.$result[0]['image1'].'" rel="lyteshow_'.$result[0]['id'].'" title=""><img align="'.Application::Get('defined_alignment').'" src="images/products/'.$result[0]['image1_thumb'].'" style="margin-right:5px;border:1px solid #f1f2f3;" width="36px" height="27px" title="'._CLICK_TO_INCREASE.'" alt="" /></a>';
							if($result[0]['image2_thumb'] != '' && $result[0]['image2'] != '') $output .= '<a href="images/products/'.$result[0]['image2'].'" rel="lyteshow_'.$result[0]['id'].'" title=""><img align="'.Application::Get('defined_alignment').'" src="images/products/'.$result[0]['image2_thumb'].'" style="margin-right:5px;border:1px solid #f1f2f3;" width="36px" height="27px" title="'._CLICK_TO_INCREASE.'" alt="" /></a>';
							if($result[0]['image3_thumb'] != '' && $result[0]['image3'] != '') $output .= '<a href="images/products/'.$result[0]['image3'].'" rel="lyteshow_'.$result[0]['id'].'" title=""><img align="'.Application::Get('defined_alignment').'" src="images/products/'.$result[0]['image3_thumb'].'" style="margin-right:5px;border:1px solid #f1f2f3;" width="36px" height="27px" title="'._CLICK_TO_INCREASE.'" alt="" /></a>';							
						$output .= '</td>
					</tr>
					<tr>
						<td colspan="3">';
						if($prices_access_level == 'all' || ($prices_access_level == 'registered' && $objLogin->IsLoggedIn())){	
							$output .= '<b>'._PRODUCT_DESCRIPTION.'</b>: '.str_replace("\n", '<br />', stripslashes($result[0]['description'])).'<br />';
							if($shopping_cart_installed && (($result[0]['units'] > 0 && $this->inventory_control == 'yes') || $this->inventory_control == 'no')){
								$output .= '<form action="index.php?page=shopping_cart" name="frmProduct_'.$result[0]['id'].'" method="post">
									'.draw_hidden_field('act', 'add', false).'
									'.draw_hidden_field('prodid', $result[0]['id'], false).'
									'.draw_token_field(false).'
									<div id="add_product_contaner">
										<div class="btn">
											'._ADD.': <input name="amount" id="amount" type="text" value="1" size="5" maxlength="4">
										</div>
										<div class="arrows">
											<img class="arrow_plus" onclick="appPlusMinus(\'amount\',\'+\')" src="images/up.png" title="+">
											<img class="arrow_minus" onclick="appPlusMinus(\'amount\',\'-\')" src="images/down.png" title="-">
										</div>
									</div>
									<br /><br />									
									<input type="submit" class="form_button" value="'._ADD_TO_CART.'" />
								</form>';
							}							
						}
						$output .= '</td>
					</tr>	
					</table>';
			$output .= '';			   
		}else{
			$output .= draw_message(_PRODUCT_NOT_FOUND, false, true);						
		}		
		
		echo $output;
       
	}
	
	////////////////////////////////////////////////////////////////////
	// STATIC METHODS
	///////////////////////////////////////////////////////////////////
    /**
	 *	Draws blocks of new products 
	 */
	public static function DrawNewProductsBlock()
	{
		global $objSettings, $objLogin;
		
		$output = '';
		$currency_format = get_currency_format();

		$shopping_cart_installed = false;
		$prices_access_level = 'all';

		if(Modules::IsModuleInstalled('shopping_cart')){				
			if(ModulesSettings::Get('shopping_cart', 'is_active') == 'yes') $shopping_cart_installed = true;
			if(ModulesSettings::Get('shopping_cart', 'prices_access_level') == 'registered') $prices_access_level = 'registered';
		}
		
		$sql = 'SELECT
				p.id,
				p.category_id,
				p.icon,
				p.icon_thumb,
				p.priority_order,
				p.price,
				p.list_price,
				p.units,
				catd.name as category_name,
				pd.language_id,
				pd.name,									
				pd.description,
				CONCAT("<a href=\"index.php?admin=mod_catalog_product_description&prodid=", p.id, "\">[ ", "'._DESCRIPTION.'", " ]</a>") as link_product_description
			FROM '.TABLE_PRODUCTS.' p
				INNER JOIN '.TABLE_CATEGORIES.' cat ON p.category_id = cat.id
				INNER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' catd ON cat.id = catd.category_id
				INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON p.id = pd.product_id
			WHERE p.is_active = 1
			GROUP BY p.id
			ORDER BY id DESC			
			LIMIT 0, 10';
		
		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS);

		if($result[1] > 0){
			$output .= '<br />';
			$output .= draw_sub_title_bar(_NEW_PRODUCTS, false);
			$output .= '<table id="tblNewProducts">';
			$output .= '<tr>';
			for($i=0; $i < $result[1]; $i++){
				if($i != 0 && $i % 5 == 0) $output .= '</tr><tr>';
				$output .= '<td align="center" valign="top">';				
				$output .= self::DrawProductBlock($result[0][$i], $shopping_cart_installed, $prices_access_level, $currency_format, false);
				$output .= '</td>';
			}
			$output .= '</tr>';
			$output .= '</table>';
		}		
		echo $output; 
	}	

	/**
	 * Draws products in category
	 * 		@param $type_id
	 * 		@param $type
	 */
	public static function DrawProducts($type_id = '', $type = 'category')
	{
		global $objLogin, $objSettings;

		$output = '';		
		$page_size = '10';
		$arr_product_types = array('0'=>'', '1'=>_DIGITAL);
		
		$currency_format = get_currency_format();
		$invert_type = ($type == 'category') ? 'manufacturer' : 'category';
			
		$shopping_cart_installed = false;
		$prices_access_level = 'all';
		
		if(Modules::IsModuleInstalled('shopping_cart')){				
			if(ModulesSettings::Get('shopping_cart', 'is_active') == 'yes') $shopping_cart_installed = true;
			if(ModulesSettings::Get('shopping_cart', 'prices_access_level') == 'registered') $prices_access_level = 'registered';
		}
		if(Modules::IsModuleInstalled('products_catalog')){
			$page_size = ModulesSettings::Get('products_catalog', 'products_per_page');			
		}
		
		$sort = (isset($_GET['sort']) && $_GET['sort'] != '') ? prepare_input($_GET['sort']) : '';
		if($sort != 'name' && $sort != 'price' && $sort != $invert_type){	
			$sort_by = 'p.priority_order';
		}else{
			if($sort == 'name') $sort_by = 'pd.'.$sort;
			else if($sort == $invert_type) $sort_by = 'm.name';
			else $sort_by = 'p.'.$sort;
		}

		$sql_from = TABLE_PRODUCTS.' p
					INNER JOIN '.TABLE_CATEGORIES.' c ON p.category_id = c.id
					INNER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' cd ON c.id = cd.category_id
					INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON p.id = pd.product_id
					LEFT OUTER JOIN '.TABLE_MANUFACTURERS.' m ON p.manufacturer_id = m.id
				WHERE
					p.is_active = 1 AND 
					pd.language_id = \''.Application::Get('lang').'\' AND 
					cd.language_id = \''.Application::Get('lang').'\' 
					'.(($type_id != '' && $type == 'category') ? ' AND c.id = '.(int)$type_id : '').'
					'.(($type_id != '' && $type == 'manufacturer') ? ' AND m.id = '.(int)$type_id : '').'
				ORDER BY '.$sort_by.' ASC';

		// pagination prepare
		$start_row = '0';
		$total_pages = '1';
		pagination_prepare($page_size, $sql_from, $start_row, $total_pages);		

		$sql = 'SELECT p.id,
					p.category_id,
					p.list_price,
					p.price,
					p.icon,
					p.icon_thumb,
					p.image1,
					p.image1_thumb,
					p.image2,
					p.image2_thumb,
					p.image3,
					p.image3_thumb,					
					p.units,
					p.priority_order,
					p.manufacturer_id,
					p.product_type,
					m.name as manufacturer_name,
					pd.language_id,
					pd.name,									
					pd.description,
					CONCAT("<a href=\"index.php?admin=mod_catalog_product_description&prodid=", p.id, "\">[ ", "'._DESCRIPTION.'", " ]</a>") as link_product_description,
					cd.name as category_name
				FROM '.$sql_from.'
				LIMIT '.$start_row.', '.$page_size;
		
		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS);

		if($result[1] > 0){
			$output .= '<table width="99%" border="0" align="center">';
			$output .= '<tr><th colspan="3" nowrap height="5px"></th></tr>
				        <tr><th colspan="3" align="'.Application::Get('defined_right').'" valign="middle">';
			
			$tid = ($type == 'category') ? '&cid='.$type_id : '&mid='.$type_id;
			$output .= _SORT_BY.': 
					<select onchange="javascript:appGoTo(\'page='.$type.'\',\''.$tid.'&sort=\'+this.value)">
						<option value="">-- '._SELECT.' --</option>
						<option value="name" '.(($sort == 'name') ? ' selected="selected"' : '').'>'._NAME.'</option>';
						if($prices_access_level == 'all' || ($prices_access_level == 'registered' && $objLogin->IsLoggedIn())){	
							$output .= '<option value="price" '.(($sort == 'price') ? ' selected="selected"' : '').'>'._PRICE.'</option>';
						}
						$output .= '<option value="'.$invert_type.'" '.(($sort == $invert_type) ? ' selected="selected"' : '').'>'.(($type == 'category') ? _MANUFACTURER : _CATEGORY).'</option>
					</select>
					</th>
				</tr>';						
			$output .= '
				<tr><th colspan="3" nowrap height="5px"></th></tr>
				<tr>
					<th align="center">'._IMAGE.'</th>
					<th>'._ITEM_NAME.'</th>
					<th align="center">'._PRICE.'</th>
				</tr>';			

			for($i=0; $i < $result[1]; $i++){
				$icon_file = ($result[0][$i]['icon'] != '') ? $result[0][$i]['icon'] : 'no_image.png';
				$icon_file_thumb = ($result[0][$i]['icon_thumb'] != '') ? $result[0][$i]['icon_thumb'] : 'no_image.png';
				$output .= '
					<tr><td colspan="3" style="padding:7px;">'.draw_line('no_margin_line', IMAGE_DIRECTORY, false).'</td></tr>					
					<tr valign="top">
						<td width="130px" align="center">
							<div class="product_icon">';
								if($icon_file != 'no_image.png') $output .= '<a href="images/products/'.$icon_file.'" rel="lyteshow_'.$result[0][$i]['id'].'">';
								$output .= '<img src="images/products/'.$icon_file_thumb.'" width="115px" rel="lyteshow_'.$result[0][$i]['id'].'" title="'._CLICK_TO_INCREASE.'" alt="" />';
								if($icon_file != 'no_image.png') $output .= '</a>';
							$output .= '</div>';
							if($result[0][$i]['image1'] != '') $output .= '<a href="images/products/'.$result[0][$i]['image1'].'" rel="lyteshow_'.$result[0][$i]['id'].'" title=""></a>';
							if($result[0][$i]['image2'] != '') $output .= '<a href="images/products/'.$result[0][$i]['image2'].'" rel="lyteshow_'.$result[0][$i]['id'].'" title=""></a>';
							if($result[0][$i]['image3'] != '') $output .= '<a href="images/products/'.$result[0][$i]['image3'].'" rel="lyteshow_'.$result[0][$i]['id'].'" title=""></a>';							
						$output .= '</td>
						<td>';
	
						$link_1 = prepare_link('product', 'prodid', $result[0][$i]['id'], '', $result[0][$i]['name'], '', _CLICK_TO_SEE_DESCR);
						$link_2 = prepare_link('category', 'cid', $result[0][$i]['category_id'], '', $result[0][$i]['category_name'], '', '');
						if($result[0][$i]['manufacturer_name'] != ''){
							$link_3 = prepare_link('manufacturer', 'mid', $result[0][$i]['manufacturer_id'], '', $result[0][$i]['manufacturer_name'], '', '');
						}else{
							$link_3 = _UNKNOWN;
						}
	
						$product_type = ($arr_product_types[$result[0][$i]['product_type']] != '') ? '('.$arr_product_types[$result[0][$i]['product_type']].')' : '';
						if($type == 'category'){
							$output .= '<b>'.$link_1.(($type_id == '') ? ' / '.$link_2 : '').'</b> '.$product_type;
							$output .= '<p><b>'._MANUFACTURER.'</b>: '.$link_3.' </p>';	
						}else{
							$output .= '<b>'.$link_1.(($type_id == '') ? ' / '.$link_3 : '').'</b> '.$product_type;							
							$output .= '<p><b>'._CATEGORY.'</b>: '.$link_2.' </p>';	
						}						
						$output .= '<p>'.strip_tags(stripslashes(substr($result[0][$i]['description'], 0, 400))).'...</p>
						</td>
						<td width="90px" align="center">';
						if($prices_access_level == 'all' || ($prices_access_level == 'registered' && $objLogin->IsLoggedIn())){	
							if($result[0][$i]['list_price'] != 0) $output .= '<strike><b>'.Currencies::PriceFormat($result[0][$i]['list_price'] * Application::Get('currency_rate'), '', '', $currency_format).'</b></strike><br />';
							$output .= '<span class="price"><b>'.Currencies::PriceFormat($result[0][$i]['price'] * Application::Get('currency_rate'), '', '', $currency_format).'</b></span>';
						}						
						$output .= '<br /><p>'.(($result[0][$i]['units'] > 0) ? _UNITS.': '.$result[0][$i]['units'] : '<span class=no>'._OUT_OF_STOCK.'</span>').'</p>';
						if($prices_access_level == 'all' || ($prices_access_level == 'registered' && $objLogin->IsLoggedIn())){	
							if($shopping_cart_installed && $result[0][$i]['units'] > 0){
								$output .= '<form action="index.php?page=shopping_cart" name="frmProduct_'.$result[0][$i]['id'].'" method="post">
								'.draw_hidden_field('act', 'add', false).'
								'.draw_hidden_field('prodid', $result[0][$i]['id'], false).'
								'.draw_token_field(false).'								
								'._ADD.': <input style="font-size:10px;" name="amount" id="amount" type="text" value="1" size="5" maxlength="4"><br /><br />
								<input type="submit" class="form_button" value="'._ADD_TO_CART.'" />
								</form>';
							}
						}							
				$output .= '</td></tr>';			
			}
			// draw pagination links
			$output .= '<tr><td colspan="3">';
			$output .= pagination_get_links($total_pages, 'index.php?page='.$type.$tid.'&sort='.$sort);
			$output .= '</td></tr>'; 
			$output .= '<tr><td colspan="3">&nbsp;</td></tr>';
			$output .= '</table>';
		}else{
			$output .= draw_message(_NO_PRODUCTS_FOUND, false, true);			
		}		
		echo $output;		
	}
	
	/**
	 * Draws all products
	 */
	public static function DrawAllProducts()
	{
		$output = '';		
		$page_size = '10';
		$cid = isset($_GET['cid']) ? (int)$_GET['cid'] : '';
		$mid = isset($_GET['mid']) ? (int)$_GET['mid'] : '';
		$sort = (isset($_GET['sort']) && $_GET['sort'] != '') ? prepare_input($_GET['sort']) : '';
		
		$currency_format = get_currency_format();
		$shopping_cart_installed = false;
		$prices_access_level = 'all';
		$arr_product_types = array('0'=>'', '1'=>_DIGITAL);
		
		if(Modules::IsModuleInstalled('shopping_cart')){				
			if(ModulesSettings::Get('shopping_cart', 'is_active') == 'yes') $shopping_cart_installed = true;
			if(ModulesSettings::Get('shopping_cart', 'prices_access_level') == 'registered') $prices_access_level = 'registered';
		}
		if(Modules::IsModuleInstalled('products_catalog')){
			$page_size = ModulesSettings::Get('products_catalog', 'products_per_page');			
		}

		if($sort == 'name') $sort_by = 'pd.name';
		else if($sort == 'price') $sort_by = 'p.price';
		else if($sort == 'manufacturer') $sort_by = 'm.name';
		else if($sort == 'category') $sort_by = 'cd.name';
		else $sort_by = 'p.priority_order';

		$sql_from = TABLE_PRODUCTS.' p
					INNER JOIN '.TABLE_CATEGORIES.' c ON p.category_id = c.id
					INNER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' cd ON c.id = cd.category_id
					INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON p.id = pd.product_id
					LEFT OUTER JOIN '.TABLE_MANUFACTURERS.' m ON p.manufacturer_id = m.id
				WHERE
					p.is_active = 1 AND 
					pd.language_id = \''.Application::Get('lang').'\' AND 
					cd.language_id = \''.Application::Get('lang').'\' 
					'.(($cid != '') ? ' AND c.id = '.(int)$cid : '').'
					'.(($mid != '') ? ' AND m.id = '.(int)$mid : '').'
				ORDER BY '.$sort_by.' ASC';

		// pagination prepare
		$start_row = '0';
		$total_pages = '1';
		pagination_prepare($page_size, $sql_from, $start_row, $total_pages);		

		$sql = 'SELECT p.id,
					p.category_id,
					p.list_price,
					p.price,
					p.icon,
					p.icon_thumb,
					p.image1,
					p.image1_thumb,
					p.image2,
					p.image2_thumb,
					p.image3,
					p.image3_thumb,					
					p.units,
					p.priority_order,
					p.manufacturer_id,
					p.product_type,
					m.name as manufacturer_name,
					cd.name as category_name,  
					pd.language_id,
					pd.name,									
					pd.description,
					CONCAT("<a href=\"index.php?admin=mod_catalog_product_description&prodid=", p.id, "\">[ ", "'._DESCRIPTION.'", " ]</a>") as link_product_description,
					cd.name as category_name
				FROM '.$sql_from.'
				LIMIT '.$start_row.', '.$page_size;
		
		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS);

			$output .= '<table width="99%" border="0" align="center">';
			$output .= '<tr><th colspan="3" nowrap height="5px"></th></tr>
				<tr>
					<th colspan="3" align="'.Application::Get('defined_right').'" valign="middle">';

					$output .= _MANUFACTURERS.': ';
					$output .= Manufacturers::GetManufacturersSelectBox('admin=mod_catalog_all_products', $mid).'&nbsp;&nbsp;&nbsp;';										
					$output .= _CATEGORIES.': ';
					$output .= Categories::GetCategoriesSelectBox('admin=mod_catalog_all_products', $cid).'&nbsp;&nbsp;&nbsp;';					

			$tid = ($cid != '') ? '&cid='.$cid: '&mid='.$mid;
			$output .= _SORT_BY.': 
					<select onchange="javascript:appGoTo(\'admin=mod_catalog_all_products\',\''.$tid.'&sort=\'+this.value)">
						<option value="">-- '._SELECT.' --</option>
						<option value="name" '.(($sort == 'name') ? ' selected="selected"' : '').'>'._NAME.'</option>
						<option value="price" '.(($sort == 'price') ? ' selected="selected"' : '').'>'._PRICE.'</option>
						<option value="manufacturer" '.(($sort == 'manufacturer') ? ' selected="selected"' : '').'>'._MANUFACTURER.'</option>
						<option value="category" '.(($sort == 'category') ? ' selected="selected"' : '').'>'._CATEGORY.'</option>
					</select>
					</th>
				</tr>';						
			$output .= '<tr><th colspan="3" nowrap height="5px"></th></tr>
				<tr>
					<th width="15%" align="center">'._IMAGE.'</th>
					<th width="70%" align="'.Application::Get('defined_left').'">'._ITEM_NAME.'</th>
					<th width="15%" align="center">'._PRICE.'</th>
				</tr>';			

		if($result[1] > 0){	
			for($i=0; $i < $result[1]; $i++){
				$icon_file = ($result[0][$i]['icon'] != '') ? $result[0][$i]['icon'] : 'no_image.png';
				$icon_file_thumb = ($result[0][$i]['icon_thumb'] != '') ? $result[0][$i]['icon_thumb'] : 'no_image.png';
				$output .= '
					<tr><td colspan="3" style="padding:7px;">'.draw_line('no_margin_line', IMAGE_DIRECTORY, false).'</td></tr>					
					<tr valign="top">
						<td align="center">
							<div class="product_icon">';
								if($icon_file != 'no_image.png') $output .= '<a href="images/products/'.$icon_file.'" rel="lyteshow_'.$result[0][$i]['id'].'">';
								$output .= '<img src="images/products/'.$icon_file_thumb.'" width="115px" rel="lyteshow_'.$result[0][$i]['id'].'" title="'._CLICK_TO_INCREASE.'" alt="" />';
								if($icon_file != 'no_image.png') $output .= '</a>';
							$output .= '</div>';
							if($result[0][$i]['image1'] != '') $output .= '<a href="images/products/'.$result[0][$i]['image1'].'" rel="lyteshow_'.$result[0][$i]['id'].'" title=""></a>';
							if($result[0][$i]['image2'] != '') $output .= '<a href="images/products/'.$result[0][$i]['image2'].'" rel="lyteshow_'.$result[0][$i]['id'].'" title=""></a>';
							if($result[0][$i]['image3'] != '') $output .= '<a href="images/products/'.$result[0][$i]['image3'].'" rel="lyteshow_'.$result[0][$i]['id'].'" title=""></a>';							
							$output .= '
						</td>
						<td>';
							$product_type = ($arr_product_types[$result[0][$i]['product_type']] != '') ? '('.$arr_product_types[$result[0][$i]['product_type']].')' : '';
							$output .= '<b>'.$result[0][$i]['name'].'</b> '.$product_type;
							$output .= '<p>'._MANUFACTURER.': '.$result[0][$i]['manufacturer_name'].'<br>';	
							$output .= _CATEGORY.': '.$result[0][$i]['category_name'].' </p>';	
							$output .= '<p>'.strip_tags(stripslashes(substr($result[0][$i]['description'], 0, 400))).'...</p>
						</td>
						<td align="center">';						
							if($prices_access_level == 'all' || ($prices_access_level == 'registered' && $objLogin->IsLoggedIn())){	
								if($result[0][$i]['list_price'] != 0) $output .= '<strike><b>'.Currencies::PriceFormat($result[0][$i]['list_price'] * Application::Get('currency_rate'), '', '', $currency_format).'</b></strike><br />';
								$output .= '<span class="price"><b>'.Currencies::PriceFormat($result[0][$i]['price'] * Application::Get('currency_rate'), '', '', $currency_format).'</b></span>';
							}						
							if($shopping_cart_installed){
								$output .= '<br /><p>'.(($result[0][$i]['units'] > 0) ? _UNITS.': '.$result[0][$i]['units'] : '<span class=no>'._OUT_OF_STOCK.'</span>').'</p>';
								if($prices_access_level == 'all' || ($prices_access_level == 'registered' && $objLogin->IsLoggedIn())){	
									if($result[0][$i]['units'] > 0){
										$output .= '<form action="index.php?page=shopping_cart" name="frmProduct_'.$result[0][$i]['id'].'" method="post">
										'.draw_hidden_field('act', 'add', false).'
										'.draw_hidden_field('prodid', $result[0][$i]['id'], false).'
										'.draw_token_field(false).'								
										'._ADD.': <input style="font-size:10px;" name="amount" id="amount" type="text" value="1" size="5" maxlength="4"><br /><br />
										<input type="submit" class="form_button" value="'._ADD_TO_CART.'" />
										</form>';
									}
								}							
							}
				$output .= '</td></tr>';			
			}			
		}else{
			$output .= '<tr><td colspan="3">';
			$output .= draw_message(_NO_PRODUCTS_FOUND, false, true);
			$output .= '</td></tr>';
		}		

		// draw pagination links
		$output .= '<tr><td colspan="3">';
		$output .= pagination_get_links($total_pages, 'index.php?admin=mod_catalog_all_products'.$tid.'&sort='.$sort);
		$output .= '</td></tr>'; 
		$output .= '<tr><td colspan="3">&nbsp;</td></tr>';
		$output .= '</table>';

		echo $output;		
	}

	/**
	 * Draws featured side block with products links
	 * 		@param $placement
	 * 		@param $draw
	 */
	public static function DrawFeaturedBlock($placement = 'side', $draw = true)
	{
		$output = '';
		
		$result = self::GetAllProducts('is_featured = 1', 'RAND() ASC');
		if($result[1] > 0){

			if($placement == 'home'){

				$currency_format = get_currency_format();		
				$shopping_cart_installed = false;
				$prices_access_level = 'all';
		
				$output .= '<br />';			
				if(Modules::IsModuleInstalled('shopping_cart')){				
					if(ModulesSettings::Get('shopping_cart', 'is_active') == 'yes') $shopping_cart_installed = true;
					if(ModulesSettings::Get('shopping_cart', 'prices_access_level') == 'registered') $prices_access_level = 'registered';
				}

				$output .= draw_sub_title_bar(_FEATURED_PRODUCTS, false);
				$output .= '<table border="0">';
				$output .= '<tr>';
				for($i=0; ($i < $result[1] && $i < 5); $i++){
					$output .= '<td align="center" valign="top">';
					$output .= self::DrawProductBlock($result[0][$i], $shopping_cart_installed, $prices_access_level, $currency_format, false);
					$output .= '</td>';
				}
				$output .= '</tr>';
				$output .= '</table>';

			}else{
				
				$output .= draw_block_top(_FEATURED_PRODUCTS, '', 'maximized', false);
				$output .= '<ul>';
				for($i=0; $i < $result[1] && ($i < 5); $i++){
					$output .= '<li>'.prepare_link('product', 'prodid', $result[0][$i]['id'], '', $result[0][$i]['name']).'</li>';
				}
				if($result[1] > 5) $output .= '<li>'.prepare_link('products', 'type', 'featured', 'all', _MORE.' &raquo;', '', _MORE).'</li>';	
				$output .= '</ul>';
				$output .= draw_block_bottom(false);
				
			}
		}else{
			$output .= _NO_PRODUCTS_TO_DISPLAY;
		}
		
		
		if($draw) echo $output;
		else return $output;
	}
	
	/**
	 * Draws list of all featured products
	 */
	public static function DrawFeaturedAll()
	{
		$currency_format = get_currency_format();

		echo '<table border="0" cellspacing="5" width="99%" align="center">';
		echo '<tr><td colspan="6">'.draw_sub_title_bar(_FEATURED_PRODUCTS, false).'</td></tr>';
		
		$result = self::GetAllProducts('is_featured = 1', 'RAND() ASC');
		if($result[1] > 0){
			echo '<tr>
					<th width="20px"></td>
					<th>'._NAME.'</th>
					<th align="center">'._CATEGORY.'</th>					
					<th width="100px" align="center">'._UNITS.'</th>
					<th width="100px" align="right">'._PRICE.'</th>
					<th align="center">'._DATE_ADDED.'</th>
			</tr>';
			for($i=0; $i < $result[1] && ($i < 100); $i++){
				echo '<tr>
						<td align="right">'.($i+1).'.</td>
						<td nowrap="nowrap">'.prepare_link('product', 'prodid', $result[0][$i]['id'], '', $result[0][$i]['name']).'</td>
						<td align="center">'.prepare_link('category', 'cid', $result[0][$i]['category_id'], '', $result[0][$i]['category_name'], '').'</td>												
						<td align="center">'.$result[0][$i]['units'].'</td>
						<td align="right">'.Currencies::PriceFormat($result[0][$i]['price'] * Application::Get('currency_rate'), '', '', $currency_format).'</td>
						<td align="center">'.format_datetime($result[0][$i]['date_added']).'</td>						
				</tr>';
			}
			echo '<tr><td colspan="6">&nbsp;</td></tr>';
		}else{
			echo '<tr><td colspan="6">'._NO_PRODUCTS_TO_DISPLAY.'</td></tr>';
		}		
		echo '</table>';		
	}	

	/**
	 * Returns products array
	 */
	public static function GetAllProducts($where_clause = '', $order_clause = 'priority_order ASC')
	{
		$lang = Application::Get('lang');
		$output = array('0'=>array(), '1'=>'0');
		
		$sql = 'SELECT p.id,
					p.category_id,
					p.priority_order,
					p.date_added,
					p.units,
					p.price,
					p.list_price,
					p.icon,
					p.icon_thumb,
					catd.name as category_name,
					pd.language_id,
					pd.name,									
					pd.description
				FROM '.TABLE_PRODUCTS.' p
					LEFT OUTER JOIN '.TABLE_CATEGORIES.' cat ON p.category_id = cat.id
					LEFT OUTER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' catd ON cat.id = catd.category_id
					INNER JOIN '.TABLE_PRODUCTS_DESCRIPTION.' pd ON p.id = pd.product_id
				WHERE
					p.is_active = 1 AND 
					pd.language_id = \''.$lang.'\' AND 
					catd.language_id = \''.$lang.'\' 
					'.(($where_clause != '') ? ' AND '.$where_clause : '').'
				ORDER BY '.$order_clause;
		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS);
		if($result[1] > 0){
			$output[0] = $result[0];
			$output[1] = $result[1];
		}
	    return $output;
	}

	/**
	 * Draw catalog statistics side block 
	 * 		@param $draw
	 */
	public static function DrawCatalogStatistics($draw = true)
	{		
		$sql = 'SELECT COUNT(*) as cnt FROM '.TABLE_PRODUCTS.' WHERE is_active = 1';
		$result = database_query($sql, DATA_ONLY, FIRST_ROW_ONLY);
		$products_total = isset($result['cnt']) ? (int)$result['cnt'] : '0';
		
		$sql = 'SELECT COUNT(*) as cnt FROM '.TABLE_PRODUCTS.' WHERE is_active = 1 AND TIMESTAMPDIFF(HOUR, date_added, \''.date('Y-m-d H:i:s').'\') < 24';
		$result = database_query($sql, DATA_ONLY, FIRST_ROW_ONLY);
		$products_last_total = isset($result['cnt']) ? (int)$result['cnt'] : '0';
		
		$sql = 'SELECT COUNT(*) as cnt FROM '.TABLE_PRODUCTS.' WHERE is_active = 0';
		$result = database_query($sql, DATA_ONLY, FIRST_ROW_ONLY);
		$products_pending = isset($result['cnt']) ? (int)$result['cnt'] : '0';
		
		$sql = 'SELECT COUNT(*) as cnt FROM '.TABLE_CATEGORIES;
		$result = database_query($sql, DATA_ONLY, FIRST_ROW_ONLY);
		$categories_total = isset($result['cnt']) ? (int)$result['cnt'] : '0';
		
		$output  = draw_block_top(_DIRECTORY_STATISTICS, '', 'maximized', false);
		$output .= '<ul>';
		$output .= '<li>'._PRODUCTS.': '.$products_total.'</li>';
		$output .= '<li>'._PENDING.': '.$products_pending.'</li>';
		$output .= '<li>'._NEW_SUBMISSION_IN_24H.': '.$products_last_total.'</li>';
		$output .= '<li>'._CATEGORIES.': '.$categories_total.'</li>';
		$output .= '</ul>';
		$output .= draw_block_bottom(false);
		
		if($draw) echo $output;
		else return $output;
	}
	

	/**
	 * Draw product block
	 * 		@param $row
	 * 		@param $shopping_cart_installed
	 * 		@param $prices_access_level
	 * 		@param $currency_format
	 * 		@param $draw	 
	 */
	private static function DrawProductBlock($row, $shopping_cart_installed, $prices_access_level, $currency_format, $draw = true)
	{
		global $objLogin;
		$output = '';

		$icon_file = ($row['icon'] != '') ? $row['icon'] : 'no_image.png';
		$icon_file_thumb = ($row['icon_thumb'] != '') ? $row['icon_thumb'] : 'no_image.png';

		$output .= '<div class="new_products_wrapper">';				
		$output .= '<a href="'.prepare_link('product', 'prodid', $row['id'], '', $row['name'], '', '', true).'" title="'.decode_text($row['name']).'">';
		$output .= '<div class="product_icon"><img src="images/products/'.$icon_file_thumb.'" alt="" /></div>';				
		$output .= '<div class="product_name">'.substr_by_word($row['name'], 35, true, Application::Get('lang')).'</div>';
		$output .= '</a>';
		$output .= '<div class="product_price_block">';
		if($prices_access_level == 'all' || ($prices_access_level == 'registered' && $objLogin->IsLoggedIn())){
			//if($row['list_price'] != 0) $output .= '<strike><b>'.Currencies::PriceFormat($row['list_price'] * Application::Get('currency_rate'), '', '', $currency_format).'</b></strike> &nbsp; ';
			$output .= '<span class="price"><b>'.Currencies::PriceFormat($row['price'] * Application::Get('currency_rate'), '', '', $currency_format).'</b></span><br />';
		}
		$output .= '</div>';				
		if($shopping_cart_installed){
			if($row['units'] > 0){
				if($prices_access_level == 'all' || ($prices_access_level == 'registered' && $objLogin->IsLoggedIn())){
					$output .= '<form action="index.php?page=shopping_cart" name="frmProduct_'.$row['id'].'" method="post">
							'.draw_hidden_field('act', 'add', false).'
							'.draw_hidden_field('prodid', $row['id'], false).'
							'.draw_hidden_field('amount', '1', false, 'amount').'
							'.draw_token_field(false).'									
							<input type="submit" class="form_button" value="'._ADD_TO_CART.'" />
							</form>';
				}
			}else{
				$output .= '<span class="no">'._OUT_OF_STOCK.'</span>';
			}
		}
		$output .= '</div>';
		
		if($draw) echo $output;
		else return $output;
	}

}
?>