<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Emipro\Custompayment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;

class CustompaymentConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCode = Custompayment::PAYMENT_METHOD_CUSTOMPAYMENT_CODE;

    /**
     * @var Checkmo
     */
    protected $method = 'custompayment';

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper
    ) {
        $this->escaper = $escaper;
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                'custompayment' => [       
                
                    'payableTo' => $this->getPayableTo(),
                    'businessID' => $this->getUsername(),
                    'password' => $this->getPassword(),
                    'orderTotal' => $this->getOrdertotal(),
                    'defaultSuccessPageUrl' => $this->getDefaultSuccessPageUrl(),
                   
                ],
            ],
        ];
        
        return $config;
    }

    /**
     * Get mailing address from config
     *
     * @return string
     */

       /**
     * Get mailing address from config
     *
     * @return string
     */
    protected function getMailingAddress()
    {
        return nl2br($this->escaper->escapeHtml($this->method->getMailingAddress()));
    }

    public function getDefaultSuccessPageUrl()
         {
            
            return $this->urlBuilder->getUrl('checkout/onepage/success/');
        }

    /**
     * Get payable to from config
     *
     * @return string
     */
    protected function getPayableTo()
    {
        return $this->method->getPayableTo();
    }

     /**
     * Get username to from config
     *
     * @return string
     */
    protected function getUsername()
    {
        return $this->method->getUsername();
    }

     /**
     * Get password to from config
     *
     * @return string
     */
    protected function getPassword()
    {
        return $this->method->getPassword();
    }

    protected function getOrdertotal()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 
        $grandTotal = $cart->getQuote()->getGrandTotal();

        return $grandTotal;

    }
    
}
