<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Merchant;
use RZP\Models\Batch\Type;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Merchant\Email;
use RZP\Models\Merchant\Entity as ME;
use RZP\Models\Merchant\Account\Entity as Account;
use RZP\Models\Batch\Helpers\SubMerchant as Helper;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;
use RZP\Models\Merchant\Detail\Core as MerchantDetailCore;

class SubMerchant extends Base
{
    /**
     * @var MerchantDetailCore
     */
    protected $merchantDetailCore;

    /**
     * @var Merchant\Service
     */
    protected $merchantService;

    /**
     * @var Merchant\Core
     */
    protected $merchantCore;

    /**
     * @var string
     */
    protected $userId;

    /**
     * Used to check if activation form  needs to be auto-submitted
     *
     * @var bool
     */
    protected $autoSubmit = false;

    /**
     * Used to check if activation form  detail needs to be auto-filled
     *
     * @var bool
     */
    protected $autofillDetails = false;

    /**
     * Used to check if sub-merchants need to be auto-activated
     *
     * @var bool
     */
    protected $autoActivate = false;

    /**
     * Used to check if sub-merchant email needs to be treated as dummy
     * when provided in which case the submerchant email is same as the
     * partner email and the dummy is stored in the merchant_emails table
     * for business purposes.
     *
     * @var bool
     */
    protected $useMerchantEmailAsDummy = true;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->merchantDetailCore = new MerchantDetailCore;

        $this->merchantService  = new Merchant\Service;

        $this->merchantCore = new Merchant\Core;
    }

    protected function processEntry(array & $entry)
    {
        $this->repo->transactionOnLiveAndTest(function() use (& $entry)
        {
            $this->createSubMerchantForEntry($entry);

            $this->unsetExtraOutputKeys($entry);
        });
    }

    protected function performPreProcessingActions()
    {
        $this->autoSubmit = (empty($this->params[ME::AUTO_SUBMIT]) === false);

        $this->autofillDetails = (empty($this->params[ME::AUTOFILL_DETAILS]) === false);

        $this->autoActivate = (empty($this->params[ME::AUTO_ACTIVATE]) === false);

        //
        // This is true by default and needs to be overridden only when an input
        // is set to False explicitly, it should not be overridden by null. Hence
        // the following explicitly check for isset.
        //
        if (isset($this->params[ME::USE_EMAIL_AS_DUMMY]) === true)
        {
            $this->useMerchantEmailAsDummy = (bool) $this->params[ME::USE_EMAIL_AS_DUMMY];
        }

        $this->userId = $this->merchant->primaryOwner()->getId();

        return parent::performPreProcessingActions();
    }

    /**
     * @param  array $entry
     *
     * @return ME
     */
    protected function createSubMerchantForEntry(array & $entry) : ME
    {
        $input = Helper::getSubMerchantInput($entry, $this->userId, $this->useMerchantEmailAsDummy);

        $subMerchantArray = $this->merchantService->createSubMerchant($input, $this->merchant);

        /** @var ME $subMerchant */
        $subMerchant = $this->repo->merchant->findOrFailPublic(
            Account::verifyIdAndStripSign($subMerchantArray[ME::ID]));

        $status = Status::SUCCESS;

        if ($this->autofillDetails === true)
        {
            // Fill in merchant details (activation form)
            $detailInput = Helper::getSubMerchantDetailInput($entry, $this->merchant, $this->useMerchantEmailAsDummy);
            $this->merchantDetailCore->saveMerchantDetails($detailInput, $subMerchant);
        }

        if ($this->autoSubmit === true)
        {
            // Save files
            $this->merchantDetailCore->saveDummyActivationFiles($subMerchant);

            // Submit activation form
            $submitData = [MerchantDetail::SUBMIT => '1'];
            $response   = $this->merchantDetailCore->saveMerchantDetails($submitData, $subMerchant);

            if ($response[MerchantDetail::SUBMITTED] === false)
            {
                $status                           = Status::FAILURE;
                $entry[Header::ERROR_DESCRIPTION] = 'Activation details not submitted successfully';
            }

            if (($response[MerchantDetail::SUBMITTED] === true) and ($this->autoActivate === true))
            {
                $status = Status::SUCCESS;

                $this->merchantCore->autoUpdateCategoryDetails(
                        $subMerchant,
                        $entry[Header::BUSINESS_CATEGORY],
                        $entry[Header::BUSINESS_SUB_CATEGORY]);

                $websiteUpdateData = [ME::WEBSITE => $entry[Header::WEBSITE_URL]];

                $this->merchantCore->edit($subMerchant, $websiteUpdateData);

                $response = (new Merchant\Activate)->activate($subMerchant, $subMerchant->merchantDetail);

                if ($response[ME::ACTIVATED] === false)
                {
                    $status = Status::FAILURE;

                    $entry[Header::ERROR_DESCRIPTION] = 'Merchant not activated successfully';
                }
            }
        }

        if (($this->useMerchantEmailAsDummy === true) and (empty($entry[Header::MERCHANT_EMAIL]) === false))
        {
            $emailInput = [
                Email\Entity::EMAIL => $entry[Header::MERCHANT_EMAIL],
                Email\Entity::TYPE  => Email\Type::PARTNER_DUMMY,
            ];

            (new Email\Core)->create($subMerchant, $emailInput);
        }

        $entry[Header::STATUS]      = $status;
        $entry[Header::MERCHANT_ID] = Account::getSignedId($subMerchant->getId());

        return $subMerchant;
    }

    protected function unsetExtraOutputKeys(array & $entry)
    {
        $outputHeaders = Header::HEADER_MAP[Type::SUB_MERCHANT][Header::OUTPUT];

        $entry = array_only($entry, $outputHeaders);
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
