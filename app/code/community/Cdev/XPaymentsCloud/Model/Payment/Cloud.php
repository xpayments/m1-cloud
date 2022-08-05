<?php
// vim: set ts=4 sw=4 sts=4 et:
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @author     Qualiteam Software <info@x-cart.com>
 * @category   Cdev
 * @package    Cdev_XPaymentsCloud
 * @copyright  (c) 2010-present Qualiteam software Ltd <info@x-cart.com>. All rights reserved
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * X-Payments Cloud payment method 
 */
class Cdev_XPaymentsCloud_Model_Payment_Cloud extends Mage_Payment_Model_Method_Abstract 
{
    /**
     * Paymet method code
     */
    protected $_code = 'xpayments_cloud';

    /**
     * Payment method flags
     */
    protected $_isGateway               = false;

    protected $_defaultLocale           = 'en';

    protected $_canUseCheckout          = true;
    protected $_canUseInternal          = true;
    protected $_canUseForMultishipping  = false;

    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;

    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;

    protected $_canVoid                 = true;

    protected $_canReviewPayment        = true;

    /**
     * Payment method info block
     */
    protected $_infoBlockType = 'xpayments_cloud/info_cloud';

    /**
     * Payment method form block
     */
    protected $_formBlockType = 'xpayments_cloud/form_cloud';

    /**
     * Rediret URL (if any for 3-D Secure)
     */
    protected static $redirectUrl = null;

    /**
     * Order (cache)
     *
     * @var Mage_Sales_Model_Order
     */
    protected $order = null;

    /**
     * Get order
     *
     * @return Mage_Sales_Model_Order
     */
    protected function getOrder()
    {
        if (null === $this->order) {
            $this->order = $this->getInfoInstance()->getOrder();
        }

        return $this->order;
    }

    /**
     * Check method availability
     *
     * @param Mage_Sales_Model_Quote $quote Quote
     *
     * @return boolean
     */
    public function isAvailable($quote = null)
    {
        return parent::isAvailable($quote)
            && Mage::getStoreConfig('payment/xpayments_cloud/widget_key');
    }

    /**
     * Save X-Payments token into the additional information of the payment instance object
     *
     * @param mixed $data
     *
     * @return Cdev_XPaymentsCloud_Model_Payment_Cloud
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance()
            ->setAdditionalInformation('xpayments_token', $data->getXpaymentsToken());

        return $this;
    }

    /**
     * Return the X-Payments token from the info instance
     *
     * @return null|string
     */
    public function getXpaymentsToken()
    {
        return $this->getInfoInstance()
            ->getAdditionalInformation('xpayments_token');
    }

    /**
     * Validate payment method information object
     *
     * @return Cdev_XPaymentsCloud_Model_Payment_Cloud
     * @throws Mage_Core_Exception
     */
    public function validate()
    {
        // Run the built in Magento validation
        parent::validate();

        if (!$this->getXpaymentsToken()) {
            $this->throwException('An error occurred, please try again.');
        }
        return $this;
    }

    /**
     * Get reference ID
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    protected function getRefId()
    {
        $refId = $this->getOrder()->getIncrementId();

        // Check we always have an ID
        if (!$refId) {
            $this->throwException('Order was lost.');
        }

        return $refId;
    }

    /**
     * Get xpid from parent transaction
     *
     * @param Varien_Object $payment
     *
     * @return string
     */
    protected function getParentXpid(Varien_Object $payment)
    {
        return substr($payment->getParentTransactionId(), 0, 32);
    }

    /**
     * Get xpid from last transaction
     *
     * @param Varien_Object $payment
     *
     * @return string
     */
    protected function getLastXpid(Varien_Object $payment)
    {
        return substr($payment->getLastTransId(), 0, 32);
    }

    /**
     * Compose URL
     *
     * @param string $action
     *
     * @return string
     */
    protected function composeUrl($action)
    {
        $params = array(
            '_secure'  => true,
            '_nosid'   => true,
        );

        return Mage::getUrl('xpayments_cloud/processing/' . $action, $params);
    }

    /**
     * Actually, get URL to redirect to 3-D Secure after the order was placed
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return static::$redirectUrl;
    }

    /**
     * Authorize the requested amount
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return Cdev_XPaymentsCloud_Model_Payment_Cloud
     * @throws Mage_Core_Exception
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        try {

            $customer = Mage::getModel('customer/customer')->load(
                $this->getOrder()->getCustomerId()
            );

            $cart = Mage::helper('xpayments_cloud/cart')->prepareCart($this->getOrder());

            $response = Mage::helper('xpayments_cloud')
                ->getClient()
                ->doPay(
                    $this->getXpaymentsToken(),
                    $this->getRefId(),
                    $customer->getData('xpayments_customer_id'),
                    $cart,
                    $this->composeUrl('continue'),
                    $this->composeUrl('callback')
                );

            if ($response->redirectUrl) {

                if (Mage::app()->getStore()->isAdmin()) {
                    throw new Exception('This payment requires 3-D Secure verification.');
                }

                static::$redirectUrl = $response->redirectUrl;

                Mage::getSingleton('checkout/type_onepage')->getCheckout()
                    ->setData('xpid', $response->getPayment()->xpid)
                    ->setData('xp_refid', $this->getRefId());

            } else {

                $this->processResponse($response, $payment, $customer);
            }

        } catch (Exception $exception) {

            $message = Mage::helper('xpayments_cloud/logger')
                ->processException($exception); 

            $this->throwException($message);
        }

        return $this;
    }

    /**
     * Capture specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Cdev_XPaymentsCloud_Model_Payment_Cloud
     * @throws Mage_Core_Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        try {

            $response = Mage::helper('xpayments_cloud')
                ->getClient()
                ->doCapture(
                    $this->getParentXpid($payment),
                    $amount
                );

            $this->processResponse($response, $payment);

        } catch (Exception $exception) {

            $message = Mage::helper('xpayments_cloud/logger')
                ->processException($exception);

            $this->throwException($message);
        }

        return $this;
    }

    /**
     * Void payment
     *
     * @param Varien_Object $payment
     *
     * @return Cdev_XPaymentsCloud_Model_Payment_Cloud
     * @throws Mage_Core_Exception
     */
    public function void(Varien_Object $payment)
    {
        try {

            $response = Mage::helper('xpayments_cloud')
                ->getClient()
                ->doVoid(
                    $this->getParentXpid($payment)
                );

            $this->processResponse($response, $payment);

        } catch (Exception $exception) {

            $message = Mage::helper('xpayments_cloud/logger')
                ->processException($exception);

            $this->throwException($message);
        }

        return $this;
    }

    /**
     * Cancel payment
     *
     * @param Varien_Object $payment
     *
     * @return Cdev_XPaymentsCloud_Model_Payment_Cloud
     * @throws Mage_Core_Exception
     */
    public function cancel(Varien_Object $payment)
    {
        return $this->void($payment);
    }

    /**
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Cdev_XPaymentsCloud_Model_Payment_Cloud
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        try {

            $response = Mage::helper('xpayments_cloud')
                ->getClient()
                ->doRefund(
                    $this->getParentXpid($payment),
                    $amount
                );

            $this->processResponse($response, $payment);

        } catch (Exception $exception) {

            $message = Mage::helper('xpayments_cloud/logger')
                ->processException($exception);

            $this->throwException($message);
        }

        return $this;
    }

    /**
     * Accept payment
     *
     * @param Mage_Payment_Model_Info $payment
     *
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        try {

            $xpid = $this->getLastXpid($payment);

            $response = Mage::helper('xpayments_cloud')
                ->getClient()
                ->doAccept($xpid);

            $result = (bool)$response->result;

        } catch (Exception $exception) {

            $message = Mage::helper('xpayments_cloud/logger')
                ->processException($exception);

            $this->throwException($message);
        }

        return $result;
    }

    /**
     * Decline payment
     *
     * @param Mage_Payment_Model_Info $payment
     *
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        try {

            $xpid = $this->getLastXpid($payment);

            $response = Mage::helper('xpayments_cloud')
                ->getClient()
                ->doDecline($xpid);

            $result = (bool)$response->result;

        } catch (Exception $exception) {

            $message = Mage::helper('xpayments_cloud/logger')
                ->processException($exception);

            $this->throwException($message);
        }

        return $result;
    }

    /**
     * Process X-Payments Cloud payment response
     *
     * @param \XPaymentsCloud\Response $response
     * @param Varien_Object $payment
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return Cdev_XPaymentsCloud_Model_Payment_Cloud
     * @throws Exception
     */
    public function processResponse(\XPaymentsCloud\Response $response, Varien_Object $payment, Mage_Customer_Model_Customer $customer = null)
    {
        if (!$response->isLastTransactionSuccessful()) {
            throw new Exception($response->message);
        }

        $info = $response->getPayment();

        // Compose transaction ID preventing duplicates
        $transactionId = Mage::helper('xpayments_cloud')->composeTransactionId($info);

        // Set some basic information about the payment
        $payment->setStatus(self::STATUS_APPROVED)
            ->setCcTransId($info->lastTransaction->txnId)
            ->setLastTransId($info->lastTransaction->txnId)
            ->setTransactionId($transactionId)
            ->setIsTransactionClosed(false)
            ->setAmount($info->amount)
            ->setShouldCloseParentTransaction(false);

        // Set information about the card
        $payment->setCcLast4($info->card->last4)
            ->setCcFirst6($info->card->first6)
            ->setCcType($info->card->type)
            ->setCcExpMonth($info->card->expireMonth)
            ->setCcExpYear($info->card->expireYear)
            ->setCcOwner($info->card->cardholderName);

        // Set transaction details
        if (!empty($info->details)) {

            $details = array_filter(
                get_object_vars($info->details)
            );

            $payment->setTransactionAdditionalInfo(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                $details
            );
        }

        // Mark or unmarke as fraud
        $payment->setIsTransactionPending(
            (bool)$info->isFraudulent
        );

        // Set X-Payments Cloud customerId
        if ($customer && $customer->getEmail()) {

            $customer->setData('xpayments_customer_id', $info->customerId)->save();
        }

        return $this;
    }

    /**
     * Throw exception
     *
     * @param string $message
     *
     * @return void
     * @throws Mage_Core_Exception
     */
    protected function throwException($message)
    {
        if (!Mage::app()->getStore()->isAdmin()) {
            Mage::getSingleton('checkout/type_onepage')->getCheckout()->setGotoSection('payment');
        }

        Mage::throwException($message);
    }
}
