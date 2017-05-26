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
	$mode   	= 'view';
	$msg 		= '';
	
	$objProductDescr = new ProductsDescription();
	
	if($action=='add'){		
		$mode = 'add';
	}else if($action=='create'){
		#if($objProductDescr->AddRecord()){
		#	$msg = draw_success_message(_ADDING_OPERATION_COMPLETED, false);
		#	$mode = 'view';
		#}else{
		#	$msg = draw_important_message($objProductDescr->error, false);
		#	$mode = 'add';
		#}
	}else if($action=='edit'){
		$mode = 'edit';
	}else if($action=='update'){
		if($objProductDescr->UpdateRecord($rid)){
			$msg = draw_success_message(_UPDATING_OPERATION_COMPLETED, false);
			$mode = 'view';
		}else{
			$msg = draw_important_message($objProductDescr->error, false);
			$mode = 'edit';
		}		
	}else if($action=='delete'){
		#if($objProductDescr->DeleteRecord($rid)){
		#	$msg = draw_success_message(_DELETING_OPERATION_COMPLETED, false);
		#}else{
		#	$msg = draw_important_message($objProductDescr->error, false);
		#}
		#$mode = 'view';
	}else if($action=='details'){		
		$mode = 'details';		
	}else if($action=='cancel_add'){		
		$mode = 'view';		
	}else if($action=='cancel_edit'){				
		$mode = 'view';
	}
	
	
	$product_info = $objProductDescr->GetInfoByID(Application::Get('product_id'));
	$product_info_name = isset($product_info['name']) ? $product_info['name'] : _PRODUCTS_DESCRIPTION;
	draw_title_bar(
		prepare_breadcrumbs(array(_PRODUCTS_CATALOG=>'',_PRODUCTS_MANAGEMENT=>'',_PRODUCTS_DESCRIPTION=>'',$product_info_name=>'',ucfirst($action)=>'')),
		prepare_permanent_link('index.php?admin=mod_catalog_products', _BUTTON_BACK)
	);
    	
	echo $msg;

	draw_content_start();	
	if($mode == 'view'){		
		$objProductDescr->DrawViewMode();	
	}else if($mode == 'add'){		
		$objProductDescr->DrawAddMode();		
	}else if($mode == 'edit'){		
		$objProductDescr->DrawEditMode($rid);		
	}else if($mode == 'details'){		
		$objProductDescr->DrawDetailsMode($rid);		
	}
	draw_content_end();	

}else{
	draw_title_bar(_ADMIN);
	draw_important_message(_NOT_AUTHORIZED);
}
?>