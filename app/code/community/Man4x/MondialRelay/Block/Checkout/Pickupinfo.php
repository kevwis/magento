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
 * @desc        Mondial Relay pickup info popup block
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */

class Man4x_MondialRelay_Block_Checkout_Pickupinfo
	extends Mage_Core_Block_Template
{
    
    /**
     * Pseudo-constructor for Pickupinfo block
     * Direct link to template
     * This block is directly provided with the pick-up data (pickup_info) when created from controller
     * (cf Man4x_MondialRelay_IndexController->pickupinfoAction())
     */
    public function _construct()
    {
        $this->setTemplate('Man4x_MondialRelay/pickupinfo.phtml');
        return parent::_construct();
    }
    
    private function _getPickupSessionData($field)
    {
        $_pickups = Mage::getModel('checkout/session')->getData('mr_pickups');
        $_pickup = $_pickups[$this->getPickupInfo()->Num];
        return $_pickup[$field];
    }
    
    public function getPickupAddress()
    {
        $_address =     $this->getPickupInfo()->LgAdr3 . 
                        ' ' . 
                        $this->getPickupInfo()->LgAdr2 .
                         '<br/>' .
                        $this->getPickupInfo()->CP .
                        ' ' .
                        $this->getPickupInfo()->Ville;
        return $_address;
    }

    public function getPickupLocHint() {
        return  trim($this->getPickupInfo()->Localisation1) .
                trim($this->getPickupInfo()->Localisation2) ? ' ' . trim($this->getPickupInfo()->Localisation2) : '';
    }
    public function getPickupLat() {return $this->_getPickupSessionData('latitude');}
    public function getPickupLong() {return $this->_getPickupSessionData('longitude');}
    public function getPickupMap() {return $this->getPickupInfo()->URL_Plan;}
    public function getPickupName() {return $this->getPickupInfo()->LgAdr1 . ' ' . $this->getPickupInfo()->LgAdr2;}
    
    public function getPickupOpeningHours() {
        $_oh = '';
        $_week = array(
               Mage::helper('mondialrelay')->__('Monday')      => $this->getPickupInfo()->Horaires_Lundi,
               Mage::helper('mondialrelay')->__('Tuesday')     => $this->getPickupInfo()->Horaires_Mardi,
               Mage::helper('mondialrelay')->__('Wednesday')   => $this->getPickupInfo()->Horaires_Mercredi,
               Mage::helper('mondialrelay')->__('Thursday')    => $this->getPickupInfo()->Horaires_Jeudi,
               Mage::helper('mondialrelay')->__('Friday')      => $this->getPickupInfo()->Horaires_Vendredi,
               Mage::helper('mondialrelay')->__('Saturday')    => $this->getPickupInfo()->Horaires_Samedi,
               Mage::helper('mondialrelay')->__('Sunday')      => $this->getPickupInfo()->Horaires_Dimanche,
        );
            
        // Formating working hours
        $_closed = Mage::helper('mondialrelay')->__('Closed');
        $_h = Mage::helper('mondialrelay')->__('%sh%s');
        foreach ($_week as $_day => $_hours)
        {
            $_oh .= '<tr>';
            $_hour = $_hours->string;
            $_nb = count($_hour);
            $_openings = '';
            $_open = true;
            $_o = 0;
            while ($_o < $_nb)
            {
                if ('0000' != $_hour[$_o])
                {
                    $_openings .= sprintf($_h, substr($_hour[$_o], 0, 2), substr($_hour[$_o], 2, 2));
                    $_openings .= ($_open ? '-' : ' ');
                    $_open = ! $_open;
                }
                $_o++;
            }
            $_oh .= '<td class="day">' . $_day . '</td>';
            $_oh .= '<td class="hours">' . (('' === $_openings) ? $_closed : $_openings) . '</td>';
            $_oh .= '</tr>';
        }
        return $_oh;
    }

    public function getPickupPicture() {
        $_url = $this->getPickupInfo()->URL_Photo;
        if (! $_url) {$_url = $this->getSkinUrl('images/Man4x_MondialRelay/logo_mondialrelay.gif');}
        return $_url;
    }
}