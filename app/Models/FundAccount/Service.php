<?php

namespace RZP\Models\FundAccount;

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
    use Base\Traits\SensitiviseCardDetails;

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
            $this->trace->info(TraceCode::FUND_ACCOUNT_CREATE_REQUEST, $this->sensitiveCardDetails($input));

            (new Validator)->setStrictFalse()->validateInput(Validator::BEFORE_CREATE, $input);

            $source = null;

            if (isset($input[Entity::CONTACT_ID]) === true) {
                /** @var Contact\Entity $source */
                $source = $this->repo->contact->findByPublicIdAndMerchant($input[Entity::CONTACT_ID], $this->merchant);
            } else if (isset($input[Entity::CUSTOMER_ID]) === true) {
                /** @var Customer\Entity $source */
                $source = $this->repo->customer->findByPublicIdAndMerchant($input[Entity::CUSTOMER_ID], $this->merchant);
            }

            if (optional($source)->isActive() === false) //copy to contact core processEntryForContact
            {
                throw new BadRequestValidationFailureException(
                    'Fund accounts cannot be created on an inactive ' . $source->getEntity());
            }

            $entity = $this->core->create($input, $this->merchant, $source);

        return $entity->toArrayPublic();
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
        $fundaccountBatch = new Base\PublicCollection;

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
                    & $fundaccountBatch,
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
                        $fundaccountBatch->push($result->toArrayPublic() +
                            [Entity::IDEMPOTENCY_KEY => $result->getIdempotencyKey()]);
                    }
                    else
                    {
                        $contact = $this->contactCore->processEntryForContact($item, $batchId);

                        $fundAccountId = $item[FundAccountHelper::FUND_ACCOUNT][FundAccountHelper::ID] ?? null;

                        if (empty($fundAccountId) === false)
                        {
                            $fundAccount = $this->checkFundAccountExistence($fundAccountId);

                            $fundaccountBatch->push($fundAccount->toArrayPublic() +
                                [Entity::IDEMPOTENCY_KEY => $fundAccount->getIdempotencyKey()]);
                        }
                        else
                        {
                            $fundAccount = $this->createFundAcccount($item, $contact, $batchId);

                            $fundaccountBatch->push($fundAccount->toArrayPublic() +
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

                $fundaccountBatch->push($exceptionData);
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

                $fundaccountBatch->push($exceptionData);
            }
        }
        return $fundaccountBatch->toArrayWithItems();
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

        $fundAccount = $this->core->create($input, $this->merchant, $contact, $batchId);

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
}
