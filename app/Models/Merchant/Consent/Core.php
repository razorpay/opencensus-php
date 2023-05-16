<?php

namespace RZP\Models\Merchant\Consent;


use Carbon\Carbon;
use RZP\Base\ConnectionType;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Exception\LogicException;
use Illuminate\Support\Facades\DB;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Consent\Processor\Factory;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Consent\Processor\Factory as ProcessorFactory;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\FetchLegalDocumentBaseResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\LegalDocumentBaseResponse;
use RZP\Models\Merchant\Consent\Constants as ConsentConstant;
use RZP\Models\Merchant\Detail\Service as DetailService;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Models\Merchant\Consent\Details\Entity as DetailEntity;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * @param $input
     *
     * @return mixed
     * @throws \Throwable
     */
    public function createMerchantConsents($input)
    {
        $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS,
                           [
                               Constants::INPUT => $input
                           ]);

        return $this->repo->transactionOnLiveAndTest(function() use ($input) {

            $consent = new Entity();

            $consent->generateId();

            if (empty($input[DetailEntity::URL]) === false)
            {
                $details = $this->repo->merchant_consent_details->getByUrl($input[DetailEntity::URL]);

                if (empty($details) === true)
                {
                    $detailsInput = [DetailEntity::URL => $input[DetailEntity::URL]];

                    $details = (new Details\Core())->createConsentDetails($detailsInput);
                }

                $input[Entity::DETAILS_ID] = $details->getId();
            }

            unset($input[DetailEntity::URL]);

            $requestContext = $this->app[Constants::REQUEST_CTX];
            $request        = $this->app[Constants::REQUEST];

            if (isset($requestContext) === false or isset($request) === false)
            {
                return null;
            }

            $headers = $request->headers;

            if (isset($headers) === false)
            {
                return null;
            }

            $input += [
                Entity::STATUS      => 'pending',
                Entity::METADATA    => [
                    Constants::USER_AGENT => $headers->get(RequestHeader::X_USER_AGENT),
                    Constants::IP         => $headers->get(RequestHeader::X_DASHBOARD_IP)
                ],
                Entity::MERCHANT_ID => optional($this->app[Constants::BASIC_AUTH]->getMerchant())->getId() ?? '',
                Entity::USER_ID     => optional($this->app[Constants::BASIC_AUTH]->getUser())->getId() ?? ''
            ];

            $consent->build($input);

            $this->repo->merchant_consents->saveOrFail($consent);

            return $consent;
        });
    }

    public function retryStoreLegalDocuments()
    {
        $this->trace->info(TraceCode::MERCHANT_STORE_CONSENTS_CRON_RETRY,
                           [
                               'message' => 'Store consents cron retry initiated!'
                           ]);

        $merchantIdList = $this->repo->merchant_consents->getUniqueMerchantIdsWithConsentsNotSuccess(
            Carbon::now()->subDays(Constants::DEFAULT_LAST_CRON_SUB_DAYS)->getTimestamp(), array_keys(ConsentConstant::VALID_LEGAL_DOC));

        if (empty($merchantIdList) === true)
        {
            $this->trace->info(TraceCode::CRON_ATTEMPT_SKIPPED, [
                'type'   => 'retry Store Legal Documents cron',
                'reason' => 'no merchants found',
                'step'   => 'get_merchants'
            ]);

            return;
        }

        $this->processRetryStoreLegalDocuments($merchantIdList);
    }

    /**
     * @param array  $merchantIdList
     * @param string $platform
     */
    public function processRetryStoreLegalDocuments(array $merchantIdList)
    {
        $mapConsentUrlToFileContent = [];

        foreach ($merchantIdList as $merchantId)
        {
            try
            {
                $this->merchant = $this->repo->merchant->findOrFail($merchantId);

                $this->app['basicauth']->setMerchant($this->merchant);

                $consentDetailsForMerchant = $this->repo->merchant_consents->getFailedConsentDetailsForMerchants($merchantId, array_keys(ConsentConstant::VALID_LEGAL_DOC));

                foreach ($consentDetailsForMerchant as $consentDetailForMerchant)
                {
                    $consents = [
                        'url'  => $consentDetailForMerchant['url'],
                        'type' => $consentDetailForMerchant['consent_for']
                    ];

                    $consentDetails[DEConstants::DOCUMENTS_DETAIL] = [$consents];

                    $documents_detail = (new DetailService())->getDocumentsDetails($consentDetails, $mapConsentUrlToFileContent);

                    $legalDocumentsInput = [
                        DEConstants::DOCUMENTS_DETAIL               => $documents_detail,
                        DEConstants::IP_ADDRESS                     => $consentDetailForMerchant['metadata']['ip_address'],
                        DEConstants::DOCUMENTS_ACCEPTANCE_TIMESTAMP => $consentDetailForMerchant['created_at'],
                    ];

                    $processor = (new Factory())->getLegalDocumentProcessor();

                    $response = $processor->processLegalDocuments($legalDocumentsInput, $this->getPlatform($consentDetailForMerchant['consent_for']));

                    $responseData = $response->getResponseData();

                    $type = $consentDetailForMerchant['consent_for'];

                    $merchantConsentDetail = $this->repo->merchant_consents->fetchMerchantConsentForTypeAndDetailsId($merchantId, $type, $consentDetailForMerchant['details_id']);

                    $input = [
                        'status'      => ConsentConstant::INITIATED,
                        'updated_at'  => Carbon::now()->getTimestamp(),
                        'request_id'  => $responseData['id'],
                        'retry_count' => $merchantConsentDetail->retry_count + 1
                    ];

                    $this->updateConsentDetails($merchantConsentDetail, $input);

                }
            }
            catch (\Throwable $e)
            {
                $this->trace->error(
                    TraceCode::RETRY_LEGAL_DOCUMENT_SAVE_CRON_FAILED,
                    [
                        'message'     => $e->getMessage(),
                        'merchant_id' => $this->merchant->getId(),
                    ]
                );
            }
        }
    }

    public function updateConsentDetails($merchantConsentDetail, $input)
    {
        try
        {
            $this->mutex->acquireAndRelease(

                $merchantConsentDetail->id,

                function() use ($merchantConsentDetail, $input) {

                    $merchantConsentDetail->edit($input, 'edit');

                    $this->repo->merchant_consents->saveOrFail($merchantConsentDetail);
                },

                Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_INVALID_STATUS_TRANSITION,
                Constants::MERCHANT_MUTEX_RETRY_COUNT);

        }
        catch (LogicException $e)
        {
            throw new LogicException($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @param string $merchantId
     *
     * @return array
     */
    public function getMerchantConsents(string $merchantId)
    {
        $consents = $this->repo->merchant_consents->fetchAllConsentForMerchantIdAndConsentType($merchantId, array_keys(ConsentConstant::VALID_LEGAL_DOC));

        if ($consents !== null)
        {
            $responseData = [];

            foreach ($consents as $consent)
            {
                try
                {
                    $this->trace->info(TraceCode::FETCH_MERCHANT_CONSENTS, [
                        "merchant_id" => $merchantId,
                        "entity_id"   => $consent['id'],
                    ]);

                    $fileStoreId = (empty($consent['metadata']['ufh_file_id']) === false) ? $consent['metadata']['ufh_file_id']
                        : $this->fetchAndSaveFileId($consent);

                    $ufhService = $this->app['ufh.service'];

                    $signedUrlResponse = $ufhService->getSignedUrl($fileStoreId, [], $merchantId)['signed_url'];

                    $data = [
                        'file_store_id' => $fileStoreId,
                        'merchant_id'   => $merchantId,
                        'created_at'    => $consent['created_at'],
                        'signed_url'    => $signedUrlResponse,
                        'consent_type'  => $consent['consent_for']
                    ];

                    $responseData[] = $data;
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException($e);
                }
            }

            return $responseData;
        }

        return [];
    }

    /**
     * @param $input
     *
     * @throws LogicException
     * @throws BadRequestException
     */
    public function saveMerchantConsents($input)
    {
        $merchant = $this->merchant;

        $merchantDetails = $this->merchant->merchantDetail;

        $merchantDetails->getValidator()->validateInput('merchantConsent', $input);

        $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS, [
            "merchant_id" => $merchant->getId(),
            "input"       => $input['consents'],
        ]);

        if($this->checkIfConsentProvided($input['consents']) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY,
                null,
                [
                    'merchant_id'      => $merchant->getId(),
                    'consent_provided' => false,
                ]);
        }

        $documentsDetail = $this->createAggregatedDocumentDetails($input['consents']);

        $legalDocumentsInput = [
            DEConstants::DOCUMENTS_DETAIL  => (new DetailService())->getDocumentsDetails($documentsDetail),
        ];

        $processor = (new ProcessorFactory())->getLegalDocumentProcessor();

        $response = $processor->processLegalDocuments($legalDocumentsInput);

        $responseData = $response->getResponseData();

        (new DetailService())->storeConsents($merchant->getId(), $documentsDetail, null, $responseData['status'], $responseData['id']);
    }

    /**
     * @param string $merchantId
     * @param string $platform
     *
     * @return
     */
    private function callBVSToGetLegalDocumentsByOwnerId($merchantId, $platform)
    {

        $requestBody = [
            "platform"                      => $platform,
            "owner_id"                      => $merchantId
        ];

        $response = app('bvs_legal_document_manager')->getLegalDocumentsByOwnerId($requestBody);

        return new FetchLegalDocumentBaseResponse($response);
    }

    private function checkIfConsentProvided($consents)
    {
        foreach ($consents as $consent)
        {
            $docType = $consent['documents_detail']['type'];

            if (array_key_exists($docType, ConsentConstant::VALID_LEGAL_DOC) === true)
            {
                if(ConsentConstant::VALID_LEGAL_DOC[$docType][ConsentConstant::MANDATORY] === true and $consent['is_provided'] == false)
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }

        return true;
    }

    private function createAggregatedDocumentDetails($consents)
    {
        $documents_detail = [];

        foreach ($consents as $consent)
        {
            array_push($documents_detail, $consent[DEConstants::DOCUMENTS_DETAIL]);
        }

        return [
            DEConstants::DOCUMENTS_DETAIL => $documents_detail
        ];
    }

    private function getPlatform(string $type)
    {
        return ConsentConstant::VALID_LEGAL_DOC[$type][ConsentConstant::PLATFORM];
    }

    private function fetchAndSaveFileId($consent)
    {
        $requestBody = [
            "id"   => $consent['request_id']
        ];

        $bvsResponse = app('bvs_legal_document_manager')->getLegalDocumentsByRequestId($requestBody);

        $bvsResponseData = $bvsResponse->getResponseData();

        $this->trace->info(TraceCode::FETCH_CONSENT_SUCCESS, [
            "request_body"  => $requestBody,
            "response"      => $bvsResponseData,
        ]);

        $documentCount = $bvsResponseData['count'];

        $documentDetail = $bvsResponseData['documents_detail'];

        for ($count = 0; $count < $documentCount; $count++)
        {
            $this->trace->info(TraceCode::FETCH_DOCUMENTS_DETAILS, [
                "type_of_document" => $documentDetail[$count]->getType(),
                "consent_for"      => $consent['consent_for'],
                "ufh_file_id"      => $documentDetail[$count]->getUfhFileId(),
                "status"           => $documentDetail[$count]->getStatus()
            ]);

            if (empty($documentDetail[$count]->getType()) === false
               and str_contains(strtolower($consent['consent_for']),
                                strtolower($documentDetail[$count]->getType())))
            {
                $input['metadata'] = $this->mergeJson($consent['metadata'], [
                    'ufh_file_id' => $documentDetail[$count]->getUfhFileId()]);

                $this->updateConsentDetails($consent, $input);

                return $documentDetail[$count]->getUfhFileId();
            }
        }

        return null;
    }

    public function mergeJson($existingDetails, $newDetails)
    {
        if (empty($newDetails) === false)
        {
            foreach ($newDetails as $key => $value)
            {
                $existingDetails[$key] = $value;
            }
        }

        return $existingDetails;
    }
}
