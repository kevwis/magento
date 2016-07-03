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
 * @desc        Available values for weight unit
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */

class Man4x_MondialRelay_Model_System_Config_Source_Weightunit {

    public function toOptionArray()
    {
        $_arr = array(
            array(
                'value' => 'g',
                'label' => Mage::helper('mondialrelay')->__('Gram'),
                ),
            array(
                'value' => 'kg',
                'label' => Mage::helper('mondialrelay')->__('Kilogram'),
                ),
            array(
                'value' => 'oz',
                'label' => Mage::helper('mondialrelay')->__('Ounce'),
                ),
            array(
                'value' => 'lb',
                'label' => Mage::helper('mondialrelay')->__('Pound'),
                ),
        );
        return $_arr;
    }

}