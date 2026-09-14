<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Setup\Patch\Schema;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Psr\Log\LoggerInterface;

class AddPortmoneOrderColumns implements SchemaPatchInterface
{
    public function __construct(
        private SchemaSetupInterface $schemaSetup,
        private LoggerInterface $logger
    ) {}

    public function apply()
    {
        $connection = $this->schemaSetup->getConnection();
        $tableName = $this->schemaSetup->getTable('sales_order');

        $columns = [
            'shop_order_number' => [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'default' => null,
                'comment' => 'Portmone Shop Order Number',
            ],
            'payee_id' => [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'default' => null,
                'comment' => 'Portmone Payee Id',
            ],
            'shop_bill_id' => [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'default' => null,
                'comment' => 'Portmone Shop Bill Id',
            ],
        ];

        try {
            foreach ($columns as $columnName => $definition) {
                if ($connection->tableColumnExists($tableName, $columnName)) {
                    continue;
                }

                $connection->addColumn($tableName, $columnName, $definition);

                $this->logger->info(sprintf(
                    'Portmone: Sales order column "%s" created successfully.',
                    $columnName
                ));
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('Portmone: Failed to update sales_order table: %s', $e->getMessage()),
                ['exception' => $e]
            );
            throw $e;
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
