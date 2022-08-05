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
 * X-Payments Widgent implementation for customer profile
 */

/**
 * Remove saved card confirmation 
 */
function confirmCardRemove(cardId, message)
{
    var form = $('xpayments-card-list-form');
    if (form && confirm(message)) {
        $('remove_card_id').value = cardId;
        form.submit();
    }
}

/**
 * Initialisation
 */
document.observe('dom:loaded', function () {

    if ($('card-setup-form')) {

        XPayments.prototype.isCurrent = function () { 
            return true;
        }

        XPayments.prototype.onSubmitPayment = XPayments.prototype.onSubmitPayment.wrap(
            function (parentMethod, event) {
                $('widget-submit').disable();
                parentMethod(event);
            }
        );

        XPayments.prototype.onFail = XPayments.prototype.onFail.wrap(
            function (parentMethod, params) {
                $('widget-submit').enable();
                parentMethod(params);
            }
        );

        $('widget-submit').on(
            'click',
            xpayments.onSubmitPayment.bind(xpayments) 
        );
    }
});