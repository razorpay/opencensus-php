<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Base\ConnectionType;
use RZP\Models\Feature\Constants;
use RZP\Models\Base\PublicCollection;

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

        // Route request to ledger statement if ledger feature is enabled
        if (($this->merchant->isFeatureEnabled(Constants::LEDGER_REVERSE_SHADOW) === true) and
            ($this->isExperimentEnabled(Merchant\RazorxTreatment::RX_REARCH_TIDB_EXPERIMENT) === true) and
            ($balance->isAccountTypeShared() === true))
        {
            $ledger = $this->repo->ledger_statement->fetch($input, $this->merchant->getId(), ConnectionType::RX_DATA_WAREHOUSE_MERCHANT);

            return $ledger->toArrayPublic();
        }

        // Route request to BAS if DA ledger feature is enabled and acc is of type direct
        if (($this->isExperimentEnabled(Merchant\RazorxTreatment::RX_DA_ACC_STMT_EXPERIMENT) === true) and
            ($balance->isAccountTypeDirect() === true))
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

            return $this->repo->direct_account_statement->fetch($input, $this->merchant->getId())->toArrayPublic();
        }

        /** @var PublicCollection $transactions */
        $transactions = $this->repo->statement->fetch($input, $this->merchant->getId());

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

        if (($this->isExperimentEnabled(Merchant\RazorxTreatment::RX_DA_ACC_STMT_EXPERIMENT) === true) and
            (strpos($id, DirectAccount\Statement\Entity::getSign()) !== false))
        {
            return $this->repo->direct_account_statement
                ->fetchByPublicIdAndMerchantForTransactionsBasedOnBasId($id, $this->merchant, $input)
                ->toArrayPublic();
        }

        // In case feature flag is added to the merchant, only in that case ledger service will be called.
        // Since here depending on the transaction, we cannot find whether this transaction is for VA or CA,
        // without depending on the transaction table, so only merchant feature flag is check is enough.
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
        }

        /** @var Entity $transaction */
        $transaction = $this->repo
                            ->statement
                            ->findByPublicIdAndMerchantForBankingBalance(
                                $id, $this->merchant, $input);

        return $transaction->toArrayPublic();
    }

    /*
     * TODO: VendorPayment Internal Auth Also Uses this method -->
     *        switching merchants between these feature flags could lead to a potential data miss, and should be handled carefully later.
     */
    // Temporary route for forcing the index for X Dashboard Requests for Account statement
    public function fetchMultipleForBanking($input)
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        $balance = $merchantValidator->validateAndTranslateAccountNumberForBanking($input);

        // Route request to ledger statement if ledger read feature is enabled
        if (($this->merchant->isFeatureEnabled(Constants::LEDGER_REVERSE_SHADOW) === true) and
            ($this->isExperimentEnabled(Merchant\RazorxTreatment::RX_REARCH_TIDB_EXPERIMENT) === true) and
            ($balance->isAccountTypeShared() === true))
        {
            $ledger = $this->repo->ledger_statement->fetch($input, $this->merchant->getId(), ConnectionType::RX_DATA_WAREHOUSE_MERCHANT);

            return $ledger->toArrayPublic();
        }

        // Route request to BAS if DA ledger feature is enabled and acc is of type direct
        if (($this->isExperimentEnabled(Merchant\RazorxTreatment::RX_DA_ACC_STMT_EXPERIMENT) === true) and
            ($balance->isAccountTypeDirect() === true))
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

            return $this->repo->direct_account_statement->fetch($input, $this->merchant->getId())->toArrayPublic();
        }

        /** @var PublicCollection $transactions */
        $transactions = $this->repo->statement->setBaseQueryAndFetchForBanking($input, $this->merchant->getId(), null, $balance);

        return $transactions->toArrayPublic();
    }

    protected function isExperimentEnabled($experiment)
    {
        $app = $this->app;

        $variant = $app['razorx']->getTreatment($this->merchant->getId(),
            $experiment, $app['basicauth']->getMode() ?? Mode::LIVE);

        return ($variant === 'on');
    }
}
