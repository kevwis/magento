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
 * @desc        Backend model for config table rate validation
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */

class Man4x_MondialRelay_Model_System_Config_Validation_Tablerate
    extends Mage_Core_Model_Config_Data
{
    /**
     * Save config value
     * 
     * @return Mage_Core_Model_Abstract
     */
    public function save()
    {
        if ('' != trim($this->getValue()))
        {
            $_output = array();
        
            $_lines = Mage::helper('mondialrelay')->splitLines(strtoupper($this->getValue()));
            $_nl = 1; // Number of line
        
            foreach ($_lines as $_line)
            {
                $_c = $_r = $_p = $_cond = '*';
            
                $_line = trim($_line);
                $_v = explode(";", $_line);
            
                $_c = $_r = $_p = $_cond = '*';

                switch (count($_v))
                {
                    case 0:
                        continue;
                
                    case 1:
                        // rate
                        $_rate = (float) $_v[0];
                        break;
                
                    case 2:
                        if ((float) $_v[0])
                        {
                            // condition; rate
                            $_cond = (float) $_v[0];
                            $_rate = (float) $_v[1];
                        }
                        else
                        {
                            // country code; rate
                            $_c = strtoupper(substr(trim($_v[0]), 0, 2));
                            $_rate = (float) $_v[1];                                
                        }
                        break;
                    
                    case 3:
                        // coundry code; condition; rate
                        $_c = strtoupper(substr(trim($_v[0]), 0, 2));
                        if ((float) $_v[1]) {$_cond = (float) $_v[1];}
                        $_rate = (float) $_v[2];
                        break;
                
                    case 4:
                        // country code; post code; condition; rate
                        $_c = strtoupper(substr(trim($_v[0]), 0, 2));
                        $_p = strtoupper(trim($_v[1]));
                        if ((float) $_v[2]) {$_cond = (float) $_v[2];}
                        $_rate = (float) $_v[3];
                        break;
                
                    case 5:
                        // country code; region code, post code; condition; rate
                        $_c = strtoupper(substr(trim($_v[0]), 0, 2));
                        $_r = strtoupper(trim($_v[1]));
                        $_p = strtoupper(trim($_v[2]));
                        if ((float) $_v[3]) {$_cond = (float) $_v[3];}
                        $_rate = (float) $_v[4];
                        break;
                    
                    default:
                        // Fields number error
                        Mage::throwException(
                            Mage::helper('mondialrelay')->__('Error in table rates line %s: %s fields found!', $_nl, count($_v))
                        );
                }
            
                $_output[]= array($_c, $_r, $_p, $_cond, $_rate);
                $_nl++;
            }
        
            // Sort rates in decreasing order of specificity
            if (usort($_output, array($this, '_cmp')))
            {  
                // Replace text field with expanded 6-fields syntax
                $_value = array();
                foreach ($_output as $_line)
                {
                   $_value[] = implode(';', $_line);
                }
                
                // Exclude duplicated rates
                $_result = array_unique($_value);
                
                $this->setValue(implode("\n", $_result));
            }
        }
        
        return parent::save();
    }

    /**
     *  Comparison function in decreasing order of specificity
     *      $a[0] = country code = alphabetical order
     *          $a[1] = region code = alphabetical order
     *              $a[2] = departement code = decreasing length order
     *                  $a[3] = condition value = increasing value order    
     * 
     * @return int
     */
    private function _cmp($a, $b)
    {
        if ($a[0] != $b[0])
        {
            if ($a[0] == '*') {return 1;}
            else if ($b[0] == '*') {return -1;}
            else {if ($a[0] < $b[0]) {return -1;} else {return 1;}}
        }
        else
        {
            if ($a[1] != $b[1])
            {
                if ($a[1] == '*') {return 1;}
                else if ($b[1] == '*') {return -1;}
                else {if ($a[1] < $b[1]) {return -1;} else {return 1;}}                
            }
            else
            {
                if ($a[2] != $b[2])
                {
                    if ($a[2] == '*') {return 1;}
                    else if ($b[2] == '*') {return -1;}
                    else {
                        $lga = strlen($a[2]); $lgb = strlen($b[2]);
                        if ($lga > $lgb) {return -1;}
                        else if ($lga < $lgb) {return 1;}
                        else {if ($a[2] < $b[2]) {return -1;} else {return 1;}}
                    }
                }
                else
                {
                    if ($a[3] == '*') {return 1;}
                    else if ($b[3] == '*') {return -1;}
                    else if ($a[3] < $b[3]) {return -1;}
                    else if ($a[3] > $b[3]) {return 1;}
                    else {return 0;}
                }
            }
        }
            
    }
}