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
			///
		}else if($action=='edit'){
			$mode = 'edit';
		}else if($action=='update'){
			///
		}else if($action=='delete'){
			///
			$mode = 'view';
		}else if($action=='details'){		
			$mode = 'details';		
		}else if($action=='cancel_add'){		
			$mode = 'view';		
		}else if($action=='cancel_edit'){				
			$mode = 'view';
		}else if($action=='description'){				
			$mode = 'description';
		}
		
		// Start main content
		draw_title_bar(prepare_breadcrumbs(array(_MY_ACCOUNT=>'',_PRODUCTS_MANAGEMENT=>'',ucfirst($action)=>'')));

		//if($objSession->IsMessage('notice')) echo $objSession->GetMessage('notice');
		echo $msg;
	
		draw_content_start();	
		if($mode == 'view'){		
			$objOrders->DrawMyProducts();	
		}else if($mode == 'add'){		
			///
		}else if($mode == 'edit'){		
			///
		}else if($mode == 'details'){		
			///
		}else if($mode == 'description'){		
			///
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