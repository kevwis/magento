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
 * @desc        Mondial Relay mass shipping / mass label printing controller for admin
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */
require_once 'Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php';

class Man4x_MondialRelay_Sales_ShippingController extends Mage_Adminhtml_Sales_Order_ShipmentController
{

    /**
     * Additional initialization
     */
    protected function _construct()
    {
        $this->setUsedModuleName('Man4x_MondialRelay');
    }

    /**
     * Display grid for mass shipping
     * as defined in Pointsrelais/etc/adminhtml.xml
     */
    public function massShippingGridAction()
    {
        $this->_title($this->__('Mondial Relay'))->_title($this->__('Mass Shipping'));
        $this->loadLayout()
                ->_setActiveMenu('sales/pointsrelais')
                ->_addContent($this->getLayout()->createBlock('mondialrelay/sales_massshipping'))
                ->renderLayout();
    }

    /**
     * Display grid for label mass printing
     * as defined Pointsrelais/etc/adminhtml.xml
     */
    public function massLabelPrintingGridAction()
    {
        $this->_title($this->__('Mondial Relay'))->_title($this->__('Labels Printing'));
        $this->loadLayout()
                ->_setActiveMenu('sales/pointsrelais')
                ->_addContent($this->getLayout()->createBlock('mondialrelay/sales_labelprinting'))
                ->renderLayout();
    }

    /**
     * Refresh mass shipping grid
     */
    public function ajaxGridAction()
    {
        $this->loadLayout('empty');
        $this->getLayout()->createBlock('core/text_list', 'root', array('output' => 'toHtml'));
        $_grid = $this->getLayout()->createBlock('mondialrelay/sales_massshipping_grid')->setOrderIds($this->getRequest()->getPost('order_ids', null));
        $this->getLayout()->getBlock('root')->append($_grid);
        $_formkey = $this->getLayout()->createBlock('core/template', 'formkey')->setTemplate('formkey.phtml');
        $this->getLayout()->getBlock('root')->append($_formkey);
        $this->getResponse()->setBody($this->getLayout()->getBlock('root')->toHtml());
    }

    /**
     * Mondial Relay mass shipping (Web Service)
     * From Admin > Sales > Mondial Relay > Mass Shipment, Action = mass shipping (web service)
     */
    public function massShippingWsAction()
    {
        // We recover the real weights defined from the mass shipping grid and save them in admin session
        $_realWeights = Mage::helper('adminhtml/js')->decodeGridSerializedInput($this->getRequest()->getPost('real_weight_input', ''));
        Mage::getSingleton('adminhtml/session')->setMondialRelayRealWeight($_realWeights);

        $_orderIds = (array) $this->getRequest()->getPost('order_ids');
        $_nbOrders = count($_orderIds);
        $_shipmentIds = array();

        // Flag to trigger Mondial Relay registration in Man4x_MondialRelay_Model_Observer->registerShipment()
        Mage::getSingleton('adminhtml/session')->setMondialRelayWsRegistration(true);

        foreach ($_orderIds as $_orderId)
        {
            $_order = Mage::getModel('sales/order')->load($_orderId);
            if (!$_order->getId())
            {
                continue;
            }

            // Determines whether notification email must be sent
            $_shipment = array(
                'send_email' => (bool) Mage::getStoreConfig('carriers/mondialrelay/sendemail', $_order->getStoreId()),
            );

            $this->getRequest()->setParam('order_id', $_orderId);
            $this->getRequest()->setPost('shipment', $_shipment);
            $this->saveAction();

            $_shipment = Mage::registry('current_shipment');
            Mage::unregister('current_shipment');
            if ($_shipment instanceof Mage_Sales_Model_Order_Shipment)
            {
                if (count($_shipment->getTracksCollection())) // Shipment has one track at least -> Shipment succeeded
                {
                    $_shipmentIds[] = $_shipment->getEntityId();
                }
            }
        }

        // Remove the session flag and weight array
        Mage::getSingleton('adminhtml/session')->unsMondialRelayWsRegistration();

        if ($_nbOrders == count($_shipmentIds))
        {
            $this->getRequest()->setParam('shipment_ids', $_shipmentIds);
            // Redirection to mass label printing
            $this->massLabelPrintingAction();
        }
        else
        {
            $this->_redirect('*/*/massShippingGrid');
        }
    }

    /**
     * Mondial Relay mass shipping (Flat File)
     * From Admin > Sales > Mondial Relay > Mass Shipment, Action = mass shipping (flat file)
     *
     * @TODO: manage file format and file extension
     */
    public function massShippingCvsAction()
    {
        $_file = '';
        $_fileName = 'mondialrelay_export_' . Mage::getSingleton('core/date')->date('Ymd_His') . '.txt';

        $_orderIds = (array) $this->getRequest()->getPost('order_ids');
        if (count($_orderIds) > 100)
        {
            $this->_getSession()->addError(
                    Mage::helper('mondialrelay')->__('Too many orders: 100 max. by export file.'));
            return $this->_redirectReferer();
        }
        foreach ($_orderIds as $_orderId)
        {
            $_order = Mage::getModel('sales/order')->load($_orderId);
            if ($_order->getId())
            {
                $_carrier = $_order->getShippingCarrier();
                if ($_carrier instanceof Man4x_MondialRelay_Model_Carrier_Abstract)
                {
                    $_file .= $_carrier->getFlatFileData($_order);
                }
            }
        }
        $_fileCharset = 'ISO-8859-1'; // possibly unicode
        $_file = utf8_decode($_file);
        $_fileMimeType = 'text/plain'; // possibly 'application/csv' for csv format;
        return $this->_prepareDownloadResponse($_fileName, $_file, $_fileMimeType . '; charset="' . $_fileCharset . '"');
    }

    /**
     * Mondial Relay mass label printing
     * from Admin > Sales > Mondial Relay > Label Print, Action = label print
     */
    public function massLabelPrintingAction()
    {
        $_shipmentIds = (array) $this->getRequest()->getParam('shipment_ids');
        // shipment ids -> tracking ids
        $_trackings = array();
        foreach ($_shipmentIds as $_shipmentId)
        {
            if ($_shipment = Mage::getModel('sales/order_shipment')->load($_shipmentId))
            {
                $_tracks = $_shipment->getTracksCollection();
                foreach ($_tracks as $_track)
                {
                    if (($_track->getParentId() == $_shipmentId)
                            && (FALSE !== strpos($_track->getCarrierCode(), 'mondialrelay')))
                    {
                        // getTrackNumber() for Magento 1.7+ and $_track->getNumber() for older versions
                        $_trackings[] = $_track->getTrackNumber() ? $_track->getTrackNumber() : $_track->getNumber();
                    }
                }
            }
        }
        $_trackingList = implode(';', $_trackings);
        try
        {
            // We get the pdf file from Mondial Relay web service
            $_urlLabel = Man4x_MondialRelay_Model_Carrier_Abstract::getWsLabelUrl($_trackingList);
            if (strlen($_urlLabel) < 4)
            {
                // Error 
                $this->_getSession()->addError(
                        Mage::helper('mondialrelay')->__(
                                'An error has occurred during label recovery (%s)', Mage::helper('mondialrelay')->convertStatToTxt($_urlLabel))
                );
            }
            else
            {
                $this->_processDownload($_urlLabel, 'url');
                exit(0);
            }
        }
        catch (Mage_Core_Exception $e)
        {
            // Error
            $this->_getSession()->addError(
                    Mage::helper('mondialrelay')->__('An error has occurred during labels recovery. Please contact Mondial Relay or try again later.'));
        }
        return $this->_redirectReferer();
    }

    /**
     * Download resource from WS server
     * 
     * @param string $resource
     * @param string $resourceType
     * @return array 
     */
    private function _processDownload($resource, $resourceType)
    {
        $_helper = Mage::helper('downloadable/download'); /* @var $helper Mage_Downloadable_Helper_Download */

        $_helper->setResource($resource, $resourceType);

        $_fileName = $_helper->getFilename();
        $_contentType = $_helper->getContentType();

        $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Content-type', $_contentType, true);

        if ($_fileSize = $_helper->getFilesize())
        {
            $this->getResponse()->setHeader('Content-Length', $_fileSize);
        }

        if ($_contentDisposition = $_helper->getContentDisposition())
        {
            $this->getResponse()->setHeader('Content-Disposition', $_contentDisposition . '; filename=' . $_fileName);
        }

        $this->getResponse()->clearBody();
        $this->getResponse()->sendHeaders();

        $_helper->output();
    }

}