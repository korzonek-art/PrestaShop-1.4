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

require_once(_PS_TOOL_DIR_.'tar/Archive_Tar.php');

class LocalizationPackCore
{
	public	$name;
	public	$version;
	private	$_errors = array();

	public function importFile($archive, $selection = array())
	{
		$gz = new Archive_Tar($archive, 'gz');
		if (!$gz->extract(_PS_TMP_DIR_, false))
			return false;
		return $this->_loadXMLFile($selection);
	}

	private function _loadXMLFile($selection)
	{
		if (!file_exists(_PS_TMP_DIR_.'localization_pack.xml'))
			return false;
		if (!$xml = simplexml_load_file(_PS_TMP_DIR_.'localization_pack.xml'))
			return false;
		$mainAttributes = $xml->attributes();
		$this->name = strval($mainAttributes['name']);
		$this->version = strval($mainAttributes['version']);
		if (empty($selection))
			return ($this->_installStates($xml) AND $this->_installTaxes($xml) AND $this->_installCurrencies($xml) AND $this->_installLanguages($xml) AND $this->_installUnits($xml));
		foreach ($selection as $selected)
			if (!Validate::isLocalizationPackSelection($selected) OR !$this->{'_install'.ucfirst($selected)}($xml))
				return false;
		return true;
	}

	private function _installStates($xml)
	{
		if (isset($xml->states->state))
			foreach ($xml->states->state as $data)
			{
				$attributes = $data->attributes();
				$state = new State();
				$state->name = strval($attributes['name']);
				$state->iso_code = strval($attributes['iso_code']);
				$state->id_country = Country::getByIso(strval($attributes['country']));
				$state->id_zone = (int)(Zone::getIdByName(strval($attributes['zone'])));
				$state->tax_behavior = (int)($attributes['tax_behavior']);
				if (!$state->validateFields())
				{
					$this->_errors[] = Tools::displayError('Invalid state properties.');
					return false;
				}
				$country = new Country($state->id_country);
				if (!$country->contains_states)
				{
					$country->contains_states = 1;
					if (!$country->update())
						$this->_errors[] = Tools::displayError('Impossible to update the associated country: ').$country->name;
				}
				if (!$state->add())
				{
					$this->_errors[] = Tools::displayError('An error occurred while adding the state.');
					return false;
				}
			}
		return true;
	}

	private function _installTaxes($xml)
	{
		if (isset($xml->taxes->tax))
			foreach ($xml->taxes->tax as $taxData)
			{
				$attributes = $taxData->attributes();
				$tax = new Tax();
				$tax->name[(int)(Configuration::get('PS_LANG_DEFAULT'))] = strval($attributes['name']);
				$tax->rate = (float)($attributes['rate']);
				if (!$tax->validateFields())
				{
					$this->_errors[] = Tools::displayError('Invalid tax properties.');
					return false;
				}
				if (!$tax->add())
				{
					$this->_errors[] = Tools::displayError('An error occurred while importing the tax: ').strval($attributes['name']);
					return false;
				}
				if (isset($taxData->applications->application))
					foreach ($taxData->applications->application as $applicationData)
					{
						$attributes = $applicationData->attributes();
						if (strval($attributes['type']) == 'state' AND !$tax->addState(State::getIdByIso(strval($attributes['id']))))
						{
							$this->_errors[] = Tools::displayError('Unable to associate the state: ').strval($attributes['id']);
							return false;
						}
						elseif (strval($attributes['type']) == 'zone' AND !$tax->addZone(Zone::getIdByName(strval($attributes['id']))))
						{
							$this->_errors[] = Tools::displayError('Unable to associate the zone: ').strval($attributes['id']);
							return false;
						}
					}
			}
		return true;
	}

	private function _installCurrencies($xml)
	{
		if (isset($xml->currencies->currency))
		{
			if (!$feed = @simplexml_load_file('http://www.prestashop.com/xml/currencies.xml'))
			{
				$this->_errors[] = Tools::displayError('Cannot parse feed!');
				return false;
			}
			if (!$defaultCurrency = (int)(Configuration::get('PS_CURRENCY_DEFAULT')))
			{
				$this->_errors[] = Tools::displayError('Cannot parse feed!');
				return false;
			}
			$isoCodeSource = strval($feed->source['iso_code']);
			$defaultCurrency = Currency::refreshCurrenciesGetDefault($feed->list, $isoCodeSource, $defaultCurrency);
			foreach ($xml->currencies->currency as $data)
			{
				$attributes = $data->attributes();
				$currency = new Currency();
				$currency->name = strval($attributes['name']);
				$currency->iso_code = strval($attributes['iso_code']);
				$currency->iso_code_num = (int)($attributes['iso_code_num']);
				$currency->sign = strval($attributes['sign']);
				$currency->blank = (int)($attributes['blank']);
				$currency->conversion_rate = 1; // This value will be updated if the store is online
				$currency->format = (int)($attributes['format']);
				$currency->decimals = (int)($attributes['decimals']);
				if (!$currency->validateFields())
				{
					$this->_errors[] = Tools::displayError('Invalid currency properties.');
					return false;
				}
				if (!$currency->add())
				{
					$this->_errors[] = Tools::displayError('An error occurred while importing the currency: ').strval($attributes['name']);
					return false;
				}
				$currency->refreshCurrency($feed->list, $isoCodeSource, $defaultCurrency);
			}
		}
		return true;
	}

	private function _installLanguages($xml)
	{
		if (isset($xml->languages->language))
			foreach ($xml->languages->language as $data)
			{
				$attributes = $data->attributes();
				$gz = new Archive_Tar(_PS_TMP_DIR_.strval($attributes['file']), true);
				if (!$gz->extract(_PS_TRANSLATIONS_DIR_.'../', false))
				{
					$this->_errors[] = Tools::displayError('Cannot decompress the translation file of the language: ').strval($attributes['iso_code']);
					return false;
				}
				if (!Language::checkAndAddLanguage(strval($attributes['iso_code'])))
				{
					$this->_errors[] = Tools::displayError('An error occurred while creating the language: ').strval($attributes['iso_code']);
					return false;
				}
			}
		return true;
	}

	private function _installUnits($xml)
	{
		$varNames = array('weight' => 'PS_WEIGHT_UNIT', 'volume' => 'PS_VOLUME_UNIT', 'short_distance' => 'PS_SHORT_DISTANCE_UNIT', 'base_distance' => 'PS_BASE_DISTANCE_UNIT', 'long_distance' => 'PS_LONG_DISTANCE_UNIT');
		if (isset($xml->units->unit))
			foreach ($xml->units->unit as $data)
			{
				$attributes = $data->attributes();
				if (!isset($varNames[strval($attributes['type'])]))
				{
					$this->_errors[] = Tools::displayError('Pack corrupted: wrong unit type.');
					return false;
				}
				if (!Configuration::updateValue($varNames[strval($attributes['type'])], strval($attributes['value'])))
				{
					$this->_errors[] = Tools::displayError('An error occurred while setting the units.');
					return false;
				}
			}
		return true;
	}

	public function getErrors()
	{
		return $this->_errors;
	}
}
