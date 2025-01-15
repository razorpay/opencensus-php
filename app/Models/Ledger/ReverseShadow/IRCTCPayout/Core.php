<?php

namespace RZP\Models\Ledger\ReverseShadow\IRCTCPayout;

use Ramsey\Uuid\Uuid;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Services\Ledger as LedgerService;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant as Merchant;

class Core extends Base\Core
{
    protected $merchant;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Asynchronously creates a ledger journal entry for a specified transaction event.
     *
     * This method prepares the payload required for creating a ledger journal entry
     * and saves it to the ledger outbox for processing. The payload includes essential
     * details such as the merchant ID, currency, transaction amount, event details,
     * and timestamps. An idempotency key is generated to ensure uniqueness of the transaction.
     *
     * @param array $data An associative array containing transaction details:
     *                    - 'merchant_id' (string): Unique identifier for the merchant.
     *                    - 'currency' (string): The currency of the transaction.
     *                    - 'amount' (string): The transaction amount.
     *                    - 'transactor_id' (string): Unique identifier of the transactor.
     * @param string $event The event name associated with the ledger transaction.
     *
     * @return void
     */
    public function createLedgerJournalAsync(array $data, string $event): void {
        $transactorId = (string) $data['transactor_id'];

        $journalPayload = [
            LedgerConstants::MERCHANT_ID => (string) $data['merchant_id'],
            LedgerConstants::CURRENCY => $data['currency'],
            LedgerConstants::MONEY_PARAMS => [
                LedgerConstants::AMOUNT => (string) $data['amount'],
            ],
            LedgerConstants::TRANSACTOR_ID => (string) $data['transactor_id'],
            LedgerConstants::TRANSACTOR_EVENT => $event,
            LedgerConstants::TRANSACTION_DATE => $data['transactor_date'],
            LedgerConstants::LEDGER_INTEGRATION_MODE       => LedgerConstants::REVERSE_SHADOW,
            LedgerConstants::IDEMPOTENCY_KEY               => Uuid::uuid1(),
            LedgerConstants::TENANT                        => LedgerConstants::TENANT_PG,
        ];


        $payloadName = $this->getPayloadName($transactorId, $event);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $journalPayload);

        $this->saveToLedgerOutbox($outboxPayload, $event);
    }

    /**
     * Retrieves the headers required for making requests to the PG<>CLS Ledger service.
     * Includes the tenant header and an idempotency key for ensuring unique requests.
     *
     * @return array The headers array for the PG Ledger service.
     */
    public function getPGLedgerHeaders(): array
    {
        return [
            LedgerService::LEDGER_TENANT_HEADER    => FeatureConstants::TENANT_PG,
            LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()
        ];
    }

    /**
     * Generates the payload for fetching accounts by merchant ID and fund account type.
     * The payload includes the merchant ID and details about the entities and their account types.
     *
     * @param string $merchantId The unique identifier for the merchant.
     * @param string $fundAccountType The type of fund account to fetch.
     *
     * @return array The payload for the fetch accounts request.
     */
    public function fetchAccountsByEntitiesAndMerchantIDPayload(string $merchantId, string $fundAccountType): array
    {
        return [
            FeatureConstants::MERCHANT_ID => $merchantId,
            FeatureConstants::ENTITIES => [
                [
                    FeatureConstants::ACCOUNT_TYPE => [FeatureConstants::PAYABLE],
                    FeatureConstants::FUND_ACCOUNT_TYPE => [$fundAccountType]
                ]
            ]
        ];
    }

    /**
     * Parses the response from the PG Ledger service to extract the balance for a single merchant.
     * The method identifies the appropriate account using account type and fund account type.
     *
     * @param array $response The response array from the PG Ledger service.
     *
     * @return float The extracted balance for the merchant. Returns 0.0 if no matching account is found.
     */
    public function parseBalanceResponseForSingleMerchant(array $response):float
    {
        $accounts = $response['accounts'] ?? [];

        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                $entities = $account['entities'] ?? [];
                $accountTypes = $entities['account_type'] ?? [];
                $fundAccountTypes = $entities['fund_account_type'] ?? [];

                if (in_array(FeatureConstants::PAYABLE, $accountTypes) &&
                    (in_array(FeatureConstants::MERCHANT_BALANCE, $fundAccountTypes) || in_array(LedgerConstants::RDS_BALANCE, $fundAccountTypes))) {
                    return floatval($account['balance']);
                }
            }
        }

        return 0.0;
    }

    /**
     * Fetches the CLS (Central Ledger) balance for a given merchant and fund account type.
     * It constructs the request payload and headers, interacts with the LedgerService to
     * fetch the balance, and parses the response for the merchant's balance.
     * If an error occurs during the process, it logs the exception and returns a default balance of 0.0.
     *
     * @param string $merchantId The unique identifier for the merchant.
     * @param string $fundAccountType The type of fund account for which the balance is being fetched.
     *
     * @return float The balance of the specified merchant and fund account type. Returns 0.0 if an error occurs.
     */
    public function getCLSBalance(string $merchantId, string $fundAccountType):float
    {
        $clsBalanceRequestPayload = $this->fetchAccountsByEntitiesAndMerchantIDPayload($merchantId, $fundAccountType);
        $clsBalanceRequestHeaders = $this->getPGLedgerHeaders();

        try {
            $response = $this->app['ledger']->fetchAccountsByEntitiesAndMerchantID($clsBalanceRequestPayload, $clsBalanceRequestHeaders, true);
            return $this->parseBalanceResponseForSingleMerchant($response['body']);
        }
        catch (\Throwable $e) {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_ACCOUNT_FETCH_MERCHANT_BALANCE_ERROR,
                []);
        }
        return 0.0;
    }

    /**
     * Checks if the merchant is IRCTC and PG ledger reverse shadow flag is enabled
     *
     * @param Merchant\Entity $merchant The payout object
     * @return bool True if eligible, false otherwise.
     */
    public function isIrctcPGLedgerReverseShadowEnabled(Merchant\Entity $merchant): bool
    {
        return in_array($merchant->getMerchantId(), LedgerConstants::IRCTC_MIDS) &&
            $merchant->isFeatureEnabled(FeatureConstants::PG_LEDGER_REVERSE_SHADOW);
    }

}
