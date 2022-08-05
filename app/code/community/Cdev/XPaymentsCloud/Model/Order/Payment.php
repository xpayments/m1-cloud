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
 * Auth/Capture/Sale logic
 */
class Cdev_XPaymentsCloud_Model_Order_Payment extends Mage_Sales_Model_Order_Payment 
{
    /**
     * Check if it was a Sale action
     *
     * @return bool
     */
    protected function isSaleAction()
    {
        $transId = $this->getOrder()->getPayment()->getLastTransId();

        return 'sale' == Mage::helper('xpayments_cloud')->getActionFromTransactionId($transId);
    }

    /**
     * Process invoice for Sale action
     *
     * @return Mage_Sales_Model_Order_Payment
     */
    protected function processSaleAction()
    {
        if ($this->isSaleAction()) {

            // Create Invoice and mark it as Paid

            $invoice = $this->getOrder()->prepareInvoice();
            $invoice->register();
            $invoice->setTransactionId($this->getOrder()->getPayment()->getLastTransId());
            $invoice->pay();

            $this->getOrder()->addRelatedObject($invoice);
        }
    }

    /**
     * Implement Auth/Capture/Sale logic
     * This method is called for all actions for authorize_capture Payment Action:
     *  - authorize only
     *  - authorize and capture (sale)
     *  - capture (after initial auth)
     *
     * @param Mage_Sales_Model_Order_Invoice
     *
     * @return Mage_Sales_Model_Order_Payment
     */
    public function capture($invoice)
    {
        $order = $this->getOrder();

        if ('xpayments_cloud' !== $order->getPayment()->getMethodInstance()->getCode()) {
            return parent::capture($invoice);
        }

        if (!$order->getState()) {

            // Order is just created
            // Execute 'pay' API action

            $this->_authorize(true, $order->getBaseTotalDue());
            $this->setAmountAuthorized($order->getTotalDue());

            // If necessary, process Sale action
            $this->processSaleAction();

        } elseif (null === $invoice) {

            // Order State is 'processing' or similar
            // Execute 'capture online' operation via Invoice creation

            $invoice = $this->_invoice();
            $this->setCreatedInvoice($invoice);

        } else {

            // Execute 'capture' API action

            parent::capture($invoice);
        }

        return $this;
    }

    /**
     * Continue payment after 3-D Secure
     *
     * @return Mage_Sales_Model_Order_Payment
     */
    public function continuePayment()
    {
        $order = $this->getOrder();
        $amount = $order->getTotalDue();

        $status = true;

        // Similar logic of "payment review" order as in authorize, capture and order methods
        // Note: is_fraud_detected flag is not used in X-Payments Cloud connector now, 
        //       so this logic is not implemented here
        if ($this->getIsTransactionPending()) {

            $message = Mage::helper('sales')->__('Authorizing amount of %s is pending approval on gateway.', $this->_formatPrice($amount));
            $state = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;

        } else {

            $message = Mage::helper('sales')->__('Authorized amount of %s.', $this->_formatPrice($amount));
            $state  = Mage_Sales_Model_Order::STATE_PROCESSING;
        }

        // Update transactions, order state and add comments
        $transaction = $this->_addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        if ($order->isNominal()) {
            $message = $this->_prependMessage(Mage::helper('sales')->__('Nominal order registered.'));
        } else {
            $message = $this->_prependMessage($message);
            $message = $this->_appendTransactionToMessage($transaction, $message);
        }

        $order->setState($state, $status, $message);

        // If necessary, process Sale action
        $this->processSaleAction();

        return $this;
    }
}
