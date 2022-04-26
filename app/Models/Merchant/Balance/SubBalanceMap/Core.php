<?php

namespace RZP\Models\Merchant\Balance\SubBalanceMap;

use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Balance;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class Core extends Base\Core
{
    // Creates one balance entity based upon existing balance and makes an entry in sub_balance_map table.
    // The existing balance acts as parent balance and newly created balance is child balance.
    public function createSubBalanceAndMap(array $input)
    {
        $this->trace->info(TraceCode::SUB_BALANCE_CREATE_REQUEST, ['input' => $input]);

        (new Validator)->validateInput(Validator::CREATE_BALANCE_RULES, $input);

        $parentBalanceId = $input[Entity::PARENT_BALANCE_ID];

        /** @var BalanceEntity $parentBalance */
        $parentBalance = $this->repo->balance->findOrFailById($parentBalanceId);

        $subBalance = (new Balance\Core)->create($parentBalance->merchant, [
            BalanceEntity::CURRENCY     => $parentBalance->getCurrency(),
            BalanceEntity::TYPE         => $parentBalance->getType(),
            BalanceEntity::ACCOUNT_TYPE => $parentBalance->getAccountType(),
            BalanceEntity::CHANNEL      => $parentBalance->getChannel(),
        ], $this->mode);

        $this->trace->info(TraceCode::SUB_BALANCE_CREATE_RESPONSE, [
            'input'  => $input,
            'entity' => $subBalance->toArray()
        ]);

        $input[Entity::CHILD_BALANCE_ID] = $subBalance->getId();

        $input[Entity::MERCHANT_ID] = $parentBalance->getMerchantId();

        [$subBalanceMap, $updatedConfigKey] = $this->createSubBalanceMap($input);

        return ['sub_balance' => $subBalance->toArray(), 'sub_balance_map' => $subBalanceMap->toArray(), 'config_key' => $updatedConfigKey];
    }

    public function createSubBalanceMap(array $input)
    {
        $this->trace->info(TraceCode::SUB_BALANCE_MAP_CREATE_REQUEST, ['input' => $input]);

        $subBalanceMap = new Entity();

        $subBalanceMap->build($input);

        $this->repo->saveOrFail($subBalanceMap);

        $updatedConfigKey = $this->updateSubBalanceMapConfigKey($subBalanceMap);

        $this->trace->info(TraceCode::SUB_BALANCE_MAP_CREATE_RESPONSE, [
            'input'      => $input,
            'entity'     => $subBalanceMap->toArray(),
            'config_key' => $updatedConfigKey
        ]);

        return [$subBalanceMap, $updatedConfigKey];
    }

    public function updateSubBalanceMapConfigKey(Entity $subBalanceMap)
    {
        $adminService = new AdminService;

        $subBalanceMapConfig = $adminService->getConfigKey(['key' => ConfigKey::SUB_BALANCES_MAP]);

        if ((empty($subBalanceMapConfig) === true) or
            (array_key_exists($subBalanceMap->getParentBalanceId(), $subBalanceMapConfig) === false))
        {
            $subBalanceMapConfig[$subBalanceMap->getParentBalanceId()] = [];
        }

        array_push($subBalanceMapConfig[$subBalanceMap->getParentBalanceId()], $subBalanceMap->getChildBalanceId());

        $adminService->setConfigKeys([ConfigKey::SUB_BALANCES_MAP => $subBalanceMapConfig]);

        return $subBalanceMapConfig;
    }

    public function getSubBalancesForParentBalance(string $parentBalanceId)
    {
        $adminService = new AdminService;

        $fetchFromDb = false;

        $childBalances = [];

        try
        {
            $subBalanceMapConfig = $adminService->getConfigKey(['key' => ConfigKey::SUB_BALANCES_MAP]);

            if (empty($subBalanceMapConfig) === true)
            {
                $fetchFromDb = true;
            }
            else
            {
                if(array_key_exists($parentBalanceId, $subBalanceMapConfig) === true)
                {
                    $childBalances = $subBalanceMapConfig[$parentBalanceId];
                }
            }
        }
        catch (\Throwable $exception)
        {
            $fetchFromDb = true;

            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::SUB_BALANCE_FETCH_FROM_CONFIG_FAILED,
                [Entity::PARENT_BALANCE_ID => $parentBalanceId]);
        }
        finally
        {
            if ($fetchFromDb === true)
            {
                //since this sub balance thing applies only for whatsapp, we are making the connection to whatsapp db
                $subBalanceMaps = $this->repo->sub_balance_map->findByParentBalanceId($parentBalanceId);

                /** @var Entity $subBalanceMap */
                foreach ($subBalanceMaps as $subBalanceMap)
                {
                    array_push($childBalances, $subBalanceMap->getChildBalanceId());
                }
            }
        }

        return $childBalances;
    }
}
