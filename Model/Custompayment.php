<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Emipro\Custompayment\Model;
/**
 * Class Custompayment
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 *
 * @api
 * @since 100.0.2
 */
class Custompayment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_CUSTOMPAYMENT_CODE = 'custompayment';
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_CUSTOMPAYMENT_CODE;
    /**
     * @var string
     */
    protected $_formBlockType = \Emipro\Custompayment\Block\Form\Custompayment::class;
    /**
     * @var string
     */
    protected $_infoBlockType = \Emipro\Custompayment\Block\Info\Custompayment::class;
    

    protected $_canAuthorize= true;
/**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_cart;

    /**
     * @var \Magento\Store\Model\StoreFactory
     */
    protected $storeFactory;

    protected $orderRepository;

     public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,

        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\Quote\Address\Rate $rate,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    )
    {
        $this->_registry = $registry;
        $this->_storeManager = $storeManager;
        $this->_customerFactory = $customerFactory;
        $this->_objectManager = $objectManager;
        $this->_productFactory = $productFactory;
        $this->_customerRepository = $customerRepository;
        $this->_cartRepositoryInterface = $cartRepository;
        $this->_shippingRate = $rate;
        $this->orderRepository = $orderRepository;
        parent::__construct($context,$registry,$storeManager,$CustomerFactory,$objectManager,$productFactory,$customerRepository,$cartRepository,$rate,$orderRepository);
    }




    /**
     * @return string
     */
    public function getPayableTo()
    {
        return $this->getConfigData('payable_to');
    }
     /**
     * @return string
     */
    public function getUsername()
    {
        return $this->getConfigData('businessID');
    }
     /**
     * @return string
     */
    public function getPassword()
    {
        return $this->getConfigData('password');
    }

     public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }
        return $this;
    }

   


     public function placeOrder($data) 
    {

        $customer = $this->_registry->registry('auth_customer');
        if (!$customer) {
            throw new Exception($this->__('User not authorized'));
        }

        $data = json_decode(json_encode($data), True);

        // $items = $this->getCartItems();
        $items = $data['products'];

        $customerAddressModel = $this->_objectManager->create('Magento\Customer\Model\Address');
        $shippingID =  $customer->getDefaultShipping();
        $address = $customerAddressModel->load($shippingID);

        $orderData = [
            'currency_id' => 'USD',
            'email' => $customer->getData('email'), //buyer email id
            'shipping_address' => [
                'firstname' => $customer->getData('firstname'),
                'lastname' => $customer->getData('lastname'),
                'street' => $address->getData('street'),
                'city' => $address->getData('city'),
                'country_id' => $address->getData('country_id'),
                'region' => $address->getData('region'),
                'postcode' => $address->getData('postcode'),
                'telephone' => $address->getData('telephone'),
                'fax' => $address->getData('fax'),
                'save_in_address_book' => 1
            ],
            'items' => $items
        ];

        return $this->createOrder($orderData, $data);
    }
    public function createOrder($orderData, $data)
    {
        $response=array();
        $response['success']=FALSE;

        if(!count($orderData['items'])) {
            $response['error_msg'] = 'Cart is Empty';
        } else {
            $this->cartManagementInterface = $this->_objectManager->get('\Magento\Quote\Api\CartManagementInterface');

            //init the store id and website id
            $store = $this->_storeManager->getStore($data['store_id']);
            $websiteId = $this->_storeManager->getStore()->getWebsiteId();

            //init the customer
            $customer = $this->_customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($orderData['email']);// load customer by email address

            //check the customer
            if (!$customer->getEntityId()) {

                //If not available then create this customer
                $customer->setWebsiteId($websiteId)
                    ->setStore($store)
                    ->setFirstname($orderData['shipping_address']['firstname'])
                    ->setLastname($orderData['shipping_address']['lastname'])
                    ->setEmail($orderData['email'])
                    ->setPassword($orderData['email']);

                $customer->save();
            }

            //init the quote
            $cart_id = $this->cartManagementInterface->createEmptyCart();
            $cart = $this->_cartRepositoryInterface->get($cart_id);

            $cart->setStore($store);

            // if you have already buyer id then you can load customer directly
            $customer = $this->_customerRepository->getById($customer->getEntityId());
            $cart->setCurrency();
            $cart->assignCustomer($customer); //Assign quote to customer

            $_productModel = $this->_productFactory->create();
            //add items in quote
            foreach ($orderData['items'] as $item) {
                $product = $_productModel->load($item['product_id']);

                try {
                    // print_r($item); die();
                    $params = array('product' => $item['product_id'], 'qty' => $item['qty']);
                    if (array_key_exists('options', $item) && $item['options']) {
                        $params['options'] = json_decode(json_encode($item['options']), True);
                    }
                    if ($product->getTypeId() == 'configurable') {
                        $params['super_attribute'] = $item['super_attribute'];
                    } elseif ($product->getTypeId() == 'bundle') {
                        $params['bundle_option'] = $item['bundle_option'];
                        $params['bundle_option_qty'] = $item['bundle_option_qty'];
                    } elseif ($product->getTypeId() == 'grouped') {
                        $params['super_group'] = $item['super_group'];
                    }

                    $objParam = new \Magento\Framework\DataObject();
                    $objParam->setData($params);
                    // print_r($objParam); die();
                    $cart->addProduct($product, $objParam);

                } catch (Exception $e) {
                    $response[$item['product_id']]= $e->getMessage();
                }
            }

            //Set Address to quote
            $cart->getBillingAddress()->addData($orderData['shipping_address']);
            $cart->getShippingAddress()->addData($orderData['shipping_address']);

            // Collect Rates and Set Shipping & Payment Method
            $this->_shippingRate
                ->setCode($data['shipping_method'])
                ->getPrice(1);

            $shippingAddress = $cart->getShippingAddress();

            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($data['shipping_method']); //shipping method
            $cart->getShippingAddress()->addShippingRate($this->_shippingRate);

            $cart->setPaymentMethod($data['payment_method']); //payment method

            $cart->setInventoryProcessed(false);

            // Set sales order payment
            $cart->getPayment()->importData(['method' => 'checkmo']);

            // Collect total and saeve
            $cart->collectTotals();

            // Submit the quote and create the order
            $cart->save();
            $cart = $this->_cartRepositoryInterface->get($cart->getId());
            try{
                $order_id = $this->cartManagementInterface->placeOrder($cart->getId());
                if(isset($order_id) && !empty($order_id)) {
                    $order = $this->orderRepository->get($order_id);
                    $this->deleteQuoteItems(); //Delete cart items
                    $response['success'] = TRUE;
                    $response['success_data']['increment_id'] = $order->getIncrementId();
                }
            } catch (Exception $e) {
                $response['error_msg']=$e->getMessage();
            }
        }
        return $response;   
    }

    public function deleteQuoteItems(){
        $checkoutSession = $this->getCheckoutSession();
        $allItems = $checkoutSession->getQuote()->getAllVisibleItems();//returns all teh items in session
        foreach ($allItems as $item) {
            $itemId = $item->getItemId();//item id of particular item
            $quoteItem=$this->getItemModel()->load($itemId);//load particular item which you want to delete by his item id
            $quoteItem->delete();//deletes the item
        }
    }
    public function getCheckoutSession(){
        $checkoutSession = $this->_objectManager->get('Magento\Checkout\Model\Session');//checkout session
        return $checkoutSession;
    }

    public function getItemModel(){
        $itemModel = $this->_objectManager->create('Magento\Quote\Model\Quote\Item');//Quote item model to load quote item
        return $itemModel;
    }

    public function getCartItems()
    {
        $cart = $this->_objectManager->get('\Magento\Checkout\Model\Cart'); 

        // retrieve quote items collection
        $itemsCollection = $cart->getQuote()->getItemsCollection();

        // get array of all items what can be display directly
        $itemsVisible = $cart->getQuote()->getAllVisibleItems();

        // retrieve quote items array
        $items = $cart->getQuote()->getAllItems();
        $itemsInCart = array();
        foreach($items as $item) {
            $itemsInCart[] = array(
                                'product_id' => $item->getProductId(),
                                'qty' => $item->getQty(),
                            );

        }
        return $itemsInCart;
    }
}

 
