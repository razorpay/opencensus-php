<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;


use Carbon\Carbon;
use RZP\Exception\TwirpException;
use RZP\Exception\IntegrationException;

use Twirp\Error;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Services\Segment\EventCode as SegmentEvent;



use Platform\Bvs\Legaldocumentmanager\V1 as legalDocumentManagerV1;
use Platform\Bvs\Legaldocumentmanager\V1\TwirpError as TwirpErrorV1;
use Platform\Bvs\Consentdocumentmanager\V2\TwirpError as TwirpErrorV2;
use Platform\Bvs\Consentdocumentmanager\V2 as consentDocumentManagerV2;

use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\FetchLegalDocumentBaseResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\FetchConsentDocumentBaseResponse;

class BvsLegalDocumentManagerClient extends BaseClient
{
    private $legalDocumentManagerApiClient;

    private $consentDocumentManagerAPIClient;

    /**
     * BvsLegalDocumentManagerClient constructor.
     *
     * @param null $merchant
     */
    function __construct($merchant = null)
    {
        parent::__construct($merchant);

        $this->legalDocumentManagerApiClient   = new legalDocumentManagerV1\LegalDocumentManagerAPIClient($this->host, $this->httpClient);

        $this->consentDocumentManagerAPIClient = new consentDocumentManagerV2\ConsentDocumentManagerAPIClient($this->host,$this->httpClient);
    }

    /**
     * @param array $document
     *
     * @return legalDocumentManagerV1\LegalDocumentsManagerResponse
     * @throws IntegrationException|TwirpException
     */

    public function createLegalDocument(array $document): legalDocumentManagerV1\LegalDocumentsManagerResponse
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
        catch(TwirpErrorV1 $ex)
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



    public function pushTrackEventHandler(array $eventAttributes, string $segmentEvent, $merchant): void
    {
        try
        {
            if (empty($merchant) === false)
            {
                $eventAttributes['merchant_id'] = $merchant->getMerchantId();

                $eventAttributes['ip'] = $_SERVER['HTTP_X_IP_ADDRESS'] ?? $this->app['request']->ip();

                $this->app['segment-analytics']->pushTrackEvent($merchant, $eventAttributes, $segmentEvent);
            }
            else
            {
                $this->trace->info(TraceCode::PUSH_TRACK_EVENT_FAILURE, [
                    'message' => 'Could not find merchant for push track event'
                ]);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::PUSH_TRACK_EVENT_ERROR, [
                'error' => $e->getErrorCode(),
                'error_message' => $e->getMessage()
            ]);
        }
    }


    /**
     * @param array $document
     *
     * @param null  $merchant
     *
     * @return consentDocumentManagerV2\ConsentDocumentsManagerResponse
     * @throws IntegrationException|TwirpException
     */

    public function createLegalDocumentV2(array $document, $merchant = null): consentDocumentManagerV2\ConsentDocumentsManagerResponse
    {
        $legalDocumentCreateRequest = $this->getCreateLegalDocumentRequestV2($document);

        $requestSuccess = false;

        try
        {
            $eventAttributes = [
                'time_stamp'    => Carbon::now()->getTimestamp()
            ];

            $this->pushTrackEventHandler($eventAttributes, SegmentEvent::AGREEMENT_CREATION_REQUEST, $merchant);

            $response = $this->consentDocumentManagerAPIClient->CreateConsentDocuments($this->apiClientCtx, $legalDocumentCreateRequest);

            $eventAttributes['response'] =  $response;

            $this->pushTrackEventHandler($eventAttributes,SegmentEvent::AGREEMENT_CREATION_RESPONSE, $merchant);

            $requestSuccess = true;

            return $response;
        }
        catch(TwirpErrorV2 $ex)
        {
            $error =  [
                'code'    => $ex->getErrorCode(),
                'msg'     => $ex->getMessage(),
                'meta'    => $ex->getMetaMap(),
            ];

            $this->trace->info(TraceCode::CONSENT_CREATION_ERROR_V2, [
                "input"       => $error,
            ]);

            throw new TwirpException($error);
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::BVS_RESPONSE_CREATE_CONSENTS_ERROR_V2, [
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
    /**
     * @param array $legalDocument
     *
     * @return consentDocumentManagerV2\CreateConsentDocumentsRequest
     */
    protected function getCreateLegalDocumentRequestV2(array $legalDocument): consentDocumentManagerV2\CreateConsentDocumentsRequest
    {
        $clientDetails = $this->createClientDetailsV2($legalDocument['client_details']);

        $documentDetail = $this->createLegalDocumentDetailsV2($legalDocument['documents_detail']);

        $ownerDetails = $this->createOwnerDetailsV2($legalDocument['owner_details']);

        $emailDetails = $this->createEmailDetailsV2($legalDocument['email_details']);

        $smsDetails = $this->createSmsDetailsV2($legalDocument['sms_details']);

        return new consentDocumentManagerV2\CreateConsentDocumentsRequest([
            'owner_details'     => $ownerDetails,
            'client_details'    => $clientDetails,
            'documents_detail'  => $documentDetail,
            'send_email'        => $legalDocument['send_email'],
            'email_details'     => $emailDetails,
            'send_sms'          => $legalDocument['send_sms'],
            'sms_details'       => $smsDetails
        ]);
    }

    private function createClientDetails($clientDetails): ?legalDocumentManagerV1\ClientDetails
    {
        $newClientDetails = new legalDocumentManagerV1\ClientDetails();

        $newClientDetails->setPlatform($clientDetails['platform']);

        return $newClientDetails;
    }


    private function createClientDetailsV2($clientDetails): ?consentDocumentManagerV2\ClientDetails
    {
        $newClientDetails = new consentDocumentManagerV2\ClientDetails();

        $newClientDetails->setPlatform($clientDetails['platform']);

        return $newClientDetails;
    }

    private function createLegalDocumentDetails($legalDocumentDetailsArray): array
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

    private function createLegalDocumentDetailsV2(array $consentDocumentDetailsArray): array
    {
        $newConsentDocumentsDetailsArray = [];

        foreach ($consentDocumentDetailsArray as $consentDocumentDetails)
        {
            $newConsentDocumentDetails = new consentDocumentManagerV2\ConsentDocumentRequestDetails();

            $newConsentDocumentDetails -> setTemplateId($consentDocumentDetails['template_id']);

            $newConsentDocumentDetails -> setType($consentDocumentDetails['type']);

            array_push($newConsentDocumentsDetailsArray, $newConsentDocumentDetails) ;
        }

        return $newConsentDocumentsDetailsArray;
    }

    private function createOwnerDetails($ownerDetails): ?LegalDocumentManagerV1\OwnerDetails
    {
        $newOwnerDetails = new LegalDocumentManagerV1\OwnerDetails();

        $newOwnerDetails->setAcceptanceTimestamp($ownerDetails['acceptance_timestamp']);
        $newOwnerDetails->setContactNumber($ownerDetails['contact_number']);
        $newOwnerDetails->setEmail($ownerDetails['email']);
        $newOwnerDetails->setIpAddress($ownerDetails['ip_address']);
        $newOwnerDetails->setOwnerId($ownerDetails['owner_id']);
        $newOwnerDetails->setOwnerName($ownerDetails['owner_name']);
        $newOwnerDetails->setSignatoryName($ownerDetails['signatory_name']);

        return $newOwnerDetails;
    }

    private function createOwnerDetailsV2($ownerDetails): ?consentDocumentManagerV2\OwnerDetails
    {
        return new consentDocumentManagerV2\OwnerDetails([
            'owner_id'              => $ownerDetails['owner_id'],
            'owner_name'            => $ownerDetails['owner_name'],
            'ip_address'            => $ownerDetails['ip_address'],
            'acceptance_timestamp'  => $ownerDetails['acceptance_timestamp'],
            'signatory_name'        => $ownerDetails['signatory_name'],
            'contact_number'        => $ownerDetails['contact_number'],
            'email'                 => $ownerDetails['email'],
            'time_zone'             => $ownerDetails['time_zone'],
        ]);
    }

    private function createEmailDetailsV2($emailDetails): consentDocumentManagerV2\EmailDetails
    {
        $setEmailFrom = new consentDocumentManagerV2\Email([
            'name'      => $emailDetails['from']['name'],
            'address'   => $emailDetails['from']['address']
        ]);

        $setEmailTo = new consentDocumentManagerV2\Email([
            'name'      => $emailDetails['to']['name'],
            'address'   => $emailDetails['to']['address']
        ]);

        $params = $emailDetails['params'] ?? [];

        return new consentDocumentManagerV2\EmailDetails([
            'service'              => $emailDetails['service'],
            'owner_id'             => $emailDetails['owner_id'],
            'owner_type'           => $emailDetails['owner_type'],
            'org_id'               => $emailDetails['org_id'],
            'template_name'        => $emailDetails['template_name'],
            'template_namespace'   => $emailDetails['template_namespace'],
            'from'                 => $setEmailFrom,
            'to'                   => [$setEmailTo],
            'params'               => get_Protobuf_Struct($params),
            'subject'              => $emailDetails['subject']
        ]);
    }

    private function createSmsDetailsV2($smsDetails): consentDocumentManagerV2\SmsDetails
    {
        return new consentDocumentManagerV2\SmsDetails([
                                                           'owner_id'           => $smsDetails['owner_id'],
                                                           'owner_type'         => $smsDetails['owner_type'],
                                                           'org_id'             => $smsDetails['org_id'],
                                                           'template_name'      => $smsDetails['template_name'],
                                                           'template_namespace' => $smsDetails['template_namespace'],
                                                           'service'            => $smsDetails['service'],
                                                           'sender'             => $smsDetails['sender'],
                                                           'destination'        => $smsDetails['destination'],
                                                           'language'           => $smsDetails['language']]);
    }

    public function getLegalDocumentsByOwnerId(array $requestBody): legalDocumentManagerV1\LegalDocumentsManagerResponse
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
        catch(TwirpErrorV1 $ex)
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

    public function getLegalDocumentsByRequestIdV2(array $requestBody): FetchConsentDocumentBaseResponse
    {
        $legalDocument = new consentDocumentManagerV2\GetConsentDocumentsRequest($requestBody);

        $requestSuccess = false;

        try
        {
            $response = $this->consentDocumentManagerAPIClient->GetConsentDocuments($this->apiClientCtx, $legalDocument);

            $requestSuccess = true;

            return new FetchConsentDocumentBaseResponse($response);
        }
        catch(TwirpErrorV2 $ex)
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
