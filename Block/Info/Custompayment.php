<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Emipro\Custompayment\Block\Info;

class Custompayment extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_payableTo;

    /**
     * @var string
     */
    protected $_mailingAddress;

    /**
     * @var string
     */
    protected $_businessID;

    /**
     * @var string
     */
    protected $_password;
 

    /**
     * @var string
     */
    protected $_template = 'Emipro_Custompayment::info/custompayment.phtml';

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getPayableTo()
    {
        if ($this->_payableTo === null) {
            $this->_convertAdditionalData();
        }
        return $this->_payableTo;
    }
      public function getUsername()
    {
        if ($this->businessID === null) {
            $this->_convertAdditionalData();
        }
        return $this->businessID;
    }
      public function getPassword()
    {
        if ($this->password === null) {
            $this->_convertAdditionalData();
        }
        return $this->password;
    }
   

  
    /**
     * Enter description here...
     *
     * @return string
     */
    public function getMailingAddress()
    {
        if ($this->_mailingAddress === null) {
            $this->_convertAdditionalData();
        }
        return $this->_mailingAddress;
    }

 

    /**
     * Enter description here...
     *
     * @return $this
     */
    protected function _convertAdditionalData()
    {
        $details = @unserialize($this->getInfo()->getAdditionalData());
        if (is_array($details)) {
            $this->_payableTo = isset($details['payable_to']) ? (string)$details['payable_to'] : '';
             $this->_mailingAddress = $this->getInfo()->getAdditionalInformation('mailing_address');
              $this->businessID = $this->getInfo()->getAdditionalInformation('businessID');
               $this->password = $this->getInfo()->getAdditionalInformation('password');
             
            
        } else {
            $this->_payableTo = 'Empty';
            
        }
        return $this;
    }

  
}
