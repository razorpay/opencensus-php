<?php

namespace RZP\Tests\Functional\Helpers\BankingAccount;

trait BankingAccountTrait
{
    protected function setUpBalancesForBasBankingAccounts(string $merchantId = '10000000000000')
    {
        $accounts = [
            [
                'merchant_id'       => $merchantId,
                'account_number'    => '401509080396',
                'channel'           => 'icici',
            ],
            [
                'merchant_id'       => $merchantId,
                'account_number'    => '401509080397',
                'channel'           => 'rbl',
            ],
        ];

        foreach ($accounts as $account)
        {
            $balance = $this->fixtures->create(
                'balance',
                [
                    'merchant_id'       => $account['merchant_id'],
                    'account_number'    => $account['account_number'],
                    'channel'           => $account['channel'],
                    'type'              => 'banking',
                    'account_type'      => 'direct',
                ]);

            $this->fixtures->create(
                'banking_account_statement_details',
                    [
                        'merchant_id'       => $account['merchant_id'],
                        'account_number'    => $account['account_number'],
                        'channel'           => $account['channel'],
                        'account_type'      => 'direct',
                        'status'            => 'active',
                        'balance_id'        => $balance['id'],
                        'gateway_balance'   => 0,
                    ]);
        }

        return $accounts;
    }

}