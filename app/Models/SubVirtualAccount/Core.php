<?php

namespace RZP\Models\SubVirtualAccount;

use Carbon\Carbon;
use Razorpay\Trace\Logger;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\VirtualAccount\Status;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Balance\Type as BalanceType;

/**
 * Class Core
 *
 * @package RZP\Models\SubVirtualAccount
 */
class Core extends Base\Core
{
    const SUB_VA_PAYOUT_ON_DIRECT_MASTER_BALANCE_ERROR = "Error in mapping sub VA payout to direct master balance";

    public function create(array $input): Entity
    {
        $input[Entity::SUB_ACCOUNT_TYPE] = $input[Entity::SUB_ACCOUNT_TYPE] ?? Type::DEFAULT;

        $subVirtualAccount = $this->getSubVirtualAccountWithSimilarDetails($input);

        if ($subVirtualAccount !== null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_ALREADY_EXISTS,
                null,
                [
                    Entity::ID                      => $subVirtualAccount->getId(),
                    Entity::MASTER_MERCHANT_ID      => $subVirtualAccount->getMasterMerchantId(),
                    Entity::SUB_MERCHANT_ID         => $subVirtualAccount->getSubMerchantId(),
                    Entity::MASTER_ACCOUNT_NUMBER   => $subVirtualAccount->getMasterAccountNumber(),
                    Entity::SUB_ACCOUNT_NUMBER      => $subVirtualAccount->getSubAccountNumber(),
                ]
            );
        }

        $accountType = AccountType::SHARED;

        /* For account sub-account setup, master_balance will be the direct balance of the master merchant */
        if ($input[Entity::SUB_ACCOUNT_TYPE] === Type::SUB_DIRECT_ACCOUNT)
        {
            $accountType = AccountType::DIRECT;
        }

        $masterBalance = $this->repo->balance->getBalanceByTypeAccountNumberAndAccountTypeOrFail($input[Entity::MASTER_ACCOUNT_NUMBER],
                                                                                                       BalanceType::BANKING,
                                                                                                       $accountType);

        $masterMerchant = $masterBalance->merchant;

        if (($input[Entity::SUB_ACCOUNT_TYPE] === Type::SUB_DIRECT_ACCOUNT) and
            ($masterMerchant->isFeatureEnabled(\RZP\Models\Feature\Constants::SUB_VIRTUAL_ACCOUNT) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                "Master merchant cannot have sub_virtual_account feature enabled",
                null,
                [
                    'master_merchant_id' => $masterMerchant->getId(),
                    'input' => $input
                ]
            );
        }

        $subBalance = $this->repo->balance->getBalanceByTypeAccountNumberAndAccountTypeOrFail($input[Entity::SUB_ACCOUNT_NUMBER],
                                                                                                    BalanceType::BANKING,
                                                                                                    AccountType::SHARED);

        $subMerchant = $subBalance->merchant;

        $subVirtualAccount = (new Entity)->build($input);

        $subVirtualAccount->masterMerchant()->associate($masterMerchant);

        $subVirtualAccount->subMerchant()->associate($subMerchant);

        $subVirtualAccount->balance()->associate($masterBalance);

        if ($input[Entity::SUB_ACCOUNT_TYPE] === Type::SUB_DIRECT_ACCOUNT)
        {
            return $this->onboardMasterAndSubMerchantOnAccountSubAccountFlow($subVirtualAccount);
        }

        $this->repo->saveOrFail($subVirtualAccount);

        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_CREATED,
                           [
                               Entity::ID => $subVirtualAccount->getId(),
                           ]);

        return $subVirtualAccount;
    }

    protected function getSubVirtualAccountWithSimilarDetails($input)
    {
        switch ($input[Entity::SUB_ACCOUNT_TYPE])
        {
            case Type::SUB_DIRECT_ACCOUNT:
                return $this->repo->sub_virtual_account->getSubVirtualAccountFromSubAccountNumber($input[Entity::SUB_ACCOUNT_NUMBER],
                                                                                                  false);

            case Type::DEFAULT:
                return $this->repo->sub_virtual_account->getSubVirtualAccountOfTypeDefaultWithSimilarDetails($input);

            default:
                throw new Exception\LogicException("Not a valid " . Entity::SUB_ACCOUNT_TYPE);
        }
    }

    public function fetchMultiple(array $input)
    {
        try
        {
            $this->repo->sub_virtual_account->setMerchantIdRequiredForMultipleFetch(false);

            $subVirtualAccounts = $this->repo->sub_virtual_account->fetch($input);

            /** @var Entity $subVirtualAccount */
            foreach ($subVirtualAccounts as $subVirtualAccount)
            {
                $routeName = $this->app['api.route']->getCurrentRouteName();

                $subMerchant = $subVirtualAccount->subMerchant;

                /**
                 * Balance fetch for sub_account happens in following cases
                 * 1. If sub_account_type is sub_direct_account.
                 * 2. If route is sub_virtual_account_list. This route is merchant dashboard route.
                 * This method is used by sub_virtual_account_list_admin route as well.
                 */
                if (($subVirtualAccount->getSubAccountType() === Type::SUB_DIRECT_ACCOUNT) or
                    ($routeName === 'sub_virtual_account_list'))
                {
                    $subAccountBalance = $subMerchant->sharedBankingBalance;

                    $currentAvailableBalance = $subAccountBalance->getBalanceWithLockedBalanceFromLedger();

                    $subVirtualAccount->setClosingBalance($currentAvailableBalance);
                }

                if ($subVirtualAccount->getSubAccountType() === Type::SUB_DIRECT_ACCOUNT)
                {
                    $subVirtualAccount->setName($subMerchant->getDisplayNameElseName());
                }
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::SUB_VIRTUAL_ACCOUNT_FETCH_MULTIPLE_FAILURE,
                [
                    'input' => $input,
                ]);

            $this->trace->count(Metric::SUB_VIRTUAL_ACCOUNT_FETCH_MULTIPLE_FAILURE);

            throw new Exception\ServerErrorException(
                'Sub Virtual Accounts Fetch Multiple Failure',
                ErrorCode::SERVER_ERROR,
                [
                    'message' => $ex->getMessage(),
                    'code' => $ex->getCode(),
                ]);
        }

        return $subVirtualAccounts;
    }

    public function enableOrDisable(string $id, array $input)
    {
        /** @var  $subVirtualAccount Entity */
        $subVirtualAccount = $this->repo->sub_virtual_account->findByPublicId($id);

        if ($input[Entity::ACTIVE] === $subVirtualAccount->getActive())
        {
            if ($subVirtualAccount->getActive() === true)
            {
                $errorCode = ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_ALREADY_ENABLED;
            }
            else
            {
                $errorCode = ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_ALREADY_DISABLED;
            }
            throw new Exception\BadRequestException(
                $errorCode,
                null,
                [
                    Entity::ID  => $subVirtualAccount->getId()
                ]
            );
        }

        if ($subVirtualAccount->getSubAccountType() === Type::SUB_DIRECT_ACCOUNT)
        {
            return $this->handleEnableDisableForSubDirectAccount($subVirtualAccount, $input);
        }

        $subVirtualAccount->setActive($input[Entity::ACTIVE]);

        $this->repo->saveOrFail($subVirtualAccount);

        return $subVirtualAccount;
    }

    public function transfer(array $input, Entity $subVirtualAccount)
    {
        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_TRANSFER_INIT);

        $transfer = new Transfer();

        switch ($subVirtualAccount->getSubAccountType())
        {
            case Type::SUB_DIRECT_ACCOUNT:
                return $transfer->transferToSubAccountUsingCreditTransfer($subVirtualAccount, $input);

            case Type::DEFAULT:
                return $transfer->transferToSubMerchantUsingAdjustments($subVirtualAccount, $input);
        }
    }

    public function getDirectBalanceOfMasterMerchantForSubAccountPayout($subAccountNumber, $subMerchantId)
    {
        /** @var Entity $subVirtualAccount */
        $subVirtualAccount = $this->repo->sub_virtual_account->getSubVirtualAccountFromSubAccountNumber($subAccountNumber);

        $subVirtualAccountValidator = new Validator();

        $directBalance  = null;
        $masterMerchant = null;

        try
        {
            $subVirtualAccountValidator->validateSubVirtualAccount($subVirtualAccount,
                                                                   [
                                                                       Entity::SUB_MERCHANT_ID => $subMerchantId
                                                                   ]);
            /** @var MerchantEntity $masterMerchant */
            $masterMerchant =  $this->repo->merchant->findOrFail($subVirtualAccount->getMasterMerchantId());

            $subVirtualAccountValidator->validateMasterMerchant($masterMerchant, $subVirtualAccount);

            if ($subVirtualAccount->getSubAccountType() === Type::SUB_DIRECT_ACCOUNT)
            {
                $directBalance = $subVirtualAccount->balance;
            }
            else
            {
                $directBalance = $this->repo->balance->getMerchantBalanceByTypeAndAccountType($masterMerchant->getId(),
                                                                                              BalanceType::BANKING,
                                                                                              AccountType::DIRECT);
            }

            if (empty($directBalance) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    [
                        Entity::MASTER_MERCHANT_ID => $masterMerchant->getId()
                    ],
                    "Master merchant direct balance not found"
                );
            }
        }
        catch(\Throwable $exception)
        {
            $this->trace->traceException($exception,
                                         Logger::ERROR,
                                         TraceCode::MASTER_MERCHANT_DIRECT_BALANCE_NOT_FOUND,
                                         [
                                             Entity::SUB_MERCHANT_ID    => $subMerchantId,
                                             Entity::MASTER_MERCHANT_ID => $masterMerchant
                                         ]);


            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [
                    Entity::SUB_MERCHANT_ID    => $subMerchantId,
                    Entity::MASTER_MERCHANT_ID => $masterMerchant,
                ],
                self::SUB_VA_PAYOUT_ON_DIRECT_MASTER_BALANCE_ERROR
            );
        }

        return $directBalance;
    }

    /*
     * If sub merchant's shared banking balance is Not 0, throws an error.
     *      Reason being, as once linked to master merchant, the fund movement will happen from
     *      master merchant's DA and hence cause money loss for the master merchant
     * Enable the feature flag assume_sub_account on sub merchant
     * Enable the feature flag block_fav for sub merchant
     * Enable the feature flag block_x_amazonpay for sub merchant
     * Enable the feature flag assume_master_account for master merchant
     * Mark VA(s) of sub merchant as CLOSED
     * Mark sub_virtual_account entity as active and save to DB
     */
    public function onboardMasterAndSubMerchantOnAccountSubAccountFlow(Entity $subVirtualAccount)
    {
        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_ON_BOARDING_INIT,
                           [
                               'sub_virtual_account' => $subVirtualAccount
                           ]);

        $subMerchant         = $subVirtualAccount->subMerchant;
        $masterMerchant      = $subVirtualAccount->masterMerchant;
        $sharedBalanceAmount = $subMerchant->sharedBankingBalance->getBalanceWithLockedBalanceFromLedger();

        if ($sharedBalanceAmount !== 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                TraceCode::BAD_REQUEST_SUB_MERCHANT_SHARED_BALANCE_NOT_ZERO,
                null,
                [
                    'sub_merchant_balance' => $sharedBalanceAmount
                ]
            );
        }

        $this->repo->transaction(function() use ($masterMerchant, $subMerchant, $subVirtualAccount)
        {
            if ($subMerchant->isFeatureEnabled(Feature\Constants::ASSUME_SUB_ACCOUNT) === false)
            {
                $this->enableMerchantFeature($subMerchant->getId(), Feature\Constants::ASSUME_SUB_ACCOUNT);
            }

            if ($subMerchant->isFeatureEnabled(Feature\Constants::BLOCK_FAV) === false)
            {
                $this->enableMerchantFeature($subMerchant->getId(), Feature\Constants::BLOCK_FAV);
            }

            if ($subMerchant->isFeatureEnabled(Feature\Constants::DISABLE_X_AMAZONPAY) === false)
            {
                $this->enableMerchantFeature($subMerchant->getId(), Feature\Constants::DISABLE_X_AMAZONPAY);
            }

            $featureCore = new Feature\Core();

            $capitalCardsFeature = $this->repo->feature->findByEntityTypeEntityIdAndName('merchant', $subMerchant->getId(), Feature\Constants::CAPITAL_CARDS);

            if ($capitalCardsFeature !== null)
            {
                $featureCore->delete($capitalCardsFeature);
            }

            $capitalCardsEligibleFeature = $this->repo->feature->findByEntityTypeEntityIdAndName('merchant', $subMerchant->getId(), Feature\Constants::CAPITAL_CARDS_ELIGIBLE);

            if ($capitalCardsEligibleFeature !== null)
            {
                $featureCore->delete($capitalCardsEligibleFeature);
            }

            if ($masterMerchant->isFeatureEnabled(Feature\Constants::ASSUME_MASTER_ACCOUNT) === false)
            {
                $this->enableMerchantFeature($masterMerchant->getId(), Feature\Constants::ASSUME_MASTER_ACCOUNT);
            }

            $this->markVirtualAccountsOfSubMerchantAsClosed($subMerchant);

            $subVirtualAccount->setActive(true);

            $this->repo->saveOrFail($subVirtualAccount);

            $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_ON_BOARDING_COMPLETE,
                               [
                                   Entity::SUB_MERCHANT_ID    => $subMerchant->getId(),
                                   Entity::MASTER_MERCHANT_ID => $masterMerchant->getId()
                               ]);

        });

        return $subVirtualAccount;
    }

    public function enableMerchantFeature($merchantId, $featureName, $entityType = Feature\Constants::MERCHANT)
    {
        $featureCreateInput = [
            Feature\Entity::NAME        => $featureName,
            Feature\Entity::ENTITY_ID   => $merchantId,
            Feature\Entity::ENTITY_TYPE => $entityType,
        ];

        try
        {
            (new Feature\Core())->create($featureCreateInput, true);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::SUB_VIRTUAL_ACCOUNT_FEATURE_ASSIGNMENT_ERROR,
                [
                    'feature_name' => $featureName,
                    'merchant_id' => $merchantId
                ]
            );

            throw $ex;
        }
    }

    public function markVirtualAccountsOfSubMerchantAsClosed(MerchantEntity $merchant)
    {
        $virtualAccounts = $this->repo->virtual_account->fetchActiveBankingVirtualAccountsFromMerchantId($merchant->getId());

        /** @var \RZP\Models\VirtualAccount\Entity $virtualAccount */
        foreach ($virtualAccounts as $virtualAccount)
        {
            if ($virtualAccount->getStatus() === Status::CLOSED)
            {
                continue;
            }

            $virtualAccount->setStatus(Status::CLOSED);

            $virtualAccount->setClosedAt(Carbon::now(Timezone::IST)->getTimestamp());

            $this->repo->saveOrFail($virtualAccount);

            $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_CLOSED,
                               [
                                   'id'     => $virtualAccount->getId(),
                                   'reason' => 'linking_to_master_merchant'
                               ]);
        }

        return $virtualAccounts;
    }

    public function handleEnableDisableForSubDirectAccount(Entity $subVirtualAccount, $input)
    {
        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_ENABLE_DISABLE_OF_SUB_DIRECT_INIT,
                           [
                               'sub_virtual_account' => $subVirtualAccount
                           ]);

        switch (boolval($input[Entity::ACTIVE]))
        {
            case true:
               $this->handleEnableForSubDirectAccount($subVirtualAccount);
               break;

            case false:
                $this->handleDisableForSubDirectAccount($subVirtualAccount);

                $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_DISABLE_COMPLETE,
                                   [
                                       'input'               => $input,
                                       'sub_virtual_account' => $subVirtualAccount->toArray()
                                   ]);
                break;
        }

        return $subVirtualAccount;
    }

    public function handleEnableForSubDirectAccount(Entity $subVirtualAccount)
    {
        $subMerchant = $subVirtualAccount->subMerchant;

        $subVirtualAccount = $this->repo->transaction(function() use ($subVirtualAccount, $subMerchant)
        {
            if ($subMerchant->isFeatureEnabled(Feature\Constants::BLOCK_VA_PAYOUTS) === true)
            {
                $featureCore = new Feature\Core();

                $blockVaPayoutFeature = $this->repo->feature->findByEntityTypeEntityIdAndName(Feature\Constants::MERCHANT,
                                                                                              $subMerchant->getId(),
                                                                                              Feature\Constants::BLOCK_VA_PAYOUTS);

                $featureCore->delete($blockVaPayoutFeature);
            }

            $subVirtualAccount->setActive(true);

            $this->repo->saveOrFail($subVirtualAccount);

            return $subVirtualAccount;
        });

        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_ENABLE_COMPLETE,
                           [
                               'sub_virtual_account' => $subVirtualAccount->toArray(),
                               'sub_mid_features'    => $subMerchant->getEnabledFeatures(),
                           ]);
    }

    public function handleDisableForSubDirectAccount(Entity $subVirtualAccount)
    {
        $masterMerchant                 = $subVirtualAccount->masterMerchant;
        $subMerchant                    = $subVirtualAccount->subMerchant;
        $subMerchantSharedBalanceAmount = $subMerchant->sharedBankingBalance->getBalanceWithLockedBalanceFromLedger();

        if ($subMerchantSharedBalanceAmount !== 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                TraceCode::BAD_REQUEST_SUB_MERCHANT_SHARED_BALANCE_NOT_ZERO,
                null,
                [
                    'sub_merchant_balance' => $subMerchantSharedBalanceAmount
                ]
            );
        }

        $this->repo->transaction(function() use ($subVirtualAccount, $masterMerchant, $subMerchant)
        {
            /* We will not allow VA payouts on the shared balance once we disable the sub virtual account */
            if ($subMerchant->isFeatureEnabled(Feature\Constants::BLOCK_VA_PAYOUTS) === false)
            {
                $this->enableMerchantFeature($subMerchant->getId(), Feature\Constants::BLOCK_VA_PAYOUTS);
            }

            $subVirtualAccount->setActive(false);

            $this->repo->saveOrFail($subVirtualAccount);
        });

        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_OFF_BOARDING_COMPLETE,
                           [
                               'sub_merchant_features'    => $subMerchant->getEnabledFeatures(),
                               'master_merchant_features' => $masterMerchant->getEnabledFeatures(),
                               'sub_virtual_account'      => $subVirtualAccount->toArray(),
                           ]);

    }
}
