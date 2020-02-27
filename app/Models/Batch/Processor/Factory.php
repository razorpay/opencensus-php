<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;

class Factory
{
    const DEFAULT = 'default';

    /**
     * @var array
     *
     * For a given type, defines its namespace structure.
     * It defaults to `type`.
     * You can specify the order of keys in the array
     * which will be used to compose the processor namespace.
     * For example: For emandate, the namespace needs to be type/sub_type/gateway.
     */
    protected static $typeToNamespaceKeyMap = [

        self::DEFAULT   => [
            Batch\Entity::TYPE
        ],

        Batch\Type::EMANDATE  => [
            Batch\Entity::TYPE,
            Batch\Entity::SUB_TYPE,
            Batch\Entity::GATEWAY,
        ],

        Batch\Type::NACH  => [
            Batch\Entity::TYPE,
            Batch\Entity::SUB_TYPE,
            Batch\Entity::GATEWAY,
        ],

        Batch\Type::TERMINAL => [
            Batch\Entity::TYPE,
            Batch\Entity::SUB_TYPE
        ],

        Batch\Type::MERCHANT_ONBOARDING => [
            Batch\Entity::TYPE,
            Batch\Entity::GATEWAY,
        ]
    ];

    /**
     * Returns instance of processor based on type using self::$typeToNamespaceKeyMap
     *
     * @param Batch\Entity $batch
     * @return Base
     */
    public static function get(Batch\Entity $batch): Base
    {
        $type = $batch->getType();

        $namespaceKeys = self::$typeToNamespaceKeyMap[$type] ?? self::$typeToNamespaceKeyMap[self::DEFAULT];

        $processor = __NAMESPACE__;

        foreach ($namespaceKeys as $key)
        {
            $methodValue = $batch->getAttribute($key);

            if (empty($methodValue) === false)
            {
                $processor .= '\\' . studly_case($methodValue);
            }
        }

        $processor = class_exists($processor) ? $processor : __NAMESPACE__ . '\\' . 'Base';

        return new $processor($batch);
    }
}
