<?php

namespace RZP\Models\Merchant\Consent;


use Carbon\Carbon;
use RZP\Base\ConnectionType;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Exception\LogicException;
use Illuminate\Support\Facades\DB;
use RZP\Models\Merchant\Consent\Processor\Factory;
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

    /**
     * @throws \Throwable
     */
    public function retryStoreLegalDocuments()
    {
        $this->trace->info(TraceCode::MERCHANT_STORE_CONSENTS_CRON_RETRY,
                           [
                               'message' => 'Store consents cron retry initiated!'
                           ]);

        $merchantIdList = $this->repo->merchant_consents->getUniqueMerchantIdsWithConsentsNotSuccess(
            Constants::VALID_LEGAL_DOC,
            Carbon::now()->subDays(Constants::DEFAULT_LAST_CRON_SUB_DAYS)->getTimestamp());

        if (empty($merchantIdList) === true)
        {
            $this->trace->info(TraceCode::CRON_ATTEMPT_SKIPPED, [
                'type'   => 'retry Store Legal Documents cron',
                'reason' => 'no merchants found',
                'step'   => 'get_merchants'
            ]);

            return;
        }

        foreach ($merchantIdList as $merchantId)
        {
            $consentDetailsForMerchant = $this->repo->merchant_consents->getFailedConsentDetailsForMerchants($merchantId);

            $documents_detail = $this->getDocumentsDetails($consentDetailsForMerchant);

            $processor = (new Factory())->getLegalDocumentProcessor();

            $response = $processor->processLegalDocuments($documents_detail);

            $responseData = $response->getResponseData();

            foreach ($consentDetailsForMerchant as $consentDetailForMerchant)
            {
                $type = $consentDetailForMerchant->consent_for;

                $merchantConsentDetail = $this->repo->merchant_consents->fetchMerchantConsentDetails($merchantId, $type);

                $retryCount = $merchantConsentDetail->retry_count + 1;

                $input = [
                    'status'      => ConsentConstant::INITIATED,
                    'updated_at'  => Carbon::now()->getTimestamp(),
                    'request_id'  => $responseData['id'],
                    'retry_count' => $retryCount
                ];

                $this->updateConsentDetails($merchantConsentDetail, $input);

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
     * @return
     */
    public function getMerchantConsents($merchantId)
    {
        $detailService = new DetailService();

        $l2Consents = $detailService->checkIfConsentsPresent($merchantId) ? $this->processAndGetConsents($merchantId, Constants::PG) : [];

        $xConsents = $detailService->checkIfConsentsPresent($merchantId, ConsentConstant::VALID_LEGAL_DOC_FOR_X) ? $this->processAndGetConsents($merchantId, Constants::RX) : [];

        return array_merge($l2Consents, $xConsents);
    }

    protected function processAndGetConsents($merchantId, $platform)
    {
        $responseData = [];

        $bvsResponse = $this->callBVSToGetLegalDocumentsByOwnerId($merchantId, $platform);

        $bvsResponseData = $bvsResponse->getResponseData();

        $documentCount = $bvsResponseData['count'];

        $documentDetail = $bvsResponseData['documents_detail'];

        for ($count = 0; $count < $documentCount; $count++)
        {
            $fileStoreId = $documentDetail[$count]->getUfhFileId();

            $ufhService        = $this->app['ufh.service'];
            $signedUrlResponse = $ufhService->getSignedUrl($fileStoreId, [], $merchantId)['signed_url'];

            $data = [
                'file_store_id' => $fileStoreId,
                'merchant_id'   => $merchantId,
                'created_at'    => $documentDetail[$count]->getAcceptanceTimestamp(),
                'signed_url'    => $signedUrlResponse,
                'consent_type'  => $documentDetail[$count]->getType()
            ];

            $responseData[] = $data;
        }

        return $responseData;
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

    /**
     * @param $consentDetailsForMerchant
     *
     * @return array
     */
    private function getDocumentsDetails($consentDetailsForMerchant): array
    {
        $documents_detail = [];

        foreach ($consentDetailsForMerchant as $consentDetailForMerchant)
        {
            $type = explode("_", $consentDetailForMerchant->consent_for)[1];

            $document_detail = [
                "type"         => $type,
                "content_type" => "html",
                "content"      => (new DetailService())->getFileContentInHtml($consentDetailForMerchant->url)
            ];

            array_push($documents_detail, $document_detail);
        }

        return $documents_detail;
    }

}
