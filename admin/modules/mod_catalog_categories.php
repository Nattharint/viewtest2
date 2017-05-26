<?php
/**
* @project ApPHP Shopping Cart
* @copyright (c) 2012 ApPHP
* @author ApPHP <info@apphp.com>
* @license http://www.gnu.org/licenses/
*/

// *** Make sure the file isn't accessed directly
defined('APPHP_EXEC') or die('Restricted Access');
//--------------------------------------------------------------------------

if($objLogin->IsLoggedInAsAdmin() && Modules::IsModuleInstalled('products_catalog')){

	$action 	= MicroGrid::GetParameter('action');
	$rid    	= MicroGrid::GetParameter('rid');
	$cid 		= isset($_GET['cid']) ? (int)$_GET['cid'] : '0';
	$mode   	= 'view';
	$msg 		= '';
	
	$objCategories = new Categories();
	
	if($action=='add'){		
		$mode = 'add';
	}else if($action=='create'){
		if($objCategories->AddRecord()){
			$msg = draw_success_message(_ADDING_OPERATION_COMPLETED, false);
			$mode = 'view';
		}else{
			$msg = draw_important_message($objCategories->error, false);
			$mode = 'add';
		}
	}else if($action=='edit'){
		$mode = 'edit';
	}else if($action=='update'){
		if($objCategories->UpdateRecord($rid)){
			$msg = draw_success_message(_UPDATING_OPERATION_COMPLETED, false);
			$mode = 'view';
		}else{
			$msg = draw_important_message($objCategories->error, false);
			$mode = 'edit';
		}		
	}else if($action=='delete'){
		if($objCategories->DeleteRecord($rid)){
			$msg = draw_success_message(_DELETING_OPERATION_COMPLETED, false);
		}else{
			$msg = draw_important_message($objCategories->error, false);
		}
		$mode = 'view';
	}else if($action=='details'){		
		$mode = 'details';		
	}else if($action=='cancel_add'){		
		$mode = 'view';		
	}else if($action=='cancel_edit'){				
		$mode = 'view';
	}else if($action=='recalculate'){
		if(Categories::RecalculateProductsCount()){
			$msg = draw_success_message(_UPDATING_OPERATION_COMPLETED, false);
		}else{
			$msg = draw_important_message(Categories::GetStaticError(), false);
		}
		$mode = 'view';
	}
	
	// Start main content
	$category_info = $objCategories->GetInfoByID($cid);
	$category_info_parent_id = isset($category_info['parent_id']) ? (int)$category_info['parent_id'] : '0';
	$category_info_name = isset($category_info['name']) ? $category_info['name'] : '';	
	$category_info = $objCategories->GetInfoByID($category_info_parent_id);
	$category_info_parent_name = isset($category_info['name']) ? $category_info['name'] : '';	

	// Start main content
	draw_title_bar(
		prepare_breadcrumbs(array(
			_PRODUCTS_CATALOG=>'',
			_PRODUCTS_MANAGEMENT=>'',
			_CATEGORIES_MANAGEMENT=>'',
			$category_info_parent_name=>'',
			$category_info_name=>'',
			ucfirst($action)=>'')),
		(($cid != '0') ? prepare_permanent_link('index.php?admin=mod_catalog_categories&cid='.$category_info_parent_id, _BUTTON_BACK) : '')
	);
    	
	echo $msg;

	draw_content_start();	
	if($mode == 'view'){
		$objCategories->DrawOperationLinks(prepare_permanent_link('index.php?admin=mod_catalog_categories&mg_action=recalculate', '[ '._RECALCULATE_PRODUCTS_COUNT.' ]'));		
		$objCategories->DrawViewMode();	
	}else if($mode == 'add'){		
		$objCategories->DrawAddMode();		
	}else if($mode == 'edit'){		
		$objCategories->DrawEditMode($rid);		
	}else if($mode == 'details'){		
		$objCategories->DrawDetailsMode($rid);		
	}
	draw_content_end();	

}else{
	draw_title_bar(_ADMIN);
	draw_important_message(_NOT_AUTHORIZED);
}
?>