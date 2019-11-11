<?php

namespace RZP\Models\Contact;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundAccount\Service as FundAccountService;
use RZP\Models\Contact\BatchHelper as ContactBatchHelper;
use RZP\Models\FundAccount\BatchHelper as FundAccountHelper;

/**
 * Class Service
 *
 * @package RZP\Models\Contact
 */
class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;

    /**
     * @var FundAccountService
     */
    protected $fundAccountService;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->contact;

        $this->fundAccountService = new FundAccountService;
    }

    public function fetch(string $id, array $input): array
    {
        $merchant = $this->merchant;

        $contact = $this->core->fetch($id, $merchant, $input);

        return $contact->toArrayPublic();
    }

    public function getTypes(): array
    {
        return (new Type)->getAll($this->merchant);
    }

    public function postType(array $input): array
    {
        (new Validator)->validateInput('create_type', $input);

        $typeObj = new Type;

        $typeObj->addNewCustom($input[Entity::TYPE], $this->merchant);

        return $typeObj->getAll($this->merchant);
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws BadRequestValidationFailureException
     */
    public function createBulkContact(array $input)
    {
        $contactBatch = new Base\PublicCollection;

        $validator = new Validator;

        $validator->validateBulkContactCount($input);

        $idempotencyKey = null;

        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id, null);

        $validator->validateBatchId($batchId);

        foreach ($input as $item)
        {
            try
            {
                $this->trace->info(
                    TraceCode::BATCH_SERVICE_CONTACT_BULK_REQUEST,
                    [
                        Entity::BATCH_ID => $batchId,
                        'input'          => $item
                    ]);

                $this->repo->transaction(function() use (& $item,
                                                         & $contactBatch,
                                                         & $batchId,
                                                         & $idempotencyKey,
                                                         $validator)
                {
                    $idempotencyKey = $item[Entity::IDEMPOTENCY_KEY] ?? null;

                    $validator->validateIdempotencyKey($idempotencyKey, $batchId);

                    $contact = $this->processEntryForContact($item, $idempotencyKey, $batchId);

                    $fundAccount = $this->processEntryForContactsFundAccount($item,
                                                                             $contact,
                                                                             $idempotencyKey,
                                                                             $batchId);

                    $contactBatch->push($fundAccount);
                });

            }
            catch (Exception\BaseException $exception)
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

                $contactBatch->push($exceptionData);
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

                $contactBatch->push($exceptionData);
            }
        }

        return $contactBatch->toArrayWithItems();
    }

    public function processEntryForContact(
        array $entry,
        string $idempotencyKey,
        string $batchId)
    {
        $contactId = (isset($entry[ContactBatchHelper::CONTACT][ContactBatchHelper::ID]) === true) ?
                     $entry[ContactBatchHelper::CONTACT][ContactBatchHelper::ID] :
                     null;

        if (empty($contactId) === false)
        {
            return $this->repo->contact->findByPublicIdAndMerchant($contactId, $this->merchant);
        }

        $input = ContactBatchHelper::getContactInput($entry);

        $contact = $this->repo->contact->getContactWithSimilarDetails($input, $this->merchant);

        $input[Entity::IDEMPOTENCY_KEY] = $idempotencyKey;

        return $contact ?: $this->core->create($input, $this->merchant, null, $batchId);
    }

    public function processEntryForContactsFundAccount(
        array $entry,
        Entity $contact,
        string $idempotencyKey,
        string $batchId): array
    {
        $fundAccountId = $entry[FundAccountHelper::FUND_ACCOUNT][FundAccountHelper::ID] ?? null;

        if (empty($fundAccountId) === false)
        {
            $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $this->merchant);

            return $fundAccount->toArrayPublic() + [Entity::IDEMPOTENCY_KEY => $idempotencyKey];
        }

        $input = FundAccountHelper::getFundAccountInput($entry, $contact);

        $input[FundAccountEntity::IDEMPOTENCY_KEY] = $idempotencyKey;

        $fundAccount = $this->repo->fund_account->getFundAccountWithSimilarDetails(
            $input,
            $this->merchant,
            $contact);

        if (empty($fundAccount) === false)
        {
            $fundAccountArr = $fundAccount->toArrayPublic() + [Entity::IDEMPOTENCY_KEY => $idempotencyKey];

            return $fundAccountArr;
        }

        return $this->fundAccountService->create($input, $batchId, $idempotencyKey) +
                [Entity::IDEMPOTENCY_KEY => $idempotencyKey];
    }
}
