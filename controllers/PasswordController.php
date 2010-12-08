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

define('MIN_PASSWD_LENGTH', 8);

class PasswordControllerCore extends FrontController
{
	public function process()
	{
		parent::process();

		if (Tools::isSubmit('email'))
		{
			if (!($email = Tools::getValue('email')) OR !Validate::isEmail($email))
				$this->errors[] = Tools::displayError('invalid e-mail address');
			else
			{
				$customer = new Customer();
				$customer->getByemail($email);
				if (!Validate::isLoadedObject($customer))
					$this->errors[] = Tools::displayError('there is no account registered to this e-mail address');
				else
				{
					if ((strtotime($customer->last_passwd_gen.'+'.(int)($min_time = Configuration::get('PS_PASSWD_TIME_FRONT')).' minutes') - time()) > 0)
						$this->errors[] = Tools::displayError('You can regenerate your password only each').' '.(int)($min_time).' '.Tools::displayError('minute(s)');
					else
					{	
						Mail::Send((int)($this->cookie->id_lang), 'password_query', Mail::l('Password query confirmation'), 
						array('{email}' => $customer->email, 
							  '{lastname}' => $customer->lastname, 
							  '{firstname}' => $customer->firstname,
							  '{path_token}' => $customer->secure_key,
							  '{id_customer}' => $customer->id), 
						$customer->email, 
						$customer->firstname.' '.$customer->lastname);
						$this->smarty->assign(array('confirmation' => 2, 'email' => $customer->email));
					}
				}
			}
		}
		elseif (($token = Tools::getValue('token')) && ($id_customer = (int)(Tools::getValue('id'))))
		{
			$email = Db::getInstance()->getValue('SELECT `email` FROM '._DB_PREFIX_.'customer c WHERE c.`secure_key` = "'.pSQL($token).'" AND c.id_customer='.(int)($id_customer));
			if ($email)
			{
				$customer = new Customer();
				$customer->getByemail($email);
				if ((strtotime($customer->last_passwd_gen.'+'.(int)($min_time = Configuration::get('PS_PASSWD_TIME_FRONT')).' minutes') - time()) > 0)
					Tools::redirect('authentication.php?error_regen_pwd');
				else
				{
					$customer->passwd = Tools::encrypt($password = Tools::passwdGen((int)(MIN_PASSWD_LENGTH)));
					$customer->last_passwd_gen = date('Y-m-d H:i:s', time());
					if ($customer->update())
					{
						Mail::Send((int)($this->cookie->id_lang), 'password', Mail::l('Your password'), 
						array('{email}' => $customer->email, 
							  '{lastname}' => $customer->lastname, 
							  '{firstname}' => $customer->firstname, 
							  '{passwd}' => $password), 
						$customer->email, 
						$customer->firstname.' '.$customer->lastname); 
						$this->smarty->assign(array('confirmation' => 1, 'email' => $customer->email));
					}
					else
						$this->errors[] = Tools::displayError('error with your account and your new password cannot be sent to your e-mail; please report your problem using the contact form');
				}
			}
			else
				$this->errors[] = Tools::displayError('We can\'t regenerate your password with the datas you submitted');
		}
		elseif (($token = Tools::getValue('token')) || ($id_customer = Tools::getValue('id')))
			$this->errors[] = Tools::displayError('We cannot regenerate your password with the data you submitted');
	}
	
	public function displayContent()
	{
		parent::displayContent();
		$this->smarty->display(_PS_THEME_DIR_.'password.tpl');
	}
}

