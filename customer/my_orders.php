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

if($objLogin->IsLoggedInAsCustomer() && Modules::IsModuleInstalled('shopping_cart')) {
	
	if(ModulesSettings::Get('shopping_cart', 'is_active') == 'yes'){
		
		$action 	= MicroGrid::GetParameter('action');
		$rid    	= MicroGrid::GetParameter('rid');
		$mode   	= 'view';
		$msg 		= '';
		
		$objOrders = new Orders($objLogin->GetLoggedID());
		
		if($action=='add'){		
			$mode = 'add';
		}else if($action=='create'){
			if($objOrders->AddRecord()){
				$msg = draw_success_message(_ADDING_OPERATION_COMPLETED, false);
				$mode = 'view';
			}else{
				$msg = draw_important_message($objOrders->error, false);
				$mode = 'add';
			}
		}else if($action=='edit'){
			$mode = 'edit';
		}else if($action=='update'){
			if($objOrders->UpdateRecord($rid)){
				$msg = draw_success_message(_UPDATING_OPERATION_COMPLETED, false);
				$mode = 'view';
			}else{
				$msg = draw_important_message($objOrders->error, false);
				$mode = 'edit';
			}		
		}else if($action=='delete'){
			if($objOrders->DeleteRecord($rid)){
				$msg = draw_success_message(_DELETING_OPERATION_COMPLETED, false);
			}else{
				$msg = draw_important_message($objOrders->error, false);
			}
			$mode = 'view';
		}else if($action=='details'){		
			$mode = 'details';		
		}else if($action=='cancel_add'){		
			$mode = 'view';		
		}else if($action=='cancel_edit'){				
			$mode = 'view';
		}else if($action=='description'){				
			$mode = 'description';
		}else if($action=='invoice'){				
			$mode = 'invoice';
		}
		
		// Start main content
		draw_title_bar(
			prepare_breadcrumbs(array(_MY_ACCOUNT=>'',_ORDERS_MANAGEMENT=>'',ucfirst($action)=>'')),
			(($mode == 'invoice' || $mode == 'description') ? '<a href="javascript:void(\''.$mode.'|preview\')" onclick="javascript:appPreview(\''.$mode.'\');">'._PRINT.'</a>' : '')
		);

		//if($objSession->IsMessage('notice')) echo $objSession->GetMessage('notice');
		echo $msg;
		
		draw_content_start();	
		if($mode == 'view'){		
			$objOrders->DrawViewMode();	
		}else if($mode == 'add'){		
			$objOrders->DrawAddMode();		
		}else if($mode == 'edit'){		
			$objOrders->DrawEditMode($rid);
		}else if($mode == 'details'){		
			$objOrders->DrawDetailsMode($rid);		
		}else if($mode == 'description'){		
			$objOrders->DrawOrderDescription($rid);		
		}else if($mode == 'invoice'){
			$objOrders->DrawOrderInvoice($rid);
		}
		draw_content_end();	
		
	}else{
		draw_important_message(_NOT_AUTHORIZED);
	}
}else{
	draw_title_bar(_PAGES);
	draw_important_message(_NOT_AUTHORIZED);
}

?>