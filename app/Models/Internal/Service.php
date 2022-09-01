<?php

namespace RZP\Models\Internal;

use App;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\ConfigKey;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Bank\Name as BankName;
use RZP\Exception\BadRequestException;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Transaction\Processor\Ledger\Internal as InternalLedgerProcessor;

class Service extends Base\Service
{
    const INTERNAL_ENTITY_CREATE_MUTEX = 'internal_entity_create_%s';
    const MUTEX_LOCK_TIMEOUT           = 30;
    const MERCHANT_ID                  = 'merchant_id';
    const ACCOUNT_NUMBER               = 'account_number';
    const VPA                          = 'vpa';
    const TRANSACTOR_ID                = 'transactor_id';
    const TRANSACTOR_EVENT             = 'transactor_event';
    const TRANSACTOR_DATE              = 'transactor_date';
    const FTS_INFO                     = 'fts_info';

    const INTER_ACCOUNT_CREDIT_PROCESSED        = 'inter_account_credit_processed';
    const INTER_ACCOUNT_CREDIT_REVERSED         = 'inter_account_credit_reversed';

    const STATUS_EXPECTED              = 'expected';
    const STATUS_RECEIVED              = 'received';
    const STATUS_FAILED                = 'failed';

    const TYPE_CREDIT                  = 'credit';
    const TENANT                       = 'tenant';
    const X                            = 'X';
    const PAYOUT_TYPE                  = 'payout_type';
    const TEST_PAYOUT_REMARK           = 'test_payout';
    const IDENTIFIERS                  = 'identifiers';

    // used for payouts done between nodal accounts.
    const INTER_ACCOUNT_PAYOUT      = 'inter_account_payout';

    // Same as inter account payouts but are used for payouts for testing purpose.
    const TEST_INTER_ACCOUNT_PAYOUT = 'test_inter_account_payout';

    // Represents payout done from PG to RX (bulk payout in ES flow)
    const ONDEMAND_SETTLEMENT_XVA_PAYOUT  = 'ondemand_settlement_xva_payout';

    protected $ledgerService;

    public function __construct()
    {
        parent::__construct();

        $this->ledgerService = $this->app['ledger'];
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

    public function fail(string $id, array $params = []): array
    {
        $this->trace->info(TraceCode::INTERNAL_FAIL_INPUT_DATA, [
            Entity::ID => $id,
            'params' => $params,
        ]);

        // fetch internal entity from the id
        $internal = $this->repo->internal->find($id);
        if ($internal == null)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ENTITY_NOT_FOUND);
        }
        $previousStatus = $internal->getStatus();

        $this->repo->transaction(
            function() use ($internal, $previousStatus, $params) {
                // update the status to failed
                $internal[Entity::STATUS] = self::STATUS_FAILED;
                $this->repo->saveOrFail($internal);

                // check previous state to reverse the receivable entries created.
                // For eg if internal entity corresponding to the payout was marked received
                // and then failed, in that case we will have to reverse the receivable
                // entries in ledger

                if ($previousStatus === self::STATUS_RECEIVED)
                {
                    return $this->reverseReceive($internal, $params);
                }
            });

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

    // The receive function is responsible to create receivable entries at ledger side
    // for the internal entity
    public function receive(string $id, array $params = []): array
    {
        if (isset($params[self::TRANSACTOR_EVENT]) === false)
        {
            $params[self::TRANSACTOR_EVENT] = self::INTER_ACCOUNT_CREDIT_PROCESSED;
        }
        // fetch internal entity from the id
        $internal = $this->repo->internal->find($id);
        if ($internal == null)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ENTITY_NOT_FOUND);
        }

        $ledgerRequest = (new InternalLedgerProcessor())->createLedgerPayloadFromEntity($internal, $params);
        $ledgerRequestHeaders = [
            LedgerService::LEDGER_TENANT_HEADER => self::X
        ];

        $journal = $this->createJournal($ledgerRequest, $ledgerRequestHeaders);

        // update the internal entity with journal_id
        $internal[Entity::TRANSACTION_ID] = $journal[Base\UniqueIdEntity::ID];
        $internal[Entity::STATUS] = self::STATUS_RECEIVED;
        $this->repo->saveOrFail($internal);

        return $internal->toArray();
    }

    public function reverseReceive($internal, array $params = []): array
    {
        if (isset($params[self::TRANSACTOR_EVENT]) === false)
        {
            $params[self::TRANSACTOR_EVENT] = self::INTER_ACCOUNT_CREDIT_REVERSED;
        }
        try
        {
            $ledgerRequest = (new InternalLedgerProcessor())->createLedgerPayloadFromEntity($internal, $params);
            $ledgerRequestHeaders = [
                LedgerService::LEDGER_TENANT_HEADER => self::X
            ];

            $response = $this->ledgerService->createJournal($ledgerRequest, $ledgerRequestHeaders, true);
            $this->trace->info(TraceCode::INTERNAL_REVERSE_RECEIVE_RESPONSE,
                [
                    'response' => $response,
                    'request'  => $ledgerRequest,
                ]);
        }
        catch(\Throwable $ex)
        {
            $this->app['trace']->traceException(
                $ex,
                Trace::ALERT,
                TraceCode::INTERNAL_ENTITY_UPDATE_FAILED);
        }

        return $internal->toArray();
    }

    public function getBeneBankNameAndMerchantIdIfBeneficiaryAccountIsWhitelistedForPayout(Payout\Entity $payout)
    {
        // based on the payout_id, the beneficiary's account number has to be identified.
        // account number is fetched by payout -> fund_account -> bank_account
        // get account_id from fund account using id
        $bankName       = null;
        $beneMerchantId = null;
        $beneAccount              = $payout->fundAccount->account;
        $beneAccountType          = $beneAccount->getEntity();
        $payoutType = Payout\Core::getInterAccountPayoutType($payout);

        // RZP_INTERNAL_ACCOUNTS contains list of internal accounts belonging to Razorpay
        // Check if the account belongs to RZP Internal accounts and it's an RZPX Account
        // RZP_INTERNAL_TEST_ACCOUNTS contains list of internal accounts belonging to Razorpay
        // It contains both bank accounts and Vpas
        $configKey = (($payoutType === self::INTER_ACCOUNT_PAYOUT) or
            ($payoutType === self::ONDEMAND_SETTLEMENT_XVA_PAYOUT)) ? ConfigKey::RZP_INTERNAL_ACCOUNTS :
            ConfigKey::RZP_INTERNAL_TEST_ACCOUNTS;

        $rzpInternalAccounts = (new AdminService)->getConfigKey(['key' => $configKey]);

        if (empty($rzpInternalAccounts) === true)
        {
            $this->trace->error(TraceCode::REDIS_CONFIG_VALUE_EMPTY,
                [
                    'config_key'   => $configKey,
                    'config_value' => $rzpInternalAccounts,
                ]);

            return [$bankName, $beneMerchantId];
        }

        for ($i = 0; $i < count($rzpInternalAccounts); $i++)
        {
            if (($beneAccountType === Constants::BANK_ACCOUNT) and
                (isset($rzpInternalAccounts[$i][self::ACCOUNT_NUMBER]) === true) and
                ($rzpInternalAccounts[$i][Constants::RZP_ENTITY] === Constants::RZP_ENTITY_RZPX) and
                ($rzpInternalAccounts[$i][self::ACCOUNT_NUMBER] === $beneAccount->getAccountNumber()))
            {
                // get account_number from bank account using id
                // fetch bank_name for the given payout
                // payout -> fund_account -> bank_account -> ifsc_code
                $ifscCode = $beneAccount->getIfscCode();
                // ifsc_code -> bank_name
                $bankName = (new BankName)->getName($ifscCode);
                $beneMerchantId = $rzpInternalAccounts[$i][self::MERCHANT_ID];
                break;
            }
            else if (($beneAccountType === Constants::VPA) and
                (isset($rzpInternalAccounts[$i][Constants::ADDRESS]) === true) and
                ($rzpInternalAccounts[$i][Constants::RZP_ENTITY] === Constants::RZP_ENTITY_RZPX) and
                ($rzpInternalAccounts[$i][Constants::ADDRESS] === $beneAccount->getAddress()))
            {
                $beneMerchantId = $rzpInternalAccounts[$i][self::MERCHANT_ID];
                break;
            }
        }

        return [$bankName, $beneMerchantId];
    }

    private function createJournal(array $request, array $headers): array
    {
        $response =  $this->ledgerService->createJournal($request, $headers, true);
        return $response[LedgerService::RESPONSE_BODY];
    }
}
