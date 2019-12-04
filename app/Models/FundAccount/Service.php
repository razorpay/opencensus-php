<?php

namespace RZP\Models\FundAccount;

use Symfony\Component\HttpFoundation\Response;

use RZP\Constants;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\Contact;
use RZP\Models\Merchant;
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
        $traceRequest = $this->core->unsetSensitiveCardDetails($input);

        $this->trace->info(TraceCode::FUND_ACCOUNT_CREATE_REQUEST, $traceRequest);

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

        // if any merchant wants to skip duplicate check
        // and unique create contact everytime
        $createDuplicate = $this->shouldCreateDuplicate();

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
     * createDuplicate during fund account creation. The value
     * for this parameter is decided on the basis of origin of
     * the request. Request coming from API will not allow
     * duplicate creation(meaning,even if all attributes are same,
     * create another entity)  by default, if some merchant wants
     * duplicate creation, he will inform RZP and we will put him
     * behind razorx feature. Also for requests coming from dashboard
     * we will not be checking for duplicates by default. This is
     * because we do not want to change the behaviour on dashboard
     * till we have proper designs and process in mind.
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

        if ((($this->auth->isStrictPrivateAuth() === true) or
             ($this->auth->isPublicAuth() === true)) and
            ($this->shouldCreateDuplicate() === false))
        {
            $createDuplicate = false;
        }

        $entity = $this->core->create($input, $this->merchant, $source, $createDuplicate);

        $responseCode = ($entity->wasRecentlyCreated === true) ? Response::HTTP_CREATED : Response::HTTP_OK;;

        $this->trace->info(TraceCode::FUND_ACCOUNT_CREATE_RESPONSE,
            [
                Constants\Entity::FUND_ACCOUNT => $entity->getId(),
                Entity::RESPONSE_CODE          => $responseCode,
            ]);

        return [
            Constants\Entity::FUND_ACCOUNT => $entity,
            Entity::RESPONSE_CODE          => $responseCode,
        ];
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

    // ToDo https://razorpay.atlassian.net/browse/RX-849
    protected function shouldCreateDuplicate()
    {
        $merchant = $this->merchant;

        $variant  = $this->app['razorx']->getTreatment($merchant->getId(),
                                                       Merchant\RazorxTreatment::X_CONTACT_AND_FUND_ACCOUNT_CREATION,
                                                       $this->mode);

        $flag = ($variant === 'create_duplicate') ? true : false;

        return $flag;
    }
}
