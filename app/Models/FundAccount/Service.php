<?php

namespace RZP\Models\FundAccount;

use RZP\Constants;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\Contact;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Exception\BaseException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Contact\Core as ContactCore;
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
        $this->trace->info(TraceCode::FUND_ACCOUNT_CREATE_REQUEST,
                            $this->core->unsetSensitiveCardDetails($input));

        (new Validator)->setStrictFalse()->validateInput(Validator::BEFORE_CREATE, $input);

        $source = null;

        if (isset($input[Entity::CONTACT_ID]) === true)
        {
            return $this->handleFundAccountCreationForContact($input);
        }

        return $this->handleFundAccountCreationForCustomer($input);
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
                        $contact = $this->contactCore->processEntryForContact($item, $batchId);

                        $fundAccountId = $item[FundAccountHelper::FUND_ACCOUNT][FundAccountHelper::ID] ?? null;

                        if (empty($fundAccountId) === false)
                        {
                            $fundAccount = $this->checkFundAccountExistence($fundAccountId);

                            $fundAccountBatch->push($fundAccount->toArrayPublic() +
                                [Entity::IDEMPOTENCY_KEY => $fundAccount->getIdempotencyKey()]);
                        }
                        else
                        {
                            $fundAccount = $this->createFundAcccount($item, $contact, $batchId);

                            $fundAccountBatch->push($fundAccount->toArrayPublic() +
                                [Entity::IDEMPOTENCY_KEY => $fundAccount->getIdempotencyKey()]);
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
                    Error::HTTP_STATUS_CODE => 500,
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
    public function createFundAcccount(array $item, Contact\Entity $contact, string $batchId)
    {
        $input = FundAccountHelper::getFundAccountInput($item, $contact);

        (new Validator)->setStrictFalse()->validateInput(Validator::BEFORE_CREATE, $input);

        $fundAccount = $this->core->create($input, $this->merchant, $contact, true, $batchId);

        return $fundAccount;
    }

    /**
     * @param string            $fundAccountId
     * @return Entity           $fundAccount
     */

    public function checkFundAccountExistence(string $fundAccountId)
    {
        $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $this->merchant);

        if (empty($fundAccount) === false)
        {
            $this->trace->info(
                TraceCode::FUND_ACCOUNT_EXIST,
                [
                    Entity::ID           => $fundAccountId,
                ]);

            return $fundAccount;
        }
    }

    protected function handleFundAccountCreationForContact(array $input)
    {
        // The fund account creation method will take the parameter
        // allowDuplicate during fund account creation. The value
        // for this parameter is decided on the basis of origin of
        // the request. The dashboard behaviour is yet to be finalised
        // so for now we are going ahead with duplication checks
        // only in API flow.
        $responseCode  = 200;

        /** @var Contact\Entity $source */
        $source = $this->repo->contact->findByPublicIdAndMerchant($input[Entity::CONTACT_ID], $this->merchant);

        if ($this->auth->isStrictPrivateAuth() === true)
        {
            $entity = $this->core->create($input, $this->merchant, $source, true);
        }
        else
        {
            $entity = $this->core->create($input, $this->merchant, $source);
        }

        $responseCode = $entity->wasRecentlyCreated === true ? 201 : $responseCode;

        return [
            Constants\Entity::FUND_ACCOUNT => $entity,
            Entity::RESPONSE_CODE          => $responseCode,
        ];
    }

    protected function handleFundAccountCreationForCustomer(array $input)
    {
        /** @var Customer\Entity $source */
        $source = $this->repo->customer->findByPublicIdAndMerchant($input[Entity::CUSTOMER_ID], $this->merchant);

        $entity = $this->core->create($input, $this->merchant, $source);

        return [
            Constants\Entity::FUND_ACCOUNT => $entity,
        ];
    }
}
