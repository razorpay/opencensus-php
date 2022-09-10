<?php

namespace RZP\Models\Workflow\Service\Config;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_config';

    public function getByConfigId($configId)
    {
        $configIdColumn = $this->dbColumn(Entity::CONFIG_ID);

        return $this->newQuery()
                    ->where($configIdColumn, '=', $configId)
                    ->first();
    }

    /**
     * SELECT * FROM `workflow_config`
     * WHERE `workflow_config`.`config_type` = ?
     * AND `workflow_config`.`merchant_id` = ?
     * AND `workflow_config`.`enabled` = ?
     * ORDER BY `workflow_config`.`created_at` DESC
     *
     * @param $configType
     * @param $merchantId
     * @return Entity
     */
    public function getByConfigTypeAndMerchantId($configType, $merchantId)
    {
        $configTypeColumn           = $this->dbColumn(Entity::CONFIG_TYPE);
        $merchantIdColumn           = $this->dbColumn(Entity::MERCHANT_ID);
        $enabledColumn              = $this->dbColumn(Entity::ENABLED);
        $createdAtColumn            = $this->dbColumn(Entity::CREATED_AT);

        return $this->newQuery()
                    ->where($configTypeColumn, '=', $configType)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->where($enabledColumn, '=', 1)
                    ->orderBy($createdAtColumn, 'desc')
                    ->firstOrFailPublic();
    }
}
