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
 * Payment Card block 
 */
class Cdev_XPaymentsCloud_Block_Customer_Payment_Card extends Mage_Core_Block_Template
{
    /**
     * Placeholder for hidden numbers in card number
     */
    const PLACEHOLDER = '&#8226;';

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setTemplate('xpayments_cloud/customer/payment/card.phtml');
    }

    /**
     * To HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if ($this->getCard()) {
            $html = parent::_toHtml(); 
        } else {
            $html = '';
        }

        return $html;
    }

    /**
     * Get field data from card
     *
     * @param string $field
     * @param string $default
     *
     * @return string
     */
    protected function getCardField($field, $default = '')
    {
        $card = $this->getCard();

        return !empty($card[$field])
            ? $card[$field]
            : $default;
    }

    /**
     * Get CSS class for card block
     *
     * @return string
     */
    protected function getCardBlockClass()
    {
        return 'product-name xpayments-card'
            . (!$this->isCardActive() && $this->getIsShowWarning() ? ' disabled' : '');
    }

    /**
     * Get CSS class for warning block
     *
     * @return string
     */
    protected function getWarningBlockClass()
    {
        return 'messages xpayments-card disabled';
    }

    /**
     * Get CSS class for card type
     *
     * @return string
     */
    protected function getCardTypeClass()
    {
        return strtolower($this->getCardField('type'));
    }

    /**
     * Get card number
     *
     * @return string
     */
    protected function getCardNumber()
    {
        $first6 = $this->getCardField('first6') ?: str_repeat(self::PLACEHOLDER, 6);

        $middleLength = ('AMEX' === $this->getCardField('type')) ? 5 : 6;
        $middle = str_repeat(self::PLACEHOLDER, $middleLength);

        return $first6 . $middle . $this->getCardField('last4');
    }

    /**
     * Get card expiration date
     *
     * @return string
     */
    protected function getCardExpiration()
    {
        return ($this->getCardField('expireMonth') && $this->getCardField('expireYear'))
            ? sprintf('(%s/%s)', $this->getCardField('expireMonth'), $this->getCardField('expireYear'))
            : '';
    }

    /**
     * Check if card is active
     *
     * @return bool
     */
    protected function isCardActive()
    {
        return $this->getCardField('isActive');
    }

    /**
     * Is in necessary to show warning that card is inactive
     *
     * @return bool
     */
    protected function isShowWarning()
    {
        return !$this->isCardActive() && $this->getIsShowWarning();
    }
}
