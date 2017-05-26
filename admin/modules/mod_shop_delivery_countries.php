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

if($objLogin->IsLoggedInAsAdmin() && Modules::IsModuleInstalled('shopping_cart')){

	// for uniquie prefix 
	$action 	= MicroGrid::GetParameter('action');
	$rid    	= MicroGrid::GetParameter('rid');
	$delivery_id = MicroGrid::GetParameter('delivery_id', false);
	$mode   	= 'view';
	$msg 		= '';
	
	$objDeliveryCountries = new DeliveryCountries($delivery_id);
	
	if($action=='add'){		
		$mode = 'add';
	}else if($action=='create'){
		if($objDeliveryCountries->AddRecord()){
			$msg = draw_success_message(_ADDING_OPERATION_COMPLETED, false);
			$mode = 'view';
		}else{
			$msg = draw_important_message($objDeliveryCountries->error, false);
			$mode = 'add';
		}
	}else if($action=='edit'){
		$mode = 'edit';
	}else if($action=='update'){
		if($objDeliveryCountries->UpdateRecord($rid)){
			$msg = draw_success_message(_UPDATING_OPERATION_COMPLETED, false);
			$mode = 'view';
		}else{
			$msg = draw_important_message($objDeliveryCountries->error, false);
			$mode = 'edit';
		}		
	}else if($action=='delete'){
		if($objDeliveryCountries->DeleteRecord($rid)){
			$msg = draw_success_message(_DELETING_OPERATION_COMPLETED, false);
		}else{
			$msg = draw_important_message($objDeliveryCountries->error, false);
		}
		$mode = 'view';
	}else if($action=='details'){		
		$mode = 'details';		
	}else if($action=='cancel_add'){		
		$mode = 'view';		
	}else if($action=='cancel_edit'){				
		$mode = 'view';
	}
	
	$objDeliveries = new Deliveries();
	$delivery_info = $objDeliveries->GetInfoByID($delivery_id);
	$delivery_info_name = isset($delivery_info['name']) ? $delivery_info['name'] : '';

	// Start main content
	draw_title_bar(
		prepare_breadcrumbs(array(_SHOPPING_CART=>'',_DELIVERY_COUNTRIES=>'',$delivery_info_name=>'',ucfirst($action)=>'')),
		prepare_permanent_link('index.php?admin=mod_shop_delivery_settings', _BUTTON_BACK)
	);
    	
	//if($objSession->IsMessage('notice')) echo $objSession->GetMessage('notice');
	echo $msg;

	draw_content_start();	
	if($mode == 'view'){		
		$objDeliveryCountries->DrawViewMode();	
	}else if($mode == 'add'){		
		$objDeliveryCountries->DrawAddMode();		
	}else if($mode == 'edit'){		
		$objDeliveryCountries->DrawEditMode($rid);		
	}else if($mode == 'details'){		
		$objDeliveryCountries->DrawDetailsMode($rid);		
	}
	draw_content_end();	

}else{
	draw_title_bar(_ADMIN);
	draw_important_message(_NOT_AUTHORIZED);
}
?>