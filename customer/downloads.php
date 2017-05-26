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

if($objLogin->IsLoggedInAsCustomer() && Modules::IsModuleInstalled('shopping_cart')){

	draw_title_bar(prepare_breadcrumbs(array(_MY_ACCOUNT=>'',_DOWNLOADS=>'')));
	draw_important_message(_DOWNLOADING_ERROR);

}else{
	draw_title_bar(_PAGES);
	draw_important_message(_NOT_AUTHORIZED);
}

?>