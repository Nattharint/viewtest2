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

$objCategory = new Categories();
$category_info = $objCategory->GetLevelsInfo(Application::Get('category_id'));

draw_title_bar(prepare_breadcrumbs(
	array(_CATEGORIES=>prepare_link('categories', '', '', 'all', _SEE_ALL, '', '', true),
        $category_info['third']['name']=>$category_info['third']['link'],
        $category_info['second']['name']=>$category_info['second']['link'],
        $category_info['first']['name']=>'')
    )
);

$objCategory->DrawCategories(Application::Get('category_id')); 

?>
	
