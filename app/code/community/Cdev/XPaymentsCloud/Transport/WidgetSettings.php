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
 * Transport for X-Payments Cloud widget settings
 */
class Cdev_XPaymentsCloud_Transport_WidgetSettings extends Varien_Object
{
    /**
     * Constructor
     *
     * @return Varien_Object
     */
    public function __construct()
    {
        $data = array(
            'account'      => Mage::getStoreConfig('payment/xpayments_cloud/account'),
            'widgetKey'    => Mage::getStoreConfig('payment/xpayments_cloud/widget_key'),
            'devUrl'       => (string)Mage::getStoreConfig('payment/xpayments_cloud/dev_url'),
            'container'    => '#xpayments-iframe-container',
            'tokenInputId' => 'xpayments-token',
            'language'     => 'en',
            'customerId'   => '',
            'autoload'     => false,
            'autoSubmit'   => false,
            'debug'        => (bool)Mage::getStoreConfig('payment/xpayments_cloud/debug'),
            'order' => array(
                'currency' => Mage::app()->getStore()->getCurrentCurrencyCode(),
            ),
            'company' => array(
                'name'        => Mage::getStoreConfig('general/store_information/name'),
                'countryCode' => Mage::getStoreConfig('general/country/default'),
            ),
        );

        return $this->setData($data);
    }

    /**
     * Converts field names for setters and geters
     * (Do not use underscopes, actually, keep orig names)
     *
     * @param string $name
     *
     * @return string
     */
    protected function _underscore($name)
    {
        return lcfirst($name);
    }

    /**
     * Set order total
     *
     * @param float|string $value
     *
     * @return Varien_Object
     */
    public function setTotal($value)
    {
        $order = $this->getOrder();

        $order['total'] = Mage::helper('xpayments_cloud/cart')->preparePrice($value);

        return $this->setOrder($order);
    }

    /**
     * Set tokenize card flag
     *
     * @param bool $value
     *
     * @return Varien_Object
     */
    public function setTokenizeCard($value)
    {
        $order = $this->getOrder();

        $order['tokenizeCard'] = (bool)$value;

        return $this->setOrder($order);
    }
}
