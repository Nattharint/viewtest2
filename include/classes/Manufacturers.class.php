<?php

/**
 *	Class Manufacturers (for Shopping Cart ONLY)
 *  -------------- 
 *	Written by  : ApPHP
 *  Updated	    : 31.10.2010
 *	Written by  : ApPHP
 *
 *	PUBLIC				  	STATIC				 	PRIVATE
 * 	------------------	  	---------------     	---------------
 *	__construct             DrawSideBlock
 *	__destruct              GetManufacturersSelectBox
 *	DrawManufacturers
 *		
 **/


class Manufacturers extends MicroGrid {
	
	protected $debug = false;
	
	//==========================================================================
    // Class Constructor
	//==========================================================================
	function __construct()
	{		
		parent::__construct();

		$this->params = array();
		
		## for standard fields
		if(isset($_POST['name']))   $this->params['name'] = prepare_input($_POST['name']);
		if(isset($_POST['description']))   $this->params['description'] = prepare_input($_POST['description']);
		
		## for checkboxes 
		//$this->params['field4'] = isset($_POST['field4']) ? prepare_input($_POST['field4']) : '0';

		## for images
		if(isset($_POST['logo_file'])){
			$this->params['logo_file'] = prepare_input($_POST['logo_file']);
		}else if(isset($_FILES['logo_file']['name']) && $_FILES['logo_file']['name'] != ''){
			// nothing 			
		}else if (self::GetParameter('action') == 'create'){
			$this->params['logo_file'] = '';
		}

		$this->params['language_id'] = MicroGrid::GetParameter('language_id');
	
		//$this->uPrefix 		= 'prefix_';
		$this->primaryKey 	= 'id';
		$this->tableName 	= TABLE_MANUFACTURERS;
		$this->dataSet 		= array();
		$this->error 		= '';
		$this->formActionURL = 'index.php?admin=mod_catalog_manufacturers';
		$this->actions      = array('add'=>true, 'edit'=>true, 'details'=>true, 'delete'=>true);
		$this->actionIcons  = true;
		$this->allowRefresh = true;

		$this->allowLanguages = false;
		$this->languageId  	= ($this->params['language_id'] != '') ? $this->params['language_id'] : Languages::GetDefaultLang();
		$this->WHERE_CLAUSE = ''; // WHERE .... 
		$this->ORDER_CLAUSE = 'ORDER BY '.$this->tableName.'.name ASC';
		
		$this->isAlterColorsAllowed = true;

		$this->isPagingAllowed = true;
		$this->pageSize = 20;

		$this->isSortingAllowed = true;

		$this->isExportingAllowed = true;
		$this->arrExportingTypes = array('csv'=>true);

		$this->isFilteringAllowed = false;
		// define filtering fields
		$this->arrFilteringFields = array();

		// prepare languages array		
		/// $total_languages = Languages::GetAllActive();
		/// $arr_languages      = array();
		/// foreach($total_languages[0] as $key => $val){
		/// 	$arr_languages[$val['abbreviation']] = $val['lang_name'];
		/// }

		//---------------------------------------------------------------------- 
		// VIEW MODE
		// format: strip_tags
		// format: nl2br
		// format: 'format'=>'date', 'format_parameter'=>'M d, Y, g:i A' + IF(date_created = '0000-00-00 00:00:00', '', date_created) as date_created,
		//---------------------------------------------------------------------- 
		$this->VIEW_MODE_SQL = 'SELECT '.$this->primaryKey.',
									name,
									description,
									logo_file,
									logo_file_thumb
								FROM '.$this->tableName;		
		// define view mode fields
		$this->arrViewModeFields = array(		
			'logo_file_thumb' => array('title'=>_IMAGE, 'type'=>'image', 'align'=>'left', 'width'=>'70px', 'sortable'=>true, 'nowrap'=>'', 'visible'=>'', 'image_width'=>'50px', 'image_height'=>'30px', 'target'=>'images/manufacturers/', 'no_image'=>'no_image.png'),
			'name'      	  => array('title'=>_NAME, 'type'=>'label', 'align'=>'left', 'width'=>'', 'sortable'=>true, 'nowrap'=>'', 'visible'=>'', 'height'=>'', 'maxlength'=>'38', 'format'=>'', 'format_parameter'=>''),
			'description' 	  => array('title'=>_DESCRIPTION, 'type'=>'label', 'align'=>'left', 'width'=>'', 'sortable'=>true, 'nowrap'=>'', 'visible'=>'', 'height'=>'', 'maxlength'=>'80', 'format'=>'', 'format_parameter'=>''),
		);
		
		//---------------------------------------------------------------------- 
		// ADD MODE
		// - Validation Type: alpha|numeric|float|alpha_numeric|text|email|ip_address|password
		// 	 Validation Sub-Type: positive (for numeric and float)
		//   Ex.: 'validation_type'=>'numeric', 'validation_type'=>'numeric|positive'
		// - Validation Max Length: 12, 255 ....
		//   Ex.: 'validation_maxlength'=>'255'
		//---------------------------------------------------------------------- 
		// define add mode fields
		$this->arrAddModeFields = array(		
			'name'  	  => array('title'=>_NAME, 'type'=>'textbox',  'width'=>'270px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'125', 'default'=>'', 'validation_type'=>'', 'unique'=>false, 'visible'=>true),
			'description' => array('title'=>_DESCRIPTION, 'type'=>'textarea', 'width'=>'370px', 'height'=>'90px', 'required'=>false, 'readonly'=>false, 'default'=>'', 'editor_type'=>'wysiwyg', 'validation_type'=>'text', 'maxlength'=>'1024', 'validation_maxlength'=>'1024', 'unique'=>false),
			'logo_file'   => array('title'=>_IMAGE, 'type'=>'image',    'width'=>'210px', 'required'=>false, 'readonly'=>false, 'target'=>'images/manufacturers/', 'no_image'=>'', 'random_name'=>'true', 'unique'=>false, 'thumbnail_create'=>true, 'thumbnail_field'=>'logo_file_thumb', 'thumbnail_width'=>'120px', 'thumbnail_height'=>'90px', 'file_maxsize'=>'400k'),
		);

		//---------------------------------------------------------------------- 
		// EDIT MODE
		// - Validation Type: alpha|numeric|float|alpha_numeric|text|email|ip_address|password
		//   Validation Sub-Type: positive (for numeric and float)
		//   Ex.: 'validation_type'=>'numeric', 'validation_type'=>'numeric|positive'
		// - Validation Max Length: 12, 255 ....
		//   Ex.: 'validation_maxlength'=>'255'
		//---------------------------------------------------------------------- 
		$this->EDIT_MODE_SQL = 'SELECT
								'.$this->tableName.'.'.$this->primaryKey.',
								'.$this->tableName.'.name,
								'.$this->tableName.'.description,
								'.$this->tableName.'.logo_file,
								'.$this->tableName.'.logo_file_thumb
							FROM '.$this->tableName.'
							WHERE '.$this->tableName.'.'.$this->primaryKey.' = _RID_';		
		// define edit mode fields
		$this->arrEditModeFields = array(		
			'name'  	  => array('title'=>_NAME, 'type'=>'textbox',  'width'=>'270px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'125', 'default'=>'', 'validation_type'=>'', 'unique'=>false, 'visible'=>true),
			'description' => array('title'=>_DESCRIPTION, 'type'=>'textarea', 'width'=>'370px', 'height'=>'90px', 'required'=>false, 'readonly'=>false, 'default'=>'', 'editor_type'=>'wysiwyg', 'validation_type'=>'text', 'maxlength'=>'1024', 'validation_maxlength'=>'1024', 'unique'=>false),
			'logo_file'   => array('title'=>_IMAGE, 'type'=>'image',    'width'=>'210px', 'required'=>false, 'target'=>'images/manufacturers/', 'no_image'=>'no_image.png', 'random_name'=>'true', 'unique'=>false, 'image_width'=>'120px', 'image_height'=>'90px', 'thumbnail_create'=>true, 'thumbnail_field'=>'logo_file_thumb', 'thumbnail_width'=>'120px', 'thumbnail_height'=>'90px', 'file_maxsize'=>'400k'),
		);

		//---------------------------------------------------------------------- 
		// DETAILS MODE
		//----------------------------------------------------------------------
		$this->DETAILS_MODE_SQL = $this->EDIT_MODE_SQL;
		$this->arrDetailsModeFields = array(		
			'name'  	  => array('title'=>_NAME, 'type'=>'label'),
			'description' => array('title'=>_DESCRIPTION, 'type'=>'label', 'format'=>'nl2br'),
			'logo_file'   => array('title'=>_IMAGE, 'type'=>'image', 'target'=>'images/manufacturers/', 'no_image'=>'no_image.png', 'image_width'=>'120px', 'image_height'=>'90px'),
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
	 * Draws side block with manufacturers links
	 * 		@param $draw
	 **/
	public static function DrawSideBlock($draw = true)
	{
		$view_type = ModulesSettings::Get('products_catalog', 'manufacturers_block_type');
		
		$output = '';
		$sql = 'SELECT id,
					name,
					description
				FROM '.TABLE_MANUFACTURERS;

		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);
		if($result[1] > 0){
			$output .= draw_block_top(_MANUFACTURERS, '', 'maximized', false);
			if($view_type == 'dropdown'){
				$output .= '<select id="selManufacturers" style="width:100%" onchange="javascript:if(this.value != \'\')appGoTo(\'page=manufacturer\',\'&mid=\'+this.value);">';
				$output .= '<option value="">-- '._SELECT.' --</option>';
				for($i=0; $i < $result[1]; $i++){
					$selected = (Application::Get('manufacturer_id') == $result[0][$i]['id']) ? ' selected="selected"' : '';
					$output .= '<option value="'.$result[0][$i]['id'].'"'.$selected.'>'.$result[0][$i]['name'].'</option>';
				}
				$output .= '</select>';
				$output .= prepare_link('manufacturers', '', '', 'all', _SEE_ALL.' &raquo;', 'main_menu_link main_menu_last');
			}else{
				$output .= '<ul>';
				for($i=0; $i < $result[1]; $i++){
					$output .= '<li>'.prepare_link('manufacturer', 'mid', $result[0][$i]['id'], '', $result[0][$i]['name'], 'main_menu_link').'</li>';
				}
				$output .= '<li>'.prepare_link('manufacturers', '', '', 'all', _SEE_ALL.' &raquo;', 'main_menu_link main_menu_last').'</li>';
				$output .= '</ul>';
			}
			$output .= draw_block_bottom(false);
		}
		
		if($draw) echo $output;
		else return $output;
	}

	/**
	 * Draws manufacturers
	 */
	public function DrawManufacturers()
	{
		$output = '';
		$sql = 'SELECT id,
					name,
					logo_file,
					logo_file_thumb,
					description
				FROM '.TABLE_MANUFACTURERS;

		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);
		$output = '<table width="98%" border="0" align="center">';
		if($result[1] > 0){			
			$output .= '<tr><th colspan="3" nowrap height="5px"></th></tr>
				<tr>					
					<th width="120px">'._NAME.'</th>
					<th>'._DESCRIPTION.'</th>
					<th width="60px" align="center">'._IMAGE.'</th>
				</tr>';			
			for($i=0; $i < $result[1]; $i++){
				$icon_file_thumb = ($result[0][$i]['logo_file_thumb'] != '') ? $result[0][$i]['logo_file_thumb'] : 'no_image.png';
				$icon_file = ($result[0][$i]['logo_file'] != '') ? $result[0][$i]['logo_file'] : 'no_image.png';
				$output .= '<tr><td colspan="3" style="padding:1px 0px;">'.draw_line('no_margin_line', IMAGE_DIRECTORY, false).'</td></tr>
					<tr valign="top">
						<td>'.prepare_link('manufacturer', 'mid', $result[0][$i]['id'], '', $result[0][$i]['name']).'</td>
						<td>'.$result[0][$i]['description'].'</td>
						<td><img style="cursor:pointer;" src="images/manufacturers/'.$icon_file_thumb.'" width="60px" title="'._CLICK_TO_INCREASE.'" alt="" onclick="appOpenPopup(\'images/manufacturers/'.$icon_file.'\')" /></td>												
					</tr>';			
			}
		}
		$output .= '</table>';
		
		if($result[1] > 0){
			echo $output;
		}		
	}
	
	/**
	 *	Returns manufacturers select box
	 *		@param $manufacturer_id
	 */
	public static function GetManufacturersSelectBox($page = 'page=manufacturers', $manufacturer_id = '')
	{
		$output = '';
		$sql = 'SELECT id,
					name,
					logo_file,
					logo_file_thumb,
					description
				FROM '.TABLE_MANUFACTURERS;
		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS, FETCH_ASSOC);
		if($result[1] > 0){
			$output .= '<select class="selManufacturers" name="selManufacturers" onchange="appGoTo(\''.$page.'\',\'&mid=\'+this.value)">';
			$output .= '<option value="">-- '._ALL.' --</option>';
			for($i=0; $i < $result[1]; $i++){
				$output .= '<option '.(($manufacturer_id == $result[0][$i]['id']) ? 'selected="selected"' : '').' value="'.$result[0][$i]['id'].'">'.$result[0][$i]['name'].'</option>';
			}
			$output .= '</select>';			
		}		
		return $output;		
	}

}
?>