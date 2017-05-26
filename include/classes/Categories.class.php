<?php

/**
 *	Categories (for Shopping Cart ONLY)
 *  -------------- 
 *	Written by  : ApPHP
 *  Updated	    : 08.10.2010
 *	Written by  : ApPHP
 *
 *	PUBLIC:				  	STATIC:				 	PRIVATE:
 * 	------------------	  	---------------     	---------------
 *	__construct             DrawSideBlock			GetAllActive
 *	__destruct				DrawHomePageBlock       GetLevel
 *	BeforeInsertRecord      GetCategoriesSelectBox  
 *	AfterInsertRecord       UpdateProductsCount
 *	BeforeDeleteRecord      GetAllExistingCategories
 *	AfterDeleteRecord       RecalculateProductsCount
 *	DrawCategories
 *	GetInfoByID	
 *	GetLevelsInfo
 *	DrawSubCategories
 *
 **/


class Categories extends MicroGrid {
	
	protected $debug = false;
	
	//------------------------------
	protected $categoryCode;
	
	//==========================================================================
    // Class Constructor
	//==========================================================================
	function __construct()
	{		
		parent::__construct();

		$this->params = array();
		
		if(isset($_POST['priority_order'])) $this->params['priority_order'] = prepare_input($_POST['priority_order']);
		if(isset($_POST['parent_id']))  	$this->params['parent_id'] = (int)$_POST['parent_id'];
		$name 			= (isset($_POST['descr_name'])) ? prepare_input($_POST['descr_name']) : '';
		$description 	= (isset($_POST['descr_description'])) ? prepare_input($_POST['descr_description']) : '';
		$cid 			= (isset($_REQUEST['cid'])) ? (int)$_REQUEST['cid'] : '0';
		if(self::GetParameter('operation') == 'filtering' && self::GetParameter('filter_by_apsc_categoriesparent_id', false) != ''){
			$cid = self::GetParameter('filter_by_apsc_categoriesparent_id', false);
		}

		if(isset($_POST['icon'])){
			$this->params['icon'] = prepare_input($_POST['icon']);
		}else if(isset($_FILES['icon']['name']) && $_FILES['icon']['name'] != ''){
			// nothing 			
		}else if (self::GetParameter('action') == 'create'){
			$this->params['icon'] = '';
		}
		
		// for checkboxes
		/// if(isset($_POST['parameter4']))   $this->params['parameter4'] = $_POST['parameter4']; else $this->params['parameter4'] = '0';
		
		$this->params['language_id'] = MicroGrid::GetParameter('language_id');
		$rid = MicroGrid::GetParameter('rid');		
	
		$this->primaryKey 	= 'id';
		$this->tableName 	= TABLE_CATEGORIES;
		$this->dataSet 		= array();
		$this->error 		= '';
		$this->formActionURL = 'index.php?admin=mod_catalog_categories&cid='.(int)$cid;
		$this->actions      = array('add'=>true, 'edit'=>true, 'details'=>true, 'delete'=>true);
		$this->actionIcons  = true;
		$this->allowRefresh = true;
		$this->isHtmlEncoding = true;
		
		$this->allowLanguages = false;
		$this->languageId  	= ''; //($this->params['language_id'] != '') ? $this->params['language_id'] : Languages::GetDefaultLang();
		$this->WHERE_CLAUSE = 'WHERE '.((self::GetParameter('operation') != 'filtering') ? $this->tableName.'.parent_id = '.(int)$cid.' AND ' : '').' 
							         '.TABLE_CATEGORIES_DESCRIPTION.'.language_id = \''.Application::Get('lang').'\'';
		
		$this->ORDER_CLAUSE = 'ORDER BY '.TABLE_CATEGORIES.'.parent_id ASC, '.TABLE_CATEGORIES.'.priority_order ASC';
		$this->categoryCode = isset($_POST['category_code']) ? prepare_input($_POST['category_code']) : '';
		
		$this->isAlterColorsAllowed = true;
        
		$this->isPagingAllowed = true;
		$this->pageSize = 20;
        
		$this->isSortingAllowed = true;

		$this->isExportingAllowed = true;
		$this->arrExportingTypes = array('csv'=>true);

		// prepare categories array		
		$total_categories = self::GetAllExistingCategories();
		//$arr_categories = array();
		//foreach($total_categories[0] as $key => $val){
		//	$arr_categories[$val['id']] =+ $val['name'];
		//}		

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
        
		$this->isFilteringAllowed = true;
		// define filtering fields
		$this->arrFilteringFields = array(
			_CATEGORY => array('table'=>$this->tableName, 'field'=>'parent_id', 'type'=>'dropdownlist', 'source'=>$arr_categories, 'sign'=>'=', 'width'=>'130px'),
		);
		
		$level = $this->GetLevel($cid);

		//---------------------------------------------------------------------- 
		// VIEW MODE
		//---------------------------------------------------------------------- 
		$this->VIEW_MODE_SQL = 'SELECT '.$this->tableName.'.'.$this->primaryKey.',
									'.$this->tableName.'.icon,
									'.$this->tableName.'.icon_thumb,
									'.$this->tableName.'.parent_id,
									'.$this->tableName.'.priority_order,
									'.$this->tableName.'.products_count,
									'.TABLE_CATEGORIES_DESCRIPTION.'.language_id,
									'.TABLE_CATEGORIES_DESCRIPTION.'.name,									
									'.TABLE_CATEGORIES_DESCRIPTION.'.description,
									CONCAT("<a href=index.php?admin=mod_catalog_category_description&cid=", '.$this->tableName.'.parent_id, "&cdid=", '.TABLE_CATEGORIES.'.'.$this->primaryKey.', ">[ ", "'._DESCRIPTION.'", " ]</a>") as link_cat_description,
									CONCAT("<a href=index.php?admin=mod_catalog_categories&cid=", '.$this->tableName.'.'.$this->primaryKey.',
										">[ ", "'._SUB_CATEGORIES.' ]</a> (",
										(SELECT COUNT(*) FROM '.$this->tableName.' c1 WHERE c1.parent_id = '.$this->tableName.'.'.$this->primaryKey.'),
										")") as link_sub_categories
								FROM '.$this->tableName.'
									LEFT OUTER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' ON '.$this->tableName.'.id = '.TABLE_CATEGORIES_DESCRIPTION.'.category_id
								';

		// define view mode fields
		$this->arrViewModeFields = array(
			'icon_thumb'  	 => array('title'=>_IMAGE, 'type'=>'image', 'align'=>'left', 'width'=>'60px', 'image_width'=>'40px', 'image_height'=>'30px', 'target'=>'images/categories/', 'no_image'=>'no_image.png'),
			'name'  		 => array('title'=>_NAME, 'type'=>'label', 'align'=>'left', 'width'=>'', 'maxlength'=>''),
			'description'    => array('title'=>_DESCRIPTION, 'type'=>'label', 'align'=>'left', 'width'=>'', 'maxlength'=>'30'),
			//'parent_id'      => array('title'=>_PARENT_CATEGORY, 'type'=>'enum', 'align'=>'center', 'width'=>'120px', 'source'=>$arr_categories),
			'priority_order' => array('title'=>_ORDER, 'type'=>'label', 'align'=>'center', 'width'=>'70px', 'maxlength'=>'', 'movable'=>true),
			'products_count' => array('title'=>_PRODUCTS, 'type'=>'label', 'align'=>'center', 'width'=>'80px', 'maxlength'=>''),
			'link_sub_categories'  => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'140px', 'maxlength'=>'', 'visible'=>(($level >= 3) ? false : true)),
			'link_cat_description' => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'100px', 'maxlength'=>''),

			// 'parameter1'  => array('title'=>'', 'type'=>'label', 'align'=>'left', 'width'=>'', 'maxlength'=>''),
		);
		
		//---------------------------------------------------------------------- 
		// ADD MODE
		//---------------------------------------------------------------------- 
		// define add mode fields
		$this->arrAddModeFields = array(		
			//'parent_id'         => array('title'=>_PARENT_CATEGORY, 'type'=>'enum',     'required'=>false, 'readonly'=>false, 'width'=>'210px', 'source'=>$arr_categories, 'unique'=>false, 'javascript_event'=>''),
			'icon'              => array('title'=>_ICON_IMAGE, 'type'=>'image', 'width'=>'310px', 'required'=>false, 'target'=>'images/categories/', 'no_image'=>'', 'random_name'=>'true', 'unique'=>false, 'image_width'=>'120px', 'image_height'=>'90px', 'thumbnail_create'=>true, 'thumbnail_field'=>'icon_thumb', 'thumbnail_width'=>'115px', 'thumbnail_height'=>'', 'file_maxsize'=>'400k'),
			'descr_name' 		=> array('title'=>_NAME, 'type'=>'textbox',  'width'=>'310px', 'required'=>true, 'readonly'=>false, 'default'=>$name, 'validation_type'=>'text', 'maxlength'=>'50'),
			'descr_description' => array('title'=>_DESCRIPTION, 'type'=>'textarea', 'width'=>'310px', 'height'=>'90px', 'required'=>true, 'readonly'=>false, 'default'=>$description, 'validation_type'=>'text', 'maxlength'=>'255', 'validation_maxlength'=>'255'),
			'priority_order'    => array('title'=>_ORDER, 'type'=>'textbox',  'width'=>'60px', 'maxlength'=>'3', 'required'=>true, 'default'=>'0', 'readonly'=>false, 'validation_type'=>'numeric|positive'),
			'parent_id'  	    => array('title'=>'', 'type'=>'hidden', 'required'=>false, 'default'=>$cid),
		);

		//---------------------------------------------------------------------- 
		// EDIT MODE
		//---------------------------------------------------------------------- 
		$this->EDIT_MODE_SQL = 'SELECT '.$this->tableName.'.'.$this->primaryKey.',
									'.$this->tableName.'.icon,
									'.$this->tableName.'.icon_thumb,
									'.$this->tableName.'.parent_id,
									'.$this->tableName.'.priority_order,
									'.$this->tableName.'.products_count,
									'.TABLE_CATEGORIES_DESCRIPTION.'.name as category_name
								FROM '.$this->tableName.'
									LEFT OUTER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' ON '.$this->tableName.'.id = '.TABLE_CATEGORIES_DESCRIPTION.'.category_id
								WHERE
									'.TABLE_CATEGORIES_DESCRIPTION.'.language_id = \''.Application::Get('lang').'\' AND
								    '.$this->tableName.'.'.$this->primaryKey.' = _RID_';		
		// define edit mode fields
		$this->arrEditModeFields = array(
			///'parent_id'       => array('title'=>_PARENT_CATEGORY, 'type'=>'enum',     'required'=>false, 'readonly'=>false, 'width'=>'210px', 'source'=>$arr_categories, 'unique'=>false, 'javascript_event'=>''),
			'category_name'   => array('title'=>_NAME, 'type'=>'label'),
			'icon'            => array('title'=>_ICON_IMAGE, 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/categories/', 'no_image'=>'', 'random_name'=>'true', 'unique'=>false, 'image_width'=>'120px', 'image_height'=>'90px', 'thumbnail_create'=>true, 'thumbnail_field'=>'icon_thumb', 'thumbnail_width'=>'115px', 'thumbnail_height'=>'', 'file_maxsize'=>'400k'),
			'products_count'  => array('title'=>_PRODUCTS, 'type'=>'label'),
			'priority_order'  => array('title'=>_ORDER, 'type'=>'textbox',  'width'=>'60px', 'maxlength'=>'3', 'required'=>true, 'readonly'=>false, 'validation_type'=>'numeric|positive'),
		);

		//---------------------------------------------------------------------- 
		// DETAILS MODE
		//----------------------------------------------------------------------
		$this->DETAILS_MODE_SQL = $this->EDIT_MODE_SQL;
		$this->arrDetailsModeFields = array(
			///'parent_id'       => array('title'=>_PARENT_CATEGORY, 'type'=>'enum', 'source'=>$arr_categories),
			'category_name'   => array('title'=>_NAME, 'type'=>'label'),
			'icon'  		  => array('title'=>_ICON_IMAGE, 'type'=>'image', 'target'=>'images/categories/', 'no_image'=>'no_image.png'),
			'products_count'  => array('title'=>_PRODUCTS, 'type'=>'label'),
			'priority_order'  => array('title'=>_ORDER, 'type'=>'label'),
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
	 * Before-insertion function
	 */
	public function BeforeInsertRecord()
	{
		$name = (isset($_POST['descr_name'])) ? prepare_input($_POST['descr_name']) : '';
		$description = (isset($_POST['descr_description'])) ? prepare_input($_POST['descr_description']) : '';

		if($name == ''){
			$this->error = str_replace('_FIELD_', '<b>'._NAME.'</b>', _FIELD_CANNOT_BE_EMPTY);
			$this->errorField = 'descr_name';
			return false;
		}else if($description == ''){
			$this->error = str_replace('_FIELD_', '<b>'._DESCRIPTION.'</b>', _FIELD_CANNOT_BE_EMPTY);
			$this->errorField = 'descr_description';
			return false;
		}else if(strlen($description) > 255){	
			$this->error = str_replace('_FIELD_', '<b>'._DESCRIPTION.'</b>', _FIELD_LENGTH_EXCEEDED);
			$this->error = str_replace('_LENGTH_', 255, $this->error);
			$this->errorField = 'descr_description';
			return false;
		}
		
		return true;
	}

	/**
	 * After-insertion function
	 */
	public function AfterInsertRecord()
	{
		$name = (isset($_POST['descr_name'])) ? prepare_input($_POST['descr_name']) : '';
		$description = (isset($_POST['descr_description'])) ? prepare_input($_POST['descr_description']) : '';
	
		// languages array		
		$total_languages = Languages::GetAllActive();
		foreach($total_languages[0] as $key => $val){			
			$sql = 'INSERT INTO '.TABLE_CATEGORIES_DESCRIPTION.'(
						id, category_id, language_id, name, description)
					VALUES(
						NULL, '.$this->lastInsertId.', \''.$val['abbreviation'].'\', \''.$name.'\', \''.$description.'\'
					)';
			if(!database_void_query($sql)){
				
			}else{
				
			}		
		}		
	}
	
	/**
	 * Before-deleting function
	 */
	public function BeforeDeleteRecord()
	{
		$cid = MicroGrid::GetParameter('rid');

		$sql = 'SELECT COUNT(*) as cnt FROM '.TABLE_CATEGORIES.' WHERE parent_id = '.(int)$cid;
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[0]['cnt'] > 0){
			$this->error = _CATEGORY_DELETE_SUBCATEGORIES;			
			return false;			
		}else{
			$sql = 'SELECT COUNT(*) as cnt FROM '.TABLE_PRODUCTS.' WHERE category_id = '.(int)$cid;
			$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
			if($result[0]['cnt'] > 0){
				$this->error = _CATEGORY_DELETE_PRODUCTS;			
				return false;			
			}			
		}

		return true;
	}

	/**
	 * After-deleting function
	 */
	public function AfterDeleteRecord()
	{
        // update products count field
		$cid = MicroGrid::GetParameter('rid');
		$sql = 'DELETE FROM '.TABLE_CATEGORIES_DESCRIPTION.' WHERE category_id = '.(int)$cid;		
		if(!database_void_query($sql)){ /* echo 'error!'; */ }		
	}

	/**
	 * Draws side block with categories links
	 * 		@param $draw
	 **/
	public static function DrawSideBlock($draw = false)
	{
		$show_products = ModulesSettings::Get('products_catalog', 'show_products_in_categories');

		ob_start();
		$sql = 'SELECT c.id,
					c.icon,
					c.products_count,
					c.priority_order,
					cd.language_id,
					cd.name,									
					cd.description
				FROM '.TABLE_CATEGORIES.' c
					LEFT OUTER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' cd ON c.id = cd.category_id
				WHERE
					c.parent_id = _CID_ AND
					cd.language_id = \''.Application::Get('lang').'\'
				ORDER BY c.priority_order ASC';
		$sql_1 = str_replace('_CID_', '0', $sql);
		$result = database_query($sql_1, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);

		draw_block_top(_CATEGORIES);
		## +---------------------------------------------------------------------------+
		## | 1. Creating & Calling:                                                    |
		## +---------------------------------------------------------------------------+

		## *** define a relative (virtual) path to treemenu.class.php file
		 define ('TREEMENU_DIR', 'modules/treemenu/');                  /* Ex.: 'treemenu/' */
		## *** include TreeMenu class
		 require_once(TREEMENU_DIR.'treemenu.class.php');
		## *** create TreeMenu object
		 $treeMenu = new TreeMenu();
		 $treeMenu->SetDirection(Application::Get('lang_dir'));
		## +---------------------------------------------------------------------------+
		## | 2. General Settings:                                                      |
		## +---------------------------------------------------------------------------+

		## *** set unique numeric (integer-valued) identifier for TreeMenu
		## *** (if you want to use several independently configured TreeMenu objects on single page)
		 $treeMenu->SetId(1);
		##  *** set style for TreeMenu
		 $treeMenu->SetStyle('vista');
		## *** set TreeMenu caption
		 //$treeMenu->SetCaption('ApPHP TreeMenu v'.$treeMenu->Version());
		## *** show debug info - false|true
		 $treeMenu->Debug(false);
		## *** set postback method: 'get', 'post' or 'ajax'
		 $treeMenu->SetPostBackMethod('post');
		## *** set variables that used to get access to the page (like: my_page.php?act=34&id=56 etc.)
		/// $treeMenu->SetHttpVars(array('id'));
		## *** show number of subnodes to the left of every node - false|true
		 $treeMenu->ShowNumSubNodes(false);

		## +---------------------------------------------------------------------------+
		## | 3. Adding nodes:                                                          |
		## +---------------------------------------------------------------------------+
		## *** add nodes
		## arguments:
		## arg #1 - node's caption
		## arg #2 - file associated with this node (optional)
		## arg #3 - icon associated with this node (optional)
		## Example: $treeMenu->AddNode('Title', 'text.txt', 'icon.gif');
		$node = array();
		for($i=0; $i < $result[1]; $i++){
			$node = $treeMenu->AddNode($result[0][$i]['name'].(($show_products == 'yes') ? ' ('.$result[0][$i]['products_count'].')' : ''), prepare_link('category', 'cid', $result[0][$i]['id'], '', $result[0][$i]['name'], '', '', true));
			$node->OpenNewWindow(true);
			
			$sql_2 = str_replace('_CID_', $result[0][$i]['id'], $sql);
			$result_2 = database_query($sql_2, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);
			for($j=0; $j < $result_2[1]; $j++){

			    $sub_node = $node->AddNode($result_2[0][$j]['name'].(($show_products == 'yes') ? ' ('.$result_2[0][$j]['products_count'].')' : ''), prepare_link('category', 'cid', $result_2[0][$j]['id'], '', $result_2[0][$j]['name'], '', '', true));
				$sub_node->OpenNewWindow(true);
				
				$sql_3 = str_replace('_CID_', $result_2[0][$j]['id'], $sql);
				$result_3 = database_query($sql_3, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);
				for($k=0; $k < $result_3[1]; $k++){
					$sub_sub_node = $sub_node->AddNode($result_3[0][$k]['name'].(($show_products == 'yes') ? ' ('.$result_3[0][$k]['products_count'].')' : ''), prepare_link('category', 'cid', $result_3[0][$k]['id'], '', $result_3[0][$k]['name'], '', '', true));
					$sub_sub_node->OpenNewWindow(true);					
				}				
			}
		}

		## +---------------------------------------------------------------------------+
		## | 5. Draw TreeMenu:                                                      |
		## +---------------------------------------------------------------------------+
		$treeMenu->ShowTree();

		echo '<ul><li>'.prepare_link('categories', '', '', 'all', _SEE_ALL.' &raquo;', 'main_menu_link main_menu_last', _SEE_ALL).'</li></ul>';
		draw_block_bottom();

		// save the contents of output buffer to the string
		$output = ob_get_contents();
		ob_end_clean();

		if($draw) echo $output;
		else return $output;
	}

	/**
	 * Draws home page block with categories links
	 * 		@param $return
	 */
	public static function DrawHomePageBlock($return = false)
	{
		global $objSettings;
		
		
		$lang = Application::Get('lang');
		$categories_images = false;
		$categories_columns = '3';
		$show_products = 'no';
		
		if(Modules::IsModuleInstalled('products_catalog')){				
			if(ModulesSettings::Get('products_catalog', 'show_categories_images') == 'yes') $categories_images = true;
			$categories_columns = ModulesSettings::Get('products_catalog', 'columns_number_on_page');
			$show_products = ModulesSettings::Get('products_catalog', 'show_products_in_categories');
		}
		
		$output = '';
		$sql = 'SELECT c.id,
					c.icon,
					c.icon_thumb,
					c.products_count,
					c.priority_order,
					cd.language_id,
					cd.name,									
					cd.description
				FROM '.TABLE_CATEGORIES.' c
					LEFT OUTER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' cd ON c.id = cd.category_id
				WHERE
					c.parent_id = _PARENT_ID_ AND 
					cd.language_id = \''.Application::Get('lang').'\'
				ORDER BY c.priority_order ASC';

		$result = database_query(str_replace('_PARENT_ID_', '0', $sql), DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);
		if($result[1] > 0){
			$output .= '<br />';
			//$link_see_all = prepare_link('categories', '', '', 'all', _SEE_ALL);
			$output .= draw_sub_title_bar(_CATEGORIES, false);
			$output .= '<table border="0" width="100%" align="center" cellspacing="5" class="categories_table">';
			$output .= '<tr>';
			for($i=0; $i < $result[1]; $i++){
				if($i != 0 && $i % $categories_columns == 0) $output .= '</tr><tr><td colspan="3" nowrap="nowrap" height="7px"></td></tr><tr>';
				
				if($categories_images){
					$output .= '<td valign="top" width="40px">';
					$icon_file_thumb = ($result[0][$i]['icon_thumb'] != '') ? $result[0][$i]['icon_thumb'] : 'no_image.png';                   
					$output .= '<img src="images/categories/'.$icon_file_thumb.'" width="64px" height="64px" alt="'.$result[0][$i]['name'].'" title="'.$result[0][$i]['name'].'" />';
					$output .= '</td>';
				}
				
				$output .= '<td valign="top" width="'.intval(100/$categories_columns).'%">';
				$output .= prepare_link('category', 'cid', $result[0][$i]['id'], $result[0][$i]['name'], $result[0][$i]['name'], 'category_link', $result[0][$i]['description']).(($show_products == 'yes') ? ' <span class="categories_span">('.$result[0][$i]['products_count'].')</span>' : '');
				$result_1 = database_query(str_replace('_PARENT_ID_', $result[0][$i]['id'], $sql), DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);
				$output .= '<br><div style="padding-top:5px">';
				for($j=0; ($j < $result_1[1] && $j <= 5); $j++){
					if($j > 0) $output .= ', ';
					if($j < 5){
						$output .= prepare_link('category', 'cid', $result_1[0][$j]['id'], $result_1[0][$j]['name'], $result_1[0][$j]['name'], 'sub_category_link', $result_1[0][$j]['description']).(($show_products == 'yes') ? ' <span class="sub_categories_span">('.$result_1[0][$j]['products_count'].')</span>' : '');					
					}else{
						$output .= prepare_link('category', 'cid', $result[0][$i]['id'], _MORE, _MORE.'...', 'sub_category_link', _READ_MORE);
					}					
				}
				$output .= '</div>';								
				$output .= '</td>';
			}
			$output .= '</tr>';
			$output .= '</table>';
		}
		if($return) return $output;
		else echo $output;
	}

	/**
	 * Draws categories
	 * 		@param $category_id
	 */
	public function DrawCategories($category_id = '0')
	{
		$output = '';
		
		$sql = 'SELECT c.id,
					c.icon,
					c.icon_thumb,
					c.products_count,
					c.priority_order,
					cd.language_id,
					cd.name,									
					cd.description
				FROM '.TABLE_CATEGORIES.' c
					LEFT OUTER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' cd ON c.id = cd.category_id
				WHERE
					c.parent_id = _CAT_ID_ AND 
					cd.language_id = \''.Application::Get('lang').'\'';
		$sql_1 = str_replace('_CAT_ID_', (int)$category_id, $sql);
		$result = database_query($sql_1, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);
		if($result[1] > 0){
			$output .= '<table border="0" width="100%" cellpadding="5" cellspacing="5">';	
			$output .= '<tr>';
			for($i=0; $i < $result[1]; $i++){
				if($i != 0 && $i % 4 == 0) $output .= '</tr><tr>';
				$output .= '<td valign="top" width="25%" style="border:1px solid #cccccc;">';
				
				$output .= '<h3>';
				$output .= prepare_link('categories', 'cid', $result[0][$i]['id'], '', $result[0][$i]['name']);
				$output .= prepare_link('category', 'cid', $result[0][$i]['id'], $result[0][$i]['name'], '&nbsp;&nbsp;<img src=images/url.gif>', '', _CLICK_TO_SEE_PRODUCTS);
				$output .= '</h3>';
			
				$icon_file_thumb = ($result[0][$i]['icon_thumb'] != '') ? $result[0][$i]['icon_thumb'] : 'no_image.png';
				$output .= '<div class="category_icon_small"><img src="images/categories/'.$icon_file_thumb.'" alt="'.$result[0][$i]['name'].'" title="'.$result[0][$i]['name'].'" /></div>';

				$sql_2 = str_replace('_CAT_ID_', (int)$result[0][$i]['id'], $sql);
				$result_2 = database_query($sql_2, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);
				for($j=0; $j < $result_2[1]; $j++){
					$output .= prepare_link('categories', 'cid', $result_2[0][$j]['id'], '', $result_2[0][$j]['name'], '', $result_2[0][$j]['description']);
					$output .= prepare_link('category', 'cid', $result_2[0][$j]['id'], $result_2[0][$j]['name'], '&nbsp;&nbsp;<img src=images/url.gif>', '', _CLICK_TO_SEE_PRODUCTS);
					$output .= '<br />';
				}				
				$output .= '</td>';
			}
			$output .= '</tr>';
			$output .= '</table>';	
		}else{
			$output .= draw_message(_NO_SUBCATEGORIES, false, true);
		}
		
		
		echo $output;
	}

	/**
	 *	Returns info by ID
	 *		@param $key
	 */
	public function GetInfoByID($key = '')
	{
		if(empty($key)) return false;
		
		$sql = 'SELECT c.id,
					c.icon,									
					c.priority_order,
					c.parent_id,
					cd.language_id,
					cd.name,									
					cd.description
				FROM '.$this->tableName.' c
					LEFT OUTER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' cd ON c.id = cd.category_id
				WHERE
					cd.language_id = \''.Application::Get('lang').'\'
					'.(($key != '') ? ' AND c.id='.(int)$key : '');
		return database_query($sql, DATA_ONLY, FIRST_ROW_ONLY);
	}
	
	/**
	 *	Returns categories select box
	 *		@param $category_id 
	 */
	public static function GetCategoriesSelectBox($page = 'page=category', $category_id = '')
	{
		$output = '';
		$total_categories = self::GetAllExistingCategories();
		$arr_categories = array();
		
		if(count($total_categories) > 0){
			$output .= '<select class="selCategories" name="selCategories" onchange="appGoTo(\''.$page.'\',\'&cid=\'+this.value)">';
			$output .= '<option value="">-- '._ALL.' --</option>';			
			foreach($total_categories as $key => $val){
				if($val['level'] == '1'){
					$output .= '<option '.(($category_id == $val['id']) ? 'selected="selected"' : '').' value="'.$val['id'].'">'.$val['name'].'</option>';
				}else if($val['level'] == '2'){
					$output .= '<option '.(($category_id == $val['id']) ? 'selected="selected"' : '').' value="'.$val['id'].'">&nbsp;&nbsp;&bull; '.$val['name'].'</option>';
				}else if($val['level'] == '3'){
					$output .= '<option '.(($category_id == $val['id']) ? 'selected="selected"' : '').' value="'.$val['id'].'">&nbsp;&nbsp;&nbsp;&nbsp;:: '.$val['name'].'</option>';
				}
			}		
			$output .= '</select>';			
		}
		return $output;
	}
	
	/**
	 *	Returns all active categories
	 */
	private function GetAllActive($rid)
	{		
		$sql = 'SELECT
					c.id,
					cd.name,									
					cd.description
				FROM '.$this->tableName.' c
					LEFT OUTER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' cd ON c.id = cd.category_id
				WHERE c.id != '.(int)$rid.'
				ORDER BY cd.name ASC';			
		return database_query($sql, DATA_AND_ROWS);
	}
	
	/**
	 *	Returns all existing categories
	 */
	public static function GetAllExistingCategories()
	{
		$sql = 'SELECT c.id,
					c.icon,
					c.products_count,
					c.priority_order,
					cd.language_id,
					cd.name,									
					cd.description
				FROM '.TABLE_CATEGORIES.' c
					LEFT OUTER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' cd ON c.id = cd.category_id
				WHERE
					c.parent_id = _CAT_ID_ AND 
					cd.language_id = \''.Application::Get('lang').'\'';
		$sql_1 = str_replace('_CAT_ID_', '0', $sql);
		$result = database_query($sql_1, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);
		$output = array();	
		if($result[1] > 0){
			for($i=0; $i < $result[1]; $i++){
				$output[$result[0][$i]['id']] = array('id'=>$result[0][$i]['id'], 'name'=>$result[0][$i]['name'], 'parent_name'=>'', 'level'=>'1');
			
				$sql_2 = str_replace('_CAT_ID_', (int)$result[0][$i]['id'], $sql);
				$result_2 = database_query($sql_2, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);
				for($j=0; $j < $result_2[1]; $j++){
					$output[$result_2[0][$j]['id']] = array('id'=>$result_2[0][$j]['id'], 'name'=>$result_2[0][$j]['name'], 'parent_name'=>$result[0][$i]['name'], 'level'=>'2');

					$sql_3 = str_replace('_CAT_ID_', (int)$result_2[0][$j]['id'], $sql);
					$result_3 = database_query($sql_3, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);
					for($k=0; $k < $result_3[1]; $k++){
						$output[$result_3[0][$k]['id']] = array('id'=>$result_3[0][$k]['id'], 'name'=>$result_3[0][$k]['name'], 'parent_name'=>$result_2[0][$j]['name'], 'level'=>'3');
						
					}					
				}					
			}
		}
		#echo '<pre>';
		#print_r($output);
		#echo '</pre>';
		return $output;
	}
	
	/**
	 * Updates products count for all categories
	 * 		@param $parent_id
	 */
	public static function RecalculateProductsCount($parent_id = 0)	
	{

		$sql = 'SELECT id, parent_id FROM '.TABLE_CATEGORIES.' WHERE parent_id = '.(int)$parent_id;
		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS);
		
		$count = 0;
		$count_public = 0;
		$total_products = 0;
		$current_products = 0;
		$child_products = 0;
		
		for($i=0; $i < $result[1]; $i++){		
			$child_products = self::RecalculateProductsCount($result[0][$i]['id']);			
		
			$sql = 'SELECT
						COUNT(*) as cnt
					FROM '.TABLE_PRODUCTS.'
						INNER JOIN '.TABLE_CATEGORIES.' ON '.TABLE_PRODUCTS.'.category_id = '.TABLE_CATEGORIES.'.id
					WHERE
						'.TABLE_PRODUCTS.'.is_active = 1 AND
						'.TABLE_PRODUCTS.'.category_id = '.(int)$result[0][$i]['id'];

			$res = database_query($sql, DATA_ONLY, FIRST_ROW_ONLY);
			$current_products = (isset($res['cnt']) ? $res['cnt'] : 0);			
			$count = $current_products + $child_products;
			$total_products += $count;
			
			$sql = 'UPDATE '.TABLE_CATEGORIES.' SET products_count = '.(int)$count.' WHERE id = '.(int)$result[0][$i]['id'];
			database_void_query($sql);
		}

		if(mysql_error() != ''){
			self::$static_error = _TRY_LATER;
			return 0;
		}else{
			return $total_products;	
		}		
	}
	
	/**
	 *	Returns level of current category
	 */
	private function GetLevel($cid = 0)
	{
		static $level = 0;
		$sql = 'SELECT id, parent_id FROM '.TABLE_CATEGORIES.' WHERE id = '.(int)$cid;
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[1] > 0){
            // additional check with level
			if(($result[0]['parent_id'] == '0') || ($level++ > 2)) return 2;
			else return 1 + $this->GetLevel($result[0]['parent_id']);
		}
		return 1;
	}
	
	/**
	 * Returns levels info
	 */
	public function GetLevelsInfo($category_id)
	{
		$output = array('first'=>array('name'=>'', 'link'=>''),
					    'second'=>array('name'=>'', 'link'=>''),
						'third'=>array('name'=>'', 'link'=>''));

		$sql = 'SELECT
					c.id,
					c.parent_id,
					cd.name									
				FROM '.TABLE_CATEGORIES.' c
					LEFT OUTER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' cd ON c.id = cd.category_id
				WHERE
					c.id = _CID_ AND
					cd.language_id = \''.Application::Get('lang').'\'';
		
		$sql_1 = str_replace('_CID_', (int)$category_id, $sql);
		$result = database_query($sql_1, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[1] > 0){			
			$output['first']['name'] = $result[0]['name'];
			$output['first']['link'] = prepare_link('category', 'cid', $result[0]['id'], '', $result[0]['name'], '', '', true);
			
			$sql_2 = str_replace('_CID_', $result[0]['parent_id'], $sql);
			$result_2 = database_query($sql_2, DATA_AND_ROWS, FIRST_ROW_ONLY);
			if($result_2[1] > 0){
				$output['second']['name'] = $result_2[0]['name'];
				$output['second']['link'] = prepare_link('category', 'cid', $result_2[0]['id'], '', $result_2[0]['name'], '', '', true);				
			
				$sql_3 = str_replace('_CID_', $result_2[0]['parent_id'], $sql);
				$result_3 = database_query($sql_3, DATA_AND_ROWS, FIRST_ROW_ONLY);
				if($result_3[1] > 0){
					$output['third']['name'] = $result_3[0]['name'];
					$output['third']['link'] = prepare_link('category', 'cid', $result_3[0]['id'], '', $result_3[0]['name'], '', '', true);
				}				
			}
		}
		
		return $output;		
	}	
	
	/**
	 * Updates products count
	 */
	public static function UpdateProductsCount($category_id = 0, $operation = '+')	
	{
		if($operation == '-'){
			$operation_clause = 'products_count = IF(products_count >= 1, products_count - 1, 0)';
		}else{
			$operation_clause = 'products_count = products_count + 1';
		}

		while(!empty($category_id)){			
			$sql = 'UPDATE '.TABLE_CATEGORIES.' SET '.$operation_clause.' WHERE id = '.(int)$category_id;
			if(!database_void_query($sql)){ /* echo 'error!'; */ }
			
			$sql = 'SELECT parent_id FROM '.TABLE_CATEGORIES.' WHERE id = '.(int)$category_id;
			$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
			if($result[1] > 0){
				$category_id = $result[0]['parent_id'];
			}else{
				$category_id = 0;
			}				
		}		
	}
	
	/**
	 * Draws sub categories
	 * 		@param $category_id
	 */
	public function DrawSubCategories($category_id = '0', $draw = true)
	{
		$lang = Application::Get('lang');
		$show_products = ModulesSettings::Get('products_catalog', 'show_products_in_categories');
		$output = '';
		
		$categories_images = false;
		$categories_columns = '3';
		
		if(Modules::IsModuleInstalled('products_catalog')){				
			if(ModulesSettings::Get('products_catalog', 'show_categories_images') == 'yes') $categories_images = true;
			$categories_columns = ModulesSettings::Get('products_catalog', 'columns_number_on_page');
		}
		
		$category_info = self::GetInfoByID($category_id);

		$sql = 'SELECT c.id,
					c.icon,
					c.icon_thumb, 
					c.products_count,
					c.priority_order,
					cd.language_id,
					cd.name,									
					cd.description
				FROM '.TABLE_CATEGORIES.' c
					LEFT OUTER JOIN '.TABLE_CATEGORIES_DESCRIPTION.' cd ON c.id = cd.category_id
				WHERE
					c.parent_id = '.(int)$category_id.' AND 
					cd.language_id = \''.$lang.'\'';
		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);
		
		if($result[1] > 0){
			$output .= '<table class="sub_categories_table" width="100%" align="center" border="0">';
			$output .= '<tr>';
			for($i=0; $i < $result[1]; $i++){
				if(($i > 0) && ($i % $categories_columns == 0)) $output .= '</tr><tr>';
				$output .= '<td align="left" valign="top" width="32px">';
				$icon_file_thumb = ($result[0][$i]['icon_thumb'] != '') ? $result[0][$i]['icon_thumb'] : '';
				if($categories_images && $icon_file_thumb != ''){
					$output .= '<img src="images/categories/'.$icon_file_thumb.'" width="24px" height="24px" alt="'.$result[0][$i]['name'].'" title="'.$result[0][$i]['name'].'" />';
				}else{
					$output .= '<img src="images/categories/default_icon.png" width="24px" height="24px" alt="'.$result[0][$i]['name'].'" title="'.$result[0][$i]['name'].'" />';				
				}
				$output .= '</td>';
				$output .= '<td>';
				$output .= prepare_link('category', 'cid', $result[0][$i]['id'], '', $result[0][$i]['name'], '', '').(($show_products == 'yes') ? ' ('.$result[0][$i]['products_count'].')' : '');
				$output .= '</td>';
			}
			$output .= '</tr>';
			$output .= '</table>';			
		}
		
		if($draw) echo $output;		
		else return $output;
	}
	
}
?>