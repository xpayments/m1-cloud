// vim: set ts=2 sw=2 sts=2 et:
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
 * Initialize widget for Sales Order page
 */
XPayments.prototype.initialize = XPayments.prototype.initialize.wrap(
    function (parentMethod, settings) {

        parentMethod(settings);

        if (
            $('edit_form')
            && 'undefined' != order
        ) {

            var self = this;

            // Switch payment method event 
            AdminOrder.prototype.setPaymentMethod = AdminOrder.prototype.setPaymentMethod.wrap(
                function (parentMethod, method) {
                    parentMethod(method);
                    self.onSwitchPaymentMethod();
                }
            );

            // Submit order event
            AdminOrder.prototype.submit = AdminOrder.prototype.submit.wrap(
                function (parentMethod) {
                    self.onSubmitPayment(parentMethod);
                }
            );
        }
    }
);

/**
 * Check if X-Payments Cloud is currently selected payment method
 */
XPayments.prototype.isCurrent = function () {
    return 'undefined' != order 
        && order.paymentMethod == 'xpayments_cloud';
}
