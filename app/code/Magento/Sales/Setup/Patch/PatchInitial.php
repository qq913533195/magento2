<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Sales\Setup\Patch;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;


/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class PatchInitial implements \Magento\Setup\Model\Patch\DataPatchInterface
{


    /**
     * @param SalesSetupFactory $salesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(SalesSetupFactory $salesSetupFactory)
    {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    /**
     * Do Upgrade
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function apply(ModuleDataSetupInterface $setup)
    {
        /** @var \Magento\Sales\Setup\SalesSetup $salesSetup */
        $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);

        /**
         * Install eav entity types to the eav/entity_type table
         */
        $salesSetup->installEntities();
        /**
         * Install order statuses from config
         */
        $data = [];
        $statuses = [
            'pending' => __('Pending'),
            'pending_payment' => __('Pending Payment'),
            'processing' => __('Processing'),
            'holded' => __('On Hold'),
            'complete' => __('Complete'),
            'closed' => __('Closed'),
            'canceled' => __('Canceled'),
            'fraud' => __('Suspected Fraud'),
            'payment_review' => __('Payment Review'),
        ];
        foreach ($statuses as $code => $info) {
            $data[] = ['status' => $code, 'label' => $info];
        }
        $setup->getConnection()->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], $data);
        /**
         * Install order states from config
         */
        $data = [];
        $states = [
            'new' => [
                'label' => __('New'),
                'statuses' => ['pending' => ['default' => '1']],
                'visible_on_front' => true,
            ],
            'pending_payment' => [
                'label' => __('Pending Payment'),
                'statuses' => ['pending_payment' => ['default' => '1']],
            ],
            'processing' => [
                'label' => __('Processing'),
                'statuses' => ['processing' => ['default' => '1'], 'fraud' => []],
                'visible_on_front' => true,
            ],
            'complete' => [
                'label' => __('Complete'),
                'statuses' => ['complete' => ['default' => '1']],
                'visible_on_front' => true,
            ],
            'closed' => [
                'label' => __('Closed'),
                'statuses' => ['closed' => ['default' => '1']],
                'visible_on_front' => true,
            ],
            'canceled' => [
                'label' => __('Canceled'),
                'statuses' => ['canceled' => ['default' => '1']],
                'visible_on_front' => true,
            ],
            'holded' => [
                'label' => __('On Hold'),
                'statuses' => ['holded' => ['default' => '1']],
                'visible_on_front' => true,
            ],
            'payment_review' => [
                'label' => __('Payment Review'),
                'statuses' => ['payment_review' => ['default' => '1'], 'fraud' => []],
                'visible_on_front' => true,
            ],
        ];
        foreach ($states as $code => $info) {
            if (isset($info['statuses'])) {
                foreach ($info['statuses'] as $status => $statusInfo) {
                    $data[] = [
                        'status' => $status,
                        'state' => $code,
                        'is_default' => is_array($statusInfo) && isset($statusInfo['default']) ? 1 : 0,
                    ];
                }
            }
        }
        $setup->getConnection()->insertArray(
            $setup->getTable('sales_order_status_state'),
            ['status', 'state', 'is_default'],
            $data
        );
        $entitiesToAlter = ['order_address'];
        $attributes = [
            'vat_id' => ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT],
            'vat_is_valid' => ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT],
            'vat_request_id' => ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT],
            'vat_request_date' => ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT],
            'vat_request_success' => ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT],
        ];
        foreach ($entitiesToAlter as $entityName) {
            foreach ($attributes as $attributeCode => $attributeParams) {
                $salesSetup->addAttribute($entityName, $attributeCode, $attributeParams);
            }
        }
        /** Update visibility for states */
        $states = ['new', 'processing', 'complete', 'closed', 'canceled', 'holded', 'payment_review'];
        foreach ($states as $state) {
            $setup->getConnection()->update(
                $setup->getTable('sales_order_status_state'),
                ['visible_on_front' => 1],
                ['state = ?' => $state]
            );
        }

    }

    /**
     * Do Revert
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function revert(ModuleDataSetupInterface $setup)
    {
    }

    /**
     * @inheritdoc
     */
    public function isDisabled()
    {
        return false;
    }


}
