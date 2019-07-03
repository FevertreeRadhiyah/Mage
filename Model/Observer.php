<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * OfflinePayments Observer
 */
namespace Empiro\Custompayment\Model;

class Observer
{
    /**
     * Sets current instructions for bank transfer account
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function beforeOrderPaymentSave(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getEvent()->getPayment();
       if ($payment->getMethod() === Custompayment::PAYMENT_METHOD_CUSTOMPAYMENT_CODE) {
            $payment->setAdditionalInformation(
                'payable_to',
                $payment->getMethodInstance()->getPayableTo()
            );
          
        }
    }
}
