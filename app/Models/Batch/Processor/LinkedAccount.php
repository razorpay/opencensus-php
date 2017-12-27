<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Merchant;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Batch\Helpers\LinkedAccount as Helper;

class LinkedAccount extends Base
{
    /**
     * @var Merchant\Core
     */
    protected $merchantCore;

    /**
     * @var MerchantDetail\Core
     */
    protected $merchantDetailCore;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->merchantCore       = new Merchant\Core;
        $this->merchantDetailCore = new MerchantDetail\Core;
    }

    protected function processEntry(array & $entry)
    {
        $input = Helper::getCreateAccountInput($entry);

        $this->repo->transactionOnLiveAndTest(function () use ($input, & $entry)
        {
            $account = $this->merchantCore->createSubMerchant($input, $this->merchant);

            $detailInput = Helper::getAccountDetailInput($entry);

            $response = $this->merchantDetailCore->saveMerchantDetails($detailInput, $account);

            $status = ($response['auto_activated'] === true) ? Status::SUCCESS : Status::FAILURE;

            // Append account ID to output fields
            $entry[Header::STATUS]     = $status;
            $entry[Header::ACCOUNT_ID] = Merchant\Account\Entity::getSignedId($account->getId());
        });
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
