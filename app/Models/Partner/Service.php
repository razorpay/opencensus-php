<?php

namespace RZP\Models\Partner;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Detail;
use RZP\Models\Partner\Activation;
use RZP\Services\Segment\EventCode as SegmentEvent;

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

    /**
     * @throws \Throwable
     * @throws Exception\LogicException
     * @throws Exception\BadRequestException
     */
    public function savePartnerDetailsForActivation(array $input)
    {
        $this->partnerActivationValidator->validatePartnerFormSaveAndSubmit($this->merchant);

        $this->partnerActivationValidator->validateInput('savePartnerActivation', $input);

        $this->merchant->load('merchantDetail');

        $merchantDetail = $this->merchant->merchantDetail;

        $merchantInput = $input;

        unset($merchantInput[Detail\Entity::SUBMIT]);
        unset($merchantInput[Detail\Entity::KYC_CLARIFICATION_REASONS]);

        if (empty($merchantInput) === false)
        {
            $merchantInput[Activation\Constants::PARTNER_KYC_FLOW] = true;

            (new Detail\Core())->saveMerchantDetails($merchantInput, $this->merchant);
        }

        $response = $this->core->processPartnerActivation($input, $merchantDetail, $this->merchant);

        $response[Detail\Constants::LOCK_COMMON_FIELDS] = (new Detail\Core())->fetchCommonFieldsToBeLocked($merchantDetail);

        return $response;
    }

    public function getPartnerActivationDetails()
    {

        $this->merchantValidator->validateIsPartner($this->merchant);

        $merchantDetails = $this->merchant->merchantDetail;

        $response = $this->core->createPartnerResponse($merchantDetails);

        $response[Detail\Constants::LOCK_COMMON_FIELDS] = (new Detail\Core())->fetchCommonFieldsToBeLocked($merchantDetails);

        return $response;
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

    public function bulkAssignReviewer(array $input): array
    {
        (new Detail\Validator())->validateInput('bulk_assign_reviewer', $input);

        $merchants  = $input[Detail\Entity::MERCHANTS];

        $reviewerId = $input[Detail\Entity::REVIEWER_ID];

        return $this->activationCore->bulkAssignReviewer($reviewerId, $merchants);
    }

    public function sendEventsOfPartnersWithPendingCommissionAndIncompleteKYC(): array
    {

        $partnerIdsWithIncompleteKyc = $this->repo->partner_activation->fetchPartnersWithIncompleteKyc();

        $month = Carbon::now(Timezone::IST)->month;

        foreach($partnerIdsWithIncompleteKyc as $partnerId)
        {
            $partner = $this->repo->merchant->fetchMerchantFromId($partnerId);

            $commissionBalance = $partner->commissionBalance;

            if($commissionBalance !== null and ($commissionBalance->getBalance() > 0))
            {
                $properties = [
                    'partner_id'     =>  $partnerId,
                    'product_group'  =>  $partner[Merchant\Entity::PRODUCT],
                    'month'          =>  $month,
                    'commission'     =>  $commissionBalance->getBalance()
                ];

                $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                    $partner, $properties, SegmentEvent::PARTNER_HAVE_COMMISSION);
            }

        }

        return ['success' => 'true'];
    }

    public function sendPartnerWeeklyActivationSummaryEmails(array $input)
    {
        $limit = $input['limit'] ?? null;

        $afterId = $input['afterId'] ?? null;

        $mock = json_decode($input['mock'] ?? false);

        return $this->core->dispatchPartnerWeeklyActivationSummaryMails($limit, $afterId, $mock);
    }
}
