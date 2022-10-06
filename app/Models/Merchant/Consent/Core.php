<?php

namespace RZP\Models\Merchant\Consent;


use RZP\Base\ConnectionType;
use RZP\Models\Base;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\FetchLegalDocumentBaseResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\LegalDocumentBaseResponse;
use RZP\Models\Merchant\Detail\Service as DetailService;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Models\Merchant\Consent\Details\Entity as DetailEntity;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;

class Core extends Base\Core
{

    /**
     * @param                $input
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
        //TODO: add retry logic here.
        return null;
    }

    /**
     * @param string $merchantId
     *
     * @return
     */
    public function getMerchantConsents($merchantId)
    {
        $responseData = [];

        if ((new DetailService())->checkIfConsentsPresent($merchantId, "L2") === false)
        {
            return $responseData;
        }

        $bvsResponse = $this->callBVSToGetLegalDocumentsByOwnerId($merchantId);

        $bvsResponseData = $bvsResponse->getResponseData();

        $documentCount = $bvsResponseData['count'];

        $documentDetail = $bvsResponseData['documents_detail'];

        for($count = 0; $count < $documentCount; $count++)
        {
            $fileStoreId  = $documentDetail[$count]->getUfhFileId();

            $ufhService = $this->app['ufh.service'];
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
     *
     * @return
     */
    private function callBVSToGetLegalDocumentsByOwnerId($merchantId)
    {
        $requestBody = [
            "platform"                      => 'pg',
            "owner_id"                      => $merchantId
        ];

        $response = (new BvsClient\BvsLegalDocumentManagerClient($this->merchant))->getLegalDocumentsByOwnerId($requestBody);

        return new FetchLegalDocumentBaseResponse($response);
    }

}
