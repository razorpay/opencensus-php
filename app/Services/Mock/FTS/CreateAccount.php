<?php

namespace RZP\Services\Mock\FTS;

use RZP\Services\FTS\CreateAccount as BaseCreateAccount;

class CreateAccount extends BaseCreateAccount
{
    public function createFundAccount(string $id, string $type, string $product): array
    {
        return [
                'message' => 'fts created account successfully.'
            ];
    }
}