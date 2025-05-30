<?php

namespace RZP\Models\Partner\KycAccessState;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use RZP\Models\User\Role;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\AccessMap;
use RZP\Error\PublicErrorDescription;
use RZP\Mail\Merchant\Partner as PartnerEmail;
use RZP\Models\Partner\Metric as PartnerMetric;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\MerchantApplications as MerchantApplications;


class Core extends Base\Core
{
    const KYC_ACCESS_MUTEX_TIMEOUT = 300; // in seconds
    const PRTS_DUAL_WRITE_MUTEX_KEY = 'kyc_access_state_dual_write';

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

        $urls = $this->getApproveRejectUrls($websiteUrl, $kycAccess, $partner);
        $viewPayload['approve_url'] = $urls['approve_url'];
        $viewPayload['reject_url'] = $urls['reject_url'];

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


     protected function getMkycAccessUrl($websiteUrl,$requestData) {
        $encoded = base64_encode($requestData);
        $newMkycAccessUrl = $websiteUrl . '/partner/kyc-access/#request_data=' . $encoded;
        return $newMkycAccessUrl;
    }

    protected function isPartnerMkycAccessExperimentEnabled($partner): bool
    {
        $expId = $this->app['config']->get('app.mkyc_reseller_experiment_id');
        $variant = 'mkyc_reseller_flow_enabled';
        if ($partner->getPartnerType() === MerchantConstants::AGGREGATOR)
        {
            $expId = $this->app['config']->get('app.mkyc_aggregator_experiment_id');
            $variant = 'mkyc_aggregator_flow_enabled';
        }
        $properties = [
            'id'            => $partner->getId(),
            'experiment_id' => $expId,
        ];
        return (new Merchant\Core())->isSplitzExperimentEnable($properties, $variant);
    }

    protected function getApproveRejectUrls($websiteUrl, $kycAccess, $partner)
    {
        $approveUrl = $websiteUrl. '/submerchant-kyc-access-request/?' . http_build_query(array('entity_id' => $kycAccess->getEntityId(), 'approve_token' => $kycAccess->getApprovedToken(), 'partner_id' => $kycAccess->getPartnerId()));
        $rejectUrl = $websiteUrl. '/submerchant-kyc-access-request/?' . http_build_query(array('entity_id' => $kycAccess->getEntityId(), 'reject_token' => $kycAccess->getRejectToken(), 'partner_id' => $kycAccess->getPartnerId()));
        if ($this->isPartnerMkycAccessExperimentEnabled($partner)) {
            $requestData = [
                'entity_id' => $kycAccess->getEntityId(),
                'partner_id' => $kycAccess->getPartnerId(),
                'partner_type' => $partner->getPartnerType(),
                'partner_name' => $partner->getName(),
            ];
            $approveRequestData = array_merge($requestData, [
                'approve_token' => $kycAccess->getApprovedToken(),
            ]);
            $rejectRequestData = array_merge($requestData, [
                'reject_token' => $kycAccess->getRejectToken(),
            ]);
            $approveUrl = $this->getMkycAccessUrl($websiteUrl, json_encode($approveRequestData));
            $rejectUrl = $this->getMkycAccessUrl($websiteUrl, json_encode($rejectRequestData));
        }

        return ['approve_url' => $approveUrl, 'reject_url' => $rejectUrl];
    }

    protected function sendKycAccessRequestSms(Merchant\Entity $partner, Entity $kycAccess)
    {
        $merchant = $this->repo->merchant->findOrFail($kycAccess->getEntityId());

        $websiteUrl = $this->app['config']->get('app.razorpay_website_url');

        $smsPayload['submerchant_name'] = $merchant->getName();
        $smsPayload['partner_name']     = $partner->getName();

        $urls = $this->getApproveRejectUrls($websiteUrl, $kycAccess, $partner);
        $smsPayload['approve_url'] = $this->app['elfin']->shorten($urls['approve_url']);
        $smsPayload['reject_url'] = $this->app['elfin']->shorten($urls['reject_url']);

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

    public function confirmRequestForSubMerchantKyc(array $input,MerchantEntity $partner)
    {
        $merchantApplicationCore = new MerchantApplications\Core();
        $appType = $merchantApplicationCore->getDefaultAppTypeForPartner($partner);
        $accessRequest = new Base\PublicCollection();

        if (isset($input[Entity::APPROVE_TOKEN]) === true)
        {
            if (isset($input[Constants::CREATE_CONSENT]) && $input[Constants::CREATE_CONSENT]) {
                try {
                    $this->generateConsentInPRTSOrFail($partner, $input);
                } catch (\Throwable $e) {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::CONSENT_CREATION_PARTNERSHIPS_ERROR,
                        ['message' => $e->getMessage()]
                    );
                    $this->trace->count(PartnerMetric::CONSENT_GENERATE_FAILURE_TOTAL);
                }
            }
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

            $accessMap     = (new AccessMap\Repository)->fetchSubMerchantReferredByPartner($input[Entity::ENTITY_ID], $input[Entity::PARTNER_ID], $appType);
            $accessMapping = (new AccessMap\Repository)->findMerchantAccessMapOnEntityId($input[Entity::ENTITY_ID], $accessMap['application_id'], 'application');

            $accessMapping->setHasKycAccess();

            $this->repo->transactionOnLiveAndTestAndAsv(function() use ($accessMapping, $subMerchantKycAccess) {
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

    private function generateConsentInPRTSOrFail(MerchantEntity $partner, array $input): void
    {
        $partnerId = $partner->getId();
        $partnerType = $partner->getPartnerType();
        $merchantId = $input[Entity::ENTITY_ID];
        $this->createKycAccessConsent($partnerId, $merchantId, $partnerType);
        $this->createPartnerTermsConsent($merchantId);
    }

    private function createConsentPayload(string $merchantId, string $eventName, string $partnerId = ''): array
    {
        $baseMetadata = ['consent_timestamp' => time()];

        $consentInput = [
            'merchant_id' => $merchantId,
            'event_name'  => $eventName,
            'metadata'    => $baseMetadata,
        ];

        if (in_array($eventName, [
            Constants::PARTNER_KYC_ACCESS_CONSENT_FOR_RESELLER,
            Constants::PARTNER_KYC_ACCESS_CONSENT_FOR_AGGREGATOR,
        ], true)) {
            $consentInput['entity_type'] = 'partner';
            $consentInput['entity_id']   = $partnerId ?? '';
        }

        return $consentInput;
    }


    private function createKycAccessConsent(string $partnerId, string $merchantId, string $partnerType): void
    {
        $consentInput = $this->createConsentPayload(
            $merchantId,
            $this->getKycAccessConsentEventName($partnerType),
            $partnerId
        );


        app('partnerships')->createConsent($consentInput);
    }

    private function createPartnerTermsConsent(string $merchantId): void
    {
        $consentInput = $this->createConsentPayload(
            $merchantId,
            Constants::L2_CONSENT_EVENT
        );
        app('partnerships')->createConsent($consentInput);
    }
    private function getKycAccessConsentEventName(string $partnerType): string
    {
        switch ($partnerType) {
            case MerchantConstants::RESELLER:
                return Constants::PARTNER_KYC_ACCESS_CONSENT_FOR_RESELLER;
            case MerchantConstants::AGGREGATOR:
                return Constants::PARTNER_KYC_ACCESS_CONSENT_FOR_AGGREGATOR;
            default:
                throw new \InvalidArgumentException("Invalid partner type: $partnerType");
        }
    }

    public function createRequestKycAndConfirmKycAccess(array $input)
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $subMerchantKycAccess = $this->createOrGetKycRequestForEasySubMerchantKyc($input);

        $eventData = [
            'partner_id'      => $input[Entity::PARTNER_ID],
            'submerchant_id'  => $input[Entity::ENTITY_ID],
        ];

        if ($input['status'] === State::APPROVED && $subMerchantKycAccess->getState() !== State::APPROVED )
        {
            $subMerchantKycAccess = $this->approveKycRequestForEasySubMerchantKyc($subMerchantKycAccess, $input);
            $eventData['status'] = State::APPROVED;
            $this->app['diag']->trackOnboardingEvent(EventCode::PARTNER_KYC_ACCESS_APPROVE, null, null, $eventData);
            $this->sendKycRequestConfirmedRejectedCommunication($subMerchantKycAccess, true);
        }
        elseif ($input['status'] === State::REJECTED)
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

    public function upsertFromPRTS(array $input): array
    {
        try
        {
            $payloadStr = $input[Constants::PAYLOAD];
            $payload    = json_decode($payloadStr, true);

            $kycAccessStateId = $payload['partner_kyc_access_state']['id'];

            $resource = self::PRTS_DUAL_WRITE_MUTEX_KEY . '_' . $kycAccessStateId;

            $merchantAccessMapId = null;
            if (isset($payload['merchant_access_map']) === true)
            {
                $merchantAccessMapId = $payload['merchant_access_map']['id'] ?? null;
            }
            $partnerId = $payload['partner_kyc_access_state']['partner_id'];
            $merchantId = $payload['partner_kyc_access_state']['entity_id'];



            $this->app['api.mutex']->acquireAndRelease(
                $resource, function() use ($input, $payload, $kycAccessStateId, $merchantAccessMapId, $partnerId, $merchantId) {
                $partner = $this->repo->merchant->findOrFail($partnerId);
                $subMerchant = $this->repo->merchant->findOrFail($merchantId);
                $kycAccessState = $this->getPKASEntityByIdForDualWrite($kycAccessStateId, $input[Entity::ID]);

                $isKYCAccessGranted = (isset($payload['merchant_access_map']['has_kyc_access']) && $payload['merchant_access_map']['has_kyc_access']);
                $merchantAccessMap = $this->getUpdatedMerchantAccessMapEntityByIdForDualWrite($input[Entity::ID], $isKYCAccessGranted, $merchantAccessMapId);

                $kycAccessState->fillSelectAttributes($payload['partner_kyc_access_state'], Entity::$prtsFillable);

                $this->repo->transactionOnLiveAndTestAndAsv(function() use ($kycAccessState, $merchantAccessMap, $partner, $subMerchant) {
                    (new Merchant\Core())->assignSubmerchantDashboardAccessIfApplicable($partner, $subMerchant,  (new MerchantApplications\Core())->getDefaultAppTypeForPartner($partner), Role::OWNER);
                    $this->repo->saveOrFail($kycAccessState);
                    if (isset($merchantAccessMap) === true)
                    {
                        $this->repo->saveOrFail($merchantAccessMap);
                    }
                });

                $timeNow = millitime();
                $lag = $timeNow - $input[Entity::CREATED_AT];
                $this->trace->histogram(PartnerMetric::REVERSE_SHADOW_KYC_ACCESS_UPSERT_LAG, $lag);
                return $kycAccessState;
            },
                self::KYC_ACCESS_MUTEX_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
            );

            $dimensions = $this->getDimensionsForKYCAccessStateDualWriteMetrics(true);
            $this->trace->count(PartnerMetric::PKAS_DUAL_WRITE_TOTAL, $dimensions);

            return $this->buildAckResponse(['upserted' => true, 'id' => $kycAccessStateId], null, $input[Entity::ID], $input[Entity::CREATED_AT]);
        }
        catch (\Throwable $e)
        {
            return $this->handleExceptionForDualWriteFromPRTS($e, $input);
        }
    }

    private function handleExceptionForDualWriteFromPRTS(\Throwable $exception, array $input)
    {
        $this->trace->traceException(
            $exception,
            Trace::ERROR,
            TraceCode::PRTS_KYC_ACCESS_STATE_DUAL_WRITE_FAILED,
            [
                'input' => $input,
            ]
        );
        $error = $this->buildError($exception->getCode(), $exception->getMessage());

        $dimensions = $this->getDimensionsForKYCAccessStateDualWriteMetrics(false);
        $this->trace->count(PartnerMetric::PKAS_DUAL_WRITE_TOTAL, $dimensions);

        return $this->buildAckResponse(null, $error, $input[Entity::ID], $input[Entity::CREATED_AT]);
    }

    private function getPKASEntityByIdForDualWrite(string $kycAccessStateId, string $outBoxId)
    {
        $kycAccessState = $this->repo->partner_kyc_access_state->find($kycAccessStateId);
        if (isset($kycAccessState) === true)
        {
            return $kycAccessState;
        }

        $this->trace->info(
            TraceCode::PRTS_KYC_ACCESS_STATE_ENTRY_NOT_FOUND,
            [
                'kyc_access_id' => $kycAccessStateId,
                'outbox_id' => $outBoxId,
            ]
        );

        return new Entity;
    }

    private function getUpdatedMerchantAccessMapEntityByIdForDualWrite(string $outBoxId, bool $isKYCAccessGranted, string $merchantAccessMapId = null)
    {
        if (isset($merchantAccessMapId) === false) {
            return null;
        }

        $merchantAccessMap = $this->repo->merchant_access_map->find($merchantAccessMapId);

        if (isset($merchantAccessMap) === false)
        {
            $this->trace->debug(
                TraceCode::PRTS_KYC_ACCESS_STATE_ENTRY_NOT_FOUND,
                [
                    'merchant_access_map_id' => $merchantAccessMapId,
                    'outbox_id'              => $outBoxId,
                ]
            );

            $this->trace->count(PartnerMetric::PKAS_DUAL_WRITE_ACCESS_MAP_NOT_FOUND, [
                'route' => $this->app['worker.ctx']->getJobName() ?? $this->app['request.ctx']->getRoute(),
            ]);
            return null;
        }
        if ($isKYCAccessGranted) {
            $merchantAccessMap->setHasKycAccess();
        }
        else {
            $merchantAccessMap->removeKycAccess();
        }
        return $merchantAccessMap;
    }

    public function createOrGetKycRequestForEasySubMerchantKyc(array $input)
    {
        $accessRequest = $this->repo->partner_kyc_access_state->findByPartnerIdAndEntityId($input[Entity::PARTNER_ID], $input[Entity::ENTITY_ID]);

        $subMerchantKycAccess = new Entity;

        if ($accessRequest->isEmpty() === true)
        {
            $data = [Entity::ENTITY_ID => $input[Entity::ENTITY_ID]];
            $subMerchantKycAccess->build($data);
            $subMerchantKycAccess->generateId();
            $expiryTime = $this->generateExpiryTime();
            $subMerchantKycAccess->setExpiryTime($expiryTime);
            $subMerchantKycAccess->setPartnerId($input[Entity::PARTNER_ID]);

            $this->repo->saveOrFail($subMerchantKycAccess);
        }

        else
        {
            $subMerchantKycAccess = $accessRequest->first();
        }

        return $subMerchantKycAccess;
    }

    protected function approveKycRequestForEasySubMerchantKyc(Entity $subMerchantKycAccess, array $input)
    {
        $subMerchantKycAccess->setRejectTokenNull();
        $subMerchantKycAccess->setState(State::APPROVED);
        $subMerchantKycAccess->setApproveTokenNull();

        $accessMap     = (new AccessMap\Repository)->fetchSubMerchantReferredByPartner($input[Entity::ENTITY_ID], $input[Entity::PARTNER_ID]);
        $accessMapping = (new AccessMap\Repository)->findMerchantAccessMapOnEntityId($input[Entity::ENTITY_ID], $accessMap['application_id'], 'application');

        $accessMapping->setHasKycAccess();

        $this->repo->transactionOnLiveAndTestAndAsv(function() use ($accessMapping, $subMerchantKycAccess) {
            $this->repo->saveOrFail($subMerchantKycAccess);
            $this->repo->saveOrFail($accessMapping);
        });

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

        $this->repo->transactionOnLiveAndTestAndAsv(function() use ($accessMapping, $partnerId, $entityId) {
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

    public function getKycAccessStatus(string $partnerId, string $subMerchantId)
    {
        $partner = $this->repo->merchant->findOrFail($partnerId);

        $accessMap = $this->repo->partner_kyc_access_state->findByPartnerIdAndEntityId($partnerId, $this->merchant->getId())->first();

        $status =  'pending';

        if( empty($accessMap) === false && ($accessMap->getState() !== State::PENDING_APPROVAL) )
        {
            $status = $accessMap->getState();
        }

        return [
            'partner_name'  => $partner->getName(),
            'status'        => $status,
        ];
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

    private function getDimensionsForKYCAccessStateDualWriteMetrics(bool $success): array
    {
        $route = $this->app['worker.ctx']->getJobName() ?? $this->app['request.ctx']->getRoute();
        return [
            'route'   => $route,
            'success' => $success,
        ];
    }

    private function buildAckResponse(?array $response = null, ?array $error = null, ?string $id = null, ?int $createdAt = null): array
    {
        return [
            "id"         => $id ?? null,
            "response"   => $response ?? null,
            "created_at" => $createdAt ?? null,
            "error"      => $error ?? null,
        ];
    }

    private function buildError(string $code, string $message): array
    {
        return [
            "code"    => $code,
            "message" => $message,
        ];
    }
}
