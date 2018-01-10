<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Batch\Helpers\SubMerchant as Helper;

class SubMerchant extends Base
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
        $this->repo->transactionOnLiveAndTest(function() use (& $entry)
        {
            $this->createSubMerchantForEntry($entry);
        });
    }

    /**
     * @param  array $entry
     *
     * @return void
     */
    protected function createSubMerchantForEntry(array & $entry)
    {
        $status = Status::SUCCESS;

        $input = Helper::getSubMerchantInput($entry);

        // Create Sub-merchant account
        $merchantService = new Merchant\Service;
        $subMerchant     = $this->merchantCore->createSubMerchant($input, $this->merchant, false);

        $merchantService->addLinkedAccountReferral($this->merchant, $subMerchant);

        $merchantService->attachSubMerchantOwner($this->merchant->primaryOwner()->getId(), $subMerchant);

        $this->repo->saveOrFail($subMerchant);

        // Fill in merchant details (activation form)
        $detailInput = Helper::getSubMerchantDetailInput($entry);
        $response    = $this->merchantDetailCore->saveMerchantDetails($detailInput, $subMerchant);

        // Save files
        $this->merchantDetailCore->saveDummyActivationFiles($subMerchant);

        // Submit activation form
        $submitData = [MerchantDetail\Entity::SUBMIT => '1'];
        $response   = $this->merchantDetailCore->saveMerchantDetails($submitData, $subMerchant);

        $status = ($response[MerchantDetail\Entity::SUBMITTED] === true) ?
            Status::SUCCESS : Status::FAILURE;

        $entry[Header::MERCHANT_ID] = $subMerchant->getId();
        $entry[Header::STATUS]      = $status;
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
