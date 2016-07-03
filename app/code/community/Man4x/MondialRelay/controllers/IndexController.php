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
 * @desc        Controller
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */

class Man4x_MondialRelay_IndexController
    extends Mage_Core_Controller_Front_Action
{
    /**
     * Generate the content for pickup selection div
     */
    public function pickuppopupAction()
    {	
        $_layout = $this->getLayout();
        // We create the pickup selection popup
        $_popupBlock = $_layout->createBlock('mondialrelay/checkout_pickuppopup', 'root');
        $_layout->addOutputBlock('root');
        $_output = $_layout->getOutput();
        $this->getResponse()->setBody($_output);
    }
    
    /**
     * Handler for click on the pickup search button
     */
    public function gatherpickupsAction()
    {
        $_countryId = $this->getRequest()->getPost('country_id');
        $_city = $this->getRequest()->getPost('city');
        $_postcode = $this->getRequest()->getPost('postcode');
        
        $_result = array();
        
        if ('' != $_postcode)
        {
            // Post code supplied
            $_result = $this->_pickupList($_postcode, $_countryId);
        }
        else
        {
            // Case where a city is supplied but no postcode => request for postcodes list
            if ('' !== $_city)
            {
                $_wsResult = Man4x_MondialRelay_Model_Carrier_Pickup::wsGetPostcodeForCity($_city, $_countryId);
                if (property_exists($_wsResult, 'Liste') && property_exists($_wsResult->Liste, 'Commune'))
                {
                    $_cityList = $_wsResult['Liste']['Commune'];
                    switch (count($_cityList))
                    {
                        case 0:
                            // No city found
                            $_result = $this->_setResult(
                                    'city-list',
                                    'City List',
                                    'No matching city has been found in this country.');
                            break;
                            
                        case 1:
                            // Only one city found: direct ws call for pickups search
                            $_commune = reset($_cityList);
                            $_result = $this->_pickupList($_commune['CP'], $_commune['Pays']);
                            break;
                        
                        default:
                            $_result = $this->_cityList($_cityList);
                    }
                }
                else
                {
                    $_result = $this->_setResult(
                            'error',
                            'Mondial Relay service temporary unavailable.',
                            $_wsResult->STAT);
                }
            }
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Zend_Json::encode($_result));
    }

    /**
     * Handler for click on the pickup selection form button
     */
    public function detailspickupAction()
    {
        $_countryId = $this->getRequest()->getPost('country_id');
        $_pickupId = $this->getRequest()->getPost('pickup_id');
        
        $_wsResult = Man4x_MondialRelay_Model_Carrier_Pickup::wsGetPickupData($_pickupId, $_countryId);
        if (property_exists($_wsResult, 'Num'))
        {
            $_result = array();
            $_week = array(
                   Mage::helper('mondialrelay')->__('Monday')      => $_wsResult->Horaires_Lundi,
                   Mage::helper('mondialrelay')->__('Tuesday')     => $_wsResult->Horaires_Mardi,
                   Mage::helper('mondialrelay')->__('Wednesday')   => $_wsResult->Horaires_Mercredi,
                   Mage::helper('mondialrelay')->__('Thursday')    => $_wsResult->Horaires_Jeudi,
                   Mage::helper('mondialrelay')->__('Friday')      => $_wsResult->Horaires_Vendredi,
                   Mage::helper('mondialrelay')->__('Saturday')    => $_wsResult->Horaires_Samedi,
                   Mage::helper('mondialrelay')->__('Sunday')      => $_wsResult->Horaires_Dimanche,
            );
            
            // Formating working hours
            $_closed = Mage::helper('mondialrelay')->__('Closed');
            $_h = Mage::helper('mondialrelay')->__('%sh%s');
            foreach ($_week as $_day => $_hours)
            {
                $_hour = $_hours->string;
                $_nb = count($_hour);
                $_openings = '';
                $_open = true;
                $_o = 0;
                while ($_o < $_nb)
                {
                    if ('0000' != $_hour[$_o])
                    {
                        $_openings .= sprintf($_h, substr($_hour[$_o], 0, 2), substr($_hour[$_o], 2, 2));
                        $_openings .= ($_open ? '-' : ' ');
                        $_open = ! $_open;
                    }
                    $_o++;
                }
                array_push($_result, array(
                        'day'   => $_day,
                        'hours' => ('' === $_openings) ? $_closed : $_openings,
                    )
                );
            }
            $_result = $this->_setResult('pickup-details', '', $_result);
        }
        else
        {
            $_result = $this->_setResult(
                    'error',
                    'Mondial Relay service temporary unavailable.',
                    $_wsResult->STAT);
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Zend_Json::encode($_result));
    }

    /**
     * Handler for click on the pickup selection link
     * Save the selected pickup details in checkout session for later use (shipment save)
     * 
     */
    public function savepickupinsessionAction()
    {
        $_pickup = array();
        $_id = $this->getRequest()->getPost('id', '0');
        $_pickup[$_id] = array();
        foreach ($this->getRequest()->getPost() as $_key => $_value)
        {
            $_pickup[$_id][$_key] = $_value;
        }
        Mage::getModel('checkout/session')->setData('mr_pickups', $_pickup);
    }
        
    /**
     * Handler for click on the pickup info link
     */
    public function pickupinfoAction()
    {
        $_pickupId = $this->getRequest()->getPost('pickup_id');
        
        $_quote = Mage::getSingleton('checkout/session')->getQuote();
        $_address = $_quote->getShippingAddress();
        if (! $_address) {$_address = $_quote->getBillingAddress();}
        $_countryId = $_address->getCountryId();
        
        if ($_pickupId && $_countryId)
        {
            $_wsResult = Man4x_MondialRelay_Model_Carrier_Pickup::wsGetPickupData($_pickupId, $_countryId);
            if (property_exists($_wsResult, 'Num'))
            {
                $_layout = $this->getLayout();
                // We create the pickupinfo block and provide it with the pick-up data
                $_infoBlock = $_layout->createBlock('mondialrelay/checkout_pickupinfo', 'root');
                $_infoBlock->setData('pickup_info', $_wsResult);
                $_layout->addOutputBlock('root');
                $_output = $_layout->getOutput();
                $this->getResponse()->setBody($_output);
            }
        }
        else
        {
            $this->_getSession()->addError(
                    Mage::helper('mondialrelay')->__('Unable to display pick-up details')
                );            
        }        
    }
        
    
    /**
     * Build result data
     * 
     * @param string $type ('error' | 'city-list' | 'pickup-list' | 'pickup-details')
     * @param string $title
     * @param array | string $data
     * 
     * @return array 
     */
    private function _setResult($type, $title, $data)
    {
        $_result = array(
            'type'  => $type,
            'title' => $title ? Mage::helper('mondialrelay')->__($title) : '',
        );
        
        if ('error' == $type)
        {
            if (Mage::getStoreConfig('carriers/mondialrelay/debug_mode', true))
            {
                // Error and Debugging mode => we log the error
                Mage::helper('mondialrelay')->logDebugData($_result, $data);
            }
        }
        else
        {
            $_result['data'] = is_array($data) ? $data : Mage::helper('mondialrelay')->__($data);
        }
        
        return $_result;
    }
    
    /**
     * Build the city list for display
     * 
     * @param array $cityList
     * @return array 
     */
    private function _cityList($cityList)
    {
        $_result = array(
            'type'  => 'city-list',
            'title' => Mage::helper('mondialrelay')->__('City List'),
            'data'  => array(),
        );
        
        foreach ($cityList as $_city)
        {
            array_push($_result['data'], array('city' => $_city['Ville'], 'postcode' => $_city['CP']));
        }
        
        return $_result;
    }
    
    /**
     * Build the pickup list for display
     * 
     * @param string $postcode
     * @param string $countryId
     * @return array 
     */
    private function _pickupList($postcode, $countryId)
    {
        $_result = array();
        
        $_wsResult = Man4x_MondialRelay_Model_Carrier_Pickup::wsGetPickups($postcode, $countryId);
        
        if (false === $_wsResult)
        {
            $_result = $this->_setResult(
                    'error',
                    'No matching result for this post code in this country',
                    false);    
        }
        else
        {
           $_result = array(
               'type'   => 'pickup-list',
               'title'  => Mage::helper('mondialrelay')->__('Pick-up List'),
               'data'   => $_wsResult,
           );
        }
        return $_result;
    }

}