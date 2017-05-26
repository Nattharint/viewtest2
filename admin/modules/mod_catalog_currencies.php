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

if($objLogin->IsLoggedInAs('owner','mainadmin') && Modules::IsModuleInstalled('products_catalog')){

	$action 	= MicroGrid::GetParameter('action');
	$rid    	= MicroGrid::GetParameter('rid');
	$mode   	= 'view';
	$msg 		= '';
	
	$objCurrencies = new Currencies();
	
	if($action=='add'){		
		$mode = 'add';
	}else if($action=='create'){
		if($objCurrencies->AddRecord()){
			$msg = draw_success_message(_ADDING_OPERATION_COMPLETED, false);
			$mode = 'view';
		}else{
			$msg = draw_important_message($objCurrencies->error, false);
			$mode = 'add';
		}
	}else if($action=='edit'){
		$mode = 'edit';
	}else if($action=='update'){
		if($objCurrencies->UpdateRecord($rid)){
			$msg = draw_success_message(_UPDATING_OPERATION_COMPLETED, false);
			$mode = 'view';
		}else{
			$msg = draw_important_message($objCurrencies->error, false);
			$mode = 'edit';
		}		
	}else if($action=='delete'){
		if($objCurrencies->DeleteRecord($rid)){
			$msg = draw_success_message(_DELETING_OPERATION_COMPLETED, false);
		}else{
			$msg = draw_important_message($objCurrencies->error, false);
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
	draw_title_bar(prepare_breadcrumbs(array(_PRODUCTS_CATALOG=>'',_SETTINGS=>'',_CURRENCIES_MANAGEMENT=>'',ucfirst($action)=>'')));
    	
	if($mode == 'view' && $msg == ''){
		$msg = draw_message(_CURRENCIES_DEFAULT_ALERT, false);		
	}
	echo $msg;

	draw_content_start();	
	if($mode == 'view'){		
		$objCurrencies->DrawViewMode();	
	}else if($mode == 'add'){		
		$objCurrencies->DrawAddMode();		
	}else if($mode == 'edit'){		
		$objCurrencies->DrawEditMode($rid);		
	}else if($mode == 'details'){		
		$objCurrencies->DrawDetailsMode($rid);		
	}
	draw_content_end();	

}else{
	draw_title_bar(_ADMIN);
	draw_important_message(_NOT_AUTHORIZED);
}
?>