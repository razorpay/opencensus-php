<?php

namespace RZP\Models\FundAccount\Validation;


use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\Error;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Traits;
use RZP\Models\Merchant\Balance;
use RZP\Models\FundAccount\Type;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Redaction;
use RZP\Error\PublicErrorDescription;
use RZP\Services\FTS\Transfer\Client as FtsClient;
use RZP\Models\ApiEventSubscriber;
use RZP\Http\Request\Request;
use RZP\Http\Response\ApiResponse;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\BadRequestException;

class Service extends Base\Service
{
    use Traits\ProcessAccountNumber;
    use Traits\ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

        $this->entityRepo = $this->repo->fund_account_validation;
    }


    /**
     * @throws \Exception
     */
    public function fetchFav(string $id, array $input): ?array
    {
        try
        {
            /** @var Entity $fav */
            $fav = $this->repo->fund_account_validation->findByPublicIdAndMerchant($id, $this->merchant);
        }
        catch (\Exception $ex)
        {
            if ($this->core->shouldFetchFavByIdViaMicroservice($this->merchant))
            {
                try
                {
                    $fav = $this->core->fetchByIdFromFavService($id, $input);
                }
                catch (\Exception $ex2)
                {
                    // Check if it's a FAV not found error from the service
                    if ($ex2->getCode() === ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_NOT_FOUND)
                    {
                        throw $ex;
                    }
                    throw $ex2;
                }
            }
            else {
                throw $ex;
            }
        }

        /** @var \RZP\Models\Merchant\Entity $merchant */
        $merchant = $this->repo->merchant->findOrFail($fav->getMerchantId());

        if (($fav->getFundAccountType() == Type::VPA) and
            ($merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::VPA_BANK_INFO_ENABLED) === true))
        {
            $fav->setIsVpaBankInfoEnabledFlag();
        }

        $fav = $this->core->setAdditionalFieldsForCompositeResponse($fav);

        return $fav->toArrayPublic();
    }

    public function create(array $input): array
    {
        $accountNumber = null;

        if (empty($input[Balance\Entity::ACCOUNT_NUMBER]) === false)
        {
            $accountNumber = $input[Balance\Entity::ACCOUNT_NUMBER];

            // mandates account number and converts to balance id
            $this->processAccountNumber($input);
        }

        if (empty($input[Entity::SOURCE_ACCOUNT_NUMBER]) === false)
        {
            $accountNumber = $input[Entity::SOURCE_ACCOUNT_NUMBER];

            $input[Balance\Entity::ACCOUNT_NUMBER] = $input[Entity::SOURCE_ACCOUNT_NUMBER];

            $this->processAccountNumber($input);
        }

        $input[Balance\Entity::ACCOUNT_NUMBER] = $accountNumber;

        $entity = $this->core->create($input, $this->merchant);

        return $entity->toArrayPublic();
    }

    public function fetchPricingInfoForFavService(array $input)
    {
        $this->trace->info(
            TraceCode::FAV_SERVICE_FETCH_PRICING_INFO_REQUEST,
            [
                'input' => $input,
            ]);

        try
        {
            (new Validator)->validateInput('fav_service_fetch_pricing_info', $input);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::FETCH_PRICING_INFO_FOR_MICROSERVICE_FAILED,
                [
                    'input' => $input,
                ]
            );

            return [
                Entity::ERROR            => $exception->getMessage(),
                Error::PUBLIC_ERROR_CODE => strval($exception->getCode()),
            ];
        }

        return $this->core->fetchPricingInfoForFavService($input);
    }

    public function validateVpaInternal(array $input)
    {
        // check that the auth in internal
        if ($this->auth->isPrivilegeAuth() === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        $this->trace->info(TraceCode::VPA_VALIDATION_REQUEST_FROM_MICROSERVICE, [
            'input' => $input
        ]);

        return $this->core->validateVpa($input);
    }

    public function validateBankAccountInternal(array $input)
    {
        if ($this->auth->isPrivilegeAuth() === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        $this->trace->info(TraceCode::BANK_ACCOUNT_VALIDATION_REQUEST_FROM_MICROSERVICE, [
            'input' => $input
        ]);

        return $this->core->validateBankAccount($input);
    }

    public function fetchMultiple(array $input): array
    {
        if (empty($input[Balance\Entity::ACCOUNT_NUMBER]) === false)
        {
            // mandates account number and converts to balance id
            $this->processAccountNumber($input);
        }

        $entities = $this->entityRepo->fetch($input, $this->merchant->getId());

        return $entities->toArrayPublic();
    }

    /**
     * @param string $favId
     * @param string $merchantId
     *
     * @return array
     */
    public function getFavByMerchantIdAndFavId(string $favId,string $merchantId)
    {
        return $this->core->getFavByMerchantIdAndFavId($favId, $merchantId);
    }

    public function bulkPatchFavAsFailed(array $input): array
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_BULK_PATCH_REQUEST, [
            'input' => $input
        ]);

        $response = $this->core->bulkPatchFavAsFailed($input);

        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_BULK_PATCH_RESPONSE, [
            'response' => $response
        ]);

        return $response;
    }

    public function manualUpdateFavToFailedState(array $favIds)
    {
        $count = 0;

        foreach ($favIds as $favId)
        {
            $this->trace->info(
                TraceCode::FAV_MANUAL_UPDATE_FROM_FTS_WEBHOOK_SERVICE_INIT,
                [
                    'fav_id'     => $favId
                ]);

            $fta = $this->repo
                ->fund_transfer_attempt
                ->getFTSAttemptBySourceId(
                    $favId,
                    'fund_account_validation',
                    true);

            if ($fta->getFTSTransferId() !== null)
            {
                continue;
            }

            $response = $this->core->manualUpdateFavToFailedState($favId);

            $this->trace->info(
                TraceCode::FAV_MANUAL_UPDATE_FROM_FTS_WEBHOOK_CORE_HANDLER_SUCCESSFUL,
                [
                    'response'     => $response,
                    'fav_id' => $favId
                ]);

            $extraInfo = [
                Attempt\Constants::BENEFICIARY_NAME => '',
                Attempt\Entity::CMS_REF_NO          => '',
                Attempt\Constants::INTERNAL_ERROR   => true,
                'ponum'                             => '',
            ];

            $input = [
                Attempt\Entity::SOURCE_ID        => $favId,
                Attempt\Entity::SOURCE_TYPE      => 'fund_account_validation',
                Attempt\Entity::BANK_STATUS_CODE => Status::FAILED,
                Attempt\Entity::REMARKS          => 'Failed manually',
                'extra_info'                     => $extraInfo,
            ];

            (new FtsClient($this->app))->doRecon($input);

            $this->trace->info(
                TraceCode::FAV_FTA_MANUAL_UPDATE_SUCCESSFUL,
                [
                    'fav_id' => $favId
                ]);

            $count++;
        }

        return ['success' => $count];
    }

    /**
     * @throws \Throwable
     */
    public function updateFavWithFtsWebhook(array $input) : array
    {
        $this->trace->info(
            TraceCode::FAV_UPDATE_FROM_FTS_WEBHOOK_SERVICE_INIT,
            [
                'input'     => (new Redaction())->redactData($input)
            ]);

        // First update the source FAV entity
        $response = $this->core->updateFavWithFtsWebhook($input);

        $this->trace->info(
            TraceCode::FAV_UPDATE_FROM_FTS_WEBHOOK_FTA_RECON_CALLED,
            [
                'input'     => (new Redaction())->redactData($input)
            ]);

        // Call the doRecon() method provided by the FTS client to update FTA for backwards compatibility
        (new FtsClient($this->app))->doRecon($input);

        return $response;
    }

    public function createFundAccountValidationViaLedgerCronJob(array $blacklistIds, array $whitelistIds, int $limit)
    {
        $this->core->createFundAccountValidationViaLedgerCronJob($blacklistIds, $whitelistIds, $limit);
    }

    public function sendWebhookToMerchant(array $input): array
    {
        $this->trace->info(TraceCode::FAV_SEND_WEBHOOK_TO_MERCHANT_REQUEST, [
            'input' => $input
        ]);

        try
        {
            // Create a validation entity from the input
            $validation = new Entity();
            $validation->setId(Entity::stripDefaultSign($input['id']));
            $validation->setFavType($input['type']);
            $validation->setMerchantId($input['merchant_id']);
            $validation->setStatus($input['status']);
            $validation->setAmount($input['amount']);
            $validation->setCurrency($input['currency']);
            $validation->setNotes($input['notes']);
            $validation->setValidationMethod($input['validation_method']);
            $validation->setReferenceId($input['reference_id']);
            $validation->setCreatedAt($input['created_at']);
            $validation->setUtr($input['utr'] ?? null);

            if ($input['status'] === 'completed'){
                $validation->setAccountStatus($input['account_status']);
                $validation->setRegisteredName($input['registered_name']);
                $validation->setNameMatchScore($input['name_match_score']);
            }
            else if($input['status'] === 'failed') {
                $validation->setErrorCode($input['error_code']);
            }

            // Get fund account ID from input
            $fundAccountId = Entity::stripDefaultSign($input['fund_account']['id']);

            // Fetch fund account entity
            $fundAccount = (new \RZP\Models\FundAccount\Repository())->findByPublicId('fa_'.$fundAccountId);

            // Associate fund account with validation
            $validation->associateFundAccount($fundAccount);

            // Dispatch the appropriate webhook event based on the status
            $eventPayload = [
                \RZP\Listeners\ApiEventSubscriber::MAIN => $validation
            ];

            if (($input['status'] === 'completed') || ($input['status'] === 'failed'))
            {
                $eventName = 'api.fund_account.validation.' . $input['status'];

                $this->app['events']->dispatch($eventName, $eventPayload);
            }

            $this->trace->info(TraceCode::FAV_SEND_WEBHOOK_TO_MERCHANT_SUCCESS, [
                'fav_id' => $input['id'],
                'merchant_id' => $input['merchant_id']
            ]);

            return [
                'success' => true,
                'message' => 'Webhook triggered successfully',
            ];
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::FAV_SEND_WEBHOOK_TO_MERCHANT_FAILED, [
                'input' => $input,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to trigger webhook',
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Handle webhook from bank for fund account validation
     *
     * @param array $input Webhook payload from bank
     * @param string $bank Bank identifier (e.g. 'citi')
     * @return array
     * @throws \Throwable
     */
    public function handleBankWebhook(array $input, string $bank): array
    {
        $this->trace->info(TraceCode::FAV_UPDATE_VIA_WEBHOOK_REQUEST, [
            'bank' => $bank,
            'input' => $input
        ]);

        try
        {
            // Forward the webhook to FAV service
            $this->core->forwardBankWebhookToFavService($input, $bank);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::FAV_UPDATE_FROM_CITI_WEBHOOK_FAILED, [
                'input'     => $input,
                'bank'      => $bank,
                'error'     => $e->getMessage()
            ]);
        }

        return [
            'status'  => 'success',
            'message' => 'Webhook processed successfully'
        ];
    }
}
