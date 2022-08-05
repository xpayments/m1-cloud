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
 * Payment Cards List 
 */
class Cdev_XPaymentsCloud_Block_Customer_Payment_Cards_List extends Mage_Core_Block_Template
{
    /**
     * X-Payments Customer ID
     *
     * @var string
     */
    protected $xpaymentsCustomerId = null;

    /**
     * List of payment cards 
     *
     * @var array
     */
    protected $cards = null;

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setTemplate('xpayments_cloud/customer/payment/cards/list.phtml');
    }

    /**
     * Prepare layout 
     *
     * @return Mage_Downloadable_Block_Customer_Products_List
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        // Communicate with X-Payments and obtain cards
        $this->getCards();

        return $this;
    }

    /**
     * Get X-Payments Customer ID
     *
     * @return string
     */
    protected function getXpaymentsCustomerId()
    {
        if (null === $this->xpaymentsCustomerId) {

            if (Mage::getModel('customer/session')->isLoggedIn()) {

                $this->xpaymentsCustomerId = Mage::getSingleton('customer/session')
                    ->getCustomer()
                    ->getData('xpayments_customer_id');

            } else {

                $this->xpaymentsCustomerId = '';
            }
        }

        return $this->xpaymentsCustomerId;
    }

    /**
     * Obtain cards from X-Payments
     *
     * @return array
     */
    protected function getCards()
    {
        if (null === $this->cards) {

            try {

                if ($this->getXpaymentsCustomerId()) {
            
                    $response = Mage::helper('xpayments_cloud')
                        ->getClient()
                        ->doGetCustomerCards(
                            $this->getXpaymentsCustomerId()
                        );

                    $this->cards = $response->cards;

                } else {

                    $response = Mage::helper('xpayments_cloud')
                        ->getClient()
                        ->doGetTokenizationSettings();

                    $this->cards = array();
                }

                Mage::getSingleton('customer/session')
                    ->setTokenizeCardAmount($response->tokenizeCardAmount)
                    ->setTokenizationEnabled($response->tokenizationEnabled)
                    ->setLimitReached($response->limitReached);

            } catch (Exception $exception) {

                $this->cards = array();

                Mage::helper('xpayments_cloud/logger')
                    ->processException($exception);
            }
        }

        return $this->cards;
    }

    /**
     * Check if customer has cards
     *
     * @return bool
     */
    protected function hasCards()
    {
        return (bool)count($this->getCards());
    }

    /**
     * Check if Add new payment card button should be shown
     *
     * @return bool
     */
    protected function isShowAddCardButton()
    {
        $result = false;

        if (Mage::getModel('customer/session')->isLoggedIn()) {

            $tokenizationEnabled = Mage::getSingleton('customer/session')
                ->getTokenizationEnabled();

            $limitReached = Mage::getSingleton('customer/session')
                ->getLimitReached();

            if ($tokenizationEnabled && !$limitReached) {

                $addresses = Mage::getSingleton('customer/session')
                    ->getCustomer()
                    ->getAddresses();

                $result = (bool)count($addresses);
            }
        }

        return $result;
    }

    /**
     * Check if limit reached warning should be shown
     *
     * @return bool
     */
    protected function isShowLimitReached()
    {
        return (bool)Mage::getSingleton('customer/session')
            ->getLimitReached();
    }

    /**
     * Get human-readable card string (type, number, expire date)
     *
     * @param array $card
     *
     * @return string
     */
    protected function getCardHtml($card)
    {
        return $this->getLayout()
            ->getBlock('xpayments_cloud_customer_payment_card')
            ->setCard($card)
            ->setIsShowWarning(true)
            ->toHtml();
    }

    /**
     * Get JS code for remove payment card
     *
     * @return string
     */
    protected function getCardRemoveJsCode($card)
    {
        return sprintf('return confirmCardRemove("%s", "%s");',
            $card['cardId'],
            $this->__('Are you sure you want to delete this card?')
        );
    }

    /**
     * Get form action URL
     *
     * @return string
     */
    protected function getFormUrl()
    {
        $params = array(
            '_secure'  => true,
        );

        return Mage::getUrl('customer/payment_cards/remove', $params);
    }

    /**
     * Get Add new card UEL
     *
     * @return string
     */
    protected function getAddCardUrl()
    {
        $params = array(
            '_secure'  => true,
        );

        return Mage::getUrl('customer/payment_cards/add', $params);
    }
 
    /**
     * Get "back" URL 
     *
     * @return string
     */
    protected function getBackUrl()
    {
        if ($this->getRefererUrl()) {
            $url = $this->getRefererUrl();
        } else {
            $url = $this->getUrl('customer/account/');
        }

        return $url;
    }
}
