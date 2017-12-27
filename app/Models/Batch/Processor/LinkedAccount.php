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
        $this->repo->transactionOnLiveAndTest(function () use (& $entry)
        {
            $this->createOrUpdateAccountForEntry($entry);
        });
    }

    protected function createOrUpdateAccountForEntry(array & $entry)
    {
        // Creates or updates if exists the account entity against given entry
        $accountId = $entry[Header::ACCOUNT_ID] ?: null;
        if ($accountId !== null)
        {
            $account = $this->repo
                            ->merchant
                            ->fetchByAccountIdAndMerchant(
                                $accountId,
                                $this->merchant,
                                true);

            $input   = Helper::getAccountEditInput($entry);
            $account = $this->merchantCore->edit($account, $input);
        }
        else
        {
            $input   = Helper::getAccountCreateInput($entry);
            $account = $this->merchantCore->createSubMerchant($input, $this->merchant);
        }


        // Now saves details entity, please note that saveMerchantDetails() method
        // handles upserts already, i.e. doesn't create details entity if already
        // exists for given account.
        $detailInput = Helper::getAccountDetailInput($entry);
        $response    = $this->merchantDetailCore->saveMerchantDetails($detailInput, $account);

        // At last appends status and account id(if not exists) in entry to be
        // flushed in processed file
        $status = ($response['auto_activated'] === true) ? Status::SUCCESS : Status::FAILURE;

        $entry[Header::STATUS]     = $status;
        $entry[Header::ACCOUNT_ID] = Merchant\AccountEntity::getSignedId($account->getId());
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
