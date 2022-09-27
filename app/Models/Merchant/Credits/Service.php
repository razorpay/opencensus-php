<?php

namespace RZP\Models\Merchant\Credits;

use Mail;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Base\RuntimeManager;
use RZP\Models\Merchant\Credits;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Balance as MerchantBalance;

class Service extends Base\Service
{
    public function grantCreditsForMerchant($mid, array $input, $payment = null)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $creditsLog = (new Credits\Core)->create($merchant, $input, $payment);

        return $creditsLog->toArrayPublic();
    }

    public function fetchCreditsLog($id)
    {
        // Raises Exception if record does not exist.
        $creditsLog = $this->repo->credits->findByPublicIdAndMerchant($id, $this->merchant);

        return $creditsLog->toArrayPublic();
    }

    /*
     * Update the CreditsLog, Presently We support update of credits only.
     *
     * @return array
     */
    public function updateCreditsLog($mid, $id, $input)
    {
        $id = Entity::verifyIdAndSilentlyStripSign($id);

        $creditsLog = $this->repo->credits->findByIdAndMerchantId($id, $mid);

        $creditsLog->getValidator()->validateInput('edit', $input);

        $credits = $input['value'];

        $creditsLog = (new Credits\Core)->updateCredits($creditsLog, $credits);

        return $creditsLog->toArrayPublic();
    }

    /**
     * Fetches multiple free credit logs based on query params.
     *
     * @return array
     */
    public function fetchMultiple($input)
    {
        $creditsLogs = $this->repo->credits->fetch($input, $this->merchant->getId());

        return $creditsLogs->toArrayPublic();
    }

    public function bulkCreateCredits(array $input)
    {
        $startTime = millitime();

        $this->trace->info(TraceCode::MERCHANT_CREDITS_BULK_REQUEST, $input);

        RuntimeManager::setTimeLimit(300);

        $failedIds = [];

        foreach ($input as $merchantId => $creditInput)
        {
            try
            {
                $this->app['workflow']->skipWorkflows(function() use ($merchantId, $creditInput)
                {
                    $this->grantCreditsForMerchant($merchantId, $creditInput);
                });
            }
            catch (\Throwable $t)
            {
                $this->trace->traceException(
                    $t,
                    \Razorpay\Trace\Logger::ERROR,
                    TraceCode::MERCHANT_CREDITS_BULK_EXCEPTION,
                    [
                        'merchant_id' => $merchantId,
                        'input'       => $creditInput,
                    ]);

                $failedIds[] = $merchantId;
            }
        }

        $timeTaken = millitime() - $startTime;

        $this->trace->info(
            TraceCode::BULK_ACTION_RESPONSE_TIME,
            [
                'action'          => 'create_credits',
                'time_taken'      => $timeTaken,
            ]);

        return [
            'total_count'  => count($input),
            'failed_count' => count($failedIds),
            'failed_ids'   => $failedIds
        ];
    }

    public function bulkCreateCreditsBatch(array $input)
    {
        $creditBatch = new Base\PublicCollection;

        $validator = new Validator;

        $validator->validateBulkCreditsCount($input);

        $idempotencyKey = null;

        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id, null);

        $validator->validateBatchId($batchId);

        $creatorId = $this->app['request']->header(RequestHeader::X_Creator_Id, null);

        $validator->validateBatchCreatorId($creatorId);

        $creatorType = $this->app['request']->header(RequestHeader::X_Creator_Type, null);

        $validator->validateBatchCreatorType($creatorType);

        foreach ($input as $item)
        {
            try
            {
                $this->trace->info(TraceCode::MERCHANT_CREDITS_BULK_REQUEST,
                    [
                        'input'     => $item,
                        'batch_id'  => $batchId,
                    ]);

                $idempotencyKey = $item[Entity::IDEMPOTENCY_KEY] ?? null;

                $validator->validateIdempotencyKey($idempotencyKey, $batchId);

                (new Validator)->validateInput(Validator::ADMIN_BATCH_UPLOAD, $item);

                $this->checkModeIfApplicableForProduct($item);

                $merchant = $this->repo->merchant->findByPublicId($item[Entity::MERCHANT_ID]);

                unset($item[Entity::MERCHANT_ID]);

                $result = $this->repo->credits->fetchByIdempotencyKey(
                                                    $item[Entity::IDEMPOTENCY_KEY],
                                                    $batchId,
                                                    $merchant);

                if ($result !== null)
                {
                    $this->trace->info(TraceCode::MERCHANT_CREDITS_EXIST_WITH_SAME_IDEMPOTENCY_KEY,
                        [
                            Entity::INPUT => $result->toArrayPublic(),
                        ]);

                    $creditBatch->push($result->toArrayPublic());
                }
                else
                {
                    $item[Entity::IDEMPOTENCY_KEY] = $idempotencyKey;

                    $item[Entity::BATCH_ID] = $batchId;

                    if ($creatorType === 'admin')
                    {
                        $creator = $this->repo->admin->findOrFailPublic($creatorId);

                        $item[Entity::CREATOR_NAME] = $creator->getName();
                    }

                    $result = (new Credits\Core)->assignCreditsToMerchant($item, $merchant);

                    (new Credits\Core)->checkMerchantStateAndSendCreditEmail($merchant, $result);

                    $creditBatch->push($result->toArrayPublic());
                }
            }
            catch (Exception\BaseException $exception)
            {
                $this->trace->traceException($exception,
                    Trace::INFO,
                    TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST);
                $exceptionData = [
                    Entity::BATCH_ID        => $batchId,
                    Entity::IDEMPOTENCY_KEY => $idempotencyKey,
                    'error'                 => [
                        Error::DESCRIPTION       => $exception->getError()->getDescription(),
                        Error::PUBLIC_ERROR_CODE => $exception->getError()->getPublicErrorCode(),
                    ],
                    Error::HTTP_STATUS_CODE => $exception->getError()->getHttpStatusCode(),
                ];

                $creditBatch->push($exceptionData);
            }
            catch (\Throwable $throwable)
            {
                $this->trace->traceException($throwable,
                    Trace::CRITICAL,
                    TraceCode::BATCH_SERVICE_BULK_EXCEPTION);

                $exceptionData = [
                    Entity::BATCH_ID        => $batchId,
                    Entity::IDEMPOTENCY_KEY => $idempotencyKey,
                    'error'                 => [
                        Error::DESCRIPTION       => $throwable->getMessage(),
                        Error::PUBLIC_ERROR_CODE => $throwable->getCode(),
                    ],
                    Error::HTTP_STATUS_CODE => 500,
                ];

                $creditBatch->push($exceptionData);
            }
        }

        $this->trace->info(TraceCode::MERCHANT_CREDITS_BULK_RESPONSE,
            ['credit_batch' => $creditBatch->toArrayWithItems()]);

        return $creditBatch->toArrayWithItems();
    }

    protected function checkModeIfApplicableForProduct(array $input)
    {
        if (($this->mode === Mode::TEST) and
            (isset($input[Entity::PRODUCT]) === true) and
            ($input[Entity::PRODUCT] === 'banking'))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_X_CREDITS_SUPPORTED_IN_ONLY_LIVE_MODE,
                null,
                ['input' => $input]);
        }
    }

    public function fetchCreditsByCampaignId($campaignId, $merchantId)
    {
        return $this->repo->credits->findByCampaignId($campaignId, $merchantId);
    }
}
