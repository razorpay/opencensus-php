<?php

namespace RZP\Services\Mock\FTS;

use RZP\Services\FTS\RegisterAccount as BaseRegisterAccount;

class RegisterAccount extends BaseRegisterAccount
{
    public function registerFundAccount(string $channel, array $ids): array
    {
        return [
                'message' => 'fund accounts registered successfully.'
            ];
    }
}