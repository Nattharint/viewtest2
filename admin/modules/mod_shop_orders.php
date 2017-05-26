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
	
	$inventory_control = ModulesSettings::Get('shopping_cart', 'inventory_control');

	$action 	   = MicroGrid::GetParameter('action');
	$title_action  = $action;
	$rid           = MicroGrid::GetParameter('rid');

	$order_status  = MicroGrid::GetParameter('status', false);
	$order_number  = MicroGrid::GetParameter('order_number', false);
	$customer_id   = MicroGrid::GetParameter('customer_id', false);
	$units_updated = MicroGrid::GetParameter('units_updated', false);

	$mode  = 'view';
	$msg   = '';
	$links = '';
	
	$objOrders = new Orders();
	
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
			if($inventory_control == 'yes' && !$units_updated){				
				// update units in stock
				Orders::UpdateUnitsInStock($order_number);
			}
			if($order_status == '2' || $order_status == '4' || $order_status == '5'){
				// 2 - PAID,  4 - RECEIVED,  5 - COMPLETED
				$objOrders->UpdatePaymentDate($rid);				
				// send email to customer
				$objCart = new ShoppingCart();
				$objCart->SendOrderEmail($order_number, 'completed', $customer_id);
			}else if($order_status == '6'){
				// 6 - REFUND - return units to stock
				$objCart = new ShoppingCart();
				$objCart->SendOrderEmail($order_number, 'refunded', $customer_id);
				Orders::ReturnUnitsToStock($order_number);
			}
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
	}else if($action=='send_invoice'){
		if($objOrders->SendInvoice($rid)){
			$msg = draw_success_message(_INVOICE_SENT_SUCCESS, false);
		}else{
			$msg = draw_important_message($objOrders->error, false);
		}
		$mode = 'view';
		$title_action = _SEND_INVOICE;
	}else if($action=='clean_credit_card'){				
		if($objOrders->CleanCreditCardInfo($rid)){
			$msg = draw_success_message(_OPERATION_COMMON_COMPLETED, false);
		}else{
			$msg = draw_important_message($objOrders->error, false);
		}
		$mode = 'view';
		$title_action = 'Clean';
	}
		
	// Start main content
	if($mode == 'invoice'){
		$links .= prepare_permanent_link('javascript:void(\'invoice|send\')', '<img src="images/mail.png" alt="" /> '._SEND_INVOICE, '', '', '', 'onclick="if(confirm(\''._PERFORM_OPERATION_COMMON_ALERT.'\')) appGoToPage(\'index.php?admin=mod_shop_orders\', \'&mg_action=send_invoice&mg_rid='.$rid.'&token='.Application::Get('token').'\', \'post\');"').' &nbsp;|&nbsp; ';
		$links .= prepare_permanent_link('javascript:void(\'invoice|preview\')', '<img src="images/printer.png" alt="" /> '._PRINT, '', '', '', 'onclick="javascript:appPreview(\'invoice\');"');
	}else if($mode == 'description'){
		$links .= prepare_permanent_link('javascript:void(\'description|preview\')', '<img src="images/printer.png" alt="" /> '._PRINT, '', '', '', 'onclick="javascript:appPreview(\'description\');"');
	}
	draw_title_bar(
		prepare_breadcrumbs(array(_SHOPPING_CART=>'',_ORDERS_MANAGEMENT=>'',ucfirst($title_action)=>'')),
		$links		
	);

	echo $msg;

	draw_content_start();	
	if($mode == 'view'){
		
		echo '<input type="button" class="mgrid_button" onclick="javascript:appGoTo(\'page=category\')" value="'._CREATE_ORDER.'" />';
		echo '&nbsp;&nbsp;'.prepare_permanent_link('index.php?page=shopping_cart', '[ '._SHOPPING_CART.' ]');		
		echo '&nbsp;&nbsp;'.prepare_permanent_link('index.php?page=checkout', '[ '._CHECKOUT.' ]');
		echo '<br /><br />';
		
		$objOrders->DrawViewMode();	
	}else if($mode == 'add'){		
		$objOrders->DrawAddMode();		
	}else if($mode == 'edit'){
		echo '<script type="text/javascript">
			function Status_Init(){			
				if(!document.getElementById("status")){
					return false;
				}else{
					Status_OnChange(document.getElementById("status").value);
				}
			}		
			
			function Status_OnChange(val){
				if(!document.getElementById("shipping_provider")){
					return false;
				}else if(val == 3){
					document.getElementById("shipping_provider").readOnly = false;
					document.getElementById("shipping_provider").style.backgroundColor = "#ffffff";
					document.getElementById("shipping_provider").focus();
					document.getElementById("shipping_id").readOnly = false;
					document.getElementById("shipping_id").style.backgroundColor = "#ffffff";					
					Toggle_ElementStatus("shipping_date", "enable");
					Toggle_ElementStatus("received_date", "disable");
				}else if(val == 4){	
					document.getElementById("shipping_provider").readOnly = true;
					document.getElementById("shipping_provider").style.backgroundColor = "#e1e2e3";
					document.getElementById("shipping_id").readOnly = true;
					document.getElementById("shipping_id").style.backgroundColor = "#e1e2e3";
					Toggle_ElementStatus("shipping_date", "disable");
					Toggle_ElementStatus("received_date", "enable");
				}else{
					document.getElementById("shipping_provider").readOnly = true;
					document.getElementById("shipping_provider").style.backgroundColor = "#e1e2e3";
					document.getElementById("shipping_id").readOnly = true;
					document.getElementById("shipping_id").style.backgroundColor = "#e1e2e3";
					Toggle_ElementStatus("shipping_date", "disable");
					Toggle_ElementStatus("received_date", "disable");
				}
			}
			
			function Toggle_ElementStatus(val, state){
				if(state == "disable"){
					document.getElementById(val+"__nc_year").disabled = true;
					document.getElementById(val+"__nc_year").style.backgroundColor = "#ffffff";
					document.getElementById(val+"__nc_month").disabled = true;
					document.getElementById(val+"__nc_month").style.backgroundColor = "#ffffff";
					document.getElementById(val+"__nc_day").disabled = true;
					document.getElementById(val+"__nc_day").style.backgroundColor = "#ffffff";
				}else{
					document.getElementById(val+"__nc_year").disabled = false;
					document.getElementById(val+"__nc_year").style.backgroundColor = "#ffffff";
					document.getElementById(val+"__nc_month").disabled = false;
					document.getElementById(val+"__nc_month").style.backgroundColor = "#ffffff";
					document.getElementById(val+"__nc_day").disabled = false;
					document.getElementById(val+"__nc_day").style.backgroundColor = "#ffffff";					
				}
			}
		</script>';
		
		$objOrders->DrawEditMode($rid);
		echo '<script type="text/javascript">Status_Init();</script>';
		
	}else if($mode == 'details'){		
		$objOrders->DrawDetailsMode($rid);		
	}else if($mode == 'description'){
		$objOrders->DrawOrderDescription($rid);		
	}else if($mode == 'invoice'){
		$objOrders->DrawOrderInvoice($rid);		
	}	
	draw_content_end();	

}else{
	draw_title_bar(_ADMIN);
	draw_important_message(_NOT_AUTHORIZED);
}
?>