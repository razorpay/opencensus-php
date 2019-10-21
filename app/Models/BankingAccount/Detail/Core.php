<?php

namespace RZP\Models\BankingAccount\Detail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount;

class Core extends Base\Core
{
    public function updateBankingAccountDetails(array $input,
                                                BankingAccount\Entity $bankingAccount,
                                                BankingAccount\Gateway\Processor $processor )
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_DETAILS_EDIT,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $bankingAccount->getChannel(),
            ]);

        (new Validator)->setStrictFalse()->validateInput(Validator::PRE_EDIT, $input);

        $input = $input[Entity::DETAILS];

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
            $bankingAccountDetail = new Entity;

            $bankingAccountDetail->bankingAccount()->associate($bankingAccount);

            $bankingAccountDetail->setGatewayKey($key);

            $bankingAccountDetail->setGatewayValue($value);

            $this->repo->saveOrFail($bankingAccountDetail);
        }
    }
}
