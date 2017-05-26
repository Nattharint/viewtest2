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

if($objLogin->IsLoggedInAsCustomer()){

    draw_title_bar(prepare_breadcrumbs(array(_MY_ACCOUNT=>'',_HOME=>'')));

	$msg  = '<div style="padding:9px;min-height:250px">';
	$msg .= str_replace(
			array('_LOGIN_NAME_', '_TODAY_', '_LAST_LOGIN_'),
			array($objLogin->GetLoggedName(), format_datetime(@date('Y-m-d H:i:s'), '', '', true), format_datetime($objLogin->GetLastLoginTime(), '', _NEVER, true)),
			_WELCOME_USER_TEXT);
	$msg .= '</div>';
	
	draw_message($msg, true, false);	
}else{
	draw_title_bar(_PAGES);
	draw_important_message(_NOT_AUTHORIZED);
}

?>