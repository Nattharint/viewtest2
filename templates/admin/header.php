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
?>

<div id="logoTop">
	<div id="siteLogo" class="<?php echo 'float_'.Application::Get('defined_left'); ?>">
		<a href="<?php echo APPHP_BASE; ?>index.php"><?php echo $objSiteDescription->DrawHeader('header_text'); ?></a>
	</div>
	<div id="siteSlogan" class="<?php echo 'float_'.Application::Get('defined_left'); ?>">
		<?php echo ($objLogin->IsLoggedInAsAdmin()) ? _ADMIN_PANEL : $objSiteDescription->GetParameter('slogan_text'); ?>
	</div>	
	<div id="siteLinks" class="<?php echo 'float_'.Application::Get('defined_right'); ?>">
		<?php if($objLogin->IsLoggedIn()){ ?>
			<form name="frmLogout" id="frmLogout" style="padding:0px;margin:0px;" action="index.php" method="post">
				<input type="hidden" name="submit_logout" value="logout">
				
				<?php /*_YOU_ARE_LOGGED_AS.': '*/ echo $objLogin->GetLoggedName(); ?>
				<?php echo '&nbsp;'.draw_divider(false).'&nbsp;'; ?>
				<a class="main_link" href="index.php?admin=home"><?php echo _HOME; ?></a>
				<?php echo '&nbsp;'.draw_divider(false).'&nbsp;'; ?>
				<a class="main_link" href="index.php?admin=my_account"><?php echo _MY_ACCOUNT; ?></a>
				<?php echo '&nbsp;'.draw_divider(false).'&nbsp;'; ?>
				<a class="main_link" href="javascript:appFormSubmit('frmLogout');"><?php echo _BUTTON_LOGOUT; ?></a>				
			</form>
		<?php } ?>	
	</div>	
</div>