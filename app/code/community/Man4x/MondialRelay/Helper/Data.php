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
 * @description Helper for web services        
 * @author      Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * @license     http://www.opensource.org/licenses/MIT  The MIT License (MIT)
 */
class Man4x_MondialRelay_Helper_Data extends Mage_Core_Helper_Abstract
{

    const LOG_FILE = 'man4x_mondialrelay_debug.log';
    const CSV_EOL = "\r\n";

    // WS Error messages
    protected $_statArray = array(
        '1' => 'Invalid Company Name',
        '2' => 'Missing Company Number',
        '3' => 'Invalid Company Account',
        '4' => '',
        '5' => 'Invalid Company File Number',
        '6' => '',
        '7' => 'Invalid Company Customer Number',
        '8' => '',
        '9' => 'Unknown/Non-unique City Name',
        '10' => 'Invalid Collection Type (1/D > Home -- 3/R > Relay)',
        '11' => 'Invalid Collection Relay Number',
        '12' => 'Invalid Collection Relay Country',
        '13' => 'Invalid Delivery Type (1/D > Home -- 3/R > Relay)',
        '14' => 'Invalid Delivery Relay Number',
        '15' => 'Invalid Delivery Relay Country',
        '16' => 'Invalid Country Code',
        '17' => 'Invalid Address',
        '18' => 'Invalid City',
        '19' => 'Invalid Post Code',
        '20' => 'Invalid Parcel Weight',
        '21' => 'Invalid Parcel Size (Length + Height)',
        '22' => 'Invalid Parcel Size',
        '23' => '',
        '24' => 'Invalid Mondial Relay Parcel Number',
        '25' => '',
        '26' => '',
        '27' => '',
        '28' => 'Invalid Collection Mode',
        '29' => 'Invalid Delivery Mode',
        '30' => 'Invalid Sender Address Line 1',
        '31' => 'Invalid Sender Address Line 2',
        '32' => '',
        '33' => 'Invalid Sender Address Line 3',
        '34' => 'Invalid Sender Address Line 4',
        '35' => 'Invalid Sender City',
        '36' => 'Invalid Sender Post Code',
        '37' => 'Invalid Sender Country',
        '38' => 'Invalid Sender Phone Number',
        '39' => 'Invalid Sender E-mail',
        '40' => 'No Available Action Without City / Post Code',
        '41' => 'Invalid Delivery Mode',
        '42' => 'Invalid COD Amount', // CRT = Contre-Remboursement ? (=> Cash On Delivery)
        '43' => 'Invalid COD Currency',
        '44' => 'Invalid Parcel Value',
        '45' => 'Invalid Parcel Value Currency',
        '46' => 'Exhausted Delivery Number Range',
        '47' => 'Invalid Parcel Number',
        '48' => 'Relay Multi-Piece Delivery is not Allowed',
        '49' => 'Invalid Collection or Delivery Mode',
        '50' => 'Invalid Recipient Address Line 1',
        '51' => 'Invalid Recipient Address Line 2',
        '52' => '',
        '53' => 'Invalid Recipient Address Line 3',
        '54' => 'Invalid Recipient Address Line 4',
        '55' => 'Invalid Recipient City',
        '56' => 'Invalid Recipient Post Code',
        '57' => 'Invalid Recipient Country',
        '58' => 'Invalid Recipient Phone Number',
        '59' => 'Invalid Recipient E-mail',
        '60' => 'Invalid Text Field',
        '61' => 'Invalid Top Notification',
        '62' => 'Invalid Delivery Instructions',
        '63' => 'Invalid Insurance',
        '64' => 'Invalid Setup TimeTemps de montage invalide',
        '65' => 'Invalid Appointment Top',
        '66' => 'Invalid Recovery Top',
        '67' => '',
        '68' => '',
        '69' => '',
        '70' => 'Invalid Relay Number',
        '71' => '',
        '72' => 'Invalid Sender Language',
        '73' => 'Invalid Recipient Language',
        '74' => 'Invalid Language',
        '75' => '',
        '76' => '',
        '77' => '',
        '78' => '',
        '79' => '',
        '80' => 'Tracking Code: Registered Parcel',
        '81' => 'Tracking Code: Mondial Relay Processing Parcel',
        '82' => 'Tracking Code: Delivered Parcel',
        '83' => 'Tracking Code: Anomaly',
        '84' => '(Tracking Code Reserved)',
        '85' => '(Tracking Code Reserved',
        '86' => '(Tracking Code Reserved)',
        '87' => '(Tracking Code Reserved)',
        '88' => '(Tracking Code Reserved)',
        '89' => '(Tracking Code Reserved)',
        '90' => 'AS400 Unavailability',
        '91' => 'Invalid Shipment Number',
        '92' => '',
        '93' => 'No Result After Sorting Plan',
        '94' => 'Nonexistent ParcelColis',
        '95' => 'Disabled Company Account',
        '96' => 'Bad Base Company Type',
        '97' => 'Invalid Security Key',
        '98' => 'Unavailable Service',
        '99' => 'Service Generic Error'
    );
    // Phone international prefix
    protected $_phoneIntPrefix = array(
        'NL' => '31',
        'BE' => '32',
        'FR' => '33',
        'ES' => '34',
        'LU' => '352',
        'AD' => '376',
        'MC' => '377',
        'DE' => '49'
    );

    /**
     * Explode $str in $nb lines after removing comments
     * Windows (CR+LF)/ Unix (LF) / Apple (CR) Hack for managing CR+LF
     * 
     * @param string str
     * @param int nb
     * @return array
     */
    public function splitLines($str, $nb = 0, $default = '')
    {
        $_lines = array();
        $str = trim($str);
        if ($str)
        {
            $_hasLF = strpos("\n", $str);
            if (FALSE === $_hasLF)
            {
                str_replace("\r", "\n", $str);
            } // MacOS -> all CR replaced with LF
            str_replace("\r", '', $str); // Remove all CR                
            preg_replace('#/\*[^*]*\*+([^/][^*]*\*+)*/#', '', $str); // Remove comments
            $_lines = explode("\n", $str);
            if ($nb)
            {
                $nb -= count($_lines);
                while ($nb-- > 0)
                {
                    $_lines[] = $default;
                }
            }
        }
        return $_lines;
    }

    /**
     * Convert weight in grams 
     * 
     * @param float $weight
     * @param string unit
     * @return integer 
     */
    public function convertWeight($weight, $unit)
    {
        switch ($unit)
        {
            case 'kg':
                $weight *= 1000;
                break;
            case 'oz':
                $weight *= 28.35;
                break;
            case 'lb':
                $weight *= 453.6;
        }
        return ceil($weight);
    }

    /**
     * Remove accent, uppercase and truncate
     * cf http://www.weirdog.com/blog/php/supprimer-les-accents-des-caracteres-accentues.html
     * @TODO manage charset formats
     * 
     * @param string str
     * @param int len
     * @param string charset
     * @return string
     */
    public function removeAccent($str, $len = 32, $charset = 'utf-8')
    {
        // Truncate
        $str = substr($str, 0, $len);
        // Replace special characters with htmlentities
        $str = trim(htmlentities($str, ENT_NOQUOTES, $charset));
        // Replace htmlentities
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // e.g. '&oelig;'
        // Remove other characters
        $str = preg_replace('#&[^;]+;#', '', $str);

        return strtoupper($str);
    }

    /**
     * Convert WS error code into error message
     * 
     * @param string $stat
     * @return string 
     */
    public function convertStatToTxt($stat)
    {
        if (isset($this->_statArray[$stat]))
        {
            return $this->__($this->_statArray[$stat]);
        }
        else
        {
            return $this->__('Unknown Error') . ' - ' . $stat;
        }
    }

    /**
     *  Debug output 
     * 
     *  @param array data
     *  @param string errorCode
     * 
     *  @return void
     */
    public function logDebugData($data, $errorCode = '0')
    {
        if ((int) $errorCode)
        {
            $data['mondialrelay_ws_error_msg'] = $this->convertStatToTxt($errorCode);
        }
        Mage::log(print_r($data, true) . self::CSV_EOL, Zend_Log::DEBUG, self::LOG_FILE, true);
    }

    /**
     * Sort pick-up by nearness
     * 
     * @param array a
     * @param array b
     * @return -1 | 0 | 1 
     */
    public function sortByNearness($a, $b)
    {
        if ($a['distance'] == $b['distance'])
        {
            return 0;
        }
        return ($a['distance'] < $b['distance']) ? -1 : 1;
    }

    /**
     * Format phone number
     * 	- remove non-numerical chars and international prefix
     * 	- replace phone with altphone is phone is "0+"
     * 
     * @param string phone
     * @param string country
     * @param string altphone
     * @return string 
     */
    public function formatPhone($phone, $country, $altphone)
    {
        $phone = preg_replace("/[^0-9]/", "", $phone);
        $_prefix = isset($this->_phoneIntPrefix[$country]) ? $this->_phoneIntPrefix[$country] : '';
        if ($_prefix === substr($phone, 0, strlen($_prefix)))
        {
            $phone = 0 . substr($phone, strlen($_prefix));
        }
        $_nullphone = str_repeat('0', strlen($phone));
        if ($_nullphone === $phone)
        {
            $phone = $altphone;
        }
        return $phone;
    }

    /**
     * Check if shipment has already a Mondial Relay track
     * 
     * @param Mage_Sales_Model_Order_Shipment shipment
     * @return bool 
     */
    public function isFirstMRTrack($shipment)
    {
        if ($shipment instanceof Mage_Sales_Model_Order_Shipment)
        {
            foreach ($shipment->getAllTracks() as $_track)
            {
                if (-1 !== strpos($_track->getCarrierCode(), 'mondialrelay'))
                {
                    return false;
                }
            }
        }
        return true;
    }

}

