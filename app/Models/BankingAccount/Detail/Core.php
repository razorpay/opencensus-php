<?php

namespace RZP\Models\BankingAccount\Detail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount;

class Core extends Base\Core
{
    /**
     * This function updates banking account details pertaining to a bank account
     * It also returns the tokenised form of the input, so that the input can now be freely used in further flows
     *
     * @param array                            $input
     * @param BankingAccount\Entity            $bankingAccount
     * @param BankingAccount\Gateway\Processor $processor
     *
     * @return mixed
     * @throws \RZP\Exception\LogicException
     */
    public function updateBankingAccountDetails(array $input,
                                                BankingAccount\Entity $bankingAccount,
                                                BankingAccount\Gateway\Processor $processor)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_DETAILS_EDIT,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $bankingAccount->getChannel(),
            ]);

        $processor->validateAccountDetails($input);

        $input = $processor->formatAccountDetails($input);

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_DETAILS_EDIT,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $bankingAccount->getChannel(),
                'input'   => $input,
            ]);

        foreach ($input as $key => $value)
        {
            $bankingAccountDetail = $this->repo
                                          ->banking_account_detail
                                          ->getDetailsForKeyAndBankingAccount($bankingAccount, $key);

            if ($bankingAccountDetail === null)
            {
                $bankingAccountDetail = new Entity;

                $bankingAccountDetail->setGatewayKey($key);
            }

            $bankingAccountDetail->bankingAccount()->associate($bankingAccount);

            $bankingAccountDetail->merchant()->associate($bankingAccount->merchant);

            $bankingAccountDetail->setGatewayValue($value);

            $this->repo->saveOrFail($bankingAccountDetail);
        }

        // Adding this return to reflect any tokenisation changes in the input
        return $input;
    }
}
