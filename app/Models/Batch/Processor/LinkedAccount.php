<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Merchant;
use RZP\Models\BankAccount;
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
     * @var BankAccount\Core
     */
    protected $bankAccountCore;

    /**
     * @var MerchantDetail\Core
     */
    protected $merchantDetailCore;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->merchantCore       = new Merchant\Core;
        $this->bankAccountCore    = new BankAccount\Core;
        $this->merchantDetailCore = new MerchantDetail\Core;
    }

    protected function processEntry(array & $entry)
    {
        $this->repo->transactionOnLiveAndTest(function () use (& $entry)
        {
            $this->createOrUpdateAccountForEntry($entry);
        });
    }

    /**
     * @param  array $entry
     *
     * @return void
     */
    protected function createOrUpdateAccountForEntry(array & $entry)
    {
        $status = Status::FAILURE;

        $accountId = $entry[Header::ACCOUNT_ID] ?: null;
        //
        // If account id exists in file row, just update the bank account
        // details. Assume that is the expected use case, must not edit/update
        // other things via batch flow.
        //
        if ($accountId !== null)
        {
            $account = $this->repo
                            ->merchant
                            ->findByAccountIdAndParent(
                                $accountId,
                                $this->merchant,
                                true);

            // Building input for bank core's method
            $buildInput = $this->bankAccountCore
                               ->buildBankAccountArrayFromMerchantDetail(
                                    $account->merchantDetail,
                                    true);
            $overriddenInput = Helper::getBankAccountInput($entry);
            $input = array_merge($buildInput, $overriddenInput);

            $this->bankAccountCore->createOrChangeBankAccount($input, $account);

            $status = Status::SUCCESS;
        }
        else
        {
            $input = Helper::getSubMerchantInput($entry);
            $account = $this->merchantCore->createSubMerchant($input, $this->merchant);

            $detailInput = Helper::getSubMerchantDetailInput($entry);
            $response = $this->merchantDetailCore->saveMerchantDetails($detailInput, $account);

            $status = ($response['auto_activated'] === true) ?
                        Status::SUCCESS : Status::FAILURE;
        }


        $entry[Header::STATUS]     = $status;
        $entry[Header::ACCOUNT_ID] = Merchant\AccountEntity::getSignedId($account->getId());
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
