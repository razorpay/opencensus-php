<?php

namespace RZP\Services\Mock\FTS;

use RZP\Services\FTS\Constants;

use RZP\Services\FTS\CreateAccount as BaseCreateAccount;

class CreateAccount extends BaseCreateAccount
{
    public function createFundAccount(): array
    {
        return [
                'body' => [
                    Constants::FUND_ACCOUNT_ID => random_integer(2),
                ],
                'code' => 201
            ];
    }

    public function createSourceAccount(string $id,
                                        string $ftsAccountId,
                                        array $content,
                                        string $product,
                                        string $channel = 'ICICI')
    {
        return
            [
                'body' => [
                    Constants::MESSAGE => 'source account registered',
                    ],
                'code' => 200,
            ];
    }
}
