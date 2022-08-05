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
 * Helper for X-Payments Cloud 
 */
class Cdev_XPaymentsCloud_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * X-Payments SDK Client
     *
     * @var \XPaymentsCloud\Client
     */
    protected $client = null;

    /**
     * Get SDK Client
     *
     * @return \XPaymentsCloud\Client
     */
    public function getClient()
    {
        if (null === $this->client) {

            $this->client = false;

            try {

                require_once(Mage::getBaseDir('lib') . DS . 'Cdev' . DS . 'XPaymentsCloud' . DS . 'Client.php');

                $this->client = new \XPaymentsCloud\Client(
                    Mage::getStoreConfig('payment/xpayments_cloud/account'),
                    Mage::getStoreConfig('payment/xpayments_cloud/api_key'),
                    Mage::getStoreConfig('payment/xpayments_cloud/secret_key')
                );

            } catch (Exception $exception) {

                $message = Mage::helper('xpayments_cloud/logger')->processException($exception);

                throw new Exception($message);
            }
        }

        return $this->client;
    }

    /**
     * Check if it's X-Payments Cloud payment method
     *
     * @param $code Payment method code to check
     *
     * @return bool
     */
    public function isCloudMethod($code)
    {
        return 'xpayments_cloud' === $code;
    }

    /**
     * Compose unique transaction ID
     *
     * @param \XPaymentsCloud\Model\Payment $payment
     *
     * @return string
     */
    public function composeTransactionId(\XPaymentsCloud\Model\Payment $payment)
    {
        return sprintf('%s-%s', $payment->xpid, $payment->lastTransaction->action);
    }

    /**
     * Get action code from transaction ID
     *
     * @param string $transactionId
     *
     * @return string
     */
    public function getActionFromTransactionId($transactionId)
    {
        return substr($transactionId, -4);
    }
}
