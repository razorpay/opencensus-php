<?php

namespace RZP\Models\FundAccount;

use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Contact;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
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

    public function create(array $input, string $batchId = null, string $idempotencyKey = null): array
    {
        if (isset($idempotencyKey) === true)
        {
            $contact = $this->contactCore->processEntryForContact($input, $idempotencyKey, $batchId);

            $fundAccount = $this->checkFundAccountExistence($input, $idempotencyKey, $contact);

            if (empty($fundAccount) === false)
            {
                return $fundAccount->toArrayPublic() + [Entity::IDEMPOTENCY_KEY => $idempotencyKey];
            }
        }

        $this->traceFundAccountNewRequest($input);

        (new Validator)->setStrictFalse()->validateInput(Validator::BEFORE_CREATE, $input);

        $source = null;

        if (isset($input[Entity::CONTACT_ID]) === true)
        {
            /** @var Contact\Entity $source */
            $source = $this->repo->contact->findByPublicIdAndMerchant($input[Entity::CONTACT_ID], $this->merchant);
        }
        else if (isset($input[Entity::CUSTOMER_ID]) === true)
        {
            /** @var Customer\Entity $source */
            $source = $this->repo->customer->findByPublicIdAndMerchant($input[Entity::CUSTOMER_ID], $this->merchant);
        }

        if (optional($source)->isActive() === false)
        {
            throw new BadRequestValidationFailureException(
                'Fund accounts cannot be created on an inactive ' . $source->getEntity());
        }

        if ($batchId !== null)
        {
            $entity = $this->core->create($input, $this->merchant, $source, $batchId);
        }
        else
        {
            $entity = $this->core->create($input, $this->merchant, $source);
        }

        return $entity->toArrayPublic() + [Entity::IDEMPOTENCY_KEY => $idempotencyKey];
    }

    public function fetch(string $id, array $input): array
    {
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant, $input);

        return $entity->toArrayPublic();
    }

    protected function traceFundAccountNewRequest(array $input)
    {
        $this->unsetSensitiveCardDetails($input);

        $this->trace->info(TraceCode::FUND_ACCOUNT_CREATE_REQUEST, $input);
    }

    protected function unsetSensitiveCardDetails(array & $input)
    {
        if ((isset($input[Entity::CARD]) === true) and
            (is_array($input[Entity::CARD]) === true))
        {
            if (empty($input[Entity::CARD][Card\Entity::NUMBER]) === false)
            {
                $input[Entity::CARD][Card\Entity::IIN] = substr($input[Entity::CARD][Card\Entity::NUMBER], 0, 6);
            }

            unset($input[Entity::CARD][Card\Entity::CVV]);
            unset($input[Entity::CARD][Card\Entity::NUMBER]);
        }
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

                    $fundAccount = $this->create($item, $batchId, $idempotencyKey);

                    $fundaccountBatch->push($fundAccount);
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
     * @param array             $entry
     * @param string            $idempotencyKey
     * @param Contact\Entity    $contact
     * @return Entity           $fundAccount
     * @throws BadRequestValidationFailureException
     */

    private function checkFundAccountExistence(
        array & $entry,
        string $idempotencyKey,
        Contact\Entity $contact)
    {
        $fundAccountId = $entry[FundAccountHelper::FUND_ACCOUNT][FundAccountHelper::ID] ?? null;

        if (empty($fundAccountId) === false)
        {
            $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $this->merchant);

            return $fundAccount;
        }

        $input = FundAccountHelper::getFundAccountInput($entry, $contact);

        $input[Entity::IDEMPOTENCY_KEY] = $idempotencyKey;

        $entry = $input;

        $fundAccount = $this->repo->fund_account->getFundAccountWithSimilarDetails(
            $input,
            $this->merchant,
            $contact);

        return $fundAccount;
    }
}
