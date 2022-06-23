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
use RZP\Models\Bank\Name as BankName;
use RZP\Exception\BadRequestException;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Admin\Service as AdminService;

class Service extends Base\Service
{
    const INTERNAL_ENTITY_CREATE_MUTEX = 'internal_entity_create_%s';
    const MUTEX_LOCK_TIMEOUT           = 30;
    const MERCHANT_ID                  = 'merchant_id';
    const ACCOUNT_NUMBER               = 'account_number';
    const TRANSACTOR_ID                = 'transactor_id';
    const TRANSACTOR_EVENT             = 'transactor_event';
    const TRANSACTOR_EVENT_NAME        = 'inter_account_credit_processed';

    const STATUS_EXPECTED              = 'expected';
    const STATUS_RECEIVED              = 'received';
    const STATUS_FAILED                = 'failed';

    const TYPE_CREDIT                  = 'credit';
    const TENANT                       = 'tenant';
    const X                            = 'X';
    const TEST_PAYOUT_REMARK           = 'test_payout';

    protected $ledgerService;

    public function __construct()
    {
        parent::__construct();

        $this->ledgerService = $this->app['ledger'];
    }

    public function createOnPayout(Payout\Entity $payout, bool $isTestPayout = false): array
    {
        $this->trace->info(TraceCode::INTERNAL_CREATE_ON_PAYOUT_INPUT_DATA, [
            Payout\Entity::ID          => $payout->getPublicId(),
            Payout\Entity::AMOUNT      => $payout->getAmount(),
            Payout\Entity::BASE_AMOUNT => $payout->getBaseAmount(),
            Payout\Entity::UTR         => $payout->getUtr(),
            Payout\Entity::CURRENCY    => $payout->getCurrency(),
            Payout\Entity::TYPE        => self::TYPE_CREDIT,
            Payout\Entity::UPDATED_AT  => $payout->getUpdatedAt(),
            Payout\Entity::MODE        => $payout->getMode(),
            Constants::IS_TEST_PAYOUT  => $isTestPayout,
        ]);

        $remarks        = null;
        $bankName       = null;
        $beneMerchantId = null;

        if ($isTestPayout === true)
        {
            list($isBeneWhitelisted, $beneMerchantId) = $this->getBeneMerchantIdIfBeneficiaryAccountIsWhitelisted($payout);

            $remarks = self::TEST_PAYOUT_REMARK;
        }
        else
        {
            list($bankName, $beneMerchantId) = $this->getBeneBankNameAndMerchantIdIfBeneficiaryAccountIsWhitelisted($payout);
        }

        if (empty($beneMerchantId) === true)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_MERCHANT_NOT_FOUND);
        }

        // create an internal entity
        return $this->create([
                                 Entity::AMOUNT           => $payout->getAmount(),
                                 Entity::BASE_AMOUNT      => $payout->getBaseAmount(),
                                 Entity::UTR              => $payout->getUtr(),
                                 Entity::MODE             => $payout->getMode(),
                                 Entity::ENTITY_ID        => $payout->getId(),
                                 Entity::ENTITY_TYPE      => $payout->getEntity(),
                                 Entity::BANK_NAME        => $bankName,
                                 Entity::CURRENCY         => $payout->getCurrency(),
                                 Entity::TYPE             => self::TYPE_CREDIT,
                                 Entity::TRANSACTION_DATE => $payout->getUpdatedAt(),
                                 Entity::MERCHANT_ID      => $beneMerchantId,
                                 Entity::REMARKS          => $remarks,
                             ]);
    }

    public function create(array $input): array
    {
        // create internal entity
        $internal = new Entity();

        // build internal entity
        $internal->build($input);

        $this->trace->info(TraceCode::INTERNAL_CREATE_INPUT_DATA, $input);

        // check if entity_id & entity_type already exists for any internal entity
        $response = $this->repo->internal->fetchByEntityIDAndType($input[Entity::ENTITY_ID], $input[Entity::ENTITY_TYPE]);
        if ($response !== null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ENTITY_ALREADY_EXISTS);
        }

        // take mutex lock on the entity_id
        $mutex = App::getFacadeRoot()['api.mutex'];
        $mutexKey = sprintf(self::INTERNAL_ENTITY_CREATE_MUTEX, $input[Entity::ENTITY_ID]);
        $mutex->acquireAndRelease($mutexKey, function() use ($input, $internal){

            $internal[Entity::STATUS] = self::STATUS_EXPECTED;
            $this->repo->saveOrFail($internal);

        }, self::MUTEX_LOCK_TIMEOUT, ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);

        $this->trace->info(TraceCode::INTERNAL_ENTITY_CREATED, $internal->toArray());

        return $internal->toArray();
    }

    public function failOnPayoutReversal(Payout\Entity $payout): array
    {
        $this->trace->info(TraceCode::INTERNAL_FAIL_ON_PAYOUT_REVERSAL_INPUT_DATA, [
            Payout\Entity::ID => $payout->getPublicId(),
        ]);

        // fetch internal entity from the utr
        $internal = $this->repo->internal->fetchByEntityIDAndType($payout->getId(), $payout->getEntity());
        if ($internal === null)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ENTITY_NOT_FOUND);
        }

        return $this->fail($internal->getId());
    }

    public function fail(string $id): array
    {
        $this->trace->info(TraceCode::INTERNAL_FAIL_INPUT_DATA, [
            Entity::ID => $id,
        ]);

        // fetch internal entity from the id
        $internal = $this->repo->internal->find($id);
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

    public function reconcile(string $id): array
    {
        // fetch internal entity from the id
        $internal = $this->repo->internal->find($id);
        if ($internal == null)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ENTITY_NOT_FOUND);
        }

        $internal[Entity::RECONCILED_AT]  = time();
        $this->repo->saveOrFail($internal);

        return $internal->toArray();
    }

    public function receive(string $id): array
    {
        // fetch internal entity from the id
        $internal = $this->repo->internal->find($id);
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
            self::TENANT             => self::X,
        ]);

        // update the internal entity with journal_id
        $internal[Entity::TRANSACTION_ID] = $journal[Base\UniqueIdEntity::ID];
        $internal[Entity::STATUS]         = self::STATUS_RECEIVED;
        $this->repo->saveOrFail($internal);

        return $internal->toArray();
    }

    private function createJournal(array $request): array
    {
        $response =  $this->ledgerService->createJournal($request, true);
        return $response[LedgerService::RESPONSE_BODY];
    }

    public function getBeneMerchantIdIfBeneficiaryAccountIsWhitelisted(Payout\Entity $payout)
    {
        $beneAccount = $payout->fundAccount->account;

        if ($beneAccount === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ACCOUNT_NOT_FOUND);
        }

        $beneAccountType      = $beneAccount->getEntity();
        $internalTestAccounts = (new AdminService())->getConfigKey(['key' => ConfigKey::RZP_INTERNAL_TEST_ACCOUNTS]);

        foreach ($internalTestAccounts as $testAccount)
        {
            if ($testAccount[Constants::ACCOUNT_TYPE] === $beneAccountType)
            {
                if ($testAccount[Constants::RZP_ENTITY] !== Constants::RZP_ENTITY_RZPX)
                {
                    continue;
                }

                if ($beneAccountType === Constants::VPA and
                    $beneAccount->getAddress() === $testAccount[Constants::ADDRESS])
                {
                    return [true, $testAccount[self::MERCHANT_ID]];
                }

                if ($beneAccountType === Constants::BANK_ACCOUNT and
                    $beneAccount->getAccountNumber() === $testAccount[self::ACCOUNT_NUMBER])
                {
                    return [true, $testAccount[self::MERCHANT_ID]];
                }
            }
        }

        return [false, null];
    }

    public function getBeneBankNameAndMerchantIdIfBeneficiaryAccountIsWhitelisted(Payout\Entity $payout)
    {
        // based on the payout_id, the beneficiary's account number has to be identified.
        // account number is fetched by payout -> fund_account -> bank_account
        // get account_id from fund account using id
        $bankName       = null;
        $beneMerchantId = null;

        $fundAccount = $this->repo->fund_account->find($payout->getFundAccountId(), [FundAccount\Entity::ACCOUNT_ID]);

        if (empty($fundAccount) === true)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ACCOUNT_NOT_FOUND);
        }

        // get account_number from bank account using id
        $bankAccount = $this->repo->bank_account->find($fundAccount->getAccountId(), [BankAccount\Entity::ACCOUNT_NUMBER, BankAccount\Entity::IFSC_CODE]);
        if (empty($bankAccount) === true)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ACCOUNT_NOT_FOUND);
        }

        // fetch bank_name for the given payout
        // payout -> fund_account -> bank_account -> ifsc_code
        $ifscCode = $bankAccount->getIfscCode();
        // ifsc_code -> bank_name
        $bankName = (new BankName)->getName($ifscCode);

        // RZP_INTERNAL_ACCOUNTS contains list of internal accounts belonging to Razorpay
        // Check if the account belongs to RZP Internal accounts and it's an RZPX Account
        $rzpInternalAccounts = (new AdminService)->getConfigKey(['key' => ConfigKey::RZP_INTERNAL_ACCOUNTS]);
        for ($i = 0; $i < count($rzpInternalAccounts); $i++)
        {
            if (isset($rzpInternalAccounts[$i][self::ACCOUNT_NUMBER])
                && $rzpInternalAccounts[$i][self::ACCOUNT_NUMBER] === $bankAccount->getAccountNumber()
                && $rzpInternalAccounts[$i][Constants::RZP_ENTITY] === Constants::RZP_ENTITY_RZPX)
            {
                $beneMerchantId = $rzpInternalAccounts[$i][self::MERCHANT_ID];
                break;
            }
        }

        return [$bankName, $beneMerchantId];
    }
}
