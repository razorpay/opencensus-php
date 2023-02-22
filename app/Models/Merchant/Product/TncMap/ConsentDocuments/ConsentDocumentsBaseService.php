<?php

namespace RZP\Models\Merchant\Product\TncMap\ConsentDocuments;

use App;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Consent;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Product\TncMap;
use RZP\Models\Merchant\Product\Entity;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Consent\Constants as ConsentConstant;
use RZP\Models\Merchant\Consent\Processor\Factory as ProcessorFactory;
use RZP\Models\Merchant\Product\BusinessUnit\Constants as BusinessUnit;

class ConsentDocumentsBaseService extends Base\Service
{
    private $merchantDetailService;

    private $merchantConsentCore;

    private $tncMapCore;

    private $tncCore;

    public function __construct()
    {
        parent::__construct();

        $this->merchantDetailService = new Detail\Service();

        $this->merchantConsentCore = new Consent\Core();

        $this->tncMapCore = new TncMap\Core();

        $this->tncCore = new TncMap\Acceptance\Core();
    }

    /**
     * This function will perform following actions
     *  i. store consent details for all the documents in merchant_consents table
     *  ii. call bvs for creation of legal docs with the owner and document details
     *  iii. store bvs response in merchant_consents table
     *
     * @param Merchant\Entity $merchant
     * @param Entity          $merchantProduct
     * @param string          $activationFormMilestone
     */
    public function createLegalDocuments(Merchant\Entity $merchant, Entity $merchantProduct, string $activationFormMilestone)
    {
        try
        {
            $startTime = microtime(true);

            $partnerId = $this->repo->merchant_access_map->fetchEntityOwnerIdsForSubmerchant($merchant->getId())->first();

            $isExpEnabled = $this->tncCore->isPartnerExcludedFromProvidingSubmerchantIp($partnerId);

            if($merchant->isNoDocOnboardingEnabled() === false and $isExpEnabled === true)
            {
                $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS, [
                    'message' => 'Consents not created as no doc onboarding is not enabled for submerchant and the partner is excluded from providing IP'
                ]);

                return;
            }

            if ($this->merchantDetailService->checkIfConsentsPresent($merchant->getId(), ConsentConstant::VALID_LEGAL_DOC_L2) === true)
            {
                $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS, [
                    'message' => 'Consents are already present.'
                ]);
                return;
            }

            $businessUnit = BusinessUnit::PRODUCT_BU_MAPPING[$merchantProduct->getProduct()];

            $tncMap = $this->tncMapCore->fetchTncForBU($businessUnit);
            $merchantTncAcceptance = $this->tncCore->fetchMerchantAcceptanceViaBU($merchant, $businessUnit);

            if (empty($merchantTncAcceptance) === true)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_TNC_ACCEPTANCE_NOT_PRESENT_FOR_MERCHANT);
            }

            $tncAgreements   = json_decode(json_encode($tncMap->getContent()), true);
            $documentsDetail = [];

            foreach ($tncAgreements as $type => $url)
            {
                $tncAgreementDetail = [
                    DEConstants::TYPE => $type,
                    DEConstants::URL  => $url
                ];
                array_push($documentsDetail, $tncAgreementDetail);
            }

            $consentDetails = [
                DEConstants::DOCUMENTS_DETAIL                => $documentsDetail,
                Detail\Entity::ACTIVATION_FORM_MILESTONE     => $activationFormMilestone,
                DEConstants::IP_ADDRESS                      => $merchantTncAcceptance->getClientIp(),
                DEConstants::DOCUMENTS_ACCEPTANCE_TIMESTAMP  => $merchantTncAcceptance->getAcceptedAt(),
                Consent\Entity::ENTITY_ID                    => $partnerId,
                Consent\Entity::ENTITY_TYPE                  => DEConstants::PARTNER
            ];

            $this->merchantDetailService->storeConsents($merchant->getId(), $consentDetails, $merchant->primaryOwner()->getId());

            $consentDocumentDetails = $this->merchantDetailService->getDocumentsDetails($consentDetails);

            $legalDocumentsInput = [
                DEConstants::DOCUMENTS_DETAIL                   => $consentDocumentDetails,
                DEConstants::IP_ADDRESS                         => $merchantTncAcceptance->getClientIp(),
                DEConstants::DOCUMENTS_ACCEPTANCE_TIMESTAMP     => $merchantTncAcceptance->getAcceptedAt()
            ];

            $processor = (new ProcessorFactory())->getLegalDocumentProcessor();

            $response = $processor->processLegalDocuments($legalDocumentsInput);

            $responseData = $response->getResponseData();

            foreach ($documentsDetail as $documentDetail)
            {
                $type = $activationFormMilestone.'_'.$documentDetail[DEConstants::TYPE] ;

                $merchantConsentDetail = $this->repo->merchant_consents->fetchMerchantConsentDetails($merchant->getId(), $type);

                $input = [
                    'status'     => ConsentConstant::INITIATED,
                    'updated_at' => Carbon::now()->getTimestamp(),
                    'request_id' => $responseData['id']
                ];

                $this->merchantConsentCore->updateConsentDetails($merchantConsentDetail, $input);
            }

            $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS, [
                'merchant_id'                 => $merchant->getId(),
                'start_time'                  => $startTime * 1000,
                'overall_duration'            => (microtime(true) - $startTime) * 1000,
                'bvs_response'                => $responseData,
            ]);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BVS_CREATE_LEGAL_DOCUMENTS_FAILED);
        }
    }
}
