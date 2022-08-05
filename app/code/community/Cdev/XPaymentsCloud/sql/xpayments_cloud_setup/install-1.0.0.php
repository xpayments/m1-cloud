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
 * X-Payments Cloud installer 
 */
$installer = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();

$entityTypeId     = $installer->getEntityTypeId('customer');
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

// Add in a new attribute for the X-Payments Cloud customer ID
$installer->addAttribute('customer', 'xpayments_customer_id', array(
    'input'         => 'text',
    'type'          => 'varchar',
    'label'         => 'X-Payments Cloud customer ID',
    'visible'       => false,
    'required'      => false,
    'user_defined'  => true,
));

// Add the attribute into the group
$installer->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    'xpayments_customer_id',
    '999'
);

$installer->endSetup();

$installer = new Mage_Sales_Model_Resource_Setup('core_setup');
$installer->startSetup();

// Add first6 attribute to Order Payment
$installer->addAttribute('order_payment', 'cc_first6', array(
    'type'   => 'varchar',
    'length' => '6',
    'after'  => 'cc_last4',
));

// Add first6 attribute to Quote Payment
$installer->addAttribute('quote_payment', 'cc_first6', array(
    'type'   => 'varchar',
    'length' => '6',
    'after'  => 'cc_last4',
));

$installer->endSetup();
