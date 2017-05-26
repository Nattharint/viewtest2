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

<div id="footerLine"></div>
<div id="footerDiv">
    <table id="footer_tbl" cellpadding="0" width="100%" border="0">
    <tbody>
    <tr>
        <td width="8"></td>
        <td>
            <div id="navSuppWrapper">
                <div id="navSupp">
                    <ul>
                        <li>
                        <?php 
                            // Draw footer menu
                            Menu::DrawFooterMenu($objLogin->IsLoggedIn());	
                        ?>		  
                        </li>
                    </ul>
                </div>
                <br />
        
                <div id="siteInfo">
                    <form name="frmLogout" id="frmLogout" style="padding:0px;margin:0px;" action="index.php" method="post">
                        <?php
                            $footer_text = $objSiteDescription->DrawFooter(false);
                            echo $footer_text;
                        ?>
                        <?php if(!empty($footer_text)) echo '&nbsp;'.draw_divider(false).'&nbsp;'; ?>
                        <?php if($objLogin->IsLoggedIn()){ ?>
                            <input type="hidden" name="submit_logout" value="logout">
                            <a class="main_link" href="javascript:appFormSubmit('frmLogout');"><?php echo _BUTTON_LOGOUT; ?></a>
                        <?php }else{ ?>
                            <a class="main_link" href="index.php?admin=login"><?php echo _ADMIN_LOGIN; ?></a>
                        <?php } ?>
                    </form>
                </div>            
            </div>
        </td>
        <td width="8"></td></tr>
    <tr>
        <td width="8"><img width="8" height="8" alt="" src="templates/default/images/cor-<?php echo Application::Get('defined_left'); ?>-bot.gif" /></td>
        <td class="tdback"></td>
        <td width="8"><img width="8" height="8" alt="" src="templates/default/images/cor-<?php echo Application::Get('defined_right'); ?>-bot.gif" /></td>
    </tr>
    </tbody>
    </table>
</div>