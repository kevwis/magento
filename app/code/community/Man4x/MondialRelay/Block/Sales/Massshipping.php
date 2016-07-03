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
 * @desc        Mass shipping grid container
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */

class Man4x_MondialRelay_Block_Sales_Massshipping
	extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'mondialrelay';
        $this->_controller = 'sales_massshipping'; // => grid = mondialrelay_block_sales_massshipping_grid
        $this->_headerText = 'Mondial Relay' . ' - ' . Mage::helper('mondialrelay')->__('Mass Shipping');
        parent::__construct();
        $this->_removeButton('add');        
    }


    protected function _prepareLayout()
    {       
        parent::_prepareLayout();
        // We create the block serializer and append it to root
        $_serializer = $this->getLayout()->createBlock('adminhtml/widget_grid_serializer', 'serializer');
        $_serializer->initSerializerBlock('sales_massshipping.grid', 'getRealWeights', 'real_weight_input', 'order_ids');
        $_serializer->addColumnInputName('poids');
        $this->getLayout()->getBlock('root')->setChild('serializer', $_serializer);
	return $this;
    }
  
    protected function _toHtml()
    {
        $_html = parent::_toHtml();
	$_html .= $this->getLayout()->getBlock('serializer')->_toHtml();
        // We append the JS code that will shunt the grid [Validate] default action
        // We clone the hidden input element linked to the grid serializer
        // and insert it inside the mass action form in order for it to be submitted
        $_js = <<<JSCODE
<script type="text/javascript">
function prepareWs(jsGrid, i) {
var _e = $$("input[name=" + i + "]")[0];
if (_e) {
   new Insertion.Bottom(jsGrid.formHiddens.parentNode, jsGrid.fieldTemplate.evaluate({name: 'real_weight_input', value: _e.getValue()}));
   jsGrid.apply();
   }
}
</script>
JSCODE;
	return $_html . $_js;
    }
}