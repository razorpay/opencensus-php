<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Currency\Currency;

class Core extends Base\Core
{
    /**
     * @param Merchant\Entity $merchant
     * @param array           $input
     * @param string          $mode
     *
     * @return Entity
     */
    public function create(Merchant\Entity $merchant, array $input, string $mode): Entity
    {
        $balance = (new Entity)->build($input);

        $balance->setConnection($mode);

        $balance->merchant()->associate($merchant);

        $this->repo->saveOrFail($balance);

        return $balance;
    }

    public function updateBalanceAccountNumber(Entity $balance, string $accountNumber)
    {
        assertTrue($balance->getAccountNumber() === null, 'Attempting to re-update balance\'s account_number!');

        $balance->setAccountNumber($accountNumber);

        $this->repo->saveOrFail($balance);
    }

    /**
     * @param Merchant\Entity $merchant
     * @param string          $balanceType
     * @param null            $mode
     *
     * @return Entity
     */
    public function createOrFetchBalance(Merchant\Entity $merchant, string $balanceType, $mode = null): Entity
    {
        $balance = $this->repo->balance->getMerchantBalanceByType($merchant->getId(), $balanceType, $mode);

        if ($balance === null)
        {
            // Evey balance we create will start with 0 balance. if needed we can extend this.
            $input = [
                Entity::TYPE     => $balanceType,
                Entity::CURRENCY => Currency::INR,
            ];

            $balance = $this->create($merchant, $input, $mode);
        }

        return $balance;
    }

    public function createBalanceForCurrentAccount(Merchant\Entity $merchant,
                                                   string $balanceType,
                                                   array $input, string $mode)
    {

        $content = [
            Entity::TYPE     => $balanceType,
            Entity::CURRENCY => Currency::INR,
        ];

        $input = array_merge($input, $content);

        $balance = $this->create($merchant, $input, $mode);

        return $balance;
    }

    /**
     * Check that a merchant's balance is greater than amount argument passed
     *
     * @param  Merchant\Entity $merchant
     * @param  int             $amount
     * @return bool
     */
    public function checkMerchantBalance(Merchant\Entity $merchant, int $amount) : bool
    {
        $balance = $this->repo->balance->getMerchantBalance($merchant);

        if ($balance->getBalance() < $amount)
        {
            $this->trace->info(
                TraceCode::MERCHANT_BALANCE_DEBIT_FAILURE,
                [
                    'message'           => 'Not enough balance',
                    'merchant_balance'  => $balance->getBalance(),
                    'debit_amount'      => $amount
                ]);

            return false;
        }

        return true;
    }
}
