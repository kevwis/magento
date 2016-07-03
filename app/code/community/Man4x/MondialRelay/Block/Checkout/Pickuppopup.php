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
 * @desc        Mondial Relay pickup selection popup block
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */

class Man4x_MondialRelay_Block_Checkout_Pickuppopup
	extends Mage_Core_Block_Template
{

    /**
     * Pseudo-constructor for Pickup popup block
     * Direct link to template
     */
    public function _construct()
    {
        $this->setTemplate('Man4x_MondialRelay/pickuppopup.phtml');
        return parent::_construct();
    }
    
    private function _getQuote()
    {
        $_quote = Mage::getSingleton('checkout/session')->getQuote();
        return $_quote;
    }
    
    private function _getAddress()
    {
        $_address = $this->_getQuote()->getShippingAddress();
        if (! $_address) {$_address = $this->getBillingAddress();}
        return $_address;
    }
    
    public function getAddressValue($field, $default = '')
    {
        $_address = $this->_getAddress();
        if ($_address instanceof Mage_Sales_Model_Quote_Address) {return $_address->getData($field);}
            else {return $default;}
    }
    
    public function getCountryHtmlSelect()
    {
        // Build the allowed country list for pick-up deliveries regarding module config
        $_countryCodes = explode(',', Mage::getStoreConfig('carriers/mondialrelaypickup/specificcountry'));
        $_allowedCountries = array();
        foreach($_countryCodes as $_countryCode)
        {
            $_allowedCountries[$_countryCode] = Mage::app()->getLocale()->getCountryTranslation($_countryCode);
        }
        
        // Initialize the control value with shipping / billing address country code or default country 
        $_address = $this->_getAddress();
        $_countryId = $_address ? $_address->getCountryId() : Mage::helper('core')->getDefaultCountry();
        
        $_select = $this->getLayout()->createBlock('core/html_select')
            ->setName('pickup_country_id')
            ->setId('pickup_country_id')
            ->setTitle(Mage::helper('checkout')->__('Country'))
            ->setClass('validate-select')
            ->setValue($_countryId)
            ->setOptions($_allowedCountries);

        return $_select->getHtml();
    }
    
    public function getPickupGatheringUrl()
    {
		$_url = Mage::getUrl('mondialrelay/index/gatherpickups', array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()));
		return $_url;
    }
    
    public function getPickupDetailsUrl()
    {
        $_url = Mage::getUrl('mondialrelay/index/detailspickup', array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure())); 
        return $_url;
    }
    
    public function getSavePickupinSessionUrl()
    {
        $_url = Mage::getUrl('mondialrelay/index/savepickupinsession', array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure())); 
        return $_url;
    }
    
    public function getLocaleStr()
    {
        $_jsArray =     '{info: "' . $this->__('Pick-up details') . '",';
        $_jsArray .=    'empty_postcode: "' . $this->__('Please enter a post code to run the search') . '",';
        $_jsArray .=    'select: "' . $this->__('Select this pick-up') . '"}';
        return $_jsArray;
    }
    
}