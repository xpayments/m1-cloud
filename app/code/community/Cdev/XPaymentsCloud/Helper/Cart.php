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
 * Helper for Cart
 */
class Cdev_XPaymentsCloud_Helper_Cart extends Mage_Core_Helper_Abstract
{
    /**
     * Format price in 1234.56 format
     *
     * @param mixed $price
     *
     * @return string
     */
    public function preparePrice($price)
    {
        return number_format($price, 2, '.', '');
    }

    /**
     * Check if quantity is positive integer
     *
     * @param int $quantity Quantity
     *
     * @return bool
     */
    protected function isNaturalNumber($quantity)
    {
        return (intval($quantity) == $quantity)
            && intval($quantity) > 0;
    }

    /**
     * Prepare items 
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function prepareItems($order)
    {
        $items = array();

        foreach ($order->getAllVisibleItems() as $item) {

            $product = $item->getProduct();

            $quantity = $item->getQtyOrdered();

            if ($this->isNaturalNumber($quantity)) {

                $items[] = array(
                    'quantity' => (int)$quantity,
                    'name'     => $product->getName(),
                    'sku'      => $product->getSku(),
                    'price'    => $this->preparePrice($product->getPrice()),
                );

            } else {

                $items[] = array(
                    'quantity' => 1,
                    'name'     => sprintf('%s (x%s)', $product->getName(), round($quantity, 2)),
                    'sku'      => $product->getSku(),
                    'price'    => $this->preparePrice($item->getData('base_row_total')),
                );

            }
        }

        return $items;
    }

    /**
     * Prepare cart
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function prepareCart(Mage_Sales_Model_Order $order)
    {
        $description = 'Order #' . $order->getIncrementId();

        $billingAddress = Mage::helper('xpayments_cloud/address')->prepareBillingAddress($order);

        if ($order->getShippingAddress()) {
            $shippingAddress = Mage::helper('xpayments_cloud/address')->prepareShippingAddress($order);
        } else {
            // For downloadable products
            $shippingAddress = $billingAddress;
        }

        $cart = array(
            'login'                => $order->getCustomerEmail(),
            'billingAddress'       => $billingAddress,
            'shippingAddress'      => $shippingAddress,
            'items'                => $this->prepareItems($order),
            'currency'             => $order->getBaseCurrencyCode(),
            'shippingCost'         => $this->preparePrice($order->getShippingAmount()),
            'taxCost'              => $this->preparePrice($order->getTaxAmount()),
            'discount'             => $this->preparePrice($order->getDiscountAmount()),
            'totalCost'            => $this->preparePrice($order->getGrandTotal()),
            'description'          => $description,
            'merchantEmail'        => Mage::getStoreConfig('trans_email/ident_sales/email'),
        );

        return $cart;
    }

    /**
     * Prepare cart
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param Mage_Customer_Model_Address $address
     *
     * @return array
     */
    public function prepareCardSetupCart(Mage_Customer_Model_Customer $customer, Mage_Customer_Model_Address $address)
    {
        $cart = array(
            'login'                => $customer->getEmail(),
            'billingAddress'       => Mage::helper('xpayments_cloud/address')->prepareCustomerAddress($customer, $address),
            'currency'             => Mage::app()->getStore()->getCurrentCurrencyCode(),
            'description'          => 'Card Setup',
            'merchantEmail'        => Mage::getStoreConfig('trans_email/ident_sales/email'),
        );

        return $cart;
    }
}
