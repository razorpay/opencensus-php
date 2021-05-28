<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant;
use RZP\Models\Merchant\AutoKyc;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

abstract class Base implements RequestDispatcher
{
    protected $merchantCore;

    protected $merchant;

    protected $merchantDetails;

    protected $documentCore;
    public function __construct(Merchant\Entity $merchant, DetailEntity $merchantDetails)
    {
        $this->merchantCore = new Merchant\Core();

        $this->documentCore = new Merchant\Document\Core();

        $this->merchant = $merchant;

        $this->merchantDetails = $merchantDetails;
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
     */
    public function performPostProcessOperation(): void
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

            $bvsValidation = (new AutoKyc\Bvs\Core())->verify($ownerId, $payload);

            if ($bvsValidation != null)
            {
                $this->performPostProcessOperation();
            }
        }
    }

    public function fetchValidationDetails()
    {
        $input = $this->getRequestPayload();

        return (new AutoKyc\Bvs\Core())->fetchValidationDetails($this->merchantDetails->getEntityId(), $input);
    }
}
