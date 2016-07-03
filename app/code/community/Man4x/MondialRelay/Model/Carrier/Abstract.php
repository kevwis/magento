<?php

/**
 * Copyright (c) 2013 Man4x
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @project     Magento Man4x Mondial Relay Module
 * @desc        Mondial Relay generic Mondial Relay carrier model class 
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */
class Man4x_MondialRelay_Model_Carrier_Abstract extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{

    const CACHE_TAG = 'man4x';
    const MIN_WEIGHT = 100;
    const CSV_SEPARATOR = ";";
    const CSV_EOL = "\r\n";
    const BASE_URL = 'http://www.mondialrelay.fr';

    protected $_code = 'mondialrelay';

    /**
     * Get all defined shipping methods for Mondial Relay carrier
     * It should be far more logicial to define this static function as abstract here and overide it
     * in the children classes thanks to the "late static binding" feature introduced PHP 5.3 but
     * this could be troublesome for earlier php versions
     * cf http://fr2.php.net/manual/en/language.oop5.late-static-bindings.php
     * cf http://stackoverflow.com/questions/999066/why-does-php-5-2-disallow-abstract-static-class-methods
     * 
     * This method returns all the defined Mondial Relay shipping methods for a given delivery mode.
     * Each method is identified with its corresponding Mondial Relay id code and its fields define the
     * relevant config fields for its configuration 
     * 
     * @param string mode
     * @return array 
     */
    static public function getAllMethods($mode = null)
    {
        $_modes = array(
            'mondialrelay' => array(),
            'mondialrelaypickup' => array(
                'standard' => array(
                    'mr_code' => '24R',
                    'data_config_suffix' => '',
                    'max_weight' => 30000,
                ),
                'colisdrive' => array(
                    'mr_code' => 'LCD',
                    'data_config_suffix' => '',
                    'max_weight' => 130000,
                ),
            ),
            'mondialrelayhome' => array(
                'standard' => array(
                    'mr_code' => 'LD1',
                    'data_config_suffix' => '',
                    'max_weight' => 30000,
                ),
                'comfort' => array(
                    'mr_code' => 'LD2',
                    'data_config_suffix' => '_comfort',
                    'max_weight' => 130000,
                ),
                'premium' => array(
                    'mr_code' => 'LDS',
                    'data_config_suffix' => '_premium',
                    'max_weight' => 130000,
                ),
            ),
        );

        if (null !== $mode)
        {
            return $_modes[$mode];
        }

        // We get all the shipping methods regardless of the mode
        $_methods = array();
        foreach ($_modes as $_mode)
        {
            foreach ($_mode as $_method)
            {
                $_methods[] = $_method;
            }
        }
        return $_methods;
    }

    /**
     * Get label URL for the given tracking numbers
     * 
     * @param array trackings
     * @return string (if an error occurs, return the WS error code) 
     */
    static public function getWsLabelUrl($trackings)
    {

        // Params for the WS (keep in  strict order)
        // We use the store config for the current store
        // @TODO: check if the "company" has to be store-dependant
        $_params = array(
            'Enseigne' => Mage::getStoreConfig('carriers/mondialrelay/company', true),
            'Expeditions' => $trackings,
            'Langue' => 'FR',
        );

        // We had Security Code
        $_code = implode('', $_params);
        $_code .= Mage::getStoreConfig('carriers/mondialrelay/key_ws', true);
        $_params["Security"] = strtoupper(md5($_code));

        // Web Service Connection
        $_client = new SoapClient(Mage::getStoreConfig('carriers/mondialrelay/url_ws', true));
        $_label = $_client->WSI2_GetEtiquettes($_params)->WSI2_GetEtiquettesResult;

        $_labelsize = Mage::getStoreConfig('carriers/mondialrelay/label_size', true);
        $_result = ('0' == $_label->STAT) ? 
                self::BASE_URL . (('A4' == $_labelsize) ? $_label->URL_PDF_A4 : $_label->URL_PDF_A5)
                : $_label->STAT;
        return $_result;
    }

    /**
     *  @ Mage_Shipping_Model_Carrier_Interface
     *  Get allowed methods regarding carrier's config settings
     * 
     *  @return array
     */
    public function getAllowedMethods()
    {
        $_allowed = array();

        if ($this->getConfigData('active'))
        {
            $_methods = self::getAllMethods($this->_code);
            foreach ($_methods as $_method => $_conf)
            {
                if ($this->getConfigData('active' . $_conf['data_config_suffix']))
                {
                    $_allowed[$_method] = $_conf;
                }
            }
        }
        return $_allowed;
    }

    /**
     *  @ Mage_Shipping_Model_Carrier_Interface
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     *  Tune up collected rates according to shipping mode 
     *
     *  @param  Mage_Shipping_Model_Rate_Request request
     *  @return Man4x_MondialRelay_Model_Carrier_Abstract
     */
    protected function _tuneUpCollectedRates(Mage_Shipping_Model_Rate_Request $request)
    {
        foreach ($this->_rates as $_name => &$_config)
        {
            $_config['title'] = $this->getConfigData('title');
            $_config['method'] = $_config['config']['mr_code'];
            $_config['method_title'] = $this->getConfigData('methodtitle' . $_config['config']['data_config_suffix']);
            $_config['method_desc'] = $this->getConfigData('desc' . $_config['config']['data_config_suffix']);
        }
        return $this;
    }

    /**
     *  @Mage_Shipping_Model_Carrier_Abstract
     *  Gathers relevant shipping methods for the given request
     * 
     *  @param Mage_Shipping_Model_Rate_Request request
     *  @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        // We cannot collect rates if no country is specified
        if (!isset($request->_data['dest_country_id']))
        {
            return array();
        }

        $this->_rates = array();

        $_rates = Mage::getModel('shipping/rate_result');

        // Calculate package weight
        $_weight = $request->getPackageWeight();
        $_weight = Mage::helper('mondialrelay')->convertWeight(
                $_weight, $this->_getGenericConfigData('catalog_weight_unit', $request->getStoreId())
        );
        $_weight = max($_weight, self::MIN_WEIGHT);

        $_allowedCountry = explode(',', $this->getConfigData('specificcountry'));

        // Gathers relevant shipping methods for the given request
        foreach ($this->getAllowedMethods() as $_method => $_config)
        {
            // @TODO: consider package splitting according to the heaviest item

            $_configsuffix = $_config['data_config_suffix'];

            // Looking if shipping method is relevant depending on...
            if (// ... shipping country...
                    !in_array($request->_data['dest_country_id'], $_allowedCountry)
                    // ... package weight...
                    || $_weight > $_config['max_weight']
                    // ... and banned items
                    || $this->_hasBannedItem($_configsuffix, $request->getAllItems()))
            {
                continue;
            }

            // Note that we keep the weight as set in the request object (i.e. not converted in g) to calculate price
            // since table rate defined in configuration is set according to catalog weight unit
            if (false !== ($_price = $this->_getPrice($request)))
            {
                // Calculate the matching price (if any)
                // Calculate true cart value (including taxes and after discount)
                if (isset($request->_data['base_subtotal_incl_tax']))
                {
                    $_cartValue = (float) $request->_data['base_subtotal_incl_tax']
                            - (float) $request->_data['package_value']
                            + (float) $request->_data['package_value_with_discount'];
                }
                else
                {
                    $_cartValue = (float) $request->_data['package_value_with_discount'];
                }
                // Calculate price regarding franco
                $_franco = (float) $this->getConfigData('franco' . $_configsuffix);
                if ($_franco && $_cartValue >= $_franco)
                {
                    $_price = 0;
                }
                else
                {
                    // Add extrafee (if set)
                    $_price += (float) $this->getConfigData('extrafee' . $_configsuffix);
                }
                $this->_rates[$_method] = array('config' => $_config, 'price' => $_price);
            }
        }

        // Adjust collected rates according to shipping method specificity
        $this->_tuneUpCollectedRates($request);

        if (empty($this->_rates))
        {
            // No relevant shipping method
            $_error = Mage::getModel('shipping/rate_result_error');
            $_error->setCarrier($this->_code);
            $_error->setCarrierTitle($this->getConfigData('title'));
            $_error->setErrorMessage($this->getConfigData('specificerrmsg'));
            $_rates->append($_error);
        }
        else
        {
            $_weightTxt = '';
            if ($this->_getGenericConfigData('display_weight', $request->getStoreId()))
            {
                $_weightTxt = ($_weight === self::MIN_WEIGHT) ? Mage::helper('mondialrelay')->__('< ') : '';
                $_weightTxt = ' (' . $_weightTxt . $_weight . ' g)';
            }
            foreach ($this->_rates as $_name => $_config)
            {
                $_method = Mage::getModel('shipping/rate_result_method');
                $_method->setCarrier($this->_code);
                $_method->setCarrierTitle($_config['title']);
                $_method->setMethod($_config['method']);
                $_method->setMethodTitle($_config['method_title'] . $_weightTxt);
                $_method->setMethodDescription($_config['method_desc']);
                // @TODO study relevance of getFinalPriceWithHandlingFee()
                $_method->setPrice($_price);

                $_rates->append($_method);
            }
        }
        return $_rates;
    }

    /**
     *  Retrieve information from Mondial Relay generic configuration
     *
     *  @param string $field
     *  @param Mage_Core_Model_Store store
     *  @return  mixed
     */
    protected function _getGenericConfigData($field, $store = false)
    {
        if (!$store)
        {
            $store = $this->getStore();
        }
        return Mage::getStoreConfig('carriers/mondialrelay/' . $field, $store);
    }

    /**
     *  Determines whether shipment includes a 'banned item' (as set in config)
     *  @TODO: enable to restrict mondial relay shippings considering others factors (attribute, category...)
     * 
     *  @param string configsuffix
     *  @param array items
     *  @return boolean
     */
    protected function _hasBannedItem($configsuffix, $items)
    {
        $_xitems = explode(',', $this->getConfigData('xitems' . $configsuffix));
        if (!empty($_xitems))
        {
            foreach ($items as $_item)
            {
                if (in_array($_item->getProductId(), $_xitems))
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     *  Get Mondial Relay rates schedule from cache/config
     *  In config, each rate line is defined as
     *      countryId; regionId; postcode; condition; rate; cost
     *  ...as formated by the Man4x_MondialRelay_Model_System_Config_Validation_Tablerate
     *  
     *  @return array
     */
    protected function _getRatesSchedule()
    {
        //Cache loading attempt
        $_cache = Mage::app()->getCache();
        $_cacheKey = self::CACHE_TAG . '_' . $this->_code . '_store_' . $this->getStore();

        if (!is_array($_ratesSchedule = unserialize($_cache->load($_cacheKey))))
        {
            // No cache -> rates extraction from config
            $_ratesSchedule = array();

            $_lines = Mage::helper('mondialrelay')->splitLines(strtoupper($this->getConfigData('table_rate')));
            foreach ($_lines as $_line)
            {
                // Shunt comment lines
                $_line = trim($_line);
                if (substr($_line, 0, 2) == '//')
                {
                    continue;
                }

                $_v = explode(";", $_line);
                $_c = $_v[0]; // country id
                $_r = $_v[1]; // region id
                $_p = (string) $_v[2]; // post code

                if (!isset($_ratesSchedule[$_c]))
                {
                    $_ratesSchedule[$_c] = array();
                }
                if (!isset($_ratesSchedule[$_c][$_r]))
                {
                    $_ratesSchedule[$_c][$_r] = array();
                }
                if (!isset($_ratesSchedule[$_c][$_r][$_p]))
                {
                    $_ratesSchedule[$_c][$_r][$_p] = array();
                }

                $_ratesSchedule[$_c][$_r][$_p][] = array(
                    ($_v[3] == '*') ? '*' : (float) $_v[3], // condition
                    (float) $_v[4], // rate
                );
            }

            // Save table rates in cache
            $_cache->save(serialize($_ratesSchedule), $_cacheKey, array(Mage_Core_Model_Config::CACHE_TAG));
        }
        return $_ratesSchedule;
    }

    /**
     *  Set condition value for the given request
     * 
     *  @param Mage_Shipping_Model_Rate_Request request
     *  @return float
     */
    protected function _getConditionValue(Mage_Shipping_Model_Rate_Request $request)
    {
        // Set the condition value to calculate shipping rate depending on config 
        $_condValue = 0;
        switch ($this->_getGenericConfigData('rate_condition', $request->getStoreId()))
        {
            case 'package_weight':
                // Rate vs weight
                $_condValue = $request->getPackageWeight();
                break;

            case 'package_value':
                // Rate vs package value 
                $_condValue = (float) $request->getBaseSubtotalInclTax() -
                        ($request->getPackageValue() - $request->getPackageValueWithDiscount());
                break;

            case 'package_qty':
                // Rate vs package quantity: we exclude free shipping items from the total quantity
                // @TODO: do the same thing (substract free shipping items from the total evaluated to
                // calculate shipping rates) for rates vs weight and value
                $_freeQty = 0;
                if ($request->getAllItems())
                {
                    foreach ($request->getAllItems() as $_item)
                    {
                        if ($_item->getProduct()->isVirtual() || $_item->getParentItem())
                        {
                            continue;
                        }
                        if ($_item->getHasChildren() && $_item->isShipSeparately())
                        {
                            foreach ($_item->getChildren() as $_child)
                            {
                                if ($_child->getFreeShipping() && !$_child->getProduct()->isVirtual())
                                {
                                    $_free = is_numeric($_child->getFreeShipping()) ? $_child->getFreeShipping() : 0;
                                    $_freeQty += $_item->getQty() * ($child->getQty() - $_free);
                                }
                            }
                        }
                        elseif ($_item->getFreeShipping())
                        {
                            $_free = is_numeric($_item->getFreeShipping()) ? $_item->getFreeShipping() : 0;
                            $_freeQty += ($item->getQty() - $_free);
                        }
                    }
                }
                $_condValue = $request->getPackageQty() - $_freeQty;
                break;
        }
        return $_condValue;
    }

    /**
     *  Get the Mondial Relay rate relevant for the given country / region / postcode / condition value
     * 
     *  NOTE: a closure might have been nicer here to match rate but php 5.3+ needed
     * 
     *  @param Mage_Shipping_Model_Rate_Request request request
     *  @return bool | float
     */
    protected function _getPrice($request)
    {
        // Get the condition value regarding request and configuration
        $_condValue = $this->_getConditionValue($request);

        // Get table rate
        $_ratesSchedule = $this->_getRatesSchedule();

        $_country = isset($request->_data['dest_country_id']) ? array($request->_data['dest_country_id']) : array();
        array_push($_country, '*');

        $_region = isset($request->_data['dest_region_id']) ? array($request->_data['dest_region_id']) : array();
        array_push($_region, '*');

        // Postcode is an user-inputted data: so uppercase and remove all non alphanumerical character
        $_postcode = isset($request->_data['dest_postcode']) ?
                array(preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($request->getDestPostcode())))) : array();
        array_push($_postcode, '*');

        $_price = false;

        // Looking for a matching price in decreasing order of specificity
        // i.e. {c,r,p} -> {c,r,*} -> {c,*,p} -> {c,*,*} -> {*,r,p} -> {*,*,p} -> {*,*,*}
        foreach ($_country as $_c)
        {
            foreach ($_region as $_r)
            {
                if (isset($_ratesSchedule[$_c][$_r]))
                {
                    foreach ($_postcode as $_p)
                    {
                        foreach ($_ratesSchedule[$_c][$_r] as $_pc => $_conds)
                        {
                            // Looking for conditions associated with the first matching post code
                            if (0 === strpos($_p, (string) $_pc))
                            {
                                foreach ($_conds as $_cond)
                                {
                                    // Searching for first rate matching condition
                                    if ($_cond[0] == '*' || $_condValue <= (float) $_cond[0])
                                    {
                                        $_price = $_cond[1];
                                        break 5;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $_price;
    }

    /**
     *  Register shipment through Mondial Relay's web service     *  
     * 
     *  @param Mage_Sales_Model_Order $order
     *  @return object
     */
    public function wsRegisterShipment(Mage_Sales_Model_Order $order)
    {
        // Gather Data for web service request
        $_params = $this->_getWsRegisterShipmentParams($order);
        // We add Security Code
        $_code = implode('', $_params);
        $_code .= $this->_getGenericConfigData('key_ws', $order->getStore());
        $_params['Security'] = strtoupper(md5($_code));

        if ((bool) $this->_getGenericConfigData('debug_mode', $order->getStore()))
        {
            // Debugging Log
            Mage::helper('mondialrelay')->logDebugData($_params);
        }
        // Web Service Connection
        $_client = new SoapClient($this->_getGenericConfigData('url_ws', $order->getStore()));
        $_wsResult = $_client->WSI2_CreationExpedition($_params)->WSI2_CreationExpeditionResult;
        // We save the WS params in the result for debugging purposes
        $_wsResult->wsParams = $_params;
        return $_wsResult;
    }

    /**
     *  Get params for MR Web Service WSI2_CreationExpedition
     *  !!! Keep parameters in strict order !!!
     *  Inherited classes add/edit complement fields according to their requirements
     * 
     *  @param Mage_Sales_Model_Order $order
     *  @return array
     */
    protected function _getWsRegisterShipmentParams(Mage_Sales_Model_Order $order)
    {
        // Gather Data for web service request
        $_helper = Mage::helper('mondialrelay');
        $_senderAddress = $_helper->splitLines($this->_getGenericConfigData('sender_address', $order->getStore()), 4, '--------------------');

        // Calculate total weight and number of boxes
        $_parcelDetails = $this->_getParcelDetails($order);

        // $order->getShippingMethod() formated as carrier . _ . method (e.g. mondialrelaypickup_24R_00000)
        $_shippingMethod = explode('_', $order->getShippingMethod());
        $_store = $order->getStore();

        $_params = array(
            'Enseigne' => $this->_getGenericConfigData('company', $_store),
            'ModeCol' => $this->_getGenericConfigData('collection_mode', $_store), // Collection mode
            'ModeLiv' => $_shippingMethod[1], // 24R, LCD...
            'NDossier' => $order->getIncrementId(),
            'NClient' => substr($_helper->removeAccent(preg_replace("/[^A-Za-z]/", "", $order->getBillingAddress()->getLastname())), 0, 8),
            // 'Expe_Langage' => strtoupper(substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2)), // Backend language
            'Expe_Langage' => 'FR',
            'Expe_Ad1' => $_helper->removeAccent($_senderAddress[0]), // Sender address
            'Expe_Ad2' => $_helper->removeAccent($_senderAddress[1]),
            'Expe_Ad3' => $_helper->removeAccent($_senderAddress[2]),
            'Expe_Ad4' => $_helper->removeAccent($_senderAddress[3]),
            'Expe_Ville' => $_helper->removeAccent($this->_getGenericConfigData('sender_city', $_store)),
            'Expe_CP' => $this->_getGenericConfigData('sender_postcode', $_store),
            'Expe_Pays' => $_helper->removeAccent($this->_getGenericConfigData('sender_country', $_store)),
            'Expe_Tel1' => $this->_getGenericConfigData('sender_phone', $_store),
            'Expe_Tel2' => $this->_getGenericConfigData('sender_mobile', $_store),
            'Expe_Mail' => $this->_getGenericConfigData('sender_email', $_store),
            // 'Dest_Langage' => strtoupper(substr(Mage::getStoreConfig('general/locale/code', $order->getStore()->getId()), 0, 2)), // Order language
            'Dest_Langage' => 'FR',
            'Dest_Ad1' => $_helper->removeAccent($order->getShippingAddress()->getName()),
            'Dest_Ad2' => $_helper->removeAccent($order->getShippingAddress()->getCompany()),
            'Dest_Ad3' => $_helper->removeAccent($order->getShippingAddress()->getStreet(1)),
            'Dest_Ad4' => $_helper->removeAccent($order->getShippingAddress()->getStreet(2)),
            'Dest_Ville' => $_helper->removeAccent($order->getShippingAddress()->getCity()),
            'Dest_CP' => $order->getShippingAddress()->getPostcode(),
            'Dest_Pays' => $_helper->removeAccent($order->getShippingAddress()->getCountry()),
            'Dest_Tel1' => $_helper->formatPhone($order->getBillingAddress()->getTelephone(), $order->getBillingAddress()->getCountryId(), $this->_getGenericConfigData('sender_phone', $_store)),
            'Dest_Mail' => $order->getCustomerEmail(),
            'Poids' => (string) $_parcelDetails['weight'],
            'NbColis' => (string) $_parcelDetails['box'],
            'CRT_Valeur' => '0',
            'CRT_Devise' => 'EUR',
            'Exp_Valeur' => '0',
            'Exp_Devise' => 'EUR',
            'COL_Rel_Pays' => $_helper->removeAccent($this->_getGenericConfigData('sender_country', $_store)),
            'COL_Rel' => (('CCC' === $this->_getGenericConfigData('collection_mode', $_store)) ? '' : $this->_getGenericConfigData('collection_pickup', $_store)),
            'LIV_Rel_Pays' => $order->getShippingAddress()->getCountryId(),
            'LIV_Rel' => '0', // Pickup store ID
        );
        return $_params;
    }

    /**
     *  Get parcel details (total weight and number of boxes)
     * 
     *  @param Mage_Sales_Model_Order $order
     *  @return array
     */
    protected function _getParcelDetails(Mage_Sales_Model_Order $order)
    {
        $_baseWeight = $order->getWeight();
        // We choose the real weight entered in the mass shipping grid (if exists)
        if (Mage::getSingleton('adminhtml/session')->hasMondialRelayRealWeight())
        {
            $_realWeights = Mage::getSingleton('adminhtml/session')->getMondialRelayRealWeight();
            if (isset($_realWeights[$order->getId()]))
            {
                $_baseWeight = $_realWeights[$order->getId()]['poids'];
            }
        }

        // Conversion of package weight in grams
        $_weight = Mage::helper('mondialrelay')->convertWeight(
                floatval($_baseWeight), $this->_getGenericConfigData('catalog_weight_unit', $order->getStore())
        );
        $_weight = max($_weight, self::MIN_WEIGHT);
        $_nbBox = 1;

        if (0 != (int) $this->_getGenericConfigData('max_weight_per_box', $order->getStore()))
        {
            // Calculate how many boxes are required
            $_nbBox = 1 + floor($_weight / (int) $this->_getGenericConfigData('max_weight_per_box', $order->getStore()));
        }

        return array(
            'box' => $_nbBox,
            'weight' => $_weight,
        );
    }

    /**
     *  Get params for CSV export
     *  !!! Keep parameters in strict order !!!
     *  Subclasses precise their own specific parameters
     * 
     *  @TODO manage home pickup (carry-back and shipping notes)
     * 
     *  @param Mage_Sales_Model_Order $order
     *  @return array
     */
    public function getFlatFileData(Mage_Sales_Model_Order $order)
    {
        $_helper = Mage::helper('mondialrelay');
        $_record = array();
        $_record[] = $order->getCustomerId(); // Customer Id
        $_record[] = $order->getIncrementId(); // Order Id

        $_address = $order->getShippingAddress();
        $_record[] = $_helper->removeAccent($_address->getName()); // Customer full name
        $_record[] = $_helper->removeAccent($_address->getCompany()); // Company name
        $_lines = $order->getShippingAddress()->getStreet();
        if (!isset($_lines[1]))
        {
            $_lines[1] = '';
        }
        $_record[] = $_helper->removeAccent($_lines[0]); // Street line #1
        $_record[] = $_helper->removeAccent($_lines[1]); // Street line #2
        $_record[] = $_helper->removeAccent($_address->getCity()); // City
        $_record[] = $_address->getPostcode(); // Post code
        $_record[] = $_address->getCountryId(); // Country code
        $_phone = $_helper->formatPhone($order->getBillingAddress()->getTelephone(), $order->getBillingAddress()->getCountryId(), $this->_getGenericConfigData('sender_phone', $order->getStore()));
        $_record[] = $_phone; // Home phone number
        $_record[] = $_phone; // Cellular
        $_record[] = $order->getCustomerEmail();
        $_record[] = ('CCC' == $this->_getGenericConfigData('collection_mode', $order->getStore())) ? 
                        'A' : 'R'; // Collection mode (<R>elais, <D>omicile, <A>gence)
        $_record[] = ('CCC' == $this->_getGenericConfigData('collection_mode', $order->getStore())) ?
                        '' : $this->_getGenericConfigData('collection_pickup', $order->getStore()); // Pickup ID (mandatory for an "R" collection)
        $_record[] = ('CCC' == $this->_getGenericConfigData('collection_mode', $order->getStore())) ? 
                        '' : trim(strtoupper($this->_getGenericConfigData('sender_country', $order->getStore()))); // Pickup country code (mandatory for an "R" collection)
        $_record[] = ''; // Shipping type (<R>elais, <D>omicile) - set by subclass

        $_record[] = ''; // Pickup ID (mandatory for a "24R" shipping mode) - set by pickup subclass     
        $_record[] = trim(strtoupper($order->getShippingAddress()->getCountryId())); // Pickup country code (mandatory for a "24R" shipping mode)
        $_method = explode('_', $order->getShippingMethod());
        $_record[] = $_method[1];

        // $_record[] = strtoupper(substr(Mage::getStoreConfig('general/locale/code', $order->getStore()->getId()), 0, 2)); // Recipient language code
        $_record[] = 'FR';

        $_parcelDetails = $this->_getParcelDetails($order);
        $_record[] = $_parcelDetails['box']; // Parcel boxes number
        $_record[] = (string) $_parcelDetails['weight']; // Parcel weight (g)
        $_record[] = ''; // Parcel length (cm)
        $_record[] = ''; // Parcel volume (cm3)

        $_record[] = round(100 * $order->getBaseTotal()); // Parcel value (cents)
        $_record[] = $order->getStore()->getCurrentCurrencyCode();
        ; // Order currency
        $_record[] = '0'; // Insurrance level

        $_record[] = '0'; // 'Montant CRT'
        $_record[] = 'EUR'; // CRT currency
        $_record[] = ''; // Shipping instructions
        $_record[] = '1'; // Notification
        $_record[] = '0'; // Home pickup ('Reprise à domicile)
        $_record[] = '0'; // Setup time ('Temps de montage')
        $_record[] = '0'; // Rendez-vous top

        $_items = $order->getAllItems();
        foreach ($_items as $_item)
        {
            $_record[] = substr($_item->getName(), 0, 30);
            if (count($_record) == 44)
            {
                break;
            }
        }
        while (count($_record) < 44)
        {
            $_record[] = '';
        }
        return $_record;
    }

    /**
     *  Gathers info for a given tracking
     * 
     *  @param string $trackingNumber
     *  @return false | string | Mage_Shipping_Model_Tracking_Result
     */
    public function getTrackingInfo($trackingNumber)
    {
        $_trackingResult = $this->_getTracking($trackingNumber);
        if ($_trackingResult instanceof Mage_Shipping_Model_Tracking_Result)
        {
            if ($_trackings = $_trackingResult->getAllTrackings())
            {
                return $_trackings[0];
            }
        }
        elseif (is_string($_trackingResult) && !empty($_trackingResult))
        {
            return $_trackingResult;
        }
        return false;
    }

    /**
     *  Gathers info for a given tracking
     * 
     *  @param string $trackingNumber
     *  @return Mage_Shipping_Model_Tracking_Result
     */
    private function _getTracking($trackingNumber)
    {
        $_key = '<' . $this->_getGenericConfigData('company_ref_tracking') . '>' . $trackingNumber .
                '<' . $this->_getGenericConfigData('key_tracking') . '>';
        $_key = md5($_key);

        $_trackingUrl = $this->_getGenericConfigData('url_tracking') .
                strtoupper($this->_getGenericConfigData('company_ref_tracking')) .
                '&nexp=' . strtoupper($trackingNumber) .
                '&crc=' . strtoupper($_key);

        $_trackingResult = Mage::getModel('shipping/tracking_result');

        $_trackingStatus = Mage::getModel('shipping/tracking_result_status');
        $_trackingStatus->setCarrier($this->_code)
                ->setCarrierTitle($this->getConfigData('title'))
                ->setTracking($trackingNumber)
                ->setUrl($_trackingUrl);

        $_trackingResult->append($_trackingStatus);

        return $_trackingResult;
    }

}