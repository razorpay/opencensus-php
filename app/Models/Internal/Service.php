<?php

namespace RZP\Models\Internal;

use App;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FundAccount;
use RZP\Models\BankAccount;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Balance\Type;
use RZP\Exception\BadRequestException;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Admin\Service as AdminService;

class Service extends Base\Service
{
    const TIME_TAKEN                   = 'time_taken';
    const INTERNAL_ENTITY_CREATE_MUTEX = 'internal_entity_create_%s';
    const MUTEX_LOCK_TIMEOUT           = 30;
    const ACCOUNT_NUMBER               = 'account_number';
    const TRANSACTOR_ID                = 'transactor_id';
    const TRANSACTOR_EVENT             = 'transactor_event';
    const IDENTIFIERS                  = 'identifiers';
    const ADDITIONAL_PARAMS            = 'additional_params';
    const BANKING_ACCOUNT_ID           = 'banking_account_id';
    const TRANSACTOR_EVENT_NAME        = 'inter_account_credit_processed';
    const PAYOUT_PURPOSE               = 'inter_account_payout';

    const STATUS_EXPECTED              = 'expected';
    const STATUS_RECEIVED              = 'received';
    const STATUS_FAILED                = 'failed';

    const TYPE_CREDIT                  = 'credit';
    const TENANT                       = 'tenant';
    const X                            = 'X';

    protected $ledgerService;

    public function __construct()
    {
        parent::__construct();

        $this->ledgerService = $this->app['ledger'];
    }

    public function createOnPayout(Payout\Entity $payout): array
    {
        $this->trace->info(TraceCode::INTERNAL_CREATE_ON_PAYOUT_INPUT_DATA, [
            Payout\Entity::ID          => $payout->getPublicId(),
            Payout\Entity::AMOUNT      => $payout->getAmount(),
            Payout\Entity::BASE_AMOUNT => $payout->getBaseAmount(),
            Payout\Entity::UTR         => $payout->getUtr(),
            Payout\Entity::CURRENCY    => $payout->getCurrency(),
            Payout\Entity::TYPE        => self::TYPE_CREDIT,
            Payout\Entity::UPDATED_AT  => $payout->getUpdatedAt(),
        ]);

        // based on the payout_id, the beneficiary's account number has to be identified.
        // account number is fetched by payout -> fund_account -> bank_account
        // get account_id from fund account using id
        $fundAccount = $this->repo->fund_account->find($payout->getFundAccountId(), [FundAccount\Entity::ACCOUNT_ID]);
        if (empty($fundAccount) === true)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ACCOUNT_NOT_FOUND);
        }

        // get account_number from bank account using id
        $bankAccount = $this->repo->bank_account->find($fundAccount->getAccountId(), [BankAccount\Entity::ACCOUNT_NUMBER]);
        if (empty($bankAccount) === true)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ACCOUNT_NOT_FOUND);
        }

        // INTER_ACCOUNT_PAYOUT_MERCHANTS contains mapping between real account number to mid mapping
        $accountNumberMerchantIdMap = (new AdminService)->getConfigKey(['key' => ConfigKey::INTER_ACCOUNT_PAYOUT_MERCHANTS]);
        if (isset($accountNumberMerchantIdMap[$bankAccount->getAccountNumber()]) === false)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_MERCHANT_NOT_FOUND);
        }
        $beneMerchantId = $accountNumberMerchantIdMap[$bankAccount->getAccountNumber()];

        // create an internal entity
        return $this->create([
            Entity::AMOUNT            => $payout->getAmount(),
            Entity::BASE_AMOUNT       => $payout->getBaseAmount(),
            Entity::UTR               => $payout->getUtr(),
            Entity::CURRENCY          => $payout->getCurrency(),
            Entity::TYPE              => self::TYPE_CREDIT,
            Entity::TRANSACTION_DATE  => $payout->getUpdatedAt(),
            Entity::MERCHANT_ID       => $beneMerchantId,
        ]);
    }

    public function create(array $input): array
    {
        // create internal entity
        $internal = new Entity();

        // build internal entity
        $internal->build($input);

        $this->trace->info(TraceCode::INTERNAL_CREATE_INPUT_DATA, $input);

        // check if utr already exists for any internal entity
        $response = $this->repo->internal->fetchByUTR($input[Entity::UTR]);
        if ($response !== null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ENTITY_ALREADY_EXISTS);
        }

        // take mutex lock on the utr
        $mutex = App::getFacadeRoot()['api.mutex'];
        $mutexKey = sprintf(self::INTERNAL_ENTITY_CREATE_MUTEX, $input[Entity::UTR]);
        $mutex->acquireAndRelease($mutexKey, function() use ($input, $internal){

            $internal[Entity::STATUS] = self::STATUS_EXPECTED;
            $this->repo->saveOrFail($internal);

        }, self::MUTEX_LOCK_TIMEOUT, ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);

        return $internal->toArray();
    }

    public function failOnPayoutReversal(string $utr): array
    {
        $this->trace->info(TraceCode::INTERNAL_FAIL_ON_PAYOUT_REVERSAL_INPUT_DATA, [
            Entity::UTR => $utr,
        ]);

        // fetch internal entity from the utr
        $internal = $this->repo->internal->fetchByUTR($utr);
        if ($internal == null)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ENTITY_NOT_FOUND);
        }

        return $this->fail($internal->getPublicId());
    }

    public function fail(string $id): array
    {
        $this->trace->info(TraceCode::INTERNAL_FAIL_INPUT_DATA, [
            Entity::ID => $id,
        ]);

        // fetch internal entity from the id
        $internal = $this->repo->internal->findByPublicId($id);
        if ($internal == null)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ENTITY_NOT_FOUND);
        }

        // update the status to received and record the reconciled date
        $internal[Entity::STATUS] = self::STATUS_FAILED;
        $this->repo->saveOrFail($internal);

        return $internal->toArray();
    }

    public function reconcile(string $id, array $input): array
    {
        // perform validation
        (new Validator)->validateInput('reconcile', $input);

        // fetch internal entity from the id
        $internal = $this->repo->internal->findByPublicId($id);
        if ($internal == null)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ENTITY_NOT_FOUND);
        }

        // get banking_account_id from merchant_id
        $balance = $this->repo->balance->getMerchantBalanceByTypeAndAccountType(
            $internal->getMerchantId(),
            Type::BANKING,
            AccountType::SHARED);
        if (empty($balance) === true)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_BALANCE_NOT_FOUND);
        }
        $bankingAccount = $this->repo->banking_account->getFromBalanceId($balance->getId());
        if (empty($bankingAccount) === true) {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_BANK_ACCOUNT_NOT_FOUND);
        }

        // make ledger create request
        $journal = $this->createJournal([
            Entity::MERCHANT_ID      => $internal[Entity::MERCHANT_ID],
            Entity::CURRENCY         => $internal[Entity::CURRENCY],
            Entity::AMOUNT           => strval($internal[Entity::AMOUNT]),
            Entity::BASE_AMOUNT      => strval($internal[Entity::BASE_AMOUNT]),
            self::TRANSACTOR_ID      => $internal->getPublicId(),
            self::TRANSACTOR_EVENT   => self::TRANSACTOR_EVENT_NAME,
            Entity::TRANSACTION_DATE => strval($internal[Entity::TRANSACTION_DATE]),
            self::IDENTIFIERS        => [
                self::BANKING_ACCOUNT_ID => $bankingAccount->getPublicId(),
            ],
            self::TENANT             => self::X,
        ]);

        // update the internal entity with journal_id
        $internal[Entity::TRANSACTION_ID] = $journal[Base\UniqueIdEntity::ID];
        $internal[Entity::STATUS]         = $input[Entity::STATUS];
        $internal[Entity::RECONCILED_AT]  = $input[Entity::RECONCILED_AT];
        $this->repo->saveOrFail($internal);

        return $internal->toArray();
    }

    private function createJournal(array $request): array
    {
        $response =  $this->ledgerService->createJournal($request, true);
        return $response[LedgerService::RESPONSE_BODY];
    }

}
