<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Diag\EventCode;
use RZP\Models\Base;
use RZP\Models\Counter;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Base\JitValidator;
use RZP\Services\PayoutService;
use RZP\Models\Feature\Constants;
use RZP\Exception\BadRequestException;

class Service extends Base\Service
{
    const FREE_PAYOUT_UPDATE_MUTEX_LOCK_TIMEOUT = 60;

    /**
     * @var PayoutService\UpdateFreePayout
     */
    protected $payoutServiceUpdateFreePayoutClient;

    public function __construct()
    {
        parent::__construct();

        $this->payoutServiceUpdateFreePayoutClient = $this->app[PayoutService\UpdateFreePayout::PAYOUT_SERVICE_UPDATE_FREE_PAYOUT];
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

    public function updateFreePayout($id, $input)
    {
        Base\UniqueIdEntity::verifyUniqueId($id, true);

        try
        {
            /** @var Entity $balance */
            $balance = $this->repo->balance->findOrFailById($id);
        }

        catch (\Exception $exception)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FREE_PAYOUTS_ATTRIBUTES_INVALID_BALANCE_ID,
                Entity::BALANCE_ID,
                [
                    Entity::BALANCE_ID => $id,
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

        $merchantId = $balance->getMerchantId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if ($merchant->isFeatureEnabled(Constants::FREE_PAYOUT_LEDGER_VIA_PS))
        {
            return $this->payoutServiceUpdateFreePayoutClient->updateFreePayoutAttributesViaMicroservice($id, $input);
        }
        else
        {
            $mutexResource = sprintf('UPDATE_FREE_PAYOUT_%s_%s',
                $id,
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
