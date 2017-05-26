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

$objProduct = new Products();
$product_info = $objProduct->GetInfoByID(Application::Get('product_id'));
$product_name = isset($product_info['name']) ? prepare_input($product_info['name']) : '';
$category_id = isset($product_info['category_id']) ? prepare_input($product_info['category_id']) : '0';

if($product_name != ''){
	draw_title_bar(
		prepare_breadcrumbs(array(_PRODUCTS=>'',$product_name=>''))
	);	
}else{
	draw_title_bar(_PRODUCT);
}

if(Application::Get('product_id') != '') {	
	$objProduct->DrawProductDescription(Application::Get('product_id')); 
}else{
	draw_important_message(_WRONG_PARAMETER_PASSED);		
}
	
?>