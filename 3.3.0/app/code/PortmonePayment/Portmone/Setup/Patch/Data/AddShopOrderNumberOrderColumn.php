<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Setup\Patch\Data;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class AddShopOrderNumberOrderColumn implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private LoggerInterface $logger
    ) {
    }

    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('sales_order');
        $columnName = 'shop_order_number';

        $connection->startSetup();

        try {
            if ($connection->tableColumnExists($tableName, $columnName)) {
                $message = sprintf(
                    'Portmone: Sales order column "%s" already exists. Skipping creation.',
                    $columnName
                );

                $this->logger->info($message);

                return $this;
            }

            $connection->addColumn(
                $tableName,
                $columnName,
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Portmone Shop Order Number',
                ]
            );

            $this->logger->info(
                sprintf(
                    'Portmone: Sales order column "%s" created successfully.',
                    $columnName
                )
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Portmone: Failed to create sales order column "%s": %s',
                    $columnName,
                    $e->getMessage()
                ),
                ['exception' => $e]
            );

            throw $e;
        } finally {
            $connection->endSetup();
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}