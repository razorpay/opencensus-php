<?php

namespace RZP\Models\Partner\KycAccessState;

use RZP\Exception;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Services\Partnerships;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Repository as MerchantRepo;
use RZP\Constants\Mode;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant;
use RZP\Models\User\Role;

use RZP\Models\Merchant\MerchantApplications as MerchantApplications;
class Service extends Base\Service
{
    use Partnerships\PartnershipServiceTrait;

    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function createRequestForSubMerchantKyc($input)
    {
        // Inject partner id from auth
        $partner = $this->fetchPartner();
        $prtsInput = array_merge($input, [
            'partner_id' => $partner->getId()
        ]);
        $prtsResult = $this->proxyToPartnershipServiceForPKYC($prtsInput, $partner->getId());
        if ($this->isExpModeReverseShadow($prtsResult['variant']) || $this->isExpModeCutOff($prtsResult['variant'])) {
            return $prtsResult['response'];
        }
        $merchantApplicationCore = new MerchantApplications\Core();
        $appType = $merchantApplicationCore->getDefaultAppTypeForPartner($partner);


        (new Entity)->getValidator()->validateInput('create', $input);

        (new Validator)->validateMerchantReferredByPartner($partner->getId(), $input[Entity::ENTITY_ID], $appType);

        $subMerchantKycAccess = $this->core->createOrGetRequestForSubMerchantKyc($partner, $input);
        $apiResponse = $subMerchantKycAccess->toArrayPublic();
        $this->checkParity($prtsResult['response'], $apiResponse);
        return $apiResponse;
    }

    public function confirmRequestForSubMerchantKyc($input)
    {
        $this->trace->info(
            TraceCode::APPROVE_REJECT_REQUEST,
            [
                'input' => $input,
            ]
        );
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);
        (new Entity)->getValidator()->validateInput('token', $input);
        $partner = (new MerchantRepo())->getMerchant($input[Entity::PARTNER_ID]);
        $prtsResult = $this->proxyToPartnershipServiceForPKYC($input, $partner->getId());
        if ($this->isExpModeReverseShadow($prtsResult['variant']) || $this->isExpModeCutOff($prtsResult['variant'])) {
            return $prtsResult['response'];
        }
        $subMerchantKycAccess = $this->core->confirmRequestForSubMerchantKyc($input, $partner);
        $this->checkParity($prtsResult, $subMerchantKycAccess->toArrayPublic());
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

    public function upsertFromPRTS(array $input): array
    {
        (new Validator())->validateInput('upsert_from_prts', $input);

        $this->trace->info(
            TraceCode::PRTS_KYC_ACCESS_STATE_UPSERT_REQUEST,
            [
                'input' => $input,
            ]
        );

        return $this->core()->upsertFromPRTS($input);
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

        if ($partner === null) {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PARTNER_CONTEXT_NOT_SET,
                Entity::PARTNER_TYPE
            );
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
