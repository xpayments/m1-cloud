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
 * Process hide/show event on config section
 */
Fieldset.toggleCollapse = Fieldset.toggleCollapse.wrap(
    function (parentMethod, containerId, saveThroughAjax) {
        parentMethod(containerId, saveThroughAjax);
        this.xpayments.load();
    }
);

/**
 * Trigger "change" event on connected field
 */
Fieldset.triggerConnectedChange = function (value)
{
    var elm = $('payment_xpayments_cloud_connected');
    elm.setValue(value);

    var evt = document.createEvent('HTMLEvents');
    evt.initEvent('change', false, true);
    elm.dispatchEvent(evt);
}

/**
 * Save X-Payments Cloud config
 */
XPaymentsConnect.prototype.saveConfig = function (params)
{
    for (var i = 0; i < this.config.configMap.length; i++) {

        var field = this.config.configMap[i].field;
        var value = params[this.config.configMap[i].param];

        $(field).setValue(value);
    }

    Fieldset.triggerConnectedChange(1);

    new Ajax.Request(
        this.config.saveUrl,
        {
            method: 'POST',
            parameters: params
        }
    );
}

/**
 * Skip connect script and redirecting to X-Payments directly (if necessary)
 */
XPaymentsConnect.prototype.getRedirectUrl = XPaymentsConnect.prototype.getRedirectUrl.wrap(
    function (parentMethod) {

        var devUrl = this.config.devUrl;

        if (devUrl) {

            if ('/' === devUrl.substr(-1)) {
                devUrl = devUrl.substr(0, devUrl.length - 1);
            }

            var url = devUrl +
                '/connect.php?shop=' + encodeURIComponent(document.location.hostname) +
                '&account=' + encodeURIComponent(this.config.account) +
                '&quickaccess=' + encodeURIComponent(this.config.quickAccessKey);;

        } else {

            var url = parentMethod();
        }

        return url;
    }
);

/**
 * Check if X-Payments Cloud section is visible
 */
XPaymentsConnect.prototype.isVisible = function () {
    return $(this.config.sectionId)
        && $(this.config.sectionId).visible();
}

/**
 * Initialize X-Payments Cloud Connect widget
 */
XPaymentsConnect.prototype.init = XPaymentsConnect.prototype.init.wrap(
    function(parentMethod, settings) {

        Object.extend(
            this.config,
            {
                loaded: '',
                saveUrl: '',
                devUrl: '',
                configMap: '',
                sectionId: '',
            }
        );

        parentMethod(settings);

        this.on('config', this.saveConfig);

        this.on('unloaded', function () {this.config.loaded = false;});

        return this;
    }
);

/**
 * Load X-Payments Cloud Connect widget
 */
XPaymentsConnect.prototype.load = XPaymentsConnect.prototype.load.wrap(
    function (parentMethod) {
        if (
            !this.config.loaded
            && this.isVisible()
        ) {

            // Hide left panel on load
            if ($$('.config-group-header').length) {
                $$('.config-group-header')[0].hide();
            }

            parentMethod();

            this.config.loaded = true;
        }
    }
);

/**
 * Initialisation
 */
document.observe('dom:loaded', function () {

    Fieldset.xpayments = new XPaymentsConnect();

    Fieldset.xpayments.init(connectSettings).load();
});
