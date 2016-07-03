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
 * @desc        Label printing grid.
 *              Enable the mass label printing through web service
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */
class Man4x_MondialRelay_Block_Sales_Labelprinting_Grid
    extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_labelprinting_grid');
        $this->setDefaultSort('order_created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _getCollectionClass()
    {
        return 'sales/order_shipment_grid_collection';
    }

    protected function _prepareCollection()
    {
        $_collection = Mage::getResourceModel($this->_getCollectionClass());
        $_collection->distinct(true);
        $_collection->getSelect()->columns(
                array('shipment_created_at' => 'main_table.created_at')
                );
        $_collection->getSelect()->join(
                array('ost'   => $_collection->getTable('sales/shipment_track')), 
                'main_table.entity_id = ost.parent_id'
                );
        $_collection->getSelect()->columns(
                array('entity_id' => 'main_table.entity_id')
                );
        $_collection->addFieldToFilter(
                'ost.carrier_code',
                array('like' => 'mondialrelay%')
                );
        $_collection->getSelect()->group('main_table.entity_id');
        $this->setCollection($_collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'increment_id',
            array(
                'header'    => Mage::helper('sales')->__('Shipment #'),
                'index'     => 'increment_id',
                'type'      => 'text',
                )
            );

        $this->addColumn(
            'shipment_created_at',
            array(
                'header'        => Mage::helper('sales')->__('Date Shipped'),
                'index'         => 'created_at',
                'type'          => 'datetime',
                'filter_index'  => 'main_table.created_at',
                )
            );

        $this->addColumn(
            'order_increment_id',
            array(
                'header'    => Mage::helper('sales')->__('Order #'),
                'index'     => 'order_increment_id',
                'type'      => 'number',
                )
            );

        $this->addColumn(
            'order_created_at',
            array(
                'header'    => Mage::helper('sales')->__('Order Date'),
                'index'     => 'order_created_at',
                'type'      => 'datetime',
                )
            );

        $this->addColumn(
            'shipping_name',
            array(
                'header'    => Mage::helper('sales')->__('Ship to Name'),
                'index'     => 'shipping_name',
                )
            );

        $this->addColumn(
            'total_qty',
            array(
                'header'    => Mage::helper('sales')->__('Total Qty'),
                'index'     => 'total_qty',
                'type'      => 'number',
                )
            );
/*
        $this->addColumn(
            'action',
            array(
                'header'    => Mage::helper('sales')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                                array(
                                    'caption'   => Mage::helper('sales')->__('View'),
                                    'url'       => array('base' => 'sales/sales_shipment/view'),
                                    'field' => 'shipment_id',
                                    )
                                ),
                'filter'    => false,
                'sortable'  => false,
                'is_system' => true,
                )
            );
*/

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        if (!Mage::getSingleton('admin/session')->isAllowed('sales/order/shipment'))
        {
            return false;
        }
        return $this->getUrl('adminhtml/sales_shipment/view', array('shipment_id' => $row->getParentId()));
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('shipment_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        // Labels printing
        $this->getMassactionBlock()->addItem(
                'pdfshipments_order',
                array(
                    'label'     => Mage::helper('sales')->__('Labels Printing'),
                    'url'        => $this->getUrl('mondialrelay/sales_shipping/massLabelPrinting'),
                    )
                );

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/*', array('_current' => true));
    }
}
