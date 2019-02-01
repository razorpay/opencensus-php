<?php
/**
 * Created by PhpStorm.
 * User: amogh
 * Date: 2019-02-01
 * Time: 14:35
 */

namespace RZP\Services\Mock\FTS;

use RZP\Services\FTS\CreateAccount as BaseCreateAccount;

class CreateAccount extends BaseCreateAccount
{
    public function createFundAccount(string $id, string $type): array
    {
        return
            [
                "message" => "fts created account successfully."
            ];
    }
}