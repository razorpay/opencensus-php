<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Error\ErrorCode;
use RZP\Models\Transaction;
use RZP\Exception\BadRequestException;

/**
 * Class Service
 *
 * @package RZP\Models\Transaction\Statement
 */
class Service extends Transaction\Service
{
    public function fetchMultiple(array $input): array
    {
        $this->merchant->getValidator()->validateAndTranslateAccountNumberForBanking($input);

        $transactions = $this->repo->statement->fetch($input, $this->merchant->getId());

        return $transactions->toArrayPublic();
    }

    public function fetch(string $id): array
    {
        $this->merchant->getValidator()->validateBusinessBankingActivated();

        $transaction = $this->repo->statement->findByPublicIdAndMerchant($id, $this->merchant);

        return $transaction->toArrayPublic();
    }
}
