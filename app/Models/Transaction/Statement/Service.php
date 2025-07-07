<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Metric;
use RZP\Models\Transaction;
use RZP\Base\ConnectionType;
use RZP\Models\Merchant\Balance;
use RZP\Models\Feature\Constants;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Transaction\Metric as TxnMetric;

/**
 * Class Service
 *
 * @package RZP\Models\Transaction\Statement
 */
class Service extends Transaction\Service
{
    protected $ledgerStatementService;

    public function __construct()
    {
        parent::__construct();

        $this->ledgerStatementService = (new Ledger\Statement\Service());
    }

    public function fetchMultiple(array $input): array
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        $balance = $merchantValidator->validateAndTranslateAccountNumberForBanking($input);

        $dimension = $this->getDimensions();

        $isLatestBalanceRequest = false;

        $requestPayload = [
            "id" => $this->merchant->getId(),
            "experiment_name" =>  Merchant\RazorxTreatment::LEDGER_REVERSE_SHADOW_LATEST_TXN_BALANCE,
            'request_data'  => json_encode(['id' => $this->merchant->getId()])
        ];

        $isExperimentEnabled = (new Merchant\Core())->isSplitzExperimentEnable($requestPayload, Merchant\RazorxTreatment::VARIANT_ENABLE);

        if ($isExperimentEnabled === true)
        {
            if ((empty($input['count']) === false) and $input['count'] == 1)
            {
                $isLatestBalanceRequest = true;
            }
        }

        // Route request to ledger statement if ledger feature is enabled for shared account
        if (($this->merchant->isFeatureEnabled(Constants::LEDGER_REVERSE_SHADOW) === true) and
            ($balance->isAccountTypeShared() === true) and $isLatestBalanceRequest === false)
        {
            // This is to ensure addQueryParamBalanceId is not used thus skipping balance join
            // The above method is dynamically called if input has the param balance id
            if (isset($input['balance_id']) === true)
            {
                unset($input['balance_id']);
            }

            $startTime = millitime();

            $ledger = $this->repo->ledger_statement->fetch($input, $this->merchant->getId(), ConnectionType::RX_DATA_WAREHOUSE_MERCHANT);

            $ledgerDuplicate = $ledger;

            $ledgerIds = $ledgerDuplicate->pluck(Entity::ID)->toArray();

            $this->trace->info(
                TraceCode::LEDGER_STATEMENT_FETCH_MULTIPLE_RESPONSE,
                [
                    'transaction_ids' => $ledgerIds,
                ]
            );

            $this->trace->histogram(
                TxnMetric::TRANSACTION_VA_REQUEST_LATENCY_MILLISECONDS,
                millitime() - $startTime,
                $dimension);

            return $ledger->toArrayPublic();
        }

        // Route request to BAS if DA ledger feature is enabled and acc is of type direct
        if ($balance->isAccountTypeDirect() === true)
        {
            $this->trace->info(
                TraceCode::DRIVING_ACCOUNT_STATEMENT_FOR_DA_VIA_BAS,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'balance_id' => $balance->getId(),
                    'balance_type' => $balance->getType(),
                    'balance_account_type' => $balance->getAccountType(),
                ]
            );

            return $this->repo->direct_account_statement
                ->fetch($input, $this->merchant->getId(), ConnectionType::SLAVE)->toArrayPublic();
        }

        $this->trace->info(TraceCode::TRANSACTION_STATEMENT_FETCH_MULTIPLE_FALLBACK_FLOW,
            [
                'input' => $input,
                'merchant_id' => $this->merchant->getId(),
            ]);

        /** @var PublicCollection $transactions */
        $transactions = $this->repo->statement->fetch($input, $this->merchant->getId(), ConnectionType::DATA_WAREHOUSE_MERCHANT);

        return $transactions->toArrayPublic();
    }

    public function fetch(string $id, array $input): array
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        $merchantValidator->validateBusinessBankingActivated();

        // If the transaction ID passed in the query does contain 'bas_' prefix
        // Then there is no need to call the ledger microservice to fetch the txn entity.
        // Also, we use a new method to construct a txn response using BAS entity.
        // We are assuming here that the ID passed here must have come from some payout or acc stmt response.
        // This means that an entity must have gotten linked to the BAS entity.
        // There is a very small edge case where a requester randomly passes a BAS ID which exists in our systems
        // but has not been linked to an entity. We are not sending invalid ID error in these edge cases.
        if (strpos($id, DirectAccount\Statement\Entity::getSign()) !== false)
        {
            $isReadCutoffMerchant = $this->isReadCutoffEnabled($this->merchant->getId());

            if ($isReadCutoffMerchant === true) {

                $dimension = $this->getDimensions();

                try {

                    return $this->redirectSearchStatementToXAS([
                        'merchant_id' => $this->merchant->getId(),
                        'id' => $id
                    ], $dimension);

                } catch (\Exception $e) {
                    $this->trace->traceException($e, null,  TraceCode::ACCOUNT_STATEMENTS_READ_CUTOFF_FAILURE, [
                        'input' => $input,
                        'merchant_id' => $this->merchant->getId()
                    ]);

                    $this->trace->info(TraceCode::ACCOUNT_STATEMENTS_READ_CUTOFF_FALLBACK_FLOW, [
                        'merchant_id' => $this->merchant->getId()
                    ]);

                    // Count fallback to BAS
                    $this->trace->count(TxnMetric::READ_CUTOFF_XAS_FALLBACK_TOTAL, $dimension);
                }
            }

            return $this->repo->direct_account_statement
                ->fetchByPublicIdAndMerchantForTransactionsBasedOnBasId($id, $this->merchant, $input)
                ->toArrayPublic();
        }

        $ledgerFlow = false;

        // In case feature flag is added to the merchant, only in that case ledger service will be called.
        // Since here depending on the transaction, we cannot find whether this transaction is for VA or CA,
        // without depending on the transaction table, so only merchant feature flag check is enough.
        if ($this->merchant->isFeatureEnabled(Constants::LEDGER_REVERSE_SHADOW) === true)
        {
            $ledgerTransaction = $this->ledgerStatementService->fetchByIdFromLedger($id);

            // Only return ledger response if ledger didn't return any error, else return from API.
            if (empty($ledgerTransaction) === false)
            {
                $this->trace->info(
                    TraceCode::LEDGER_JOURNAL_FETCH_TRANSACTION_SUCCESS,
                    [
                        "id" => $id,
                    ]);
                return $ledgerTransaction;
            }
            $ledgerFlow = true;
        }

        $this->trace->info(TraceCode::TRANSACTION_STATEMENT_FETCH_FALLBACK_FLOW,
            [
                'id' => $id,
                'ledger_flow' => $ledgerFlow,
                'merchant_id' => $this->merchant->getId(),
            ]);

        /** @var Entity $transaction */
        $transaction = $this->repo
                            ->statement
                            ->findByPublicIdAndMerchantForBankingBalance(
                                $id, $this->merchant, $input, ConnectionType::DATA_WAREHOUSE_MERCHANT);

        return $transaction->toArrayPublic();
    }

    /*
     * TODO: VendorPayment Internal Auth Also Uses this method -->
     *       switching merchants between these feature flags could lead to a potential data miss,
     *       and should be handled carefully later.
     */
    // Temporary route for forcing the index for X Dashboard Requests for Account statement
    public function fetchMultipleForBanking($input)
    {
        $balance = $this->deduceMerchantBalanceFromInput($input);

        $dimension = $this->getDimensions();

        $isLatestBalanceRequest = false;
        $requestPayload = [
            "id" => $this->merchant->getId(),
            "experiment_name" =>  Merchant\RazorxTreatment::LEDGER_REVERSE_SHADOW_LATEST_TXN_BALANCE,
            'request_data'  => json_encode(['id' => $this->merchant->getId()])
        ];

        $isExperimentEnabled = (new Merchant\Core())->isSplitzExperimentEnable($requestPayload, Merchant\RazorxTreatment::VARIANT_ENABLE);

        if ($isExperimentEnabled === true)
        {
            if ((empty($input['count']) === false) and $input['count'] == 1)
            {
                $isLatestBalanceRequest = true;
            }
        }

        // Route request to ledger statement if ledger read feature is enabled
        if (($balance->isAccountTypeShared() === true) and
            ($this->merchant->isFeatureEnabled(Constants::LEDGER_REVERSE_SHADOW) === true) and $isLatestBalanceRequest === false)
        {
            $startTime = millitime();

            $this->trace->count(TxnMetric::TRANSACTION_VA_REQUEST_TOTAL, $dimension);

            // This is to ensure addQueryParamBalanceId is not used thus skipping balance join
            // The above method is dynamically called if input has the param balance id
            if (isset($input['balance_id']) === true)
            {
                unset($input['balance_id']);
            }

            $ledger = $this->repo->ledger_statement->fetch($input,
                                                           $this->merchant->getId(),
                                                           ConnectionType::RX_DATA_WAREHOUSE_MERCHANT);

            $response = $ledger->toArrayPublic();

            $this->trace->info(
                TraceCode::FETCH_MULTIPLE_FOR_TRANSACTIONS_RESPONSE,
                [
                    'merchant_id'          => $this->merchant->getId(),
                    'balance_id'           => $balance->getId(),
                    'balance_type'         => $balance->getType(),
                    'balance_account_type' => $balance->getAccountType(),
                    'connection'           => 'ledger-tidb',
                    'response'             => $response,
                ]
            );

            $this->trace->histogram(
                TxnMetric::TRANSACTION_VA_REQUEST_LATENCY_MILLISECONDS,
                millitime() - $startTime,
                $dimension);

            return $response;
        }

        // Route request to BAS if DA ledger feature is enabled and acc is of type direct
        if ($balance->isAccountTypeDirect() === true)
        {
            $this->trace->count(TxnMetric::TRANSACTION_CA_REQUEST_TOTAL, $dimension);

            $startTime = millitime();

            $this->trace->info(
                TraceCode::DRIVING_ACCOUNT_STATEMENT_FOR_DA_VIA_BAS,
                [
                    'merchant_id'          => $this->merchant->getId(),
                    'balance_id'           => $balance->getId(),
                    'balance_type'         => $balance->getType(),
                    'balance_account_type' => $balance->getAccountType(),
                ]
            );

            $response = $this->repo->direct_account_statement
                ->fetch($input, $this->merchant->getId(), ConnectionType::SLAVE, true)->toArrayPublic();

            $endTime = millitime() - $startTime;

            $this->trace->info(
                TraceCode::FETCH_MULTIPLE_FOR_CA_TRANSACTIONS_RESPONSE,
                [
                    'merchant_id'          => $this->merchant->getId(),
                    'balance_id'           => $balance->getId(),
                    'balance_type'         => $balance->getType(),
                    'balance_account_type' => $balance->getAccountType(),
                    'connection'           => 'mysql-slave',
                    'response'             => $response,
                    'duration_ms'          => $endTime,
                ]
            );

            $this->trace->histogram(
                TxnMetric::TRANSACTION_CA_REQUEST_LATENCY_MILLISECONDS,
                $endTime,
                $dimension);

            return $response;
        }

        // metrics for legacy
        $startTime = millitime();

        $this->trace->count(TxnMetric::TRANSACTION_LEGACY_REQUEST_TOTAL, $dimension);

        /** @var PublicCollection $transactions */
        $transactions = $this->repo->statement->setBaseQueryAndFetchForBanking($input,
                                                                               $this->merchant->getId(),
                                                                               null,
                                                                               $balance);

        $response = $transactions->toArrayPublic();

        $this->trace->info(
            TraceCode::FETCH_MULTIPLE_FOR_TRANSACTIONS_RESPONSE,
            [
                'merchant_id'          => $this->merchant->getId(),
                'balance_id'           => $balance->getId(),
                'balance_type'         => $balance->getType(),
                'balance_account_type' => $balance->getAccountType(),
                'connection'           => 'default',
                'response'             => $response,
            ]
        );

        $this->trace->histogram(
            TxnMetric::TRANSACTION_LEGACY_REQUEST_LATENCY_MILLISECONDS,
            millitime() - $startTime,
            $dimension);

        return $response;
    }

    protected function isExperimentEnabled($experiment)
    {
        $app = $this->app;

        $variant = $app['razorx']->getTreatment($this->merchant->getId(),
            $experiment, $app['basicauth']->getMode() ?? Mode::LIVE);

        return ($variant === 'on');
    }

    /**
     * @return array
     */
    private function getDimensions(): array
    {
        $dimensions = [];

        $dimensions[Metric::LABEL_RZP_INTERNAL_APP_NAME] = app('request.ctx')->getInternalAppName()
                                                           ?? Metric::LABEL_NONE_VALUE;

        return $dimensions;
    }

    /**
     * @param array $input
     *
     */
    private function deduceMerchantBalanceFromInput(array &$input)
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        /** @var Balance\Entity $balance */
        $balance = null;

        $balanceId = $input[Balance\Entity::BALANCE_ID];

        if ($balanceId != null)
        {
            $balance = $this->repo->balance->findOrFailById($balanceId);
            array_pull($input, Balance\Entity::ACCOUNT_NUMBER);
        }
        else
        {
            $balance = $merchantValidator->validateAndTranslateAccountNumberForBanking($input);
        }

        return $balance;
    }

    /**
     * @throws \Exception
     */
    private function redirectSearchStatementToXAS(array $input, $dimension): array
    {
        $this->trace->info(TraceCode::DEBUG_LOGGING, [
            "account_statements_xas_redirect" => true,
            "input" => $input
        ]);

        $xasService = $this->app['x_account_statements'];

        $startTimeMs = round(microtime(true) * 1000);

        // Count all XAS attempts for account statements read cutoff
        $this->trace->count(TxnMetric::READ_CUTOFF_XAS_REQUEST_TOTAL, $dimension);

        $xasResponse = $xasService->getAccountStatements($input);

        $endTimeMs = round(microtime(true) * 1000);

        // Track latency for XAS requests
        $this->trace->histogram(
            TxnMetric::READ_CUTOFF_XAS_REQUEST_LATENCY_MILLISECONDS,
            $endTimeMs - $startTimeMs,
            $dimension
        );

        // Transform XAS response to maintain API response structure
        $transformedResponse = $this->transformXASResponse($xasResponse, $input);

        $this->trace->info(
            TraceCode::STATEMENT_SEARCH_XAS_SERVICE_SUCCESS,
            [
                'input' => $input,
                'merchant_id' => $this->merchant->getId(),
                'original_response_count' => count($xasResponse['statements'] ?? []),
                'transformed_response_count' => isset($transformedResponse['count']) ? $transformedResponse['count'] : (isset($transformedResponse['id']) ? 1 : 0),
            ]
        );

        // Count all XAS success responses for account statements read cutoff
        $this->trace->count(TxnMetric::READ_CUTOFF_XAS_SUCCESS_TOTAL, $dimension);

        return $transformedResponse;
    }

    /**
     * Transform XAS service response to maintain the existing /transactions_banking API response structure
     *
     * @param array $xasResponse
     * @param array $input Original input parameters to determine response format
     * @return array
     * @throws \Exception
     */
    private function transformXASResponse(array $xasResponse, array $input = []): array
    {
        // Check if the response contains statements
        if (!isset($xasResponse['statements']) || !is_array($xasResponse['statements'])) {
            $xasServiceException = new \Exception("XAS service error: missing statements");

            // Log the response and throw exception to caller
            $this->trace->traceException($xasServiceException, null, TraceCode::STATEMENT_SEARCH_XAS_SERVICE_ERROR, [
                'response' => $xasResponse,
            ]);

            throw $xasServiceException;
        }

        $statements = $xasResponse['statements'];

        // If id is provided in input, we expect a single transaction - return it directly
        if (isset($input['id']) && !empty($input['id'])) {
            if (count($statements) === 0) {
                return []; // Return empty array if no statement found
            }

            $statement = $statements[0]; // Get the first (and should be only) statement

            $transformedItem = [
                'id' => 'txn_' . $statement['id'], // Prefix with txn_ to match API format
                'entity' => 'transaction',
                'account_number' => $statement['account_number'] ?? '',
                'amount' => abs((int) ($statement['amount'] ?? 0)), // Always positive amount
                'currency' => $statement['currency'] ?? 'INR',
                'credit' => $statement['type'] === 'credit' ? abs((int) ($statement['amount'] ?? 0)) : 0,
                'debit' => $statement['type'] === 'debit' ? abs((int) ($statement['amount'] ?? 0)) : 0,
                'balance' => (int) ($statement['balance'] ?? 0),
                'created_at' => (int) ($statement['posted_date'] ?? $statement['created_at'] ?? time()),
            ];

            // Transform source based on entity_type
            $source = $this->transformXASSourceEntity($statement);
            if (!empty($source)) {
                $transformedItem['source'] = $source;
            }

            return $transformedItem; // Return single transaction object directly
        }

        // Otherwise, process multiple transactions and return collection format
        $transformedItems = [];

        foreach ($statements as $statement) {
            $transformedItem = [
                'id' => 'txn_' . $statement['id'], // Prefix with txn_ to match API format
                'entity' => 'transaction',
                'account_number' => $statement['account_number'] ?? '',
                'amount' => abs((int) ($statement['amount'] ?? 0)), // Always positive amount
                'currency' => $statement['currency'] ?? 'INR',
                'credit' => $statement['type'] === 'credit' ? abs((int) ($statement['amount'] ?? 0)) : 0,
                'debit' => $statement['type'] === 'debit' ? abs((int) ($statement['amount'] ?? 0)) : 0,
                'balance' => (int) ($statement['balance'] ?? 0),
                'created_at' => (int) ($statement['posted_date'] ?? $statement['created_at'] ?? time()),
            ];

            // Transform source based on entity_type
            $source = $this->transformXASSourceEntity($statement);
            if (!empty($source)) {
                $transformedItem['source'] = $source;
            }

            $transformedItems[] = $transformedItem;
        }

        return [
            'entity' => 'collection',
            'count' => count($transformedItems),
            'items' => $transformedItems
        ];
    }

    /**
     * Transform XAS statement to source entity format
     *
     * @param array $statement
     * @return array
     */
    private function transformXASSourceEntity(array $statement): array
    {
        $entityType = $statement['entity_type'] ?? '';
        $entityId = $statement['entity_id'] ?? '';

        $source = [
            'id' => $entityId,
            'entity' => $entityType,
            'amount' => (int) ($statement['amount'] ?? 0),
            'utr' => $statement['utr'] ?? '',
            'created_at' => (int) ($statement['posted_date'] ?? $statement['created_at'] ?? time()),
        ];

        $source['id'] = $this->mapPublicID($entityType).$source['id'];

        // Add entity-specific fields based on entity_type
        switch ($entityType) {
            case 'payout':
                // Fetch actual payout details from database
                $payoutDetails = $this->fetchPayoutDetails("pout_".$entityId);
                if ($payoutDetails) {
                    $source = array_merge($source, [
                        'fund_account_id' => "fa_".$payoutDetails['fund_account_id'] ?? null,
                        'notes' => $payoutDetails['notes'] ?? [],
                        'fees' => (int) ($payoutDetails['fees'] ?? 0),
                        'tax' => (int) ($payoutDetails['tax'] ?? 0),
                        'status' => $payoutDetails['status'] ?? 'processed',
                        'mode' => $payoutDetails['mode'] ?? $this->getModeFromDescription($statement['description'] ?? ''),
                        'fee_type' => $payoutDetails['fee_type'] ?? null,
                    ]);
                } else {
                    // Fallback if payout not found
                    $source = array_merge($source, [
                        'fund_account_id' => null,
                        'notes' => [],
                        'fees' => 0,
                        'tax' => 0,
                        'status' => 'processed',
                        'mode' => $this->getModeFromDescription($statement['description'] ?? ''),
                        'fee_type' => null,
                    ]);
                }
                break;

            case 'payout_reversal':
                $source['entity'] = 'reversal';
                $source = array_merge($source, [
                    'payout_id' => "pout_".$entityId,
                ]);
                // Fetch Payout details if payout ID comes from XAS
                $payoutDetails = $this->fetchPayoutDetails("pout_".$entityId);
                if ($payoutDetails){
                    $source = array_merge($source, [
                        'fee' => (int) ($payoutDetails['fees'] ?? 0),
                        'tax' => (int) ($payoutDetails['tax'] ?? 0),
                    ]);
                }
                break;

            case 'external':
                $source['id'] = "ext_".$statement['id'];
                break;

            default:
                break;
        }

        return $source;
    }

    /**
     * Fetch payout details from database
     *
     * @param string $payoutId
     * @return array|null
     */
    private function fetchPayoutDetails(string $payoutId): ?array
    {
        try {
            $payout = $this->repo->payout->findByPublicId($payoutId);
            if ($payout) {
                return [
                    'fund_account_id' => $payout->getFundAccountId(),
                    'notes' => $payout->getNotes() ?? [],
                    'fees' => $payout->getFees(),
                    'tax' => $payout->getTax(),
                    'status' => $payout->getStatus(),
                    'mode' => $payout->getMode(),
                    'fee_type' => $payout->getFeeType(),
                ];
            }
        } catch (\Throwable $e) {
            $this->trace->traceException($e, null, TraceCode::PAYOUT_FETCH_FAILED_FOR_XAS_RESPONSE, [
                'payout_id' => $payoutId
            ]);
        }

        return null;
    }

    /**
     * Extract payment mode from transaction description
     *
     * @param string $description
     * @return string
     */
    private function getModeFromDescription(string $description): string
    {
        $description = strtoupper($description);

        if (str_contains($description, 'NEFT')) return 'NEFT';
        if (str_contains($description, 'RTGS')) return 'RTGS';
        if (str_contains($description, 'IMPS')) return 'IMPS';
        if (str_contains($description, 'UPI')) return 'UPI';

        return ''; // Default mode
    }

    private function isReadCutoffEnabled(string $merchantID): bool{
        $requestPayload = [
            "id" =>  $merchantID,
            "experiment_name" => Merchant\RazorxTreatment::ACCOUNT_STATEMENTS_READ_CUTOFF,
            'request_data'  => json_encode(['id' =>  $merchantID])
        ];

        return (new Merchant\Core)->isSplitzExperimentEnable($requestPayload,Merchant\RazorxTreatment::VARIANT_ENABLE);
    }

    private function mapPublicID(string $entityType) : string {
        $publicIdMap = [
            'payout' => 'pout_',
            'external' => 'ext_',
            'reversal' => 'rvrsl_',
            'payout_reversal' => 'rvrsl_',
            '' => '' // No mapping for empty entity types
        ];

        return $publicIdMap[$entityType];
    }
}
