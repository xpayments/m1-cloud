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
 * @package    Cdev_XPaymentsConnector
 * @copyright  (c) 2010-present Qualiteam software Ltd <info@x-cart.com>. All rights reserved
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Add Payment Card
 */
class Cdev_XPaymentsCloud_Block_Customer_Payment_Cards_Add extends Mage_Core_Block_Template
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setTemplate('xpayments_cloud/customer/payment/cards/add.phtml');
    }

    /**
     * Prepare layout 
     *
     * @return Mage_Downloadable_Block_Customer_Products_List
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $amount = Mage::getSingleton('customer/session')->getTokenizeCardAmount();

        if ($amount) {

            $amount = sprintf(
                '<strong>%s</strong>',
                Mage::helper('core')->currency($amount, true, false)
            );

        } else {

            $amount = $this->__('small amount');
        }

        $this->getMessagesBlock()->addWarning(
            $this->__('We will authorize %s on your credit or debit card in order to attach it to your account.
                       The amount will be released back to your card, usually within a few seconds.', $amount)
        );

        return $this;
    }

    /**
     * Get list of customer's addresses
     *
     * @return array
     */
    protected function getAddressList()
    {
        $list = array();

        if (Mage::getModel('customer/session')->isLoggedIn()) {

            $addresses = Mage::getSingleton('customer/session')
                ->getCustomer()
                ->getAddresses();

            foreach ($addresses as $address) {
                $list[$address->getId()] = $address->format('text');
            }
        }

        return $list; 
    }


    /**
     * Get hash of widget settings
     *
     * @return array
     */
    protected function getCardSetupWidgetSettings()
    {
        $settings = new Cdev_XPaymentsCloud_Transport_WidgetSettings;

        return $settings->setShowSaveCard(false)
            ->setTokenizeCard(true)
            ->setAutoSubmit(true)
            ->setForm('#card-setup-form')
            ->setTokenInputName('xpayments_token');
    }

    /**
     * Get form action URL
     *
     * @return string
     */
    public function getFormUrl()
    {
        $params = array(
            '_secure'  => true,
        );

        return Mage::getUrl('customer/payment_cards/save', $params);
    }
    
    /**
     * Get "back" URL 
     *
     * @return string
     */
    public function getBackUrl()
    {
        if ($this->getRefererUrl()) {
            $url = $this->getRefererUrl();
        } else {
            $url = $this->getUrl('customer/payment_cards/list');
        }

        return $url;
    }
}
