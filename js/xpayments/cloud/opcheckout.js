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
 * Initialize widget for One page Checkout
 */
XPayments.prototype.initialize = XPayments.prototype.initialize.wrap(
    function (parentMethod, settings) {

        parentMethod(settings);

        if (
            $('co-payment-form')
            && 'undefined' != payment
        ) {

            var self = this;

            // Initialize payment checkout step
            payment.addAfterInitFunction(
                'xpayments_cloud',
                this.onSwitchPaymentMethod.bind(this)
            );

            // Switch payment method event
            Payment.prototype.switchMethod = Payment.prototype.switchMethod.wrap(
                function (parentMethod, method) {
                    parentMethod(method);
                    self.onSwitchPaymentMethod();
                }
            );

            // Switch checkout section event
            Checkout.prototype.gotoSection = Checkout.prototype.gotoSection.wrap(
                function (parentMethod, section, reloadProgressBlock) {
                    parentMethod(section, reloadProgressBlock);
                    self.onSwitchPaymentMethod();
                }
            );

            // Save payment checkout step event
            Payment.prototype.save = Payment.prototype.save.wrap(
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
    return 'undefined' != typeof payment
        && 'xpayments_cloud' == payment.currentMethod;
}

/**
 * Toggle Apple Pay button
 */
XPayments.prototype.toggleApplePayButton = function (isApple) {
    $$('.btn-checkout')[0].toggleClassName('apple-pay-button', isApple);
}

/**
 * Load widget at checkout
 */
XPayments.prototype.load = XPayments.prototype.load.wrap(
    function (parentMethod) {

        if (
            'undefined' != typeof checkout 
            && checkout.setLoadWaiting
        ) {
            // Shade checkout step
            checkout.setLoadWaiting('payment');
        }

        parentMethod();
    }
);

/**
 * Some actions after load
 */
XPayments.prototype.onLoaded = XPayments.prototype.onLoaded.wrap(
    function (parentMethod) {

        parentMethod();

        if ('undefined' != typeof checkout) {

            // Unshade checkout step
            if (checkout.setLoadWaiting) {
                checkout.setLoadWaiting(false);
            }

            // Show/hide save card option
            this.getWidget().showSaveCard('guest' != checkout.method);
        }
    }
);
