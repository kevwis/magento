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
 * @desc        Backend model for config sender address validation
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */

class Man4x_MondialRelay_Model_System_Config_Validation_Senderaddress
    extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $_helper = Mage::helper('mondialrelay');
        $_rates = $_helper->splitLines($this->getValue()); // Get sender address from config
        if (count($_rates) > 4)
        {
            Mage::getSingleton('adminhtml/session')->addWarning(
                $_helper->__('Only four lines are allowed in sender address')
            );
        }
        for ($_i = 0; $_i < 4; $_i++)
        {
            if (isset($_rates[$_i]) && mb_strlen($_rates[$_i]) > 32)
            {
                Mage::getSingleton('adminhtml/session')->addWarning(
                    $_helper->__('Only 32 characters per line for sender address')
                );
                break;
            }
        }
        return parent::save();
    }

}