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
	
	$objProducts = new Products();
	
	if($action=='add'){		
		$mode = 'add';
	}else if($action=='create'){
		if($objProducts->AddRecord()){
			$msg = draw_success_message(_ADDING_OPERATION_COMPLETED, false);
			$mode = 'view';
		}else{
			$msg = draw_important_message($objProducts->error, false);
			$mode = 'add';
		}
	}else if($action=='edit'){
		$mode = 'edit';
	}else if($action=='update'){
		if($objProducts->UpdateRecord($rid)){
			$msg = draw_success_message(_UPDATING_OPERATION_COMPLETED, false);
			$mode = 'view';
		}else{
			$msg = draw_important_message($objProducts->error, false);
			$mode = 'edit';
		}		
	}else if($action=='delete'){
		if($objProducts->DeleteRecord($rid)){
			$msg = draw_success_message(_DELETING_OPERATION_COMPLETED, false);
		}else{
			$msg = draw_important_message($objProducts->error, false);
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
	draw_title_bar(prepare_breadcrumbs(array(_PRODUCTS_CATALOG=>'',_PRODUCTS_MANAGEMENT=>'',_PRODUCTS=>'',ucfirst($action)=>'')));
    	
	echo $msg;

	draw_content_start();	

	echo '<script type="text/javascript">
		function product_type_OnChange(val){
			if(val == "1"){
				jQuery("#mg_row_product_file").show();
			}else{
				jQuery("#mg_row_product_file").hide();
			}			
		}
	</script>';

	if($mode == 'view'){		
		$objProducts->DrawOperationLinks(prepare_permanent_link('index.php?admin=mod_catalog_all_products', '[ '._ALL_PRODUCTS.' ]'));		
		$objProducts->DrawViewMode();	
	}else if($mode == 'add'){		
		$objProducts->DrawAddMode();		
	}else if($mode == 'edit'){		
		$objProducts->DrawEditMode($rid);		
	}else if($mode == 'details'){		
		$objProducts->DrawDetailsMode($rid);		
	}

	echo '<script type="text/javascript">
		product_type_OnChange(jQuery("#product_type").val());
	</script>';

	draw_content_end();	

}else{
	draw_title_bar(_ADMIN);
	draw_important_message(_NOT_AUTHORIZED);
}
?>