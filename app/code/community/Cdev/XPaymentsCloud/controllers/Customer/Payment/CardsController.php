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
 * Customer's payment cards controller 
 */
class Cdev_XPaymentsCloud_Customer_Payment_CardsController extends Mage_Core_Controller_Front_Action
{
    /**
     * Check customer authentication
     *
     * @return Mage_Core_Controller_Front_Action
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $loginUrl = Mage::helper('customer')->getLoginUrl();

        if (!Mage::getSingleton('customer/session')->authenticate($this, $loginUrl)) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }

        if ('continue' !== $this->getRequest()->getActionName()) {
            // Just in case clear X-Payments ID from session
            Mage::getSingleton('customer/session')->setData('xpid', '');
        }

        return $this;
    }

    /**
     * Get X-Payments Customer ID
     *
     * @return string
     */
    protected function getXpaymentsCustomerId()
    {
        if (Mage::getModel('customer/session')->isLoggedIn()) {

            $xpaymentsCustomerId = Mage::getSingleton('customer/session')
                ->getCustomer()
                ->getData('xpayments_customer_id');

        } else {

            $paymentsCustomerId = '';
        }

        return $xpaymentsCustomerId;
    }

    /**
     * Display list of Payment Cards action
     *
     * @return void
     */
    public function listAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $this->renderLayout();
    }

    /**
     * Remove Payment Card action
     *
     * @return void
     */
    public function removeAction()
    {
        try {

            $cardId = $this->getRequest()->get('remove_card_id');

            $response = Mage::helper('xpayments_cloud')
                ->getClient()
                ->doDeleteCustomerCard(
                    $this->getXpaymentsCustomerId(),
                    $cardId
                );

            $result = (bool)$response->result;

        } catch (Exception $exception) {

            Mage::helper('xpayments_cloud/logger')
                ->processException($exception);

            $result = false;
        }

        if ($result) {
            Mage::getSingleton('customer/session')->addSuccess('Payment card has been deleted');
        } else {
            Mage::getSingleton('customer/session')->addError('Failed to delete payment card');
        }

        $this->_redirect('customer/payment_cards/list'); 
    }

    /**
     * Add Payment Card action
     *
     * @return void
     */
    public function addAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $this->renderLayout();
    }

    /**
     * Check submitted address
     *
     * @return Mage_Customer_Model_Address $address|null
     */
    protected function checkAddress()
    {
        $addressId = (int)$this->getRequest()->get('address_id', 0);

        $addresses = Mage::getSingleton('customer/session')
            ->getCustomer()
            ->getAddresses();

        $result = null;

        foreach ($addresses as $address) {
            if ($addressId == $address->getId()) {
                $result = $address;
                break;
            }
        }

        if (null === $result) {

            Mage::throwException('Invalid billing address');
        }

        return $result;
    }

    /**
     * Save Payment Card action
     *
     * @return Mage_Core_Controller_Varien_Action 
     */
    public function saveAction()
    {
        try {

            $address = $this->checkAddress();

            $token = $this->getRequest()->get('xpayments_token');

            $cart = Mage::helper('xpayments_cloud/cart')->prepareCardSetupCart(
                Mage::getSingleton('customer/session')->getCustomer(),
                $address
            );

            $url = Mage::getUrl('customer/payment_cards/continue', array('_secure'  => true));

            $response = Mage::helper('xpayments_cloud')
                ->getClient()
                ->doTokenizeCard(
                    $token,
                    'Card Setup',
                    $this->getXpaymentsCustomerId(),
                    $cart,
                    $url,
                    ''
                );

            $redirectUrl = $response->redirectUrl ?? false;

            if (!empty($redirectUrl)) {

                Mage::getSingleton('customer/session')->setData('xpid', $response->getPayment()->xpid);

            } else {

                $this->finalizeCardSetup($response);
            }

        } catch (Exception $exception) {

            $message = Mage::helper('xpayments_cloud/logger')
                ->processException($exception);

            Mage::getSingleton('customer/session')->addError($message);
        }

        return empty($redirectUrl)
            ? $this->_redirect('customer/payment_cards/list')
            : Mage::app()->getResponse()->setRedirect($redirectUrl)->sendResponse();
    }

    /**
     * Continue save Payment Card action
     *
     * @return Mage_Core_Controller_Varien_Action 
     */
    public function continueAction()
    {
        try {

            $response = Mage::helper('xpayments_cloud')
                ->getClient()
                ->doContinue(
                    Mage::getSingleton('customer/session')->getData('xpid')
                );

            $this->finalizeCardSetup($response);

        } catch (Exception $exception) {

            $message = Mage::helper('xpayments_cloud/logger')
                ->processException($exception);

            Mage::getSingleton('customer/session')->addError($message);
        }

        return $this->_redirect('customer/payment_cards/list');
    }

    /**
     * Finalize Card Setup
     *
     * @return void
     */
    protected function finalizeCardSetup(\XPaymentsCloud\Response $response)
    {
        if (!empty($response->getPayment()->card->saved)) {

            Mage::getSingleton('customer/session')->addSuccess('Card has been successfully saved');

            if ($response->getPayment()->customerId) {
                Mage::getSingleton('customer/session')
                    ->getCustomer()
                    ->setXpaymentsCustomerId($response->getPayment()->customerId)
                    ->save();
            }

        } else {

            Mage::getSingleton('customer/session')->addError($response->getPayment()->message);
        }
    }
}
