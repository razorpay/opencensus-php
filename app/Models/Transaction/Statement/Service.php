<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Models\Merchant;
use RZP\Models\Transaction;
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

        $merchantValidator->validateAndTranslateAccountNumberForBanking($input);

        /** @var PublicCollection $transactions */
        $transactions = $this->repo->statement->fetch($input, $this->merchant->getId());

        return $transactions->toArrayPublic();
    }

    public function fetch(string $id, array $input): array
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        $merchantValidator->validateBusinessBankingActivated();

        // In case feature flag is added to the merchant and it is a shared banking balance,
        // only in that case ledger service will be called.
        if (($this->ledgerStatementService->isLedgerJournalReadsEnabled() === true) &&
            ($this->merchant->sharedBankingBalance !== null))
        {
            $ledgerTransaction = $this->ledgerStatementService->fetchFromLedger($id);

            // Only return ledger response if ledger didn't return any error, else return from API.
            if (empty($ledgerTransaction) === false)
            {
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
}
