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
 * @desc        Mass shipping grid.
 *              Enable mass shipping (through web service or flat file)
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */

class Man4x_MondialRelay_Block_Sales_Massshipping_Grid
    extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_massshipping_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
    }

    protected function _getCollectionClass()
    {
        return 'sales/order_invoice_grid_collection';
    }

    private function _getOrdersCollection()
    {
        $_collection = Mage::getResourceModel($this->_getCollectionClass())
                ->join(
                        'order',
                        'main_table.order_increment_id = order.increment_id',
                        array('shipping_method', 'status', 'entity_id', 'weight')
                        )
                ->addAttributeToFilter('shipping_method', array("like" => 'mondialrelay%'))
                ->addAttributeToFilter('status', 'processing');
        return $_collection;
    }
    
    protected function _prepareCollection()
    {
        $_collection = $this->_getOrdersCollection();
        $this->setCollection($_collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'invoice_increment_id',
            array(
                'header'        => Mage::helper('sales')->__('Invoice #'),
                'width'         => '80px',
                'type'          => 'number',
                'index'         => 'increment_id',
                'filter_index'  => 'main_table.increment_id',
                )
            );

        $this->addColumn(
            'created_at',
            array(
                'header'        => Mage::helper('sales')->__('Invoice Date'),
                'index'         => 'created_at',
                'filter_index'  => 'main_table.created_at',
                'type'          => 'datetime',
                'width'         => '100px',
                )
            );

        $this->addColumn(
            'order_increment_id',
            array(
                'header'        => Mage::helper('sales')->__('Order #'),
                'width'         => '80px',
                'type'          => 'text',
                'index'         => 'order_increment_id',
                'filter_index'  => 'main_table.order_increment_id',
                )
            );

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                'store_id',
                array(
                    'header'            => Mage::helper('sales')->__('Purchased from (store)'),
                    'index'             => 'store_id',
                    'type'              => 'store',
                    'store_view'        => true,
                    'filter_index'      => 'main_table.store_id',
                    'display_deleted'   => true,
                    )
                );
        }

        $this->addColumn(
            'billing_name',
            array(
                'header'            => Mage::helper('sales')->__('Bill to Name'),
                'index'             => 'billing_name',
                )
            );

        $this->addColumn(
            'base_grand_total',
            array(
                'header'            => Mage::helper('sales')->__('G.T. (Base)'),
                'index'             => 'base_grand_total',
                'type'              => 'currency',
                'currency'          => 'base_currency_code',
                'filter_index'      => 'main_table.base_grand_total'
                )
            );

        $this->addColumn(
            'carrier',
             array(
                'header'            => Mage::helper('sales')->__('Carrier'),
                'index'             => 'shipping_method',
                )
            );

        $this->addColumn(
            'weight',
             array(
                'header'            => Mage::helper('sales')->__('Weight (calculated)'),
                'index'             => 'weight',
                )
            );

        $this->addColumn(
            'poids',
            array(
                'header'            => Mage::helper('sales')->__('Weight (real)'),
            	'type'              => 'input',
                'name'              => 'poids',
            	'validate_class'    => 'validate-not-negative-number',
                'editable'          => true,
                )
            );

/*
        if (Mage::getSingleton('admin/session')->isAllowed('mondialrelay/index/actions/view'))
        {
            $this->addColumn(
                    'action',
                    array(
                        'header'    => Mage::helper('sales')->__('Action'),
                        'width'     => '50px',
                        'type'      => 'action',
                        'getter'    => 'getId',
                        'actions'   => array(
                                        array(
                                            'caption'   => Mage::helper('mondialrelay')->__('Change weight'),
                                            'url'       => array('base' => ''),
                                            'field'     => 'order_id',
                                            )
                                       ),
                        'renderer'  => 'Man4x_MondialRelay_Block_Adminhtml_GridJsRendererAction',
                        'filter'    => false,
                        'sortable'  => false,
                        'index'     => 'stores',
                        'is_system' => true,
                        )
                    );
        }
*/
        

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
   {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view'))
        {
            return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getId()));
        }
        return false;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('order_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem(
                'massshipping_order_ws',
                array(
                    'label'     => Mage::helper('mondialrelay')->__('Mass Shipping (Web Service)'),
                    'url'       => $this->getUrl('mondialrelay/sales_shipping/massShippingWs'),
                )
        );
        $this->getMassactionBlock()->addItem(
                'massshipping_order_cvs',
                array(
                    'label'     => Mage::helper('mondialrelay')->__('Mass Shipping (Flat File)'),
                    'url'       => $this->getUrl('mondialrelay/sales_shipping/massShippingCvs'),
                )
        );

        return $this;
    }

    /**
    /* Ajax grid refreshing URL
    */
    public function getGridUrl()
    {
       return $this->_getData('grid_url') ? $this->_getData('grid_url') : $this->getUrl('*/*/ajaxgrid', array('_current'=>true));
    }

    /**
    /* Called by the linked widget block serializer to get initial values
    */
    public function getRealWeights()
    {
        $_realWeights = array();
		$_savedWeights = array();
        if (Mage::getSingleton('adminhtml/session')->hasMondialRelayRealWeight())
		{
			$_savedWeights = Mage::getSingleton('adminhtml/session')->getMondialRelayRealWeight();
		}
        foreach($this->_getOrdersCollection()->load() as $_order)
        {
            $_realWeights[$_order->getId()] = 
				array('poids' => (isset($_savedWeight[$_order->getId()]) ? $_savedWeight[$_order->getId()] : $_order->getWeight()));
        }
        return $_realWeights;
    }
    
    protected function _afterToHtml($html)
    {
        // We had the 'checkbox' class to the checkbox input to enable grid serializer code
        $html = str_replace('massaction-checkbox', 'massaction-checkbox checkbox', $html);
        // We replace the grid [validate] button default action to call our JS
        $html = str_replace("sales_massshipping_grid_massactionJsObject.apply()", "prepareWs(sales_massshipping_grid_massactionJsObject, 'real_weight_input');", $html);
        return $html;
    }


}
