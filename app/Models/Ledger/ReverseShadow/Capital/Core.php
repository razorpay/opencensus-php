<?php

namespace RZP\Models\Ledger\ReverseShadow\Capital;

use Ramsey\Uuid\Uuid;
use RZP\Services\Ledger as LedgerService;
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
        $moneyParams = [];

        $moneyParams[Constants::LEDGER_ONDEMAND_SETTLEMENT_AMOUNT] = strval($amount);

        $moneyParams[Constants::LEDGER_ONDEMAND_SETTLEMENT_FEE] = strval($fee-$tax);

        $moneyParams[Constants::LEDGER_ONDEMAND_SETTLEMENT_TAX] = strval($tax);

        return $moneyParams;
    }

}
