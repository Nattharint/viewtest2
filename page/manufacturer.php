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

$objManufacturer = new Manufacturers();
$manufacturer_info = $objManufacturer->GetInfoByID(Application::Get('manufacturer_id'));

draw_title_bar(prepare_breadcrumbs(array(_MANUFACTURER=>'',(isset($manufacturer_info['name']) ? $manufacturer_info['name']: '')=>'')));

Products::DrawProducts(Application::Get('manufacturer_id'), 'manufacturer'); 	
?>