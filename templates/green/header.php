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

<!-- logotop -->
<div id="logoTop">
	<!-- cart icon -->
	<div id="siteLogo">
		<a href="<?php echo APPHP_BASE; ?>index.php"><?php echo ($objLogin->IsLoggedInAsAdmin()) ? _ADMIN_PANEL : $objSiteDescription->DrawHeader('header_text'); ?></a>
	</div>
	<div id="siteSlogan">
		<?php
			if($objLogin->IsLoggedInAsAdmin() && Application::Get('preview') == 'yes'){
				echo '<a class="header" href="index.php?preview=no">'._BACK_TO_ADMIN_PANEL.'</a>';						
			}else{
				echo $objSiteDescription->GetParameter('slogan_text');				
			}
		?>
	</div>

	<?php
		$shopping_cart_installed = false;
		if(Modules::IsModuleInstalled('shopping_cart')){				
			if(ModulesSettings::Get('shopping_cart', 'is_active') == 'yes') $shopping_cart_installed = true;
		}
		if($shopping_cart_installed){
			$objShopCart = new ShoppingCart();
	?>
		<div id="shoppingCartTop" class="<?php echo 'float_'.Application::Get('defined_right');?>">
			<table>
			<tr>
				<td rowspan="2" valign="top"><img src="images/shopping_cart.gif" alt="" style="margin:0px;" /></td>
				<td><?php echo _MY_SHOPPING_CART;?></td>
			</tr>
			<tr>
				<td valign="top" align="<?php echo Application::Get('defined_right');?>">
					<?php echo $objShopCart->GetCartCount(); ?> item(s):<br/>
					<a class="shopping-cart-link" href="index.php?page=shopping_cart"><?php echo '<b>'.$objShopCart->GetCartSum().'</b>'; ?></a>
				</td>
			</tr>
			</table>
		</div>	
	<?php } ?>
</div>

<div id="topMenuBar">
  <table style="border-collapse:collapse;" cellpadding="0" width="100%" border="0">
  <tbody>
  <tr>
    <td width="34px"><img width="34px" height="41px" alt="" src="<?php echo APPHP_BASE;?>templates/green/images/cor-start-<?php echo Application::Get('lang_dir');?>.png" /></td>
    <td style="vertical-align:middle;">
		<div id="navPagesTop">
		<ul class="nav_top dropdown_outer">
			<?php 
				// Draw top menu
				Menu::DrawTopMenu();	
			?>		  
		</ul>
		</div>
	</td>
	<td style="vertical-align:bottom;text-align:<?php echo Application::Get('defined_right');?>;">
	<?php
		//$news_rss = ModulesSettings::Get('news', 'news_rss');
		
	    $text_align_left = (Application::Get('lang_dir') == 'ltr') ? 'text-align:left;' : 'text-align:right;padding-right:15px;'; 
		$text_align_right = (Application::Get('lang_dir') == 'ltr') ? 'text-align:right;padding-right:15px;' : 'text-align:left;';

		if($objSettings->GetParameter('rss_feed')){
			echo '<a href="feeds/rss.xml" title="RSS Feed"><img src="images/rss.gif" alt="RSS Feed" border="0" /></a>&nbsp;';
		}
		echo '<a href="mailto:'.$objSettings->GetParameter('admin_email').'" title="'._CONTACT_US.'"><img src="images/letter.gif" alt="'._EMAIL_ADDRESS.'" border="0" /></a>&nbsp;';
	?>
	</td>
	<td width="9px"><img width="9px" height="41" alt="" src="<?php echo APPHP_BASE;?>templates/green/images/cor-end-<?php echo Application::Get('lang_dir');?>.png" /></td>	
	</tr>
	</tbody>
	</table>
</div>
<?php
	if(Application::Get('defined_alignment') == 'left'){
		echo '<div class="round_top"><img width="5" height="5" alt="" src="'.APPHP_BASE.'templates/green/images/round-top-left.gif" /></div>';
	}else{
		echo '<div class="round_top_right"><img width="5" height="5" alt="" src="'.APPHP_BASE.'templates/green/images/round-top-left.gif" /></div>';
	}
?>

<div id="navWrapper">	
	<!-- language -->
	<div class="nav_language <?php echo 'float_'.Application::Get('defined_left'); ?>">
		<?php				
			$objLang  = new Languages();				
			if($objLang->GetLanguagesCount('front-end') > 1){
				echo '<div style="padding-top:3px;margin:0px 6px;float:'.Application::Get('defined_left').';">'._LANGUAGES.'</div>';			
				echo '<div style="padding-top:4px;float:left;">';
				$objLang->DrawLanguagesBar();
				echo '</div>';
			}				
		?>		
	</div>		

	<!-- currencies -->
	<div class="nav_currencies <?php echo 'float_'.Application::Get('defined_left'); ?>">
	<?php
		echo Currencies::GetCurrenciesDDL();
	?>
	</div>

	<?php
		echo Search::DrawQuickSearch();
	?>
</div>