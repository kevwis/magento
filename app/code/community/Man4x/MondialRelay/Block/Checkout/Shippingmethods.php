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
 * @desc        Special Mondial Relay shipping methods template for checkout
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */


class Man4x_MondialRelay_Block_Checkout_Shippingmethods
	extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{
    // Get methods description for shipping methods 
    public function getMethodsDescription()
    {
        $_methodsDesc = 'var _methodsDesc = new Object(); ';
        
        $_rates = $this->getShippingRates();        
        foreach ($_rates as $_rate => $_methods)
        {
            foreach ($_methods as $_method)
            {
                if ($_method['method_description'])
                {
                    $_desc = str_replace('"', '\"', $_method['method_description']);
                    $_methodsDesc .= sprintf('_methodsDesc["%s"] = "%s"; ', $_method['code'], $_desc);
                }
            }
        }
        return $_methodsDesc;
    }
    
    // Get Pick-up popup URL
    public function getPickupPopupUrl()
    {
        $_url = Mage::getUrl('mondialrelay/index/pickuppopup', array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure())); 
        return $_url;
    }
    
    // Get Pick-up details URL
    public function getPickupInfoUrl()
    {
        $_url = Mage::getUrl('mondialrelay/index/pickupinfo', array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure())); 
        return $_url;
    }
    
    // Get Pick-up popup URL
    public function onMapSelection()
    {
        $_map = Mage::getStoreConfig('carriers/mondialrelaypickup/map_selection', true) ? 'true' : 'false';
        return $_map;
    }
    
}