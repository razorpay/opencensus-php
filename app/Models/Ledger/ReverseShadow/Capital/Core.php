<?php

namespace RZP\Models\Ledger\ReverseShadow\Capital;

use Ramsey\Uuid\Uuid;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\LedgerOutbox\Core as LedgerOutboxCore;
use RZP\Models\Transaction\CreditType;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Pricing\Fee;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Ledger\Constants;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Models\Transaction\Entity;
use RZP\Models\Settlement\Ondemand\Entity as OndemandEntity;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Models\Merchant\Balance\Type;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;

class Core extends Base\Core
{
    protected $merchant;

    use ReverseShadowTrait;

    const NEGATIVE_BALANCE_ALLOWED_PAYMENT_METHODS = [
        Payment\Method::EMANDATE,
        Payment\Method::NACH,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    public function createLedgerEntryForSettlementOndemandProcessedInReverseShadow(OndemandEntity $ondemand)
    {
        $moneyParams = $this->generateMoneyParams($ondemand->getAmount(),$ondemand->getTotalFees(),$ondemand->getTotalTax());

        $transactorId = Constants::LEDGER_ONDEMAND_PROCESSED_TRANSACTOR_ID_PREFIX.$ondemand->getId();

        $transactorEvent = Constants::LEDGER_ONDEMAND_SETTLEMENT_PROCESSED;

        $this->createLedgerEntryInReverseShadow($ondemand, $moneyParams, $transactorId, $transactorEvent);
    }

    public function createLedgerEntryForOndemandReversalInReverseShadow(OndemandPayout\Entity $ondemandPayout, $reversalId)
    {
        $moneyParams = $this->generateMoneyParams($ondemandPayout->getAmount(), $ondemandPayout->getFees(), $ondemandPayout->getTax());

        $transactorId = Constants::LEDGER_ONDEMAND_REVERSED_TRANSACTOR_ID_PREFIX.$reversalId;

        $transactorEvent = Constants::LEDGER_ONDEMAND_SETTLEMENT_REVERSED;

        $this->createLedgerEntryInReverseShadow($ondemandPayout, $moneyParams, $transactorId, $transactorEvent);
    }

    private function createLedgerEntryInReverseShadow(Base\PublicEntity $entity, array $moneyParams, string $transactorId, string $transactorEvent) {
        $merchantCaptureData = array(
            Constants::TRANSACTOR_ID                 => $transactorId,
            Constants::TRANSACTOR_EVENT              => $transactorEvent,
            Constants::MONEY_PARAMS                  => $moneyParams,
            Constants::LEDGER_INTEGRATION_MODE       => Constants::REVERSE_SHADOW,
            Constants::IDEMPOTENCY_KEY               => Uuid::uuid1(),
            Constants::TENANT                        => Constants::TENANT_PG,
        );

        $transactionMessage = $this->generateBaseForJournalEntry($entity, round(millitime()/1000));

        $journalPayload = array_merge($transactionMessage, $merchantCaptureData);

        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $journalPayload);

        $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);
    }

    public function validateBalance(array $input,Merchant\Entity $merchant): array
    {
        $validationResponse = [];

        $ledgerService = $this->app['ledger'];

        $merchantAccountBalances = $this->getMerchantAccountBalance($ledgerService, $merchant->getMerchantId());

        $validationResponse[Constants::MERCHANT_BALANCE] =  $merchantAccountBalances[Constants::MERCHANT_BALANCE];

        if ($input[OndemandEntity::AMOUNT] > $merchantAccountBalances[Constants::MERCHANT_BALANCE])
        {
            $validationResponse[Constants::IS_AMOUNT_VALID] = false;
            return $validationResponse;
        }

        $validationResponse[Constants::IS_AMOUNT_VALID] = true;
        return $validationResponse;

    }

    public function getMerchantAccountBalance($ledgerService, $merchantId): array
    {
        $accountPayload = $this->getAccountBalancePayloadForMerchantBalanceOnly($merchantId);

        $requestHeaders = [
            LedgerService::LEDGER_TENANT_HEADER    => Constants::TENANT_PG,
            LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()
        ];

        $response = $ledgerService->fetchAccountsByEntitiesAndMerchantID($accountPayload, $requestHeaders, true);

        $merchantAccountBalancesList = $response['body']['accounts'];

        return $this->getMerchantAccountBalancesMap($merchantAccountBalancesList);
    }

    private function getAccountBalancePayloadForMerchantBalanceOnly($merchantId) :array
    {
        return [
            Constants::MERCHANT_ID => $merchantId,
            Constants::ENTITIES => [
                // PG Merchant Balance Account
                [
                    Constants::ACCOUNT_TYPE => [Constants::PAYABLE],
                    Constants::FUND_ACCOUNT_TYPE => [Constants::MERCHANT_BALANCE]
                ],
            ],
        ];
    }

    public function fetchBalance($merchant){

        $ledgerService = $this->app['ledger'];

        $merchantAccountBalances = $this->getMerchantAccountBalances($ledgerService, $merchant->getMerchantId());

        return $merchantAccountBalances[Constants::MERCHANT_BALANCE];

    }

    protected function generateMoneyParams($amount, $fee, $tax): array {
        return [
            Constants::LEDGER_ONDEMAND_SETTLEMENT_AMOUNT => (string) ($amount ?? 0),
            Constants::LEDGER_ONDEMAND_SETTLEMENT_FEE => (string) (($fee ?? 0) - ($tax ?? 0)),
            Constants::LEDGER_ONDEMAND_SETTLEMENT_TAX => (string) ($tax ?? 0),
        ];
    }

    private function getTransactionAmountForTransactionType($journalResponse, $transactorType)
    {
        if ($transactorType === "reversal")
        {
            $transactionAmountLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::PAYABLE, Constants::MERCHANT_VA_MERCHANT);

            return $transactionAmountLedgerEntry['amount'];
        }
        else
        {
            $transactionAmountLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::PAYABLE, Constants::MERCHANT_ONDEMAND_SETTLEMENT_LEDGER);

            return $transactionAmountLedgerEntry['amount'];
        }
    }

    public function transformJournalResponseToTransactionEntityBaseForODSForProcessed($journalResponse)
    {
        $transactorPublicId = $journalResponse[Constants::TRANSACTOR_ID];

        $transactorInfo = $this->determineTransactionType($transactorPublicId);

        $transactionType = $transactorInfo[Constants::TYPE];

        $transactorId =  $transactorInfo[Constants::ID];

        $merchantBalanceLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::MERCHANT_BALANCE_FUND_ACCOUNT);

        $transactionAmount = $this->getTransactionAmountForTransactionType($journalResponse,$transactionType);

        $commissionLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::CASH, Constants::ONDEMAND_INCOME);

        $taxBalanceLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::MERCHANT_ONDEMAND_GST);

        $merchant = $this->repo->merchant->findOrFail($merchantBalanceLedgerEntry[Constants::MERCHANT_ID]);

        $credit = $merchantBalanceLedgerEntry[Constants::TYPE] === Constants::ENTRY_TYPE_CREDIT ? $merchantBalanceLedgerEntry[Constants::AMOUNT] : 0;

        $debit = $merchantBalanceLedgerEntry[Constants::TYPE] === Constants::ENTRY_TYPE_DEBIT ? $merchantBalanceLedgerEntry[Constants::AMOUNT] : 0;

        $fees = $commissionLedgerEntry[Constants::AMOUNT] + $taxBalanceLedgerEntry[Constants::AMOUNT];

        $tax =  $taxBalanceLedgerEntry[Constants::AMOUNT];

        $transaction = [
            TransactionEntity::ID               => $journalResponse[Constants::ID],
            TransactionEntity::ENTITY_ID        => $transactorId,
            TransactionEntity::TYPE             => $transactionType,
            TransactionEntity::MERCHANT_ID      => $merchantBalanceLedgerEntry[Constants::MERCHANT_ID],
            TransactionEntity::AMOUNT           => (int) $transactionAmount,
            TransactionEntity::CURRENCY         => $merchantBalanceLedgerEntry[Constants::CURRENCY],
            TransactionEntity::CREDIT           => (int) $credit,
            TransactionEntity::DEBIT            => (int) $debit,
            TransactionEntity::BALANCE          => (int) $merchantBalanceLedgerEntry[Constants::BALANCE],
            TransactionEntity::FEE              => (int) $fees,
            TransactionEntity::TAX              => (int) $tax,
            TransactionEntity::CHANNEL          => $merchant->getChannel(),
            TransactionEntity::CREDITS          => 0,
            TransactionEntity::CREDIT_TYPE      => CreditType::DEFAULT,
            TransactionEntity::BALANCE_ID       => null,
            TransactionEntity::CREATED_AT       => $journalResponse[Constants::CREATED_AT],
            TransactionEntity::UPDATED_AT       => $journalResponse[Constants::UPDATED_AT],
            TransactionEntity::BALANCE_UPDATED  => $merchantBalanceLedgerEntry[Constants::BALANCE_UPDATED],
            TransactionEntity::FEE_BEARER       => Merchant\FeeBearer::NA,
            TransactionEntity::API_FEE          => 0,
            TransactionEntity::MDR              => null,
        ];

        $txn = new TransactionEntity();

        $txn->forceFill($transaction);

        return $txn;
    }

    public function transformJournalResponseToTransactionEntityBaseForReversal($journalResponse)
    {
        $transactorPublicId = $journalResponse[Constants::TRANSACTOR_ID];

        $transactorInfo = $this->determineTransactionType($transactorPublicId);

        $transactionType = $transactorInfo[Constants::TYPE];

        $transactorId =  $transactorInfo[Constants::ID];

        $merchantBalanceLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::MERCHANT_BALANCE_FUND_ACCOUNT);

        $merchant = $this->repo->merchant->findOrFail($merchantBalanceLedgerEntry[Constants::MERCHANT_ID]);

        $credit = $merchantBalanceLedgerEntry[Constants::TYPE] === Constants::ENTRY_TYPE_CREDIT ? $merchantBalanceLedgerEntry[Constants::AMOUNT] : 0;

        $transaction = [
            TransactionEntity::ID               => $journalResponse[Constants::ID],
            TransactionEntity::ENTITY_ID        => $transactorId,
            TransactionEntity::TYPE             => $transactionType,
            TransactionEntity::MERCHANT_ID      => $merchantBalanceLedgerEntry[Constants::MERCHANT_ID],
            TransactionEntity::AMOUNT           => (int) $credit,
            TransactionEntity::CURRENCY         => $merchantBalanceLedgerEntry[Constants::CURRENCY],
            TransactionEntity::CREDIT           => (int) $credit,
            TransactionEntity::DEBIT            => 0,
            TransactionEntity::BALANCE          => (int) $merchantBalanceLedgerEntry[Constants::BALANCE],
            TransactionEntity::FEE              => 0,
            TransactionEntity::TAX              => 0,
            TransactionEntity::CHANNEL          => $merchant->getChannel(),
            TransactionEntity::CREDITS          => 0,
            TransactionEntity::CREDIT_TYPE      => CreditType::DEFAULT,
            TransactionEntity::BALANCE_ID       => null,
            TransactionEntity::CREATED_AT       => $journalResponse[Constants::CREATED_AT],
            TransactionEntity::UPDATED_AT       => $journalResponse[Constants::UPDATED_AT],
            TransactionEntity::BALANCE_UPDATED  => $merchantBalanceLedgerEntry[Constants::BALANCE_UPDATED],
            TransactionEntity::FEE_BEARER       => Merchant\FeeBearer::NA,
            TransactionEntity::API_FEE          => 0,
            TransactionEntity::MDR              => null,
        ];

        $txn = new TransactionEntity();

        $txn->forceFill($transaction);

        return $txn;
    }

    public function determineTransactionType($transactorId) :array
    {
        $transactorIdArr = $this->getTransactorIDArray($transactorId);

        $publicIdPrefix = $transactorIdArr[0];

        $res = [
            LedgerConstants::TRANSACTOR_ID => $transactorId,
            LedgerConstants::ID            => $transactorIdArr[1]
        ];

        switch ($publicIdPrefix)
        {
            case "setlod":
                $res[Constants::TYPE] = "settlement.ondemand";
                return $res;
            case "setlodrvrsl":
                $res[Constants::TYPE] = "reversal";
                return $res;
            default:
                $res[Constants::TYPE] = "";
                return $res;
        }
    }


    private function getTransactorIDArray(string $transactorId): array
    {
        $transactorIdArr = explode('_', $transactorId);

        if(count($transactorIdArr) != 2)
        {
            $this->trace->debug(
                TraceCode::INVALID_TRANSACTOR_ID,
                [
                    LedgerConstants::MESSAGE        => "provide a valid public ID for transactor",
                    LedgerConstants::TRANSACTOR_ID  => $transactorId
                ]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_TRANSACTOR_ID);
        }

        return $transactorIdArr;
    }

}
