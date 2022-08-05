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
 * X-Payments Cloud config controller 
 */
class Cdev_XPaymentsCloud_Adminhtml_System_ConfigController extends Mage_Adminhtml_System_ConfigController
{
    /**
     * Save X-Payments config
     *
     * @return void
     */
    public function xpaymentsConfigAction()
    {
        $fields = array(
            'account',
            'api_key',
            'secret_key',
            'widget_key',
            'quick_access_key',
        );

        $data = $this->getRequest()->getPost();

        foreach ($fields as $field) {

            $path = 'payment/xpayments_cloud/' . $field;

            $key = lcfirst(uc_words($field, ''));

            $value = isset($data[$key]) ? $data[$key] : '';
            Mage::getModel('core/config')->saveConfig($path, $value);
        }

        Mage::getModel('core/config')->cleanCache();
    }
}
