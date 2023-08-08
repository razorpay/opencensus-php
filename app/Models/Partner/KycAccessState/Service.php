<?php

namespace RZP\Models\Partner\KycAccessState;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Entity as MerchantEntity;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function createRequestForSubMerchantKyc($input)
    {
        $partner = $this->fetchPartner();

        (new Entity)->getValidator()->validateInput('create', $input);

        (new Validator)->validateMerchantReferredByPartner($partner->getId(), $input[Entity::ENTITY_ID]);

        $subMerchantKycAccess = $this->core->createOrGetRequestForSubMerchantKyc($partner, $input);

        return $subMerchantKycAccess->toArrayPublic();
    }

    public function confirmRequestForSubMerchantKyc($input)
    {
        (new Entity)->getValidator()->validateInput('token', $input);

        $subMerchantKycAccess = $this->core->confirmRequestForSubMerchantKyc($input);

        return $subMerchantKycAccess->toArrayPublic();
    }

    public function revokeKycAccess($input)
    {
        (new Entity)->getValidator()->validateInput('revoke_access', $input);

        (new Validator)->validateMerchantReferredByPartner($input[Entity::PARTNER_ID], $this->merchant->getId());

        $accessMap = $this->core->revokeKycAccess($input[Entity::PARTNER_ID], $this->merchant->getId());

        return $accessMap->toArrayPublic();
    }

    public function getKycAccessStatus($input)
    {
        (new Entity)->getValidator()->validateInput('get_access_status', $input);

        $partnerId = $this->fetchPartnerFromReferralCode($input['ref_code']);

        (new Validator)->validateMerchantReferredByPartner($partnerId, $this->merchant->getId());

        return $this->core->getKycAccessStatus($partnerId, $this->merchant->getId());
    }

    protected function fetchPartnerFromReferralCode(string $referralCode)
    {
        $referral = $this->repo->referrals->getReferralByReferralCode($referralCode);

        return $referral->getMerchantId();
    }

    /**
     * @return MerchantEntity
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function fetchPartner(): MerchantEntity
    {
        $partner = $this->merchant;

        if ($partner === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PARTNER_CONTEXT_NOT_SET,
                Entity::PARTNER_TYPE);
        }

        return $partner;
    }

    public function CreateAndUpdateKycAccess($input)
    {
        (new Entity)->getValidator()->validateInput('consent', $input);

        $input[Entity::PARTNER_ID] = $this->fetchPartnerFromReferralCode($input['ref_code']);

        $input[Entity::ENTITY_ID] = $this->merchant->getId();

        (new Validator)->validateMerchantReferredByPartner($input[Entity::PARTNER_ID], $input[Entity::ENTITY_ID]);

        $this->core->createRequestKycAndConfirmKycAccess($input);

        return ['success' => true];
    }

}
