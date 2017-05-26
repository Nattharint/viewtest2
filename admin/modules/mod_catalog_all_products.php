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
	
	// Start main content
	draw_title_bar(prepare_breadcrumbs(array(_PRODUCTS_CATALOG=>'',_PRODUCTS=>'',_ALL_PRODUCTS=>'')));
    	
	draw_content_start();	

    Products::DrawAllProducts();

	draw_content_end();	

}else{
	draw_title_bar(_ADMIN);
	draw_important_message(_NOT_AUTHORIZED);
}
?>