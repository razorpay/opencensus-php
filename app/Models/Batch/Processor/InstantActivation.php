<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Merchant\Core as Core;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

class InstantActivation extends Base
{
    private $merchantDetailCore;

    private $merchantCore;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->merchantDetailCore = new DetailCore();

        $this->merchantCore = new Core();
    }

    protected function processEntry(array & $entry)
    {
        $this->repo->transactionOnLiveAndTest(function() use (& $entry) {

            $merchant = $this->repo->merchant->findOrFailPublic(trim($entry[Merchant::MERCHANT_ID]));

            $this->updateAuthDetails($merchant);

            $merchantDetails = $merchant->merchantDetail;

            $entityInput = $this->getEntityInput($merchantDetails);

            $this->validateInput($merchantDetails, $entityInput);

            $this->processInput($merchant, $entityInput);
        });

        $entry[Header::STATUS] = Status::SUCCESS;
    }

    /**
     * updates merchant information into auth,
     * this is being used to set org id and merchant info
     *
     * @param Merchant $merchant
     */
    private function updateAuthDetails(Merchant $merchant)
    {
        $this->app['basicauth']->setMerchant($merchant);
    }

    /**
     * @param DetailEntity $merchantDetails
     *
     * @return array
     */
    private function getEntityInput(DetailEntity $merchantDetails): array
    {
        $entityInputKeys = [
            DetailEntity::BUSINESS_CATEGORY,
            DetailEntity::BUSINESS_SUBCATEGORY,
            DetailEntity::PROMOTER_PAN,
            DetailEntity::BUSINESS_NAME,
            DetailEntity::BUSINESS_WEBSITE,
            DetailEntity::BUSINESS_MODEL,
            DetailEntity::BUSINESS_DBA,
            DetailEntity::BUSINESS_TYPE,
        ];

        $entityInput = array_only($merchantDetails->getAttributes(), $entityInputKeys);

        // remove null values from input array
        return array_filter($entityInput, function($var) {
            return (is_null($var) === false);
        });
    }

    /**
     * contains validation of instant activation batch entries
     *
     * @param DetailEntity $merchantDetails
     * @param array        $entityInput
     */
    public function validateInput(DetailEntity $merchantDetails, array $entityInput)
    {
        $merchantDetails->validateInput('instant_activation', $entityInput);
    }

    /**
     * @param $merchant
     * @param $entityInput
     *
     * @throws BadRequestException
     */
    public function processInput(Merchant $merchant, array $entityInput)
    {
        $businessSubCategory = $entityInput[DetailEntity::BUSINESS_SUBCATEGORY] ?? null;

        //
        // This step must happen before saveInstantActivationDetails function.
        // In saveInstantActivationDetails, category and category2 are auto-populated
        // only when the category and subcategory are updated. Since a batch is being
        // used, the category and subcategory sent in the input are fetched from the
        // database itself and hence category and category2 must be force populated.
        //
        $this->merchantCore->autoUpdateCategoryDetails($merchant,
                                                       $entityInput[DetailEntity::BUSINESS_CATEGORY],
                                                       $businessSubCategory);

        $this->merchantDetailCore->saveInstantActivationDetails($entityInput, $merchant);
    }

    protected function sendProcessedMail()
    {
        return;
    }
}
