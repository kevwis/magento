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
 * @desc        Mondial Relay carrier model class for pickup deliveries
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */

class Man4x_MondialRelay_Model_Carrier_Pickup
    extends Man4x_MondialRelay_Model_Carrier_Abstract
{    

    const MAX_CITY_LIST = '20';
    
    protected $_code = 'mondialrelaypickup';
 

    /**
     * Define the Mondial Relay pickup delivery methods
     * 
     * @return array 
     */
    static public function getAllMethods($mode = null)
    {
        return parent::getAllMethods('mondialrelaypickup');
    }

    /**
     * Get the postcode list for a given city/country
     * 
     * @param string $city
     * @param string $country
     * @return array 
     */
    static public function wsGetPostcodeForCity($city, $country)
    {
        // Params for the WS (keep in  strict order)
        $_params = array(
            'Enseigne'  => Mage::getStoreConfig('carriers/mondialrelay/company', true),
            'Pays'      => $country,
            'Ville'     => $city,
        );
        // Security Key
        $_code = implode('', $_params);
        $_code .= Mage::getStoreConfig('carriers/mondialrelay/key_ws', true);
        $_params['Security'] = strtoupper(md5($_code));

        // We collect Mondial Relay pick-up locations
        $_client = new SoapClient(Mage::getStoreConfig('carriers/mondialrelay/url_ws', true));
        $_postcodes = $_client->WSI2_RechercheCP($_params)->WSI2_RechercheCPResult;
        return $_postcodes;
    }

    /**
     * Get the pick-ups list for a given postcode/country sorted by growing nearness
     * 
     * @param string $postcode
     * @param string $country
     * @return false | array 
     */
    static public function wsGetPickups($postcode, $country)
    {
        // Params for the WS (keep in  strict order)
        $_params = array(
            'Enseigne'  => Mage::getStoreConfig('carriers/mondialrelay/company', true),
            'Pays'      => $country,
            'CP'        => $postcode,
        );
        // Security Key
        $_code = implode('', $_params);
        $_code .= Mage::getStoreConfig('carriers/mondialrelay/key_ws', true);
        $_params['Security'] = strtoupper(md5($_code));

        // We collect Mondial Relay pick-up locations
        $_client = new SoapClient(Mage::getStoreConfig('carriers/mondialrelay/url_ws', true));
        $_wsResult = $_client->WSI2_RecherchePointRelaisAvancee($_params)->WSI2_RecherchePointRelaisAvanceeResult;
        
        if (! property_exists($_wsResult, 'ListePR') || ! property_exists($_wsResult->ListePR, 'ret_WSI2_sub_PointRelaisAvancee'))
        {
            return false;
        }
        
        $_pickups = array();
        foreach ($_wsResult->ListePR->ret_WSI2_sub_PointRelaisAvancee as $_pickup)
        {
           $_pickup = array(
               'id'         => $_pickup->Num,
               'name'       => trim($_pickup->LgAdr1) . (trim($_pickup->LgAdr2) ? ' ' . trim($_pickup->LgAdr2) : ''),
               'street'     => trim($_pickup->LgAdr3) . (trim($_pickup->LgAdr4) ? ' ' . trim($_pickup->LgAdr4) : ''),
               'postcode'   => $_pickup->CP,
               'city'       => $_pickup->Ville,
               'latitude'   => (float) str_replace(',', '.', $_pickup->Latitude),
               'longitude'  => (float) str_replace(',', '.', $_pickup->Longitude),
               'country_id' => $_pickup->Pays,
               'distance'   => (float) $_pickup->Distance,
           );
               
           array_push($_pickups, $_pickup);
        }
        
        $_relayCount = (int) Mage::getStoreConfig('carriers/mondialrelaypickup/relay_count', true);
        // We sort the pickup in growing order of nearness
        usort($_pickups, array(Mage::helper('mondialrelay'), 'sortByNearness'));
           
        while (count($_pickups) > $_relayCount)
        {
            array_pop($_pickups);
        }
        return $_pickups;
    }
    
    /**
     * Get the data for a given pickup id/country
     * 
     * @param string $id
     * @param string $country
     * @return array 
     */
    static public function wsGetPickupData($id, $country)
    {
        // Params for the WS (keep in  strict order)
        $_params = array(
            'Enseigne'  => Mage::getStoreConfig('carriers/mondialrelay/company', true),
            'Num'       => $id,
            'Pays'      => $country,
        );        
        // Security Key
        $_code = implode('', $_params);
        $_code .= Mage::getStoreConfig('carriers/mondialrelay/key_ws', true);
        $_params['Security'] = strtoupper(md5($_code));
        
        // Connexion to MR Web Service
        $_client = new SoapClient(Mage::getStoreConfig('carriers/mondialrelay/url_ws', true));
        $_pickupData = $_client->WSI2_DetailPointRelais($_params)->WSI2_DetailPointRelaisResult;
        return $_pickupData;
    }
    
    /** 
     *  Tune up collected rates according to shipping mode
     *  If the pick-up selection form is enabled, we only create one shipping method (default behaviour)
     *  If the pick-up selection form is disabled and postcode and country code are set,
     *  we create a shipping method for each relevant pick-up 
     * 
     *  @param  Mage_Shipping_Model_Rate_Request request
     *  @return Man4x_MondialRelay_Model_Carrier_Abstract
     */
    protected function _tuneUpCollectedRates(Mage_Shipping_Model_Rate_Request $request)
    {
        // If both pickup methods are available, we get only the standard one
        if (isset($this->_rates['standard']) && isset($this->_rates['colisdrive']))
        {
            unset($this->_rates['colisdrive']);
        }
        
        if (! $this->getConfigFlag('map_selection') && $request->getDestPostcode() !== null)
        {
            // One method for each relevant pick-up
            $_wsPickups = self::wsGetPickups($request->getDestPostcode(), $request->getDestCountryId());
            
            if ($_wsPickups)
            {
                // Pickups array to store in checkout session
                Mage::getModel('checkout/session')->unsetData('mr_pickups');                

                $_mrCode = isset($this->_rates['colisdrive']) ? 
                    $this->_rates['colisdrive']['config']['mr_code'] : 
                    $this->_rates['standard']['config']['mr_code'];
                
                // We remove the generic rate to populate with the pickup data
                $this->_rates = array();
                
                $_pickups = array();
                foreach ($_wsPickups as $_pickup)
                {
                    $this->_rates[] = array(
                        'title'         => $this->getConfigData('title'),
                        'method'        => $_mrCode . '_' . $_pickup['id'],
                        'method_title'  => $_pickup['name'],
                        'method_desc'   => $_pickup['street'] . ' ' . $_pickup['postcode'] . ' ' . $_pickup['city'],
                    );  
                    
                    $_pickups[$_pickup['id']] = $_pickup;
                }
                
                Mage::getModel('checkout/session')->setData('mr_pickups', $_pickups);
                return $this;
            }
        }     
        
        // Single generic pick-up method (default behaviour)
        parent::_tuneUpCollectedRates($request);
            
        return $this;
    }

    /**
     *  Get params for MR Web Service WSI2_CreationExpedition
     *  !!! Keep parameters in strict order !!!
     *  See Espace Enseigne-Fichier CSV-MondialRelay-20110801-fr-V2.2
     * 
     *  @param Mage_Sales_Model_Order $order
     *  @return array
     */
    final function _getWsRegisterShipmentParams(Mage_Sales_Model_Order $order)
    {   
        $_params = parent::_getWsRegisterShipmentParams($order);
        
        // $order->getShippingMethod() formated as carrier . _ . method (e.g. mondialrelaypickup_24R_000000)
        $_shippingMethod = explode('_', $order->getShippingMethod());
        $_params['LIV_Rel'] = $_shippingMethod[2];
        
        return $_params;
    }
    
    /**
     *  Get params for CSV export
     *  !!! Keep parameters in strict order !!!
     *  Subclasses precise their own specific parameters
     * 
     *  @param Mage_Sales_Model_Order $order
     *  @return string
     */
    public function getFlatFileData(Mage_Sales_Model_Order $order)
    {
        $_record = parent::getFlatFileData($order);
        $_record[15] = 'R'; // Shipping type (<R>elais, <D>omicile)
        $_method = explode('_', $order->getShippingMethod());
        $_record[16] = $_method[2]; // Pickup ID
        $_csvLine = implode(Man4x_MondialRelay_Model_Carrier_Abstract::CSV_SEPARATOR, $_record);
        $_csvLine .= Man4x_MondialRelay_Model_Carrier_Abstract::CSV_EOL;
        return $_csvLine; 
    }
}