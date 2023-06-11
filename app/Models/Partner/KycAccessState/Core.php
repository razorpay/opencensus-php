<?php

namespace RZP\Models\Partner\KycAccessState;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\AccessMap;
use RZP\Error\PublicErrorDescription;
use RZP\Mail\Merchant\Partner as PartnerEmail;
use RZP\Models\Partner\Metric as PartnerMetric;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;

class Core extends Base\Core
{
    public function accessRequestExistHandle(Base\PublicCollection $accessRequest)
    {
        $kycAccessState = $accessRequest->first();

        $state = $kycAccessState->getState();
        $rejectionCount = $kycAccessState->getRejectionCount();

        if ($state === State::APPROVED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_KYC_ACCESS_ALREADY_APPROVED);
        }

        if ($rejectionCount >= Constants::MAX_REJECTION_COUNT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_KYC_ACCESS_ALREADY_REJECTED);
        }

        $expiry = $kycAccessState->getExpiryTime();

        if (($this->isTokenTimeExpired($expiry) === false) and ($this->isTokensNotNull($kycAccessState) === true))
        {
            // reset the expiry time back to token expiry time due to resend
            $kycAccessState->setExpiryTime($this->generateExpiryTime());
        }
        else
        {
            // generate new tokens and expiry
            $kycAccessState->generateApproveToken();
            $kycAccessState->generateRejectToken();
            $kycAccessState->setExpiryTime($this->generateExpiryTime());
        }

        $kycAccessState->setState(State::PENDING_APPROVAL);
        $this->repo->saveOrFail($kycAccessState);

        return $kycAccessState;
    }


    /**
     *
     * Initiate a new kyc access request if no records found for a partner and subMerchant.
     *  If request already exists in Db, check the state
     *      - if state is approved/rejected , throw error
     *      - if state is pending_approval,
     *         -  generate new tokens and expiry if old tokens are null or expired.
     *         -  else return the db request
     *
     * @param Merchant\Entity $partner
     * @param array $input
     * @return mixed|Entity
     * @throws Exception\BadRequestException
     */
    public function createOrGetRequestForSubMerchantKyc(Merchant\Entity $partner, array $input)
    {
        // check if any record exists
        $accessRequest = $this->repo->partner_kyc_access_state->findByPartnerIdAndEntityId($partner->getMerchantId(), $input[Entity::ENTITY_ID]);

        if ($accessRequest->isEmpty() === false)
        {
            $subMerchantKycAccess = $this->accessRequestExistHandle($accessRequest);
        }
        else
        {
            $subMerchantKycAccess = new Entity;
            $subMerchantKycAccess->build($input);
            $subMerchantKycAccess->generateId();
            $expiryTime = $this->generateExpiryTime();
            $subMerchantKycAccess->setExpiryTime($expiryTime);
            $subMerchantKycAccess->setPartnerId($partner->getId());

            $this->repo->saveOrFail($subMerchantKycAccess);
        }

        $this->sendKycAccessRequestEmail($partner, $subMerchantKycAccess);

        $this->sendKycAccessRequestSms($partner, $subMerchantKycAccess);

        return $subMerchantKycAccess;
    }

    protected function sendKycAccessRequestEmail(Merchant\Entity $partner, Entity $kycAccess)
    {
        $merchant = $this->repo->merchant->findOrFail($kycAccess->getEntityId());

        $websiteUrl = $this->app['config']->get('app.razorpay_website_url');

        $viewPayload['merchant'] = $merchant->toArray();
        $viewPayload['partner']  = $partner->toArray();
        $viewPayload['approve_url'] = $websiteUrl. '/submerchant-kyc-access-request/?' . http_build_query(array('entity_id' => $kycAccess->getEntityId(), 'approve_token' => $kycAccess->getApprovedToken(), 'partner_id' => $kycAccess->getPartnerId()));
        $viewPayload['reject_url'] = $websiteUrl. '/submerchant-kyc-access-request/?' . http_build_query(array('entity_id' => $kycAccess->getEntityId(), 'reject_token' => $kycAccess->getRejectToken(), 'partner_id' => $kycAccess->getPartnerId()));

        try
        {
            $mail = new PartnerEmail\KycAccessRequest($viewPayload);
            Mail::send($mail);
        }
        catch (\Throwable $e)
        {
            $this->trace->count(PartnerMetric::PARTNER_KYC_REQUEST_EMAIL_FAILED);

            $this->trace->traceException($e, Trace::CRITICAL, TraceCode::PARTNER_KYC_REQUEST_EMAIL_FAILED, [
                'merchant_id'      => $merchant->getId(),
                'partner_id'       => $partner->getId()
            ]);
        }

    }

    /**
     *
     * Sends  kyc access request Sms to sub merchant with approval and reject option.
     *
     * @param Merchant\Entity $partner
     * @param Entity $kycAccess
     *
     */
    protected function sendKycAccessRequestSms(Merchant\Entity $partner, Entity $kycAccess)
    {
        $merchant = $this->repo->merchant->findOrFail($kycAccess->getEntityId());

        $websiteUrl = $this->app['config']->get('app.razorpay_website_url');

        $smsPayload['submerchant_name'] = $merchant->getName();
        $smsPayload['partner_name']     = $partner->getName();

        $approveUrl    = $websiteUrl. '/submerchant-kyc-access-request/?' . http_build_query(array('entity_id' => $kycAccess->getEntityId(), 'approve_token' => $kycAccess->getApprovedToken(), 'partner_id' => $kycAccess->getPartnerId()));
        $rejectUrl     = $websiteUrl. '/submerchant-kyc-access-request/?' . http_build_query(array('entity_id' => $kycAccess->getEntityId(), 'reject_token' => $kycAccess->getRejectToken(), 'partner_id' => $kycAccess->getPartnerId()));
        $smsPayload['approve_url']      = $this->app['elfin']->shorten($approveUrl);
        $smsPayload['reject_url']       = $this->app['elfin']->shorten($rejectUrl);

        try
        {
            $user = $merchant->primaryOwner();

            if($user->isContactMobileVerified())
            {
                $smsPayload = [
                    'ownerId'           => $merchant->getId(),
                    'ownerType'         => 'merchant',
                    'orgId'             => $merchant->getOrgId(),
                    'sender'            => 'RZRPAY',
                    'destination'       => $merchant->merchantDetail->getContactMobile(),
                    'templateName'      => 'Sms.Submerchant_kyc_access.Requested',
                    'templateNamespace' => 'partnerships',
                    'language'          => 'english',
                    'contentParams'     => [
                        'subMerchantName'        => $smsPayload['submerchant_name'],
                        'partnerName'            => $smsPayload['partner_name'],
                        'approvalUrl'            => $smsPayload['approve_url'],
                        'rejectUrl'              => $smsPayload['reject_url'],
                    ]
                ];

                $this->app->stork_service->sendSms($this->mode, $smsPayload);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->count(PartnerMetric::PARTNER_KYC_REQUEST_SMS_FAILED);

            $this->trace->traceException($e, Trace::CRITICAL, TraceCode::PARTNER_KYC_REQUEST_SMS_FAILED, [
                'merchant_id'      => $merchant->getId(),
                'partner_id'       => $partner->getId()
            ]);
        }
    }
    /**
     * Trigger sending Email/SMS/Whatsapp to partner when KYC access request is confirmed or rejected
     *
     * @param Entity $kycAccess
     * @param bool   $isConfirmed
     */
    protected function sendKycRequestConfirmedRejectedCommunication(Entity $kycAccess, bool $isConfirmed)
    {
        $merchant = $this->repo->merchant->findOrFail($kycAccess->getEntityId());
        $partner  = $this->repo->merchant->findOrFail($kycAccess->getPartnerId());

        $this->sendKycRequestConfirmedRejectedEmail($merchant, $partner, $isConfirmed);
        $this->sendKycRequestConfirmedRejectedMessages($merchant, $partner, $isConfirmed);
    }

    /**
     * Trigger sending SMS/Whatsapp to partner when KYC access request is confirmed or rejected
     *
     * @param Merchant\Entity $subMerchant
     * @param Merchant\Entity $partner
     * @param bool            $isConfirmed
     */
    protected function sendKycRequestConfirmedRejectedMessages(Merchant\Entity $subMerchant, Merchant\Entity $partner, bool $isConfirmed)
    {

        // Note: setting partner in the args sends sms/wa notification exclusively to the passed $partner.
        $args = [
            MerchantConstants::MERCHANT => $subMerchant,
            MerchantConstants::PARTNER  => $partner,
            MerchantConstants::PARAMS   => [
                'subMerchantName' => $subMerchant->getTrimmedName(25, "..."),
                'subMerchantId'   => $subMerchant->getId()
            ]
        ];

        $notificationHandler = new OnboardingNotificationHandler($args);
        $notificationEvent   = $isConfirmed ? OnboardingEvents::PARTNER_SUBMERCHANT_KYC_ACCESS_APPROVED : OnboardingEvents::PARTNER_SUBMERCHANT_KYC_ACCESS_REJECTED;
        $notificationHandler->sendForEvent($notificationEvent);
        $dimensions = array("channel" => "sms/whatsapp");
        $this->trace->count(PartnerMetric::PARTNER_KYC_NOTIFICATION_TOTAL, $dimensions);
    }

    /**
     * Trigger sending Email to partner when KYC access request is confirmed or rejected
     *
     * @param Merchant\Entity $merchant
     * @param Merchant\Entity $partner
     * @param bool            $isConfirmed
     */
    protected function sendKycRequestConfirmedRejectedEmail(Merchant\Entity $merchant, Merchant\Entity $partner, bool $isConfirmed)
    {
        $viewPayload['merchant'] = $merchant->toArray();
        $viewPayload['partner']  = $partner->toArray();

        if ($isConfirmed)
        {
            $mail = new PartnerEmail\KycAccessConfirmed($viewPayload);
        }
        else
        {
            $mail = new PartnerEmail\KycAccessRejected($viewPayload);
        }
        Mail::send($mail);
        $dimensions = array("channel" => "email");
        $this->trace->count(PartnerMetric::PARTNER_KYC_NOTIFICATION_TOTAL, $dimensions);
    }

    public function confirmRequestForSubMerchantKyc(array $input)
    {
        $accessRequest = new Base\PublicCollection();

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        if (isset($input[Entity::APPROVE_TOKEN]) === true)
        {
            $accessRequest = $this->repo->partner_kyc_access_state->findByPartnerIdAndEntityIdAndToken($input[Entity::PARTNER_ID], $input[Entity::ENTITY_ID], 'approve_token', $input[Entity::APPROVE_TOKEN]);
        }
        elseif (isset($input[Entity::REJECT_TOKEN]) === true)
        {
            $accessRequest = $this->repo->partner_kyc_access_state->findByPartnerIdAndEntityIdAndToken($input[Entity::PARTNER_ID], $input[Entity::ENTITY_ID], 'reject_token', $input[Entity::REJECT_TOKEN]);
        }

        if ($accessRequest->isEmpty() === true)
        {
            // throw an exception that access request doesnt exist
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_NO_RECORDS_FOUND);
        }

        $subMerchantKycAccess = $accessRequest->first();

        $eventData = [
            'partner_id'    => $input[Entity::PARTNER_ID],
            'submerchant_id'  => $input[Entity::ENTITY_ID],
        ];

        $this->mode= Mode::LIVE;

        if (isset($input[Entity::APPROVE_TOKEN]) === true)
        {
            $subMerchantKycAccess->setState(State::APPROVED);
            $subMerchantKycAccess->setRejectTokenNull();
            $subMerchantKycAccess->setApproveTokenNull();

            $accessMap     = (new AccessMap\Repository)->fetchSubMerchantReferredByPartner($input[Entity::ENTITY_ID], $input[Entity::PARTNER_ID]);
            $accessMapping = (new AccessMap\Repository)->findMerchantAccessMapOnEntityId($input[Entity::ENTITY_ID], $accessMap['application_id'], 'application');

            $accessMapping->setHasKycAccess();

            $this->repo->transactionOnLiveAndTest(function() use ($accessMapping, $subMerchantKycAccess) {
                $this->repo->saveOrFail($subMerchantKycAccess);
                $this->repo->saveOrFail($accessMapping);
            });

            $eventData['status'] = State::APPROVED;
            $this->app['diag']->trackOnboardingEvent(EventCode::PARTNER_KYC_ACCESS_APPROVE, null, null, $eventData);
            $this->sendKycRequestConfirmedRejectedCommunication($subMerchantKycAccess, true);
        }
        elseif (isset($input[Entity::REJECT_TOKEN]) === true)
        {
            $subMerchantKycAccess->setState(State::REJECTED);
            $subMerchantKycAccess->incrementRejectionCount();

            $this->repo->saveOrFail($subMerchantKycAccess);

            $eventData['status'] = State::REJECTED;
            $this->app['diag']->trackOnboardingEvent(EventCode::PARTNER_KYC_ACCESS_REJECT, null, null, $eventData);
            $this->sendKycRequestConfirmedRejectedCommunication($subMerchantKycAccess, false);
        }

        $this->trace->info(TraceCode::PARTNER_KYC_ACCESS__REQUEST, ['events_data' => $eventData]);

        return $subMerchantKycAccess;
    }

    /**
     * This function does the following things:
     * 1. Deletes the access state entity if available
     * 2. Set the has_kyc_access flag to false in access map table
     *
     * @param $partnerId
     * @param $entityId
     * @return mixed
     * @throws \Throwable
     */
    public function revokeKycAccess($partnerId, $entityId)
    {
        $accessMap     = (new AccessMap\Repository)->fetchSubMerchantReferredByPartner($entityId, $partnerId);
        $accessMapping = (new AccessMap\Repository)->findMerchantAccessMapOnEntityId($entityId, $accessMap['application_id'], 'application');

        $this->repo->transactionOnLiveAndTest(function() use ($accessMapping, $partnerId, $entityId) {
            // check if any record exists
            $accessRequest = $this->repo->partner_kyc_access_state->findByPartnerIdAndEntityId($partnerId, $entityId)->first();

            if (empty($accessRequest) === false)
            {
                $this->repo->deleteOrFail($accessRequest);
            }

            $accessMapping->removeKycAccess();
            $this->repo->saveOrFail($accessMapping);
        });

        return $accessMapping;
    }

    public function generateExpiryTime()
    {
        return Carbon::now(Timezone::IST)->timestamp + Constants::TOKEN_EXPIRY_TIME;
    }

    public function isTokensNotNull(Entity $kycAccessState)
    {
        if ($kycAccessState->isApproved() === true or $kycAccessState->isRejected() === true)
        {
            return false;
        }

        return true;
    }

    public function isTokenTimeExpired($expiry): bool
    {
        if ($expiry < Carbon::now(Timezone::IST)->timestamp)
        {
            return true;
        }

        return false;
    }
}
