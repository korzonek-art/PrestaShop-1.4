<?php
/*
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
*/

include(dirname(__FILE__).'/config/config.inc.php');
include(dirname(__FILE__).'/init.php');
Tools::addCSS(_THEME_CSS_DIR_.'product_list.css');
//will be initialized bellow...
if((int)(Configuration::get('PS_REWRITING_SETTINGS')) === 1)
	$rewrited_url = null;

if (!isset($objectType))
	$objectType = 'supplier';

$className = ucfirst($objectType);
$errors = array();
	
$controller = new FrontController();

if ($id = (int)(Tools::getValue('id_'.$objectType)))
{
	$controller->productSort();
	$controller->displayHeader();
	
	$object = new $className((int)($id), $cookie->id_lang);
	if (!Validate::isLoadedObject($object) OR !$object->active)
	{
		if ($objectType == 'supplier')
			$errors[] = Tools::displayError('supplier does not exist');
		elseif ($objectType == 'manufacturer')
			$errors[] = Tools::displayError('manufacturer does not exist');
		else
			$errors[] = Tools::displayError('object does not exist');
	}
	else
	{
		/* rewrited url set */
		if ($objectType == 'supplier')
			$rewrited_url = $link->getSupplierLink($object->id, $object->link_rewrite);
		elseif ($objectType == 'manufacturer')
			$rewrited_url = $link->getManufacturerLink($object->id, $object->link_rewrite);
		
		$nbProducts = $object->getProducts($id, NULL, NULL, NULL, $controller->orderBy, $controller->orderWay, true);
		$controller->pagination($nbProducts);
		$smarty->assign(array(
			'nb_products' => $nbProducts,
			'products' => $object->getProducts($id, (int)($cookie->id_lang), (int)($controller->p), (int)($controller->n), $controller->orderBy, $controller->orderWay),
			$objectType => $object));
	}
	
	$smarty->assign(array(
		'errors' => $errors,
		'path' => ($object->active ? Tools::safeOutput($object->name) : ''),
		'id_lang' => (int)($cookie->id_lang),
		'add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY')
	));
	$smarty->display(_PS_THEME_DIR_.$objectType.'.tpl');
}
else
{
	$controller->displayHeader();
	$data = call_user_func(array($className, 'get'.$className.'s'), false, (int)($cookie->id_lang), true);
	$nbProducts = sizeof($data);
	$controller->pagination($nbProducts);

	$data = call_user_func(array($className, 'get'.$className.'s'), true, (int)($cookie->id_lang), true, $controller->p, $controller->n);
	$imgDir = $objectType == 'supplier' ? _PS_SUPP_IMG_DIR_ : _PS_MANU_IMG_DIR_;
	foreach ($data AS &$item)
		$item['image'] = (!file_exists($imgDir.'/'.$item['id_'.$objectType].'-medium.jpg')) ? 
			Language::getIsoById((int)($cookie->id_lang)).'-default' :	$item['id_'.$objectType];

	$smarty->assign(array(
		'pages_nb' => ceil($nbProducts / (int)($controller->n)),
		'nb'.$className.'s' => $nbProducts,
		'mediumSize' => Image::getSize('medium'),
		$objectType.'s' => $data,
		'add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
	));
	$smarty->display(_PS_THEME_DIR_.$objectType.'-list.tpl');
}

include(dirname(__FILE__).'/footer.php');


