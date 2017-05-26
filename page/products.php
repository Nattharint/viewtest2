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

$product_type = Application::Get('type');

draw_title_bar(
    prepare_breadcrumbs(
        array(_PRODUCTS=>'index.php', _FEATURED=>'index.php?page=products&type=featured')
    )
);

if($product_type == 'featured') {	
	Products::DrawFeaturedAll();    
}else{
	draw_important_message(_PAGE_UNKNOWN);		
}
	
?>