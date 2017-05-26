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

draw_title_bar(prepare_breadcrumbs(array(_MANUFACTURERS=>'')));

$objManufacturer = new Manufacturers();
$objManufacturer->DrawManufacturers(); 
	
?>