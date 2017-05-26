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

if($objLogin->IsLoggedInAs('owner','mainadmin') && Modules::IsModuleInstalled('products_catalog')) {
	
	$action = MicroGrid::GetParameter('action');
	$rid    = MicroGrid::GetParameter('rid');
	$settings_key    = MicroGrid::GetParameter('settings_key', false);
	$settings_value  = MicroGrid::GetParameter('settings_value', false);
	$mode   = 'view';
	$msg    = '';
	
	$objProductsCatalog = new ModulesSettings('products_catalog');
	
	if($action=='add'){		
		$mode = 'add';
	}else if($action=='create'){
		if($objProductsCatalog->AddRecord()){
			$msg = draw_success_message(_ADDING_OPERATION_COMPLETED, false);
			$mode = 'view';
		}else{
			$msg = draw_important_message($objProductsCatalog->error, false);
			$mode = 'add';
		}
	}else if($action=='edit'){
		$mode = 'edit';
	}else if($action=='update'){
		if($objProductsCatalog->UpdateRecord($rid)){
			if($settings_key == 'is_active' && $settings_value == 'no'){
				$objProductsCatalog->InactiveDependentModules();
			}
			$msg = draw_success_message(_UPDATING_OPERATION_COMPLETED, false);
			$mode = 'view';
		}else{
			$msg = draw_important_message($objProductsCatalog->error, false);
			$mode = 'edit';
		}		
	}else if($action=='delete'){
		if($objProductsCatalog->DeleteRecord($rid)){
			$msg = draw_success_message(_DELETING_OPERATION_COMPLETED, false);
		}else{
			$msg = draw_important_message($objProductsCatalog->error, false);
		}
		$mode = 'view';
	}else if($action=='details'){		
		$mode = 'details';		
	}else if($action=='cancel_add'){		
		$mode = 'view';		
	}else if($action=='cancel_edit'){				
		$mode = 'view';
	}
	
	// Start main content
	draw_title_bar(prepare_breadcrumbs(array(_PRODUCTS_CATALOG=>'',_SETTINGS=>'',_CATALOG_SETTINGS=>'',ucfirst($action)=>'')));
    echo '<br />';
	
	echo $msg;

	draw_content_start();

	if($mode == 'view'){
		$objProductsCatalog->DrawViewMode();	
	}else if($mode == 'add'){		
		$objProductsCatalog->DrawAddMode();		
	}else if($mode == 'edit'){		
		$objProductsCatalog->DrawEditMode($rid);		
	}else if($mode == 'details'){ 
		$objProductsCatalog->DrawDetailsMode($rid);		
	}
	draw_content_end();

}else{
	draw_title_bar(_ADMIN);
	draw_important_message(_NOT_AUTHORIZED);
}
?>