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
        $statements = $this->repo->statement->fetch($input, $this->merchant->getId());

        return $statements->toArrayPublic();
    }

    public function fetch(string $id): array
    {
        $statement = $this->repo->statement->findByPublicIdAndMerchant($id, $this->merchant);

        // Asserts that requested transaction id is in correct context!
        $balance = $this->repo->balance->getBalanceForRequestContext();
        if ($statement->getBalanceId() !== $balance->getId())
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        return $statement->toArrayPublic();
    }
}
