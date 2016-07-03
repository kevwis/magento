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
 * @description Observer for
 *                  - <sales_convert_quote_address_to_order> (frontend)
 *                  - <sales_order_shipment_save_before> (adminhtml)  
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */
class Man4x_MondialRelay_Model_Observer
{

    /**
     * Observer for <sales_convert_quote_address_to_order> frontend event
     * Replace customer shipping address with the selected pickup address
     */
    public function replaceShippingAddress($observer)
    {

        $_order = $observer->getEvent()->getOrder();
        $_address = $observer->getEvent()->getAddress();
        $_carrier = $_order->getShippingCarrier();

        // Carrier is Mondial Relay address is a shipping address
        if (($_carrier instanceof Man4x_MondialRelay_Model_Carrier_Pickup) && ('shipping' == $_address->getAddressType()))
        {
            // We recover pick-up data set during checkout
            $_mrPickups = Mage::getModel('checkout/session')->getData('mr_pickups');
            $_method = explode('_', $_address->getShippingMethod());

            if (2 == count($_method) && is_array($_selpickup = current($_mrPickups)))
            {
                // if on-map selection, there is only one pick-up saved in checkout/session: we recover its id
                // to build final method (mondialrelaypickup_24R_######)
                $_method[] = $_selpickup['id'];
            }


            if (isset($_mrPickups[$_method[2]]))
            {
                // We recover selected pick-up data in session data
                $_selpickup = $_mrPickups[$_method[2]];

                $_pickupMethod = implode('_', $_method);
                // ... update shipping method for the order (appended with pick-up id)
                $_order->setShippingMethod($_pickupMethod);

                // We convert quote address model to order address model
                // $_osAddress = Mage::getModel('Sales/Convert_Quote')->addressToOrderAddress($_address);
                $_osAddress = $_address;
                
                // Shipping address replacement with pick-up data
                $_osAddress->setCompany($_selpickup['name'])
                        ->setStreet($_selpickup['street'])
                        ->setPostcode($_selpickup['postcode'])
                        ->setCity($_selpickup['city'])
                        ->setCountryId($_selpickup['country_id'])
                        ->setShippingMethod($_pickupMethod);

                Mage::helper('checkout/cart')->getQuote()->setShippingAddress($_osAddress);
                // $_order->setShippingAddress($_osAddress);
            }
        }
    }

    /**
     * Observer for <sales_order_shipment_save_before> adminhtml event
     * Register Mondial Relay shipping code for the shipment
     */
    public function registerShipment($observer)
    {
        $_shipment = $observer->getShipment();
        $_order = $_shipment->getOrder();
        $_carrier = $_order->getShippingCarrier();

        // Order exists and MondialRelay is the registered shipping method for the given order
        if ($_order->getId() && ($_carrier instanceof Man4x_MondialRelay_Model_Carrier_Abstract))
        {
            // We are running a MR mass shipping (flag set in ShippingController->massShippingWsAction)
            // or MR registration is enabled for single shipment (cf settings) and it's a first MR shipment
            if (Mage::getSingleton('adminhtml/session')->hasMondialRelayWsRegistration()
			// if (isset($_order->_data['man4x_mondialrelay_mass_registration'])
                    || (Mage::getStoreConfig('carriers/mondialrelay/auto_ws', $_order->getStore())
                        && Mage::helper('mondialrelay')->isFirstMRTrack($_shipment))
            )
            {
                // $_manualParams = Mage::getSingleton('adminhtml/session')->hasMondialRelayRealWeight() ? Mage::getSingleton('adminhtml/session')->getMondialRelayRealWeight() : array();

                // We record the shipment at Mondial Relay web service
                $_wsResult = $_carrier->wsRegisterShipment($_order);
                if (!property_exists($_wsResult, 'ExpeditionNum'))
                {
                    $_errMsg = Mage::helper('mondialrelay')->convertStatToTxt($_wsResult->STAT);
                    if (Mage::getStoreConfig('carriers/mondialrelay/debug_mode', $_order->getStore()))
                    {
                        $_errMsg .= ' [Debug data || ' . print_r($_wsResult->wsParams, true) . ']';
                    }
                    Mage::throwException(
                            Mage::helper('mondialrelay')->__(
                                    'Mondial Relay shipment error for order #%s (%s)', 
                                    $_order->getIncrementId(),
                                    $_errMsg
                            )
                    );
                }

                //@TODO: calcule the shipment weight (can be different from order weight if partial shipping)
                //
                // We create the shipment track
                $_track = Mage::getModel('sales/order_shipment_track')
                        ->setNumber($_wsResult->ExpeditionNum)
                        ->setCarrier('Mondial Relay')
                        ->setCarrierCode($_carrier->getCarrierCode())
                        ->setTitle($_carrier->getConfigData('methodtitle'))
                        ->setPopup(1);

                $_shipment->addTrack($_track);
            }
        }
    }

}