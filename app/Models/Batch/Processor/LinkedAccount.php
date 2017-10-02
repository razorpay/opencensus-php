<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Merchant;
use RZP\Models\Batch\Header;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Batch\Helpers\LinkedAccount as Helper;

class LinkedAccount extends Base
{
    protected function processEntry(array & $entry)
    {
        $input = Helper::getCreateAccountInput($entry);

        $this->repo->transactionOnLiveAndTest(function () use ($input, $entry)
        {
            $account = (new Merchant\Core)->createSubMerchant($input, $this->merchant);

            $detailInput = Helper::getAccountDetailInput($entry);

            $response = (new MerchantDetail\Core)->saveMerchantDetails($detailInput, $account);

            // Append account ID to output fields
            $entry[Header::STATUS]     = $response['auto_activated'];
            $entry[Header::ACCOUNT_ID] = Merchant\AccountEntity::getSignedId($account->getId());
        });
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
