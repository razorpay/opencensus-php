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
    public function fetchMultiple(array $input): array
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        $merchantValidator->validateAndTranslateAccountNumberForBanking($input);

        /** @var PublicCollection $transactions */
        $transactions = $this->repo->statement->fetch($input, $this->merchant->getId());

        return $transactions->toArrayPublic();
    }

    public function fetch(string $id): array
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        $merchantValidator->validateBusinessBankingActivated();

        /** @var Entity $transaction */
        $transaction = $this->repo->statement->findByPublicIdAndMerchant($id, $this->merchant);

        return $transaction->toArrayPublic();
    }
}
