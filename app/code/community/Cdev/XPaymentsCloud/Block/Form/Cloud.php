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
 * X-Payments Cloud iframe
 */
class Cdev_XPaymentsCloud_Block_Form_Cloud extends Mage_Payment_Block_Form
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('xpayments_cloud/form/cloud.phtml');
    }

    /**
     * Get X-Payments Customer ID
     *
     * @return string
     */
    protected function getXpaymentsCustomerId()
    {
        if (Mage::app()->getStore()->isAdmin()) {

            $customerId = Mage::getSingleton('adminhtml/session_quote')
                ->getQuote()
                ->getCustomerId();

            $customer = Mage::getModel('customer/customer')->load($customerId);

            $xpaymentsCustomerId = $customer->getData('xpayments_customer_id');

         } elseif (Mage::getModel('customer/session')->isLoggedIn()) {

            $xpaymentsCustomerId = Mage::getSingleton('customer/session')
                ->getCustomer()
                ->getData('xpayments_customer_id');

        } else {

            $xpaymentsCustomerId = '';
        }

        return $xpaymentsCustomerId;
    }

    /**
     * Widget settings for checkout
     *
     * @return array
     */
    protected function getCheckoutWidgetSettings()
    {
        $total = Mage::getModel('checkout/session')
            ->getQuote()
            ->getGrandTotal();

        $showSaveCard = Mage::getModel('customer/session')->isLoggedIn();

        $settings = new Cdev_XPaymentsCloud_Transport_WidgetSettings;

        return $settings->setShowSaveCard($showSaveCard)
            ->setTokenizeCard(false)
            ->setAutoSubmit(false)
            ->setCustomerId($this->getXpaymentsCustomerId())
            ->setForm('#co-payment-form')
            ->setTokenInputName('payment[xpayments_token]')
            ->setTotal($total);
    }

    /**
     * Widget settings for order create in Adminhtml
     *
     * @return array
     */
    protected function getAdminhtmlWidgetSettings()
    {
        $total = Mage::getSingleton('adminhtml/session_quote')
            ->getQuote()
            ->getGrandTotal();

        $settings = new Cdev_XPaymentsCloud_Transport_WidgetSettings;

        return $settings->setShowSaveCard(true)
            ->setTokenizeCard(false)
            ->setAutoSubmit(false)
            ->setCustomerId($this->getXpaymentsCustomerId())
            ->setForm('#edit_form')
            ->setTokenInputName('payment[xpayments_token]')
            ->setTotal($total);
    }
}
