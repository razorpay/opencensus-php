<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;


use Carbon\Carbon;
use RZP\Exception\TwirpException;
use RZP\Exception\IntegrationException;
use Platform\Bvs\Legaldocumentmanager\V1 as legalDocumentManagerV1;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Trace\TraceCode;
use Twirp\Error;
use Platform\Bvs\Legaldocumentmanager\V1\TwirpError;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\FetchLegalDocumentBaseResponse;

class BvsLegalDocumentManagerClient extends BaseClient
{
    private $legalDocumentManagerApiClient;

    /**
     * BvsLegalDocumentManagerClient constructor.
     *
     * @param null $merchant
     */
    function __construct($merchant = null)
    {
        parent::__construct($merchant);

        $this->legalDocumentManagerApiClient = new legalDocumentManagerV1\LegalDocumentManagerAPIClient($this->host, $this->httpClient);

    }

    /**
     * @param array $document
     *
     * @return legalDocumentManagerV1\LegalDocumentsManagerResponse
     * @throws IntegrationException|TwirpException
     */
    public function createLegalDocument(array $document)
    {
        $legalDocumentCreateRequest = $this->getCreateLegalDocumentRequest($document);

        $requestSuccess = false;

        try
        {
            if (empty($this->merchant) === false)
            {
                $eventAttributes = [
                    'time_stamp'    => Carbon::now()->getTimestamp(),
                    'merchant_id'   => $this->merchant->getMerchantId(),
                    'ip'            => $_SERVER['HTTP_X_IP_ADDRESS'] ?? $this->app['request']->ip()
                ];

                $this->app['segment-analytics']->pushTrackEvent($this->merchant, $eventAttributes, SegmentEvent::AGREEMENT_CREATION_REQUEST);
            }

            $response = $this->legalDocumentManagerApiClient->CreateLegalDocuments($this->apiClientCtx, $legalDocumentCreateRequest);

            if (empty($this->merchant) === false)
            {
                $eventAttributes = [
                    'time_stamp'    => Carbon::now()->getTimestamp(),
                    'merchant_id'   => $this->merchant->getMerchantId(),
                    'ip'            => $_SERVER['HTTP_X_IP_ADDRESS'] ?? $this->app['request']->ip(),
                    'response'      => $response,
                ];

                $this->app['segment-analytics']->pushTrackEvent($this->merchant, $eventAttributes, SegmentEvent::AGREEMENT_CREATION_RESPONSE);
            }

            $requestSuccess = true;

            return $response;
        }
        catch(TwirpError $ex)
        {
            $error =  [
                'code'    => $ex->getErrorCode(),
                'msg'     => $ex->getMessage(),
                'meta'    => $ex->getMetaMap(),
            ];

            $this->trace->info(TraceCode::CONSENT_CREATION_ERROR, [
                "input"       => $error,
            ]);

            throw new TwirpException($error);
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::BVS_RESPONSE_CREATE_CONSENTS, [
                'error'         => $e->getErrorCode(),
                'error_message' => $e->getMessage()
            ]);

            throw new IntegrationException('
                Could not receive proper response from BVS service');
        }
        finally
        {
            $dimension = [
                Constant::SUCCESS       => $requestSuccess,
            ];

            $this->trace->count(Metric::BVS_RESPONSE_TOTAL, $dimension);
        }
    }

    /**
     * @param array $legalDocument
     *
     * @return legalDocumentManagerV1\CreateLegalDocumentsRequest
     */
    protected function getCreateLegalDocumentRequest(array $legalDocument): legalDocumentManagerV1\CreateLegalDocumentsRequest
    {
        $createLegalDocument = new legalDocumentManagerV1\CreateLegalDocumentsRequest();

        $clientDetails = $this->createClientDetails($legalDocument['client_details']);
        $createLegalDocument->setClientDetails($clientDetails);

        $documentDetail = $this->createLegalDocumentDetails($legalDocument['documents_detail']);
        $createLegalDocument->setDocumentsDetail($documentDetail);

        $ownerDetails = $this->createOwnerDetails($legalDocument['owner_details']);
        $createLegalDocument->setOwnerDetails($ownerDetails);

        return $createLegalDocument;
    }

    private function createClientDetails($clientDetails): ?legalDocumentManagerV1\ClientDetails
    {
        $newClientDetails = new legalDocumentManagerV1\ClientDetails();

        $newClientDetails->setPlatform($clientDetails['platform']);

        return $newClientDetails;
    }

    private function createLegalDocumentDetails($legalDocumentDetailsArray)
    {
        $newLegalDocumentsDetailsArray = [];

        foreach ($legalDocumentDetailsArray as $legalDocumentDetails)
        {
            $newLegalDocumentDetails = new legalDocumentManagerV1\LegalDocumentRequestDetails();

            $newLegalDocumentDetails -> setContent($legalDocumentDetails['content']);
            $newLegalDocumentDetails -> setContentType($legalDocumentDetails['content_type']);
            $newLegalDocumentDetails -> setType($legalDocumentDetails['type']);

            array_push($newLegalDocumentsDetailsArray, $newLegalDocumentDetails) ;
        }

        return $newLegalDocumentsDetailsArray;

    }

    private function createOwnerDetails($ownerDetails): ?legalDocumentManagerV1\OwnerDetails
    {
        $newOwnerDetails = new legalDocumentManagerV1\OwnerDetails();

        $newOwnerDetails->setAcceptanceTimestamp($ownerDetails['acceptance_timestamp']);
        $newOwnerDetails->setContactNumber($ownerDetails['contact_number']);
        $newOwnerDetails->setEmail($ownerDetails['email']);
        $newOwnerDetails->setIpAddress($ownerDetails['ip_address']);
        $newOwnerDetails->setOwnerId($ownerDetails['owner_id']);
        $newOwnerDetails->setOwnerName($ownerDetails['owner_name']);
        $newOwnerDetails->setSignatoryName($ownerDetails['signatory_name']);

        return $newOwnerDetails;
    }

    public function getLegalDocumentsByOwnerId(array $requestBody)
    {
        $fetchLegalDocumentRequest = $this->createFetchLegalDocumentRequest($requestBody);

        $requestSuccess = false;

        try
        {
            $response = $this->legalDocumentManagerApiClient->GetLegalDocumentsByOwnerId($this->apiClientCtx, $fetchLegalDocumentRequest);

            $requestSuccess = true;

            return $response;
        }
        catch (Error $e)
        {
            throw new IntegrationException('
                Could not receive proper response from BVS service');
        }
        finally
        {
            $dimension = [
                Constant::SUCCESS       => $requestSuccess,
            ];

            $this->trace->count(Metric::BVS_RESPONSE_TOTAL, $dimension);
        }
    }

    private function createFetchLegalDocumentRequest(array $requestBody)
    {
        $fetchLegalDocument = new legalDocumentManagerV1\GetLegalDocumentsByOwnerIdRequest();

        $fetchLegalDocument->setOwnerId($requestBody['owner_id']);
        $fetchLegalDocument->setPlatform($requestBody['platform']);

        return $fetchLegalDocument;
    }

    /**
     * @param array $requestBody
     *
     * @return FetchLegalDocumentBaseResponse
     * @throws IntegrationException
     * @throws TwirpException
     */
    public function getLegalDocumentsByRequestId(array $requestBody)
    {
        $legalDocument = new legalDocumentManagerV1\GetLegalDocumentsRequest($requestBody);

        $requestSuccess = false;

        try
        {
            $response = $this->legalDocumentManagerApiClient->GetLegalDocuments($this->apiClientCtx, $legalDocument);

            $requestSuccess = true;

            return new FetchLegalDocumentBaseResponse($response);
        }
        catch(TwirpError $ex)
        {
            $error =  [
                'code'    => $ex->getErrorCode(),
                'msg'     => $ex->getMessage(),
                'meta'    => $ex->getMetaMap(),
            ];

            $this->trace->info(TraceCode::FETCH_CONSENT_FAILURE, [
                "input"       => $error,
            ]);

            throw new TwirpException($error);
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::FETCH_CONSENT_FAILURE, [
                'error'         => $e->getErrorCode(),
                'error_message' => $e->getMessage()
            ]);

            throw new IntegrationException('
                Could not receive proper response from BVS service');
        }
        finally
        {
            $dimension = [
                Constant::SUCCESS       => $requestSuccess,
            ];

            $this->trace->count(Metric::FETCH_CONSENT_SUCCESS, $dimension);
        }
    }
}
