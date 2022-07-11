<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;
use App;
use RZP\Models\Merchant;
use RZP\Base\RepositoryManager;
use RZP\Models\Merchant\AutoKyc;
use Illuminate\Foundation\Application;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Detail\RetryStatus as RetryStatus;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\Store\ConfigKey;

abstract class Base implements RequestDispatcher
{
    protected $merchantCore;

    protected $sync;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    protected $merchant;

    protected $merchantDetails;

    protected $documentCore;
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;
    public function __construct(Merchant\Entity $merchant, DetailEntity $merchantDetails)
    {
        $this->merchantCore = new Merchant\Core();

        $this->documentCore = new Merchant\Document\Core();

        $this->merchant = $merchant;

        $this->merchantDetails = $merchantDetails;

        $this->sync=false;

        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

    }

    /**
     * Checks condition that we can trigger validation or not
     *
     * @return bool
     */
    public abstract function canTriggerValidation(): bool;

    /**
     * @return array
     */
    public abstract function getRequestPayload(): array;

    /**
     * Used this function for doing post process action
     *
     * @param BvsValidation\Entity $bvsValidation
     */
    public function performPostProcessOperation(BvsValidation\Entity $bvsValidation): void
    {

    }

    /**
     * Triggers bvs validation request
     */
    public function triggerBVSRequest(): void
    {
        if ($this->canTriggerValidation() === true)
        {
            $payload = $this->getRequestPayload();

            if (array_key_exists(Constant::OWNER_ID, $payload) === true)
            {
                $ownerId = array_pull($payload, Constant::OWNER_ID);

                $payload[Constant::OWNER_TYPE] = Constant::BANKING_ACCOUNT;
            }
            else
            {
                $ownerId = $this->merchantDetails->getEntityId();

                $payload[Constant::OWNER_TYPE] = Constant::MERCHANT;
            }

            $bvsValidation = (new AutoKyc\Bvs\Core($this->merchant,$this->merchantDetails))->verify($ownerId, $payload);

            if ($bvsValidation != null)
            {
                $this->performPostProcessOperation($bvsValidation);
            }
        }
    }

    public function fetchEnrichmentDetails(): ?AutoKyc\Response
    {
        $payload = $this->getRequestPayload();

        $ownerId = $this->merchantDetails->getEntityId();

        $payload[Constant::OWNER_TYPE] = Constant::MERCHANT;

        return (new AutoKyc\Bvs\Core($this->merchant, $this->merchantDetails))->fetchEnrichmentDetails($ownerId, $payload);
    }

    public function fetchValidationDetails($validationId = null)
    {
        $input = $this->getRequestPayload();

        return (new AutoKyc\Bvs\Core($this->merchant,$this->merchantDetails))->fetchValidationDetails($this->merchantDetails->getEntityId(), $input, $validationId);
    }

    protected function isDedupeCheckForNoDocOnboardingPass(string $field)
    {
        $store = new StoreCore();
        $data  = $store->fetchValuesFromStore($this->merchant->getId(), ConfigKey::ONBOARDING_NAMESPACE,
                                              [ConfigKey::NO_DOC_ONBOARDING_INFO], StoreConstants::INTERNAL);
        $noDocConfig = $data[ConfigKey::NO_DOC_ONBOARDING_INFO];

        if (isset($noDocConfig[DEConstants::DEDUPE][$field]) === true and $noDocConfig[DEConstants::DEDUPE][$field][DEConstants::STATUS] != RetryStatus::PASSED)
        {
            return false;
        }

        return true;
    }
}
