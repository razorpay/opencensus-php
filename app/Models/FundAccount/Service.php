<?php

namespace RZP\Models\FundAccount;

use RZP\Services\Segment\EventCode as SegmentEvent;
use Symfony\Component\HttpFoundation\Response;

use RZP\Constants;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\BankAccount;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Exception\BaseException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Contact\Core as ContactCore;
use RZP\Models\Order\Service as OrderService;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundAccount\BatchHelper as FundAccountHelper;

/**
 * Class Service
 *
 * @package RZP\Models\FundAccount
 */
class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var ContactCore
     */
    protected $contactCore;

    /**
     * @var Repository
     */
    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->contactCore = new ContactCore;

        $this->entityRepo = $this->repo->fund_account;
    }

    public function create(array $input): array
    {
        $input = $this->trimCardNumberIfRequired($input);

        $traceRequest = $this->core->unsetSensitiveCardDetails($input);

        $this->trace->info(TraceCode::FUND_ACCOUNT_CREATE_REQUEST, $traceRequest);

        $this->unsetIfscIfRequired($input);

        (new OrderService())->updateIfscIfRequired($input);

        (new Validator)->setStrictFalse()->validateInput(Validator::BEFORE_CREATE, $input);

        if (isset($input[Entity::CONTACT_ID]) === true)
        {
            return $this->handleFundAccountCreationForContact($input);
        }

        return $this->handleFundAccountCreationForCustomer($input);
    }

    /**
     * @param array           $input
     * @param Contact\Entity  $contact
     * @param array           $traceData
     *
     * @param Merchant\Entity $merchant
     * @param bool            $compositePayoutSaveOrFail
     * @param array           $metadata
     *
     * @return Entity
     * @throws BadRequestValidationFailureException
     */
    public function createForCompositePayout(array $input,
                                             Contact\Entity $contact,
                                             array $traceData,
                                             Merchant\Entity $merchant,
                                             $compositePayoutSaveOrFail = true,
                                             array $metadata = []): Entity
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_RAW_REQUEST_FOR_COMPOSITE_PAYOUT, [
            'input'             => $traceData,
            'save_or_fail_flag' => $compositePayoutSaveOrFail,
            'metadata'          => $metadata
        ]);

        $this->preProcessingForCard($input, $traceData);

        if ($contact->isActive() === false)
        {
            throw new BadRequestValidationFailureException(
                'Fund accounts cannot be created on an inactive ' . $contact->getEntity());
        }

        return $this->core->createForCompositePayout($input,
                                                     $merchant,
                                                     $contact,
                                                     $traceData,
                                                     $compositePayoutSaveOrFail,
                                                     $metadata);
    }

    protected function preProcessingForCard(array &$input, array &$traceData)
    {
        $input     = $this->trimCardNumberIfRequired($input);
        $traceData = $this->trimCardNumberIfRequired($traceData);

        $this->unsetIfscIfRequired($input);
        $this->unsetIfscIfRequired($traceData);

        $orderService = (new OrderService);
        $orderService->updateIfscIfRequired($input);
        $orderService->updateIfscIfRequired($traceData);
    }

    public function fetch(string $id, array $input): array
    {
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant, $input);

        return $entity->toArrayPublic();
    }

    /**
     * @param array $input
     * @return array
     * @throws BadRequestValidationFailureException
     */

    public function createBulkFundAccount(array $input)
    {
        $fundAccountBatch = new Base\PublicCollection;

        $validator = new Validator;

        $validator->validateBulkFundAccountCount($input);

        $idempotencyKey = null;

        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id, null);

        $validator->validateBatchId($batchId);

        // This used to rely on a razorx experiment but we never ended up using this.
        // As part of code cleanup, we're setting this to default `false`
        $createDuplicate = false;

        foreach ($input as $item)
        {
            try
            {
                $this->trace->info(
                    TraceCode::BATCH_SERVICE_FUND_ACCOUNT_BULK_REQUEST,
                    [
                        Entity::BATCH_ID => $batchId,
                        'input'          => $item
                    ]);

                $this->repo->transaction(function() use (
                    $createDuplicate,
                    & $item,
                    & $fundAccountBatch,
                    & $batchId,
                    & $idempotencyKey,
                    $validator)
                {
                    $idempotencyKey = $item[Entity::IDEMPOTENCY_KEY] ?? null;

                    $validator->validateIdempotencyKey($idempotencyKey, $batchId);

                    $result = $this->repo->fund_account->fetchByIdempotentKey(
                                                                              $item[Entity::IDEMPOTENCY_KEY],
                                                                              $this->merchant->getId(),
                                                                              $batchId);
                    if ($result !== null)
                    {
                        $this->trace->info(TraceCode::FUND_ACCOUNT_EXIST_WITH_SAME_IDEMPOTENCY_KEY,
                                            ['input' => $result->toArrayPublic(),
                                             Entity::IDEMPOTENCY_KEY => $item[Entity::IDEMPOTENCY_KEY]]);

                        $fundAccountBatch->push($result->toArrayPublic() +
                            [Entity::IDEMPOTENCY_KEY => $result->getIdempotencyKey()]);
                    }
                    else
                    {
                        $fundAccountId = $item[FundAccountHelper::FUND_ACCOUNT][FundAccountHelper::ID] ?? null;

                        if (empty($fundAccountId) === false)
                        {
                            $fundAccount = $this->checkFundAccountExistence($fundAccountId);

                            $fundAccountBatch->push($fundAccount->toArrayPublic() +
                                                    [Entity::IDEMPOTENCY_KEY => $idempotencyKey]);
                        }
                        else
                        {
                            $contact = $this->contactCore->processEntryForContact($item, $batchId, $createDuplicate);

                            $fundAccount = $this->createFundAcccount($item, $contact, $batchId, $createDuplicate);

                            $fundAccountBatch->push($fundAccount->toArrayPublic() +
                                                    [Entity::IDEMPOTENCY_KEY => $idempotencyKey]);
                        }
                    }
                });
            }
            catch (BaseException $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::INFO,
                    TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST);

                $exceptionData = [
                    Entity::BATCH_ID        => $batchId,
                    Entity::IDEMPOTENCY_KEY => $idempotencyKey,
                    Error::HTTP_STATUS_CODE => $exception->getError()->getHttpStatusCode(),
                    'error'                 => [
                        Error::DESCRIPTION       => $exception->getError()->getDescription(),
                        Error::PUBLIC_ERROR_CODE => $exception->getError()->getPublicErrorCode(),
                    ],
                ];

                $fundAccountBatch->push($exceptionData);
            }
            catch (\Throwable $throwable)
            {
                $this->trace->traceException($throwable,
                    Trace::CRITICAL,
                    TraceCode::BATCH_SERVICE_BULK_EXCEPTION);

                $exceptionData = [
                    Entity::BATCH_ID        => $batchId,
                    Entity::IDEMPOTENCY_KEY => $idempotencyKey,
                    Error::HTTP_STATUS_CODE => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'error'                 => [
                        Error::DESCRIPTION       => $throwable->getMessage(),
                        Error::PUBLIC_ERROR_CODE => $throwable->getCode(),
                    ],
                ];

                $fundAccountBatch->push($exceptionData);
            }
        }

        return $fundAccountBatch->toArrayWithItems();
    }

    /**
     * @param array $item
     * @param Contact\Entity $contact
     * @param string $batchId
     * @return Entity           $fundAccount
     * @throws BadRequestValidationFailureException
     */
    public function createFundAcccount(array $item, Contact\Entity $contact, string $batchId, bool $createDuplicate)
    {
        $input = FundAccountHelper::getFundAccountInput($item, $contact);

        (new OrderService())->updateIfscIfRequired($input);

        (new Validator)->setStrictFalse()->validateInput(Validator::BEFORE_CREATE, $input);

        $fundAccount = $this->core->create($input, $this->merchant, $contact, $createDuplicate, $batchId);

        return $fundAccount;
    }

    /**
     * @param string            $fundAccountId
     * @return Entity           $fundAccount
     */

    public function checkFundAccountExistence(string $fundAccountId): Entity
    {
        $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $this->merchant);

        $this->trace->info(
            TraceCode::FUND_ACCOUNT_EXIST,
            [
                Entity::ID           => $fundAccountId,
            ]);

        return $fundAccount;
    }

    /**
     * The fund account creation method will take the parameter
     * createDuplicate during fund account creation. By default,
     * if some merchant wants duplicate creation, he will inform RZP
     * and we will put him behind razorx feature. In this case createDuplicate will be true.
     *
     * @param array $input
     *
     * @return array
     * @throws BadRequestValidationFailureException
     */
    protected function handleFundAccountCreationForContact(array $input)
    {
        /** @var Contact\Entity $source */
        $source = $this->repo->contact->findByPublicIdAndMerchant($input[Entity::CONTACT_ID], $this->merchant);

        if ($source->isActive() === false)
        {
            throw new BadRequestValidationFailureException(
                'Fund accounts cannot be created on an inactive ' . $source->getEntity());
        }


        $createDuplicate = true;

        if (($this->auth->isPrivateAuth() === true) or
            ($this->auth->isPublicAuth() === true) or
            ($this->isAllowedInternalAppForDeDuplicateFA() === true))
        {
            $createDuplicate = false;
        }

        $batchId = (isset($input[Entity::BATCH_ID]) === true) ? $input[Entity::BATCH_ID] : null;

        $entity = $this->core->create($input, $this->merchant, $source, $createDuplicate, $batchId);

        $responseCode = ($entity->wasRecentlyCreated === true) ? Response::HTTP_CREATED : Response::HTTP_OK;

        $this->trace->info(TraceCode::FUND_ACCOUNT_CREATION_RESPONSE,
            [
                Constants\Entity::FUND_ACCOUNT => $entity->getId(),
                Entity::RESPONSE_CODE          => $responseCode,
            ]);

        return [
            Constants\Entity::FUND_ACCOUNT => $entity,
            Entity::RESPONSE_CODE          => $responseCode,
        ];
    }

    protected function isAllowedInternalAppForDeDuplicateFA(): bool
    {
        return (($this->auth->isPayoutLinkApp() === true) or
                ($this->auth->isAccountsReceivableApp() === true) or
                ($this->auth->isVendorPaymentApp() === true) or
                ($this->auth->isSettlementsApp() === true) or
                ($this->auth->isScroogeApp() === true) or
                ($this->auth->isXPayrollApp() === true) or
                ($this->auth->isPayoutService() === true) or
                ($this->auth->isCapitalCollectionsApp() === true) or
                ($this->isFundManagementPayoutInitiateWorker() === true));
    }

    protected function isFundManagementPayoutInitiateWorker(): bool
    {
        $jobName = app('worker.ctx')->getJobName() ?? null;

        if ($jobName !== Payout\Constants::FUND_MANAGEMENT_PAYOUT_INITIATE)
        {
            return false;
        }

        return true;
    }

    protected function handleFundAccountCreationForCustomer(array $input)
    {
        /** @var Customer\Entity $source */
        $source = $this->repo->customer->findByPublicIdAndMerchant($input[Entity::CUSTOMER_ID], $this->merchant);

        if ($source->isActive() === false)
        {
            throw new BadRequestValidationFailureException(
                'Fund accounts cannot be created on an inactive ' . $source->getEntity());
        }

        $entity = $this->core->create($input, $this->merchant, $source);

        return [
            Constants\Entity::FUND_ACCOUNT => $entity,
        ];
    }

    protected function unsetIfscIfRequired(array & $input)
    {
        if (isset($input[Payout\Entity::CARD][Payout\Entity::IFSC]) === true)
        {
            unset($input[Payout\Entity::CARD][Payout\Entity::IFSC]);
        }
    }

    protected function trimCardNumberIfRequired(array $input)
    {
        if (isset($input[Payout\Entity::CARD][Payout\Entity::NUMBER]) === true)
        {
            $cardNumber = $input[Payout\Entity::CARD][Payout\Entity::NUMBER];

            $input[Payout\Entity::CARD][Payout\Entity::NUMBER] = ltrim($cardNumber, '0');
        }

        return $input;
    }

    public function fetchMultiple(array $input): array
    {
        $entities = $this->core->fetchMultiple($this->merchant, $input);

        return $entities->toArrayPublic();
    }


}
