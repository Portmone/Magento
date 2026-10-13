<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class AddPortmonePayeeIdProductAttribute implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private LoggerInterface $logger
    ) {
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create([
            'setup' => $this->moduleDataSetup,
        ]);

        $attributeCode = 'portmone_payee_id';

        if ($eavSetup->getAttributeId(Product::ENTITY, $attributeCode)) {
            $message = sprintf(
                'Portmone: Product attribute "%s" already exists. Skipping creation.',
                $attributeCode
            );

            $this->logger->info($message);

            $this->moduleDataSetup->getConnection()->endSetup();

            return $this;
        }

        $eavSetup->addAttribute(
            Product::ENTITY,
            $attributeCode,
            [
                'type' => 'varchar',
                'label' => 'Компанія-отримувач',
                'input' => 'text',
                'default' => '',
                'required' => false,
                'visible' => true,
                'visible_on_front' => false,
                'note' => 'Ідентифікатор компанії Portmone, на яку надходить оплата за цей товар. Якщо не заповнено, оплата надходить на основну компанію.',
            ]
        );

        $message = sprintf(
            'Portmone: Product attribute "%s" created successfully.',
            $attributeCode
        );

        $this->logger->info($message);

        $this->moduleDataSetup->getConnection()->endSetup();

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