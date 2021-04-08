<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Services\FTS;
use RZP\Models\Admin;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Models\FundAccount;
use RZP\Models\Pricing\Fee;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Exception\BadRequestException;
use RZP\Services\FTS\Constants as FtsConstants;
use RZP\Services\FTS\Transfer\RequestFields as FtsRequestFields;

class Core extends Base\Core
{
    protected $fundAccountCore;
    private $mutex;

    const VALIDATION_UPDATE_MUTEX = "FUND_ACCOUNT_VALIDATION_BEING_UPDATED";

    const VALIDATION_UPDATE_MUTEX_LOCK_TIMEOUT = 20;

    const VALIDATION_UPDATE_MUTEX_RETRY_COUNT = 1;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->fundAccountCore = new FundAccount\Core();
    }

    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     * @return Entity
     * @throws \Throwable
     */
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_REQUEST, [
            'input' => $input
        ]);

        try
        {
            $fundAccountValidation = $this->createValidationEntity($input, $merchant);

            $processor = Processor\Factory::get($fundAccountValidation);

            $processor->preProcessValidation();

        }
        catch (\Throwable $e)
        {
            (new Metric)->pushExceptionMetrics($e, Metric::FUND_ACCOUNT_VALIDATION_FAILED);

            throw $e;
        }

        (new Metric)->pushCreatedMetrics($fundAccountValidation->getFundAccountType());

        return $fundAccountValidation;
    }

    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     * @return Entity
     */
    protected function buildValidationEntity(array $input, Merchant\Entity $merchant): Entity
    {
        $validation = new Entity;

        $validation->build($input);

        $validation->merchant()->associate($merchant);

        $this->associateBalance($validation, $input);

        return $validation;
    }

    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     * @return FundAccount\Entity
     * @throws Exception\BaseException
     */
    protected function createOrGetFundAccount(array $input, Merchant\Entity $merchant): FundAccount\Entity
    {
        $this->fundAccountCore->modifyRequestForBackwardCompatibility($input['fund_account']);

        try
        {
            if (empty($input['fund_account']['id']) === false)
            {
                return $this->fundAccountCore->findByPublicIdAndMerchant($input['fund_account']['id'], $merchant);
            }

            return $this->fundAccountCore->create($input['fund_account'], $merchant);
        }
        catch (Exception\BaseException $e)
        {
            $e->appendFieldToError('fund_account');

            throw $e;
        }
    }

    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    protected function createValidationEntity(array $input, Merchant\Entity $merchant): Entity
    {
        $validation = $this->buildValidationEntity($input, $merchant);

        $validation = $this->repo->transaction(function () use ($input, $validation, $merchant)
        {
            $fundAccount = $this->createOrGetFundAccount($input, $merchant);

            $validation->associateFundAccount($fundAccount);

            $this->runInputValidations($validation, $input);

            $processor = Processor\Factory::get($validation);

            $processor->setDefaultValuesForValidation();

            $validation->setAttempts(1);

            // We are saving here because when when creating transaction,
            // it is assumed that source already exist.
            $this->repo->saveOrFail($validation);

            $txn = $this->createTransactionIfApplicable($validation, $merchant, $processor);

            if ($txn !== null)
            {
                $validation->setFees($txn->getFee());
                $validation->setTax($txn->getTax());

                $this->repo->saveOrFail($validation);
            }

            return $validation;
        });

        return $validation;
    }

    /**
     * @param Entity $validation
     * @param array  $input
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function runInputValidations(Entity $validation, array $input)
    {
        $type = $validation->fundAccount->account->getEntityName();

        Processor\Factory::validate($type);

        // Extra checks are not required for non banking, create rules are sufficient
        if ($validation->balance->isTypeBanking() === false)
        {
            return;
        }

        $accountType = $validation->fundAccount->getAccountType();

        $validationRuleName = Product::BANKING . '_' . $accountType;

        (new Validator($validation))->validateInput($validationRuleName, $input);
    }

    /**
     * @param Entity          $validation
     * @param Merchant\Entity $merchant
     * @param Processor\Base  $processor
     *
     * @return \RZP\Models\Transaction\Entity|null
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    protected function createTransactionIfApplicable(Entity $validation,
                                                     Merchant\Entity $merchant,
                                                     Processor\Base $processor)
    {
        if ($validation->getFundAccountType() === FundAccount\Type::VPA)
        {
            return null;
        }

        $this->verifyFeesLessThanApplicableBalance($validation, $merchant);

        // Transaction might fail because of concurrent request verifying and changing balance at the same time.
        try
        {
            $txn = $processor->createTransaction();
        }
        catch (Exception\LogicException $e)
        {
            if ($e->getMessage() === 'Something very wrong is happening! Balance is going negative')
            {
                $this->trace->info(TraceCode::UPDATE_STATUS_AFTER_FTA_INITIATED, $e->getData());

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INSUFFICIENT_BALANCE,
                    null,
                    null);
            }

            throw $e;
        }

        return $txn;
    }

    /**
     * @param Entity $validation
     * @param Attempt\Entity $fta
     * @throws \Throwable
     */
    public function updateStatusAfterFtaInitiated(Entity $validation, Attempt\Entity $fta)
    {
        $this->trace->info(TraceCode::UPDATE_STATUS_AFTER_FTA_INITIATED, [
            'validation_id' => $validation->getId(),
            'fta_id'        => $fta->getId(),
        ]);

        try
        {
            $this->mutex->acquireAndRelease(
                self::VALIDATION_UPDATE_MUTEX . $validation->getId(),
                function () use ($validation, $fta)
                {
                    $processor = Processor\Factory::get($validation);

                    $processor->updateStatusAfterFtaInitiated($fta);

                    return;
                },
                self::VALIDATION_UPDATE_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::FUND_ACCOUNT_VALIDATION_FTA_HOOK_FAILED, [
                'fav_id' => $validation->getId()
            ]);

            throw $e;
        }
    }

    /**
     * Updates validation entity status before FTA recon
     *
     * @param Entity $validation
     * @param array $input
     * @throws \Throwable
     */
    public function updateWithDetailsBeforeFtaRecon(Entity $validation, array $input)
    {
        $this->trace->info(TraceCode::UPDATE_WITH_DETAILS_BEFORE_FTA_RECON, [
            'input' => $input,
            'validation_status' => $validation->getStatus(),
        ]);

        try
        {
            $this->mutex->acquireAndRelease(
                self::VALIDATION_UPDATE_MUTEX . $validation->getId(),
                function () use ($validation, $input)
                {
                    $processor = Processor\Factory::get($validation);

                    $processor->updateWithDetailsBeforeFtaRecon($input);

                    return;
                },
                self::VALIDATION_UPDATE_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
                self::VALIDATION_UPDATE_MUTEX_RETRY_COUNT);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::FUND_ACCOUNT_VALIDATION_FTA_HOOK_FAILED, [
                'fav_id' => $validation->getId()
            ]);

            throw $e;
        }
    }

    /**
     * Updates validation entity status after FTA recon
     *
     * @param Entity $validation
     * @param array $input
     * @throws \Throwable
     */
    public function updateStatusAfterFtaRecon(Entity $validation, array $input)
    {
        $this->trace->info(TraceCode::UPDATE_STATUS_AFTER_FTA_RECON, [
            'input' => $input,
            'validation_status' => $validation->getStatus(),
        ]);

        try
        {
            $this->mutex->acquireAndRelease(
                self::VALIDATION_UPDATE_MUTEX . $validation->getId(),
                function () use ($validation, $input)
                {
                    $processor = Processor\Factory::get($validation);

                    $processor->updateStatusAfterFtaRecon($input);

                    return;
                },
                self::VALIDATION_UPDATE_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
                self::VALIDATION_UPDATE_MUTEX_RETRY_COUNT);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::FUND_ACCOUNT_VALIDATION_FTA_HOOK_FAILED, [
                'fav_id' => $validation->getId()
            ]);

            throw $e;
        }
    }

    /**
     * @param Entity          $validation
     * @param Merchant\Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    private function verifyFeesLessThanApplicableBalance(Entity $validation, Merchant\Entity $merchant)
    {
        if ($merchant->getFeeModel() === Merchant\FeeModel::POSTPAID)
        {
            return;
        }

        list($fee, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($validation);

        if ($validation->hasBalance() === true)
        {
            $balance = $validation->balance;
        }
        else
        {
            $balance = $this->merchant->primaryBalance;
        }

        if ($balance->getFeeCredits() >= $fee)
        {
            return;
        }

        if ($balance->getBalance() >= $fee)
        {
            return;
        }

        throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INSUFFICIENT_BALANCE,
                null,
                [
                    'fees'      => $fee,
                    'fee_credits'    =>  $balance->getFeeCredits(),
                    'balance'    =>  $balance->getBalance(),
                ]);
    }

    public function updateEntityWithFtsTransferId(Entity $entity, $ftsTransferId)
    {
        if (empty($ftsTransferId) === false)
        {
            $entity->setFTSTransferId($ftsTransferId);

            $this->repo->saveOrFail($entity);
        }
    }

    protected function associateBalance(Entity $fundAccValidation, array $input)
    {
        $balanceId = $input[Entity::BALANCE_ID] ?? null;

        if (empty($balanceId) === true)
        {
            $balance = $fundAccValidation->merchant->primaryBalance;
        }
        else
        {
            $balance = $this->repo->balance->findByIdAndMerchant($balanceId, $fundAccValidation->merchant);
        }

        $this->blockFAVIfApplicable($balance);

        $fundAccValidation->balance()->associate($balance);
    }

    protected function blockFAVIfApplicable($balance)
    {
        if (($balance->isTypeBanking() === true) and
            ($balance->getChannel() === Channel::YESBANK))
        {
            $config = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::BLOCK_YESBANK_RX_FAV]) ?? false;

            if (boolval($config) === false)
            {
                return;
            }

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FAV_NOT_ALLOWED_CURRENTLY,
                null,
                [
                    'channel'       => 'yesbank',
                    'merchant_id'   => $balance->getMerchantId(),
                    'balance_id'    => $balance->getId(),
                    'config'        => $config,
                ]);
        }
    }

    public function getFavByMerchantIdAndFavId(string $favId, string $merchantId)
    {
        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        $entity = $this->repo->fund_account_validation
            ->findByPublicIdAndMerchant($favId, $merchant);

        return $entity->toArrayPublic();
    }

    public function bulkPatchFavAsFailed(array $input): array
    {
        (new Validator())->validateInput('bulk_patch_fav', $input);

        $favIds = $input[Entity::FUND_ACCOUNT_VALIDATION_IDS];

        foreach ($favIds as $i => $favId)
        {
            $favIds[$i] =  Entity::stripSignWithoutValidation($favId);
        }

        $validations = $this->repo->fund_account_validation->getFundAccountValidationsToFail($favIds);

        $recordsReceived = count($favIds);

        if ($recordsReceived !== $validations->count())
        {
            $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_BULK_PATCH_FAILED, [
                'input' => $input
            ]);

            return [
                'processed'         => 0,
                'failed'            => $recordsReceived,
            ];
        }

        $processed = 0;
        foreach ($validations as $validation)
        {
            try
            {
                $this->mutex->acquireAndRelease(
                    self::VALIDATION_UPDATE_MUTEX . $validation->getId(),
                    function () use ($validation)
                    {
                        $processor = Processor\Factory::get($validation);

                        $processor->markValidationAsFailed();

                        return;
                    });
                $processed++;
            }
            catch (\Throwable $e)
            {
                $this->trace->error(TraceCode::FUND_ACCOUNT_VALIDATION_STATUS_CHANGE_FAILED, [
                    'fav_id' => $validation->getId()
                ]);
            }
        }

        return [
            'processed'         => $processed,
            'failed'            => $recordsReceived - $processed,
        ];
    }

    /**
     * This function does the following-
     * 1. Fetch the FAV entity from the FAV ID.
     * 2. Create the request body as per the API contract.
     * 3. Create a new Services\FTS\Transfer\Client object, and set its $request using setRequest() method call.
     * 4. Invoke Client object's doTransfer() method.
     * 5. Update FAV if there's no exception thrown (doTransfer() will throw exceptions, halting this step)
     * 6. Returns the response if no exception is thrown.
     *
     * @param  $favId string The FAV ID to be processed.
     *
     * @return mixed
     */
    public function sendFAVRequestToFTS(string $favId)
    {
        /**
         * @var Entity
         */
        $this->trace->info(
            TraceCode::FAV_QUEUE_FOR_FTS_JOB_HANDLER_INIT,
            [
                'fav_id'   => $favId,
            ]
        );

        $fav = $this->repo->fund_account_validation->findOrFail($favId);

        $request = $this->createRequestBodyFromFavForFTS($fav);

        $ftsClient = new FTS\Transfer\Client($this->app);

        $ftsClient->setRequest($request);

        return $ftsClient->doTransfer();
    }

    /**
     * This function creates the request array from an FAV entity.
     *
     * @param $fav Entity The FAV entity
     *
     * @return array[] Consisting of the request body
     */
    protected function createRequestBodyFromFavForFTS($fav)
    {
        $this->trace->info(
            TraceCode::FAV_QUEUE_FOR_FTS_REQUEST_BODY_CREATION_INIT,
            [
                'fav_id' => $fav->getPublicId(),
            ]
        );

        // Create the basic request body
        $request = [
            FtsRequestFields::TRANSFER => [
                FtsRequestFields::SOURCE_ID             => $fav->getId(),
                FtsRequestFields::SOURCE_TYPE           => FtsConstants::FUND_ACCOUNT_VALIDATION,
                FtsRequestFields::AMOUNT                => $fav->getAmount(),
                FtsRequestFields::MERCHANT_ID           => $fav->getMerchantId(),
                FtsRequestFields::TRANSFER_ACCOUNT_TYPE => FtsConstants::BANK_ACCOUNT,
                FtsRequestFields::PURPOSE               => FtsConstants::PENNY_TESTING,
                FtsRequestFields::PREFERRED_MODE        => FtsConstants::MODE_IMPS,
            ],
        ];

        // Add notes as narration if notes exist in the FAV.
        $narration = $this->getNarration($fav);

        // - Now fetch the bank account from the fund account associated with the FAV
        // - We are assuming that the associated account is of the type bank account,
        //   hence directly using the account relation
        $bankAccount = $fav->fundAccount->account;

        // Fill the request array further, by nesting bank_account sub-array, using the contents of $bankAccount var
        $request[FtsRequestFields::BANK_ACCOUNT] = [
            FtsRequestFields::ID             => $bankAccount->getId(),
            FtsConstants::IFSC_CODE          => $bankAccount->getIfscCode(),
            FtsRequestFields::ACCOUNT_TYPE   => $bankAccount->getAccountType(),
            FtsRequestFields::ACCOUNT_NUMBER => $bankAccount->getAccountNumber(),
            FtsConstants::BENEFICIARY_NAME   => $bankAccount->getBeneficiaryName(),
        ];

        if (is_null($bankAccount->getAccountType()) === true)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::ACCOUNT_TYPE] = FtsConstants::SAVING;
        }

        // Fill up optional fields in bank_account sub-array

        if (is_null($bankAccount->isVirtual()) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsConstants::IS_VIRTUAL_ACCOUNT] = $bankAccount->isVirtual();
        }

        if (empty($bankAccount->getBeneficiaryCity()) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::BENEFICIARY_CITY] =
                $bankAccount->getBeneficiaryCity();
        }

        if (empty($bankAccount->getBeneficiaryEmail()) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::BENEFICIARY_EMAIL] =
                $bankAccount->getBeneficiaryEmail();
        }

        if (empty($bankAccount->getBeneficiaryState()) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::BENEFICIARY_STATE] =
                $bankAccount->getBeneficiaryState();
        }

        if (empty($bankAccount->getBeneficiaryMobile()) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::BENEFICIARY_MOBILE] =
                $bankAccount->getBeneficiaryMobile();
        }

        if (empty($bankAccount->getBeneficiaryCountry()) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::BENEFICIARY_COUNTRY] =
                $bankAccount->getBeneficiaryCountry();
        }

        $address = $this->getAddressFromBankAccountEntity($bankAccount);

        if (is_null($address) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::BENEFICIARY_ADDRESS] = $address;
        }

        $this->trace->info(
            TraceCode::FAV_QUEUE_FOR_FTS_REQUEST_CREATED,
            [
                'fav_id'       => $fav->getPublicId(),
                'request_body' => $request,
            ]
        );

        return $request;
    }

    public function setTransferId(string $favId, string $transferId)
    {
        $this->trace->info(
            TraceCode::FAV_QUEUE_FOR_FTS_JOB_TRANSFER_ID_UPDATE_INIT,
            [
                'fav_id'   => $favId,
                'transfer_id' => $transferId,
            ]
        );

        $fav = $this->repo->fund_account_validation->findOrFail($favId);
        $fav->setFTSTransferId($transferId);
        $this->repo->fund_account_validation->saveOrFail($fav);
    }

    protected function getNarration(Entity $fav)
    {
        $merchant = $fav->merchant;

        $merchantBillingLabel = $merchant->getBillingLabel();

        // Remove all characters other than a-z, A-Z, 0-9 and space
        $formattedLabel = preg_replace('/[^a-zA-Z0-9 ]+/', '', $merchantBillingLabel);

        // If formattedLabel is non-empty, pick the first 30 chars, else fallback to 'Razorpay'
        $formattedLabel = ($formattedLabel ? $formattedLabel : 'Razorpay');

        $narration = $formattedLabel . ' FAV';

        $narration = str_limit($narration, 30, '');

        return $narration;
    }

    protected function getAddressFromBankAccountEntity($bankAccount)
    {
        $address = null;

        if (empty($bankAccount->getBeneficiaryAddress1()) === false)
        {
            $address = $bankAccount->getBeneficiaryAddress1();
        }

        if (empty($bankAccount->getBeneficiaryAddress2()) === false)
        {
            $address = $address . ', ' . $bankAccount->getBeneficiaryAddress2();
        }

        if (empty($bankAccount->getBeneficiaryAddress3()) === false)
        {
            $address = $address . ', ' . $bankAccount->getBeneficiaryAddress3();
        }

        if (empty($bankAccount->getBeneficiaryAddress4()) === false)
        {
            $address = $address . ', ' . $bankAccount->getBeneficiaryAddress4();
        }

        return $address;
    }
}
