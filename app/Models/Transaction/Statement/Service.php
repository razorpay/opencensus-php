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

            $isSearchReadCutoffMerchant = $this->isReadCutoffEnabledForSearch($this->merchant->getId());

            if ($isSearchReadCutoffMerchant === true) {

                $dimension = $this->getDimensions();

                try {

                    return $this->redirectSearchStatementToXperience($input, $dimension);

                } catch (\Exception $e) {
                    $this->trace->traceException($e, null,  TraceCode::XPERINCE_STATEMENT_SEARCH_FAILURE, [
                        'input' => $input,
                        'merchant_id' => $this->merchant->getId()
                    ]);

                    $this->trace->info(TraceCode::XPERIENCE_SEARCH_READ_CUTOFF_FALLBACK_FLOW, [
                        'merchant_id' => $this->merchant->getId()
                    ]);

                    // Count fallback to BAS
                    $this->trace->count(TxnMetric::READ_CUTOFF_XPERIENCE_FALLBACK_TOTAL, $dimension);
                }
            }

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

    private function redirectSearchStatementToXperience(array $input, $dimension): array
    {
        $this->trace->info(TraceCode::DEBUG_LOGGING, [
            "account_statements_xperience_redirect" => true,
            "input" => $input
        ]);

        $xperienceService = $this->app['xperience'];

        $startTimeMs = round(microtime(true) * 1000);

        // Count all Xperience attempts for account statements read cutoff
        $this->trace->count(TxnMetric::READ_CUTOFF_XPERIENCE_REQUEST_TOTAL, $dimension);

        $xperienceResponse = $xperienceService->statementSearch($input);

        $endTimeMs = round(microtime(true) * 1000);

        // Track latency for Xperience requests
        $this->trace->histogram(
            TxnMetric::READ_CUTOFF_XPERIENCE_REQUEST_LATENCY_MILLISECONDS,
            $endTimeMs - $startTimeMs,
            $dimension
        );

        // Transform Xperience response to maintain API response structure
        $transformedResponse = $this->transformXperienceResponseForSearch($xperienceResponse);

        $this->trace->info(
            TraceCode::STATEMENT_SEARCH_XPERIENCE_SERVICE_SUCCESS,
            [
                'input' => $input,
                'merchant_id' => $this->merchant->getId(),
                'original_response_count' => count($xasResponse['statements'] ?? []),
                'transformed_response_count' => isset($transformedResponse['count']) ? $transformedResponse['count'] : (isset($transformedResponse['id']) ? 1 : 0),
            ]
        );

        // Count all Xperience success responses for account statements read cutoff
        $this->trace->count(TxnMetric::READ_CUTOFF_XPERIENCE_SUCCESS_TOTAL, $dimension);

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

    private function isReadCutoffEnabledForSearch(string $merchantID): bool{
        $requestPayload = [
            "id" =>  $merchantID,
            "experiment_name" => Merchant\RazorxTreatment::ACCOUNT_STATEMENTS_READ_CUTOFF_FOR_SEARCH,
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

    /**
     * Transform XPerience service response to match the original API response format
     */
    public function transformXperienceResponseForSearch(array $xperienceResponse): array
    {
        $data = $xperienceResponse['data'] ?? [];

        $transformedResponse = [
            'entity' => $data['entity'] ?? 'collection',
            'count' => $data['count'] ?? 0,
            'has_more' => false,
            'items' => []
        ];

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $transformedResponse['items'][] = $this->transformTransactionItem($item);
            }
        }

        return $transformedResponse;
    }

    private function transformTransactionItem(array $item): array
    {
        return [
            'id' => $item['id'] ?? null,
            'entity' => $item['entity'] ?? 'transaction',
            'account_number' => $item['account_number'] ?? '',
            'amount' => (int) ($item['amount'] ?? 0),
            'currency' => $item['currency'] ?? 'INR',
            'credit' => (int) ($item['credit'] ?? 0),
            'debit' => (int) ($item['debit'] ?? 0),
            'balance' => (int) ($item['balance'] ?? 0),
            'created_at' => (int) ($item['created_at'] ?? 0),
            'source' => isset($item['source']) ? $this->transformSourceItem($item['source']) : null
        ];
    }

    private function transformSourceItem(array $source): array
    {
        return [
            'id' => $source['id'] ?? null,
            'entity' => $source['entity'] ?? 'payout',
            'fund_account_id' => $source['fund_account_id'] ?? null,
            'fund_account' => isset($source['fund_account']) ? $this->transformFundAccount($source['fund_account']) : null,
            'amount' => (int) ($source['amount'] ?? 0),
            'notes' => $this->transformNotes($source['notes'] ?? []),
            'fees' => (int) ($source['fees'] ?? 0),
            'tax' => (int) ($source['tax'] ?? 0),
            'status' => $source['status'] ?? null,
            'utr' => $source['utr'] ?? null,
            'mode' => $source['mode'] ?? null,
            'created_at' => (int) ($source['created_at'] ?? 0),
            'fee_type' => $source['fee_type'] ?? null
        ];
    }

    private function transformFundAccount(array $fundAccount): array
    {
        return [
            'id' => $fundAccount['id'] ?? null,
            'entity' => $fundAccount['entity'] ?? 'fund_account',
            'contact_id' => $fundAccount['contact_id'] ?? null,
            'contact' => isset($fundAccount['contact']) ? $this->transformContact($fundAccount['contact']) : null,
            'account_type' => $fundAccount['account_type'] ?? null,
            'merchant_disabled' => $fundAccount['merchant_disabled'] ?? false,
            'bank_account' => isset($fundAccount['bank_account']) ? $this->transformBankAccount($fundAccount['bank_account']) : null,
            'batch_id' => $fundAccount['batch_id'] ?? null,
            'active' => $fundAccount['active'] ?? true,
            'created_at' => (int) ($fundAccount['created_at'] ?? 0)
        ];
    }

    private function transformContact(array $contact): array
    {
        return [
            'id' => $contact['id'] ?? null,
            'entity' => $contact['entity'] ?? 'contact',
            'name' => $contact['name'] ?? null,
            'contact' => $contact['contact'] ?? null,
            'email' => $contact['email'] ?? null,
            'type' => $contact['type'] ?? null,
            'reference_id' => $contact['reference_id'] ?? null,
            'batch_id' => $contact['batch_id'] ?? null,
            'active' => $contact['active'] ?? true,
            'notes' => $this->transformContactNotes($contact['notes'] ?? []),
            'created_at' => (int) ($contact['created_at'] ?? 0),
            'gstin' => $contact['gstin'] ?? null
        ];
    }

    private function transformBankAccount(array $bankAccount): array
    {
        return [
            'ifsc' => $bankAccount['ifsc'] ?? null,
            'bank_name' => $bankAccount['bank_name'] ?? null,
            'name' => $bankAccount['name'] ?? null,
            'notes' => [],
            'account_number' => $bankAccount['account_number'] ?? null
        ];
    }

    private function transformNotes(array $notes): array
    {
        if ($this->isAssociativeArray($notes)) {
            return $notes;
        }
        return [];
    }

    private function transformContactNotes(array $notes): array
    {
        if ($this->isAssociativeArray($notes)) {
            return $notes;
        }

        $parsedNotes = [];
        foreach ($notes as $noteString) {
            if (is_string($noteString) && strpos($noteString, ':') !== false) {
                $parts = explode(':', $noteString, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    $parsedNotes[$key] = $value;
                }
            }
        }
        return $parsedNotes;
    }

    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return true;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
