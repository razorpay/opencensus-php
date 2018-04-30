<?php

namespace RZP\Models\Batch\Processor;

use Razorpay\OAuth;

use RZP\Models\Partner;
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

    /**
     * @var OAuth\Application\Entity
     */
    protected $partnerApp;

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
            $subMerchant = $this->createSubMerchantForEntry($entry);

            $this->processPartnerAppIfApplicable($subMerchant, $entry);
        });
    }

    protected function performPreProcessingActions()
    {
        $this->setPartnerAppIfApplicable();

        return parent::performPreProcessingActions();
    }

    protected function setPartnerAppIfApplicable()
    {
        $appId = $this->params[Entity::APPLICATION_ID] ?? null;

        if (empty($appId) === true)
        {
            return;
        }

        $this->partnerApp = (new OAuth\Application\Repository)->findOrFailPublic($appId);

        // Validate that the app is a partner app

        // Get the app->client, client should be of type 'partner'
    }

    /**
     * @param  array $entry
     *
     * @return Merchant\Entity
     */
    protected function createSubMerchantForEntry(array & $entry) : Merchant\Entity
    {
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

        return $subMerchant;
    }

    protected function processPartnerAppIfApplicable(Merchant\Entity $subMerchant, array & $entry)
    {
        if ($this->partnerApp === null)
        {
            return;
        }

        $token = (new Partner\Core)->connectMerchant($this->partnerApp, $this->merchant, $subMerchant);

        $entry[Header::PARTNER_TOKEN] = $token;
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
