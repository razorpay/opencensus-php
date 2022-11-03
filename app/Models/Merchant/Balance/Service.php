<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Diag\EventCode;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Counter;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Base\JitValidator;
use RZP\Services\PayoutService;
use RZP\Models\Feature\Constants;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Feature\Constants as FeatureConstants;

class Service extends Base\Service
{
    const FREE_PAYOUT_UPDATE_MUTEX_LOCK_TIMEOUT = 60;

    /**
     * @var PayoutService\FreePayout
     */
    protected $payoutServiceFreePayoutClient;

    public function __construct()
    {
        parent::__construct();

        $this->payoutServiceFreePayoutClient = $this->app[PayoutService\FreePayout::PAYOUT_SERVICE_FREE_PAYOUT];
    }

    public function createCapitalBalance($input)
    {
        (new Validator())->validateInput('create_capital_balance_input', $input);

        $merchant = $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);
        unset($input[Entity::MERCHANT_ID]);

        $initialBalance = $input[Entity::BALANCE] ?? 0;
        unset($input[Entity::BALANCE]);

        return $this->core()->createWithInitialBalance($merchant, $input, $this->mode, $initialBalance);
    }

    public function fetchBalanceByIdAndParams(string $id, array $input)
    {
        $this->trace->info(TraceCode::FETCH_BALANCE_REQUEST, [
                'id'        => $id,
                'input'     => $input,
            ]);

        (new JitValidator)->rules([
                              Entity::MERCHANT_ID => 'required|string|size:14'
              ])->validate($input);

        $balance = $this->repo->balance->findByIdAndMerchantId($id, $input[Entity::MERCHANT_ID]);

        return $balance->toArrayPublic();
    }

    public function fetchBalanceById(string $id)
    {
        $this->trace->info(TraceCode::FETCH_BALANCE_REQUEST, [
            'balance_id'        => $id,
        ]);

        return $this->repo->balance->findByIdAndMerchantId($id, $this->merchant->getId())->toArrayPublic();
    }

    public function fetchBalanceMultiple($input)
    {
        $this->trace->info(TraceCode::FETCH_MULTIPLE_BALANCE_REQUEST, [
            'input'     => $input,
        ]);

        (new JitValidator)->rules([
            'ids'   => 'required|array',
            'ids.*' => 'required|string|size:14',
        ])->validate($input);

        return $this->repo->balance->findMany($input['ids'])->toArrayPublic();
    }

    public function fetchBalancesForBalanceIds(array $input): object
    {

        (new JitValidator)->rules([
            'balance_ids'   => 'required|array',
            'balance_ids.*' => 'required|string|size:14',
        ])->validate($input);

        $balanceIds = $input['balance_ids'];

        $balances = $this->repo->balance->getBalancesForBalanceIds($balanceIds);

        $response = (object) [
            Payout\Entity::BALANCES => $balances
        ];
        $traceData = [
            'response'                     => $response
        ];
        $this->trace->info(TraceCode::BALANCES_FOR_BALANCE_IDS, $traceData);

        return $response;
    }

    public function fetchBalancesForMerchantIds(array $input)
    {
        $this->trace->info(TraceCode::FETCH_MULTIPLE_MERCHANT_BALANCE_REQUEST, [
            'input'     => $input,
        ]);

        (new JitValidator)->rules([
            'merchant_ids'    => 'required|array',
            'merchant_ids.*'  => 'required|string|size:14',
            'balance_type'    => 'required|in:primary,banking'
        ])->validate($input);

        $merchantIds = $input['merchant_ids'];

        $result = new Base\PublicCollection;

        $balances = $this->repo->balance->getBalancesForMerchantIds($merchantIds, $input['balance_type']);

        foreach($balances as $merchantId => $balance) {
            $result->push([
                'merchant_id'=> stringify($merchantId),
                'balance'    => $balance
            ]);
        }

        $response = (object) [
            Payout\Entity::BALANCES => $result
        ];

        return $response;

    }

    public function updateFreePayout($balanceId, $input)
    {
        $balance = $this->getBankingTypeBalanceEntity($balanceId);

        $merchantId = $balance->getMerchantId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if ($merchant->isFeatureEnabled(Constants::PAYOUT_SERVICE_ENABLED))
        {
            return $this->payoutServiceFreePayoutClient->updateFreePayoutAttributesViaMicroservice($balanceId, $input);
        }
        else
        {
            $mutexResource = sprintf('UPDATE_FREE_PAYOUT_%s_%s',
                $balanceId,
                $this->mode);

            return $this->app['api.mutex']->acquireAndRelease(
                $mutexResource,
                function () use ($balance, $input) {
                    (new Validator)->validateInput(Validator::UPDATE_FREE_PAYOUTS_ATTRIBUTES, $input);

                    $this->trace->info(
                        TraceCode::UPDATE_FREE_PAYOUTS_ATTRIBUTES_REQUEST,
                        [
                            'balance_id' => $balance->getId(),
                            'input' => $input,
                        ]
                    );

                    $updatePayoutsAttributes = $this->createCounterForBalance($balance, $input);

                    $this->trace->info(
                        TraceCode::UPDATE_FREE_PAYOUTS_ATTRIBUTES_SUCCESS,
                        [
                            'balance_id' => $balance->getId(),
                            'updated_free_payouts_data' => $updatePayoutsAttributes,
                        ]
                    );

                    return $updatePayoutsAttributes;
                },
                self::FREE_PAYOUT_UPDATE_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_FREE_PAYOUT_UPDATE_ANOTHER_OPERATION_IN_PROGRESS);
        }
    }

    public function getBankingTypeBalanceEntity($balanceId)
    {
        Base\UniqueIdEntity::verifyUniqueId($balanceId, true);

        try
        {
            $balance = $this->repo->balance->findOrFailById($balanceId);
        }

        catch (\Exception $exception)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FREE_PAYOUTS_ATTRIBUTES_INVALID_BALANCE_ID,
                Entity::BALANCE_ID,
                [
                    Entity::BALANCE_ID => $balanceId,
                ]);
        }

        $balanceType = $balance->getType();

        if ($balanceType !== Type::BANKING)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FREE_PAYOUTS_ATTRIBUTES_INCORRECT_BALANCE_TYPE,
                Entity::BALANCE_ID,
                [
                    Entity::BALANCE_ID => $balance->getId(),
                    Entity::TYPE       => $balanceType,
                ]);
        }

        return $balance;
    }

    public function migrateFreePayout($merchant, $balanceId, $action)
    {
        $balance = $this->getBankingTypeBalanceEntity($balanceId);

        switch ($action)
        {
            case EntityConstants::ENABLE:
                return $this->handleFreePayoutEnable($merchant, $balance, $action);

            case EntityConstants::DISABLE:
                return $this->handleFreePayoutDisable($merchant, $balance, $action);

            default:
                throw new ServerErrorException(
                    "Invalid action for free payout migration",
                    ErrorCode::SERVER_ERROR,
                    [
                        Entity::MERCHANT_ID     => $merchant->getId(),
                        Entity::BALANCE_ID      => $balance->getId(),
                        EntityConstants::ACTION => $action,
                    ]
                );
        }
    }

    protected function handleFreePayoutEnable($merchant, $balance, $action)
    {
        $counter = (new Payout\CounterHelper)->getCounterForBalance($balance);

        (new Payout\Core)->freePayoutMigrationFeatureChecks($action, $merchant->getId());

        $response = $this->repo->counter->transaction(
            function() use ($counter, $balance, $merchant, $action)
            {
                return $this->migrateFreePayoutToMicroservice($counter,
                                                              $balance,
                                                              $merchant,
                                                              $action);
            });

        $this->trace->info(
            TraceCode::MIGRATE_FREE_PAYOUT_PAYOUTS_SERVICE_RESPONSE,
            [
                EntityConstants::RESPONSE => $response,
                EntityConstants::ACTION   => $action,
            ]);

        return $response;
    }

    protected function handleFreePayoutDisable($merchant, $balance, $action)
    {
        (new Payout\Core)->freePayoutMigrationFeatureChecks($action, $merchant->getId());

        $input = [
            Entity::MERCHANT_ID     => $merchant->getId(),
            Entity::BALANCE_ID      => $balance->getId(),
            EntityConstants::ACTION => $action,
        ];

        return $this->payoutServiceFreePayoutClient->freePayoutMigrationForMicroservice($input);
    }

    // migrateFreePayoutToMicroservice sends counters and balance records to Payouts Service
    // and assigns payout_service_enabled feature to the merchant
    public function migrateFreePayoutToMicroservice($counter, $balance, $merchant, $action)
    {
        $counter = $this->repo->counter->lockForUpdate($counter->getId());

        $freePayoutsConsumed = $counter->getFreePayoutsConsumed();

        $freePayoutsConsumedLastResetAt = $counter->getFreePayoutsConsumedLastResetAt();

        $input = [
            EntityConstants::ACTION                             => $action,
            Entity::MERCHANT_ID                                 => $merchant->getId(),
            EntityConstants::BALANCE_TYPE                       => $balance->getAccountType(),
            EntityConstants::COUNTER_ID                         => $counter->getId(),
            Entity::BALANCE_ID                                  => $balance->getId(),
            Counter\Entity::FREE_PAYOUTS_CONSUMED               => $freePayoutsConsumed,
            Counter\Entity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => $freePayoutsConsumedLastResetAt,
            EntityConstants::COUNTER_CREATED_AT                 => $counter->getCreatedAt(),
        ];

        $freePayoutCountRecord = (new FreePayout)->getFreePayoutsCountRecord($balance);

        if ($freePayoutCountRecord !== null)
        {
            $freePayoutCount = (int) $freePayoutCountRecord[EntityConstants::VALUE];

            $input += [
                FreePayout::FREE_PAYOUTS_COUNT                => $freePayoutCount,
                EntityConstants::FREE_PAYOUT_COUNT_CREATED_AT => $freePayoutCountRecord[EntityConstants::CREATED_AT],
            ];
        }

        $freePayoutSupportedModesRecord = (new FreePayout)->getFreePayoutsSupportedModesRecord($balance);

        if ($freePayoutSupportedModesRecord !== null)
        {
            $freePayoutsSupportedModes = explode(',', $freePayoutSupportedModesRecord[EntityConstants::VALUE]);

            $input += [
                FreePayout::FREE_PAYOUTS_SUPPORTED_MODES                => $freePayoutsSupportedModes,
                EntityConstants::FREE_PAYOUT_SUPPORTED_MODES_CREATED_AT => $freePayoutSupportedModesRecord[EntityConstants::CREATED_AT],
            ];
        }

        $response = $this->payoutServiceFreePayoutClient->freePayoutMigrationForMicroservice($input);

        if (($response[EntityConstants::COUNTER_MIGRATED] === true) and
            ($response[EntityConstants::SETTINGS_MIGRATED] === true))
        {
            $this->addPayoutServiceEnabledFeature($merchant);
        }
        else
        {
            $this->trace->error(TraceCode::FREE_PAYOUT_MIGRATION_PAYOUTS_SERVICE_CALL_FAILED, [
                Entity::MERCHANT_ID => $merchant->getId(),
                EntityConstants::ACTION    => $action,
            ]);

            throw new ServerErrorException(
                "Counter and Settings migration failed",
                ErrorCode::SERVER_ERROR,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                    Entity::BALANCE_ID  => $balance->getId(),
                    EntityConstants::RESPONSE  => $response,
                ]
            );
        }

        return $response;
    }

    protected function addPayoutServiceEnabledFeature($merchant)
    {
        try
        {
            $feature = (new Feature\Core)->enablePayoutService(
                [
                    Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                    Feature\Entity::ENTITY_ID   => $merchant->getId(),
                    Feature\Entity::NAME        => Feature\Constants::PAYOUT_SERVICE_ENABLED,
                ]);

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_ENABLED_FEATURE_ASSIGNED,
                [
                    Entity::MERCHANT_ID      => $merchant->getId(),
                    EntityConstants::FEATURE => $feature,
                ]);
        }
        catch (\Exception $exception)
        {
            $this->trace->error(TraceCode::PAYOUT_SERVICE_ENABLED_FEATURE_ASSIGN_FAILED, [
                Entity::MERCHANT_ID           => $merchant->getId(),
                EntityConstants::FEATURE_NAME => Feature\Constants::PAYOUT_SERVICE_ENABLED,
            ]);

            throw $exception;
        }
    }

    public function createCounterForBalance($balance, $input)
    {
        $updatePayoutsAttributes = [];

        $freePayoutObj = new FreePayout;

        if (isset($input[FreePayout::FREE_PAYOUTS_COUNT]) === true)
        {
            $freePayoutsCount = $input[FreePayout::FREE_PAYOUTS_COUNT];

            (new Counter\Core)->fetchOrCreate($balance);

            $freePayoutObj->addNewAttribute($freePayoutsCount,
                $balance,
                FreePayout::FREE_PAYOUTS_COUNT);

            $updatePayoutsAttributes[FreePayout::FREE_PAYOUTS_COUNT] = $freePayoutsCount;
        }

        if (isset($input[FreePayout::FREE_PAYOUTS_SUPPORTED_MODES]) === true)
        {
            $freePayoutsSupportedModes = $input[FreePayout::FREE_PAYOUTS_SUPPORTED_MODES];

            if (is_array($freePayoutsSupportedModes) === false)
            {
                $freePayoutsSupportedModes = [];
            }

            $freePayoutObj->addNewAttribute($freePayoutsSupportedModes,
                $balance,
                FreePayout::FREE_PAYOUTS_SUPPORTED_MODES);

            $updatePayoutsAttributes[FreePayout::FREE_PAYOUTS_SUPPORTED_MODES] = $freePayoutsSupportedModes;
        }

        return $updatePayoutsAttributes;
    }
}
