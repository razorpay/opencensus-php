<?php

namespace RZP\Models\Partner;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Partner\Activation;

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
        $this->validatePartnerFormSaveAndSubmit($this->merchant);

        $this->partnerActivationValidator->validateInput('savePartnerActivation', $input);

        $oldMerchantDetail = clone $this->merchant->merchantDetail;

        $response = (new Detail\Core())->saveMerchantDetails($input, $this->merchant);

        $this->merchant->load('merchantDetail');

        $merchantDetail = $this->merchant->merchantDetail;

        return $this->core->processPartnerActivation($input, $merchantDetail, $this->merchant, $oldMerchantDetail);

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

        $merchantDetail = $merchant->merchantDetail;

        $partnerActivation = $activationCore->createOrFetchPartnerActivationForMerchant($merchant, false);

        $admin = $this->app['basicauth']->getAdmin();

        return $this->activationCore->updatePartnerActivationStatus($merchant, $merchantDetail, $partnerActivation, $admin, $input);
    }


    public function editPartnerActivationDetails($id, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $this->merchantValidator->validateIsPartner($merchant);

        $this->core->editPartnerActivation($merchant, $input);

        return $this->core->createPartnerResponse($merchant->merchantDetail);
    }

    private function validatePartnerFormSaveAndSubmit(Merchant\Entity $merchant)
    {
        $this->merchantValidator->validateIsPartner($merchant);

        $merchantDetails = $merchant->merchantDetail;

        if($merchantDetails->isLocked() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED);
        }

        $partnerActivation = $this->core->getPartnerActivation($merchant);

        if($partnerActivation->isLocked() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PARTNER_ACTIVATION_ALREADY_LOCKED);
        }
    }
}
