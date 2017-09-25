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

        $account = (new Merchant\Core)->createSubMerchant($input, $this->merchant);

        $detailInput = Helper::getAccountDetailInput($entry);

        (new MerchantDetail\Service)->saveMerchantDetails($detailInput);

        // Append account ID to output fields
        $entry[Header::ACCOUNT_ID] = Merchant\AccountEntity::getSignedId($account->getId());
    }
}
