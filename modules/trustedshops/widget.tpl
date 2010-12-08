{*
* 2007-2010 PrestaShop 
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author Prestashop SA <contact@prestashop.com>
*  @copyright  2007-2010 Prestashop SA : 6 rue lacepede, 75005 PARIS
*  @version  Release: $Revision: 1.4 $
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registred Trademark & Property of PrestaShop SA
*}

{if $display_widget}
<div style="text-align: center">
	<a target="_blank" href="https://www.trustedshops.com/buyerrating/info_{$ts_id}.html" title="See customer ratings of {$shop_name}"><img alt="Customer ratings of {$shop_name}" border="0"  src="{$filename}"/></a>
</div>
<br />
{/if}
	
{if $display_rating_link}
<div style="text-align: center">
	<a target="_blank" href="{$rating_url}" title="Rate this shop"><img alt="Rate this shop" border="0" src="{$module_dir}/img/apply_{$language}.gif" /></a>
</div>
<br />
{/if}