<?php

namespace RZP\Models\BankingAccount\Detail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount;

class Core extends Base\Core
{
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
    }
}
