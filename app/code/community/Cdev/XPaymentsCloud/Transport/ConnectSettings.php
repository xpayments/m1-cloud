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
 * Transport for X-Payments Cloud Connect widget settings
 */
class Cdev_XPaymentsCloud_Transport_ConnectSettings extends Varien_Object
{
    /**
     * Constructor
     *
     * @return Varien_Object
     */
    public function __construct()
    {
        $referrerUrl = preg_replace('/\/key\/\w+/', '', Mage::helper('core/url')->getCurrentUrl());

        $data = array(
            'account'        => Mage::getStoreConfig('payment/xpayments_cloud/account'),
            'quickAccessKey' => Mage::getStoreConfig('payment/xpayments_cloud/quick_access_key'),
            'devUrl'         => (string)Mage::getStoreConfig('payment/xpayments_cloud/dev_url'),
            'referrerUrl'    => $referrerUrl,
            'topElement'     => '',
            'container'      => '#xpayments-iframe-container',
            'sectionId'      => 'payment_xpayments_cloud',
            'loaded'         => false,
            'saveUrl'        => $this->getSaveUrl(),
            'configMap'      => $this->getConfigMap(),
            'debug'          => (bool)Mage::getStoreConfig('payment/xpayments_cloud/debug'),
        );

        return $this->setData($data);
    }

    /**
     * Get X-Payments Config save URL
     *
     * @return string
     */
    protected function getSaveUrl()
    {
        $params = array(
            'section' => 'payment',
        );

        if (Mage::app()->getRequest()->getParam('website')) {
            $params['website'] = Mage::app()->getRequest()->getParam('website');
        }

        if (Mage::app()->getRequest()->getParam('store')) {
            $params['store'] = Mage::app()->getRequest()->getParam('store');
        }

        return Mage::helper('adminhtml')->getUrl('adminhtml/system_config/xpaymentsConfig', $params);
    }

    /**
     * Get map of the configuration fields: {
     *   field: payment_xpayments_cloud_some_option 
     *   param: someOption
     * }
     *
     * @return array
     */
    protected function getConfigMap()
    {
        $fields = array(
            'account',
            'api_key',
            'secret_key',
            'widget_key',
            'quick_access_key',
        );

        $map = array();

        foreach ($fields as $field) {

            $map[] = array(
                'field' => 'payment_xpayments_cloud_' . $field,
                'param' => lcfirst(uc_words($field, '')),
            );
        }

        return $map;
    }
}
