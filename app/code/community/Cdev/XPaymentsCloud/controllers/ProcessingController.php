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
 * Processing controller 
 */
class Cdev_XPaymentsCloud_ProcessingController extends Mage_Core_Controller_Front_Action
{
    /**
     * Get current order
     *
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    protected function getOrder()
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId(
            Mage::getSingleton('checkout/type_onepage')->getCheckout()->getData('xp_refid')
        );

        if (!$order->getId()) {
            throw new Exception('Order was lost');
        }

        return $order;
    }

    /**
     * Callback
     *
     * @return void
     */
    public function callbackAction()
    {
        try {

            $info = Mage::helper('xpayments_cloud')
                ->getClient()
                ->parseCallback();

            if ($info->getPayment()) {

                $payment = Mage::getModel('sales/order_payment')
                    ->getCollection()
                    ->addFieldToFilter(
                        array('last_trans_id'),
                        array(array('like' => $info->getPayment()->xpid . '%'))
                    )->getFirstItem();

                $order = Mage::getModel('sales/order')->load($payment->getParentId());

                if (
                    $order->getId()
                    && $order->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW
                    && !$info->getPayment()->isFraudulent
                ) {
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Callback request');
                    $order->save();
                } 
            }

        } catch (Exception $exception) {

            Mage::helper('xpayments_cloud/logger')->processException($exception);
        }
    }

    /**
     * Continue payment after 3-D Secure
     *
     * @return void
     */
    public function continueAction()
    {
        try {

            $response = Mage::helper('xpayments_cloud')
                ->getClient()
                ->doContinue(
                    Mage::getSingleton('checkout/type_onepage')->getCheckout()->getData('xpid')
                );

            $order = $this->getOrder();
            $payment = $order->getPayment();

            $customer = Mage::getModel('customer/customer')->load(
                $this->getOrder()->getCustomerId()
            );

            $payment->getMethodInstance()->processResponse($response, $payment, $customer);
            $payment->continuePayment();

            $payment->save();
            $order->save();

            $this->_redirect('checkout/onepage/success');

        } catch (Exception $exception) {

            $message = Mage::helper('xpayments_cloud/logger')
                ->processException($exception);

            Mage::getSingleton('checkout/session')->addError($message);

            $order->cancel()
                ->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, $message)
                ->save();

            // Use native PayPal's method to restore cart
            Mage::helper('paypal/checkout')->restoreQuote();

            $this->_redirect('checkout/cart');
        }
    }	
}
