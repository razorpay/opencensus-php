<?php

namespace RZP\Models\Partner;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Partner\Activation;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    private $activationCore;

    private $merchantValidator;

    private $partnerActivationValidator;

    public function __construct()
    {
        $this->core = new Core();

        $this->activationCore = new Activation\Core();

        $this->merchantValidator = new Merchant\Validator();

        $this->partnerActivationValidator = new Activation\Validator();

        parent::__construct();
    }

    public function savePartnerDetailsForActivation(array $input)
    {
        $submit = $input[Detail\Entity::SUBMIT] ?? "0";

        $isFormSubmit = ($submit === "1") and count($input) === 1; // input should contain only submit in it

        $this->validatePartnerFormSaveAndSubmit($this->merchant, $isFormSubmit);

        $this->partnerActivationValidator->validateInput('savePartnerActivation', $input);

        $this->merchant->load('merchantDetail');

        $merchantDetail = $this->merchant->merchantDetail;

        if ($merchantDetail->isLocked() === false and $isFormSubmit === false)
        {
            (new Detail\Core())->saveMerchantDetails($input, $this->merchant);
        }

        return $this->core->processPartnerActivation($input, $merchantDetail, $this->merchant);
    }

    public function getPartnerActivationDetails()
    {
        $this->merchantValidator->validateIsPartner($this->merchant);

        $merchantDetails = $this->merchant->merchantDetail;

        return $this->core->createPartnerResponse($merchantDetails);
    }

    public function updatePartnerActivationStatus(string $merchantId, array $input)
    {
        $activationCore = new Activation\Core();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->merchantValidator->validateIsPartner($merchant);

        $partnerActivation = $activationCore->createOrFetchPartnerActivationForMerchant($merchant, false);

        $admin = $this->app['basicauth']->getAdmin();

        return $this->activationCore->updatePartnerActivationStatus($merchant, $partnerActivation, $admin, $input);
    }


    public function editPartnerActivationDetails($id, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $this->merchantValidator->validateIsPartner($merchant);

        $this->core->editPartnerActivation($merchant, $input);

        return $this->core->createPartnerResponse($merchant->merchantDetail);
    }

    private function validatePartnerFormSaveAndSubmit(Merchant\Entity $merchant, bool $isPartnerFormSubmit)
    {
        $this->merchantValidator->validateIsPartner($merchant);

        $merchantDetails = $merchant->merchantDetail;

        // partner can submit the form even merchant activation is locked.
        if($merchantDetails->isLocked() === true and $isPartnerFormSubmit === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED);
        }

        $partnerActivation = $this->core->getPartnerActivation($merchant);

        if($partnerActivation->isLocked() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PARTNER_ACTIVATION_ALREADY_LOCKED);
        }
    }

    public function performAction(string $id, array $input)
    {
        $this->trace->info(
            TraceCode::PARTNER_ACTION_DATA,
            [
                'merchant_id' => $id,
                'input'       => $input,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $this->merchantValidator->validateIsPartner($merchant);

        $partnerActivation = $this->core->getPartnerActivation($merchant);

        return $this->activationCore->performAction($partnerActivation, $input);
    }
}
