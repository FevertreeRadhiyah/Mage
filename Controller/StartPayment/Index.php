<?php
namespace Empiro\Custompayment\Controller\StartPayment;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;


class Index extends Action {
	/** @var Session */
	private $checkoutSession;
	/** @var Gateway */

	/**
	* @param Context $context
	* @param Session $checkoutSession
	* @param Gateway $gateway
	*/
	public function __construct(
		Context $context,
		Session $checkoutSession,
		Gateway $gateway
	) {
		parent::__construct($context);
		$this->checkoutSession = $checkoutSession;
		
	}
	/**
	* Initialize redirect to Nocks
	*/
	public function execute() {
		$order = $this->checkoutSession->getLastRealOrder();
		if (!$order) {
			return $this->_redirect('checkout/cart');
		}
		$payment = $order->getPayment();
		$data = array_filter($payment->getAdditionalInformation(), function($key) {
			return $key !== 'method_title';
		}, ARRAY_FILTER_USE_KEY);
		$data['paymentMethod'] = substr($payment->getMethodInstance()->getCode(), 6);
		$response = $this->gateway->purchase($this->checkoutSession->getLastRealOrder(), $data);
		// Update state to pending payment
		$order->setState(Order::STATE_PENDING_PAYMENT)
		      ->setStatus(Order::STATE_PENDING_PAYMENT)
		      ->save();
		return $response->redirect();
	}
}