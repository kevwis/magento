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
 * @desc        Mondial Relay carrier model class for home deliveries
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */

class Man4x_MondialRelay_Model_Carrier_Home
    extends Man4x_MondialRelay_Model_Carrier_Abstract
{    
    
    protected $_code = 'mondialrelayhome';

    /**
     * Define the Mondial Relay home delivery methods
     * 
     * @return array 
     */
    static public function getAllMethods($mode = null)
    {
        return parent::getAllMethods('mondialrelayhome');
    }
    
        /**
     *  Get params for CSV export
     *  !!! Keep parameters in strict order !!!
     *  Subclasses precise their own specific parameters
     * 
     *  @param Mage_Sales_Model_Order $order
     *  @return string
     */
    public function getFlatFileData(Mage_Sales_Model_Order $order)
    {
        $_record = parent::getFlatFileData($order);
        $_record[16] = 'D'; // Shipping type (<R>elais, <D>omicile)
        $_csvLine = implode(Man4x_MondialRelay_Model_Carrier_Abstract::CSV_SEPARATOR, $_record);
        $_csvLine .= Man4x_MondialRelay_Model_Carrier_Abstract::CSV_EOL;
        return $_csvLine; 
    }
    
}