<?php

namespace RZP\Services;

use App;
use Request;
use RZP\Http\Request\Requests;
use RZP\Exception;
use RZP\Http\RequestHeader;
use RZP\Models\Batch;
use GuzzleHttp\Client;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use GuzzleHttp\RequestOptions;
use RZP\Models\Payment\Gateway;
use Razorpay\Trace\Logger as Trace;
use RZP\Http\BasicAuth\KeylessPublicAuth;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\BadResponseException;


class BatchMicroService
{
    const BATCH_SERVICE = 'service';

    const FILE_STORE = 'file_store';

    protected $trace;

    protected $mode;

    protected $app;

    protected $batchServiceConfig;

    protected $batchServiceUrl;

    protected $username;

    protected $secret;

    protected $client;

    protected $repo;

    const BATCH_URLS = [
        'download'  => 'download',
        'batch'     => 'batch',
        Batch\Constants::VALIDATE_FILE_NAME_URL => 'batch/validateFileName',
        'filestore' => 'filestore',
        'notify'    => 'batch/{id}/settings',
        'batch_entry' => 'batch-entry',
    ];

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo   = $this->app['repo'];

        $this->mode = (isset($this->app['rzp.mode']) === true) ? $this->app['rzp.mode'] : Mode::LIVE;

        $this->batchServiceConfig = $this->app['config']->get('applications.batch');

        $this->batchServiceUrl = $this->batchServiceConfig['url'];

        $this->username = $this->batchServiceConfig['username'];

        $this->secret = $this->batchServiceConfig['password'];

        // Timeout if the batch service fails to connect to the api in 1 second.
        $this->client = new Client(['base_uri' => $this->batchServiceUrl,'connect_timeout' => 1]);
    }

    public function forwardToBatchServiceRequest(array $input, Merchant\Entity $merchant, FileStore\Entity $ufhFile = null)
    {
        $data = [
            'batchTypeId' => $input[Batch\Entity::TYPE],
        ];

        if($data['batchTypeId'] === "nach")
        {
            $subType = $input[Batch\Entity::SUB_TYPE];
            $gateway = $input[Batch\Entity::GATEWAY];
            $data['batchTypeId'] = 'nach_' . $subType . '_' . $gateway . '';
        }

        if($data['batchTypeId'] === "emandate")
        {
            $subType = $input[Batch\Entity::SUB_TYPE];
            $gateway = $input[Batch\Entity::GATEWAY];

            if($gateway === Gateway::ENACH_NPCI_NETBANKING){
                $data['batchTypeId'] = $gateway;
            } else {
                $data['batchTypeId'] = 'emandate_' . $subType . '_' . $gateway;
            }
        }

        $this->checkAndInsert('name', $input, $data);

        if (isset($input['file_id']))
        {
            $multipartData = $this->formAndGetMultipartPayload($input, $merchant);

            $relativeUri = self::BATCH_URLS['batch'];
        }
        else
        {
            $multipartData = [
                [
                    'name'     => 'multipartFile',
                    'contents' => fopen($ufhFile->getFullFilePath(), 'r'),
                    'filename' => $ufhFile->getName() . '.' . $ufhFile->getExtension(),
                ]
            ];

            // Ecollect Yesbank MIS file has an extension of .xls but the underlying content type
            // matches to Office 2007(.xlsx). Hence manually setting the extension to
            // avoid conversion errors at batch service.
            if ($input[Batch\Entity::TYPE] === Batch\Type::ECOLLECT_YESBANK)
            {
                $multipartData[0]['filename'] = $ufhFile->getName() . '.xlsx';
            }

            $relativeUri = '/'. self::BATCH_URLS['batch'] . '?' . http_build_query($data);
        }

        if (isset($input['config']))
        {
            array_push($multipartData, [
                'name'     => 'settings',
                'contents' => json_encode($input['config']),
            ]);
        }

        if (isset($input['schedule']))
        {
            array_push($multipartData, [
                'name'     => 'schedule',
                'contents' => $input['schedule'],
            ]);
        }

        $this->trace->info(TraceCode::BATCH_SERVICE_MULTIPART_PAYLOAD, ['multipartData' => $multipartData]);

        $response = $this->sendToBatchService($multipartData, $merchant, $relativeUri);

        $batchResponse = json_decode($response->getBody(), true);

        $batchResponse['id'] = 'batch_' . $batchResponse['id'];

        if ($input[Batch\Entity::TYPE] !== Batch\Type::REFUND)
        {
            $batchResponse['status'] = $this->statusClusterMapping($batchResponse['status']);
        }
        else
        {
            $batchResponse['status'] = $this->statusClusterMappingRefundBatch($batchResponse['status']);
        }

        $batchResponse[Batch\Entity::TYPE] = $input[Batch\Entity::TYPE];

        $this->trace->info(TraceCode::BATCH_SERVICE_CREATED_RESPONSE, ['response' => $batchResponse]);

        return $batchResponse;
    }

    public function getBatchEntries(string $batchId, array $input, Merchant\Entity $merchant = null)
    {
        $relativeUri = self::BATCH_URLS['batch_entry'];

        $input['batchId'] = Batch\Entity::verifyIdAndStripSign($batchId);

        $options = [
            'X-Entity-Id' => $merchant->getId(),
            'mode'        => $this->mode,
        ];

        return $this->getResponseFromBatchService($relativeUri, Requests::GET, $options, $input);
    }

    public function processBatch(string $previousBatchId, array $input, Merchant\Entity $merchant)
    {
        $userId = $this->app['request']->header(RequestHeader::X_DASHBOARD_USER_ID, null);

        $creatorType = 'user';

        $headers = [
            'X-Entity-Id'    => $merchant->getId(),
            'mode'           => $this->mode,
            'X-Creator-Id'   => $userId,
            'X-Creator-Type' => $creatorType,
        ];

        if(!empty(Request::header(RequestHeader::DEV_SERVE_USER))){
            $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }

        $relativeUri = self::BATCH_URLS['batch'];

        $multipartData = [
            [
                'name'     => 'previousBatchId',
                'contents' => Batch\Entity::verifyIdAndStripSign($previousBatchId),
            ],
        ];

        if (isset($input['config']))
        {
            array_push($multipartData, [
                'name'     => 'settings',
                'contents' => json_encode($input['config']),
            ]);
        }

        if (isset($input['name']))
        {
            array_push($multipartData, [
                'name'     => 'name',
                'contents' => $input['name'],
            ]);
        }

        try
        {
            $response = $this->client->request(Requests::PUT, $relativeUri, [
                RequestOptions::MULTIPART =>
                    $multipartData,
                'auth'      => [
                    $this->username,
                    $this->secret,
                ],
                'headers'   => $headers,
            ]);
        }
        catch (BadResponseException $exception)
        {
            $this->trace->traceException($exception,
                Trace::INFO,
                TraceCode::BATCH_SERVICE_BAD_REQUEST);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BATCH_SERVICE_ERROR,
                $exception->getMessage());
        }
        catch (\Throwable $throwable)
        {
            $this->trace->traceException($throwable,
                Trace::CRITICAL,
                TraceCode::BATCH_SERVICE_FAILED);

            throw new Exception\ServerNotFoundException(ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_FOUND,
                $throwable->getMessage());
        }

        return  json_decode($response->getBody(), true);
    }

    protected function checkAndInsert(string $index, array $input = null, array & $output)
    {
        if ($input != null && isset($input[$index]))
        {
            $output[$index] = $input[$index];
        }

        return $output;
    }

    public function sendToBatchService(array $multipartData,
                                       Merchant\Entity $merchant,
                                       string $relativeUri)
    {
        try
        {
            $userId = $this->app['request']->header(RequestHeader::X_DASHBOARD_USER_ID, null);

            $creatorType = 'user';

            // making creator_type as null for requests coming to 'payouts_batch_create'
            // as these requests are not coming from dashboard, user has no context for these requests.
            if ($this->app['api.route']->getCurrentRouteName() === 'payouts_batch_create')
            {
                $creatorType = null;
            }

            $headers = [
                'X-Entity-Id'    => $merchant->getId(),
                'mode'           => $this->mode,
                'X-Creator-Id'   => $userId,
                'X-Creator-Type' => $creatorType,
            ];
            if(!empty(Request::header(RequestHeader::DEV_SERVE_USER))){
                $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
            }

            $admin = $this->app['basicauth']->getAdmin();

            if ($admin !== null)
            {
                $creatorType = 'admin';

                $headers['X-Creator-Type'] = $creatorType;

                $headers['X-Creator-Id']    = $admin->getId();
            }

            $response = $this->client->request(Requests::POST, $relativeUri, [
                'multipart' =>
                    $multipartData,
                'auth'      => [
                    $this->username,
                    $this->secret,
                ],
                'headers'   => $headers,
            ]);
        }
        catch (ConnectException $connectException)
        {
            $this->trace->traceException($connectException,
                                         Trace::CRITICAL,
                                         TraceCode::BATCH_SERVICE_FAILED);

            throw new Exception\ServerErrorException(
                'Error uploading the batch request',
                ErrorCode::SERVER_ERROR_BATCH_SERVICE_UPLOAD_FAILURE
            );
        }
        return $response;
    }


    /**
     * @param string          $batchId
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return array
     * @throws Exception\ServerNotFoundException
     */
    public function forwardNotify(string $batchId, array $input,Merchant\Entity $merchant): array
    {
        $relativeUri = self::BATCH_URLS['notify'];

        $relativeUri = str_replace('{id}', Batch\Entity::verifyIdAndStripSign($batchId), $relativeUri);

        $response = $this->notifyToBatchService($input, $merchant, $relativeUri);

        return json_decode($response->getBody(), true);
    }

    public function notifyToBatchService(array $input,Merchant\Entity $merchant, string $relativeUri)
    {
        if ($this->shouldBatchServiceBeCalled() === false)
        {
            throw new Exception\ServerNotFoundException('BatchService is not called',
                                                        ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_CALLED);
        }

        try
        {
            $response = $this->client->patch($relativeUri, [
                RequestOptions::JSON => $input,
                'auth'      => [
                    $this->username,
                    $this->secret
                ],
                'headers'   => [
                    'X-Entity-Id' => $merchant->getId(),
                    'mode'        => $this->mode,
                ],
            ]);
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception, Trace::INFO, TraceCode::BATCH_SERVICE_BAD_REQUEST);

            throw new Exception\ServerNotFoundException('Batch Id Not found',
                                                        ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_FOUND);
        }

        return $response;
    }

    public function getResponseFromBatchService(string $relativeUrl,
                                                string $method,
                                                array $options,
                                                array $input = null)
    {
        if ($this->shouldBatchServiceBeCalled() === false)
        {
            throw new Exception\ServerNotFoundException('BatchService is not called',
                                                        ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_CALLED);
        }

        $headers = [
            'mode' => $options['mode']
        ];

        if(!empty(Request::header(RequestHeader::DEV_SERVE_USER))){
            $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }

        $requestOptions = [
            'auth'    => [
                $this->username,
                $this->secret,
            ],
            'headers' => $headers,
        ];

        if(isset($options['X-Entity-Id']) == true)
        {
            $requestOptions['headers']['X-Entity-Id'] = $options['X-Entity-Id'];
        }

        if ($input != null)
        {
            if ($method === Requests::GET)
            {
                $relativeUrl = $relativeUrl . '?' . http_build_query($input);
            }
            else
            {
                $requestOptions[RequestOptions::JSON] = $input;
            }
        }

        try
        {
            $response = $this->client->request($method, $relativeUrl, $requestOptions);
        }
        catch (BadResponseException $exception)
        {
            $this->trace->traceException($exception,
                                         Trace::INFO,
                                         TraceCode::BATCH_SERVICE_BAD_REQUEST);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BATCH_SERVICE_ERROR,
                                                    $exception->getMessage());
        }
        catch (\Throwable $throwable)
        {
            // Batch Service Unavailable or some critical error occurred.

            $this->trace->traceException($throwable,
                                         Trace::CRITICAL,
                                         TraceCode::BATCH_SERVICE_FAILED);

            throw new Exception\ServerNotFoundException(ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_FOUND,
                                                        $throwable->getMessage());
        }

        return  json_decode($response->getBody(), true);
    }

    public function formAndGetMultipartPayload(array $input, Merchant\Entity $merchant)
    {
        $inputFileId = $input[Batch\Entity::FILE_ID];

        // Download the file and store in local
        $accessor = new FileStore\Accessor;

        $accessor->id($inputFileId)
                 ->merchantId($merchant->getId())
                 ->getFile();

        $storeHandler = [
            'location' => $accessor->get()->getLocation(),
            'store'    => $accessor->get()->getStore(),
            'bucket'   => $accessor->get()->getBucket(),
            'region'   => $accessor->get()->getRegion(),
            'mimeType' => $accessor->get()->getMime(),
            'fileSize' => $accessor->get()->getSize(),
        ];

        $multipartData = [
            [
                'name'     => 'batchTypeId',
                'contents' => $input[Batch\Entity::TYPE],
            ],
            [
                'name'     => 'storeHandler',
                'contents' => json_encode($storeHandler),
            ],
        ];

        if (isset($input['name']))
        {
            array_push($multipartData, [
                'name'     => 'name',
                'contents' => $input['name'],
            ]);
        }

        return $multipartData;
    }

    public function statusClusterMapping(string $status)
    {
        switch ($status)
        {
            case 'CREATED':
                return Batch\Status::CREATED;

            case 'COMPLETED':
                return Batch\Status::PROCESSED;

            case 'FAILED':
                return Batch\Status::FAILURE;

            case 'CANCELLED':
                return Batch\Status::CANCELLED;

            case 'SCHEDULED':
                return Batch\Status::SCHEDULED;

            case 'VALIDATED':
                return Batch\Status::VALIDATED;

            case 'VALIDATING':
                return Batch\Status::VALIDATING;

            case 'VALIDATION_FAILED':
                return Batch\Status::VALIDATION_FAILED;

            default:
                return Batch\Status::PARTIALLY_PROCESSED;
        }
    }

    protected function statusClusterMappingRefundBatch(string $status)
    {
        switch ($status)
        {
            case 'CREATED':
                return Batch\Status::CREATED;

            case 'COMPLETED':
                return Batch\Status::PROCESSED;

            case 'FAILED':
                return Batch\Status::FAILURE;

            case 'CANCELLED':
                return Batch\Status::CANCELLED;

            case 'SCHEDULED':
                return Batch\Status::CREATED;

            default:
                return Batch\Status::PROCESSING;
        }
    }

    /**
     * @param array           $fetchResult
     * @param array           $input
     *
     * @param Merchant\Entity $merchant
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getBatchesFromBatchServiceAndMerge(array $fetchResult, array $input, Merchant\Entity $merchant = null): array
    {
        //
        // call batch service and fetch all batches
        // merge the result and update the count
        // If skip/count is present, update the fetchResult
        //
        $batchResult =  $this->getBatchesFromBatchService($input['id']??null, $merchant, $input);

        if ($batchResult === null)
        {
            return $fetchResult;
        }

        if ( (isset($batchResult['data']) == false) ) {
            $result['data'] = [];
            array_push($result['data'], $batchResult);
            $batchResult = $result;
        }

        $batchServiceArray = (array)$batchResult['data'];

        $count = 0;

        foreach ($batchServiceArray as $item)
        {
            $itemToAdd = (array) $item;

            $this->prepareBatchItemResponse($itemToAdd);

            array_push($fetchResult['items'], $itemToAdd);

            $count++;
        }

        $fetchResult['count'] = $fetchResult['count'] + $count;

        // sorting is done by key(created_at) in DESC

        usort($fetchResult['items'], function($item1, $item2) {
            return $item2['created_at'] <=> $item1['created_at'];
        });

        if (isset($input['skip']) && isset($input['count']))
        {
            $fetchResult['items'] = array_slice($fetchResult['items'], $input['skip'], $input['count']);
            $fetchResult['count'] = count($fetchResult['items']);
        }

        return $fetchResult;
    }

    public function prepareBatchItemResponse(array & $input)
    {
        $input['type'] = $input['batch_type_id'];

        $input['entity'] = 'batch';

        if (isset($input['id']))
        {
            $input['id'] = 'batch_' . $input['id'];
        }

        if (isset($input['status']))
        {
            if ( $input['type'] !== Batch\Type::REFUND)
            {
                $input['status'] = $this->statusClusterMapping($input['status']);
            }
            else
            {
                $input['status'] = $this->statusClusterMappingRefundBatch($input['status']);
            }
        }

        if(array_key_exists('settings',$input))
        {
            $input['config'] = $input['settings'];
            unset($input['settings']);
        }
    }

    /**
     * @param string|null     $batchId
     *
     * @param Merchant\Entity $merchant
     *
     * @param array|null      $inputQueryParams
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getBatchesFromBatchService(string $batchId = null, Merchant\Entity $merchant = null, array $inputQueryParams = null)
    {
        $this->trace->info(
            TraceCode::GET_BATCHES_BATCH_SERVICE,
            [
                'batchId'          => $batchId,
                'inputQueryParams' => $inputQueryParams,
            ]);

        $queryParams = [];

        if (isset($inputQueryParams['type']) and
            empty($inputQueryParams['type']) === false)
        {
            $queryParams['batchTypeId'] = $inputQueryParams['type'];
        }

        if (isset($inputQueryParams['types']) and
            empty($inputQueryParams['types']) === false)
        {
            $queryParams['batchTypeIds'] = $inputQueryParams['types'];
        }

        $this->checkAndMergeBatchTypes($queryParams);

        $this->checkAndInsert('from', $inputQueryParams, $queryParams);

        $this->checkAndInsert('to', $inputQueryParams, $queryParams);

        $this->checkAndInsert('status',$inputQueryParams, $queryParams);

        $this->checkAndInsert('settings',$inputQueryParams, $queryParams);

        if ($merchant != null)
        {
            // this is part of query param when we hit route on batch service. route = /batch
            $queryParams['entityId'] = $merchant->getId();

            // this is part headers as the referred route does not accept query params
            //when we hit route on batch service. route = /batch/{id}
            $options['X-Entity-Id'] = $merchant->getId();
        }

        $relativeUrl = ($batchId != null) ? self::BATCH_URLS['batch'] . '/' . Batch\Entity::verifyIdAndStripSign($batchId)
                            : self::BATCH_URLS['batch'];

        try
        {
            $options['mode'] = $this->mode;

            $response = $this->getResponseFromBatchService($relativeUrl, Requests::GET, $options, $queryParams);
        }
        catch (\Exception $exception)
        {
            // Handling  5xx and 4xx exceptions as one.
            // Returning null as the caller has to take care of the response.

            $this->trace->info(TraceCode::GET_BATCHES_BATCH_SERVICE, ['response' => 'no response']);

            return null;
        }

        return $response;
    }


    public function getMultipleBatchesFromBatchService(Merchant\Entity $merchant = null, array $inputQueryParams = null)
    {
        $this->trace->info(
            TraceCode::GET_MULTIPLE_BATCHES_BATCH_SERVICE,
            [
                'inputQueryParams' => $inputQueryParams,
            ]);


        if ($merchant != null)
        {
            // this is part headers as the referred route does not accept query params
            //when we hit route on batch service. route = /batch/{id}
            $options['X-Entity-Id'] = $merchant->getId();
        }

        $inputQueryParams = implode(",", $inputQueryParams);

        $relativeUrl = self::BATCH_URLS['batch'] . '/getBatches/' . $inputQueryParams ;
        $this->trace->info(TraceCode::GET_MULTIPLE_BATCHES_BATCH_SERVICE, ['$relativeUrl' => $relativeUrl]);

        try
        {
            $options['mode'] = $this->mode;

            $response = $this->getResponseFromBatchService($relativeUrl, Requests::GET, $options);
        }
        catch (\Exception $exception)
        {
            // Handling  5xx and 4xx exceptions as one.
            // Returning null as the caller has to take care of the response.

            $this->trace->info(TraceCode::GET_MULTIPLE_BATCHES_BATCH_SERVICE, ['response' => 'no response']);

            return null;
        }

        return $response;
    }

    /**
     * @throws Exception\BadRequestException
     * @throws Exception\ServerNotFoundException
     */
    public function validateFileName(array $inputQueryParams, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::VERIFY_DUPLICATE_FILE_NAME,
            [
                TraceCode::INPUT_QUERY_PARAMS => $inputQueryParams,
            ]);

        $queryParams = [];

        $queryParams[Batch\Constants::BATCH_TYPE_ID] = $inputQueryParams[Batch\Constants::BATCH_TYPE_ID];

        $options[KeylessPublicAuth::X_ENTITY_ID_HEADER_KEY] = $merchant->getId();

        $queryParams[Batch\Constants::FILENAME] = $inputQueryParams[Batch\Constants::FILENAME];

        $relativeUrl = self::BATCH_URLS[Batch\Constants::VALIDATE_FILE_NAME_URL];

        try {
            $options['mode'] = $this->mode;

            $response = $this->getResponseFromBatchService($relativeUrl, Requests::GET, $options, $queryParams);

            $this->trace->info(
                TraceCode::VERIFY_DUPLICATE_FILE_NAME,
                [
                    TraceCode::VALIDATE_FILENAME_RESPONSE => $response,
                    TraceCode::INPUT_QUERY_PARAMS => $inputQueryParams,
                ]);

        } catch (\Exception $exception) {

            $this->trace->traceException($exception,
                Trace::ERROR,
                TraceCode::VALIDATE_FILE_NAME_BAD_REQUEST);

                throw $exception;
        }

        return $response;
    }

    /**
     * @throws ServerErrorException
     */
    public function validateFile(array $input, Merchant\Entity $merchant)
    {
        $data = [
            'batchTypeId' => $input[Batch\Entity::TYPE],
        ];

        $batchFile = $input['file'];

        $multipartData = [
            [
                'name'     => 'multipartFile',
                'contents' => fopen($batchFile->getPathname(), 'r'),
                'filename' => $batchFile->getFilename()
            ],
            [
                'name'     => 'batchTypeId',
                'contents' => $input['type'],
            ],
            [
                'name'     => 'version',
                'contents' => '2.0',
            ],
            [
                'name'     => 'name',
                'contents' => $batchFile->getFilename(),
            ],
        ];

        $relativeUri = self::BATCH_URLS['batch'];

        $response = $this->sendToBatchService($multipartData, $merchant, $relativeUri);

        $batchResponse = json_decode($response->getBody(), true);

        $batchResponse['batch_id'] = 'batch_' . $batchResponse['id'];

        $batchResponse['status'] = $this->statusClusterMapping($batchResponse['status']);

        $batchResponse[Batch\Entity::TYPE] = $input[Batch\Entity::TYPE];

        return $batchResponse;
    }

    private function checkAndMergeBatchTypes(& $output)
    {
        if (isset($output['batchTypeId']) and
           isset($output['batchTypeIds']))
        {
            array_push($output['batchTypeIds'], $output['batchTypeId']);
            unset($output['batchTypeId']);
        }
    }

    public function isMigratingBatchType(string $type): bool
    {
        return in_array($type, Batch\Type::$batchTypeMigrating, true);
    }

    public function isCompletelyMigratedBatchType(string $type): bool
    {
        return in_array($type, Batch\Type::$batchTypeMigrationCompleted, true);
    }

    /**
     * @param string $id
     * @param string $batchOrFileStore
     * @param string $merchantId
     *
     * @return mixed
     * @throws Exception\ServerNotFoundException
     */
    public function downloadS3UrlForBatchOrFileStore(string $id, string $batchOrFileStore, string $merchantId = null)
    {
        if ($batchOrFileStore === 'batch')
        {
            $urlComponent = [self::BATCH_URLS['batch'], Batch\Entity::verifyIdAndStripSign($id), self::BATCH_URLS['download']];
        }
        else
        {
            $urlComponent = [self::BATCH_URLS['filestore'], $id, self::BATCH_URLS['download']];
        }

        $relativeUrl = implode('/', $urlComponent);

        try
        {
            $options['mode'] = $this->mode;

            if ($merchantId !== null)
            {
                $options['X-Entity-Id'] = $merchantId;
            }

            $response = $this->getResponseFromBatchService($relativeUrl, Requests::GET, $options);
        }
        catch(\Exception $exception)
        {
            throw new Exception\ServerNotFoundException('Batch Id Not found',ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_FOUND);
        }

        return $response;
    }

    public function getFileStores(array $fetchResult, array $input): array
    {
        $fileResults =  $this->getFileStoreById(null, $input);

        if($fileResults === null)
        {
            return $fetchResult;
        }

        $fileServiceArray = (array)$fileResults['data'];

        $count = 0;

        foreach ($fileServiceArray as $item)
        {
            $itemToAdd = (array)$item;

            $itemToAdd['id'] = 'file_'.$itemToAdd['id'];

            array_push($fetchResult['items'],$itemToAdd);

            $count++;
        }

        $fetchResult['count'] = $fetchResult['count'] + $count;

        return $fetchResult;
    }

    public function getFileStoreById(string $id = null, array $input)
    {
        $relativeUrl = ($id != null ) ? self::BATCH_URLS['filestore'] . '/' . FileStore\Entity::verifyIdAndStripSign($id) : self::BATCH_URLS['filestore'];

        try
        {
            $options['mode'] = $this->mode;

            $response = $this->getResponseFromBatchService($relativeUrl, Requests::GET, $options, $input);
        }
        catch (\Exception $ex)
        {
            return null;
        }

        return $response;
    }

    public function fetchMultiple(string $entity, array $input)
    {
        $merchantId = $input['merchant_id'] ?? null;

        $merchant = null;

        if ($merchantId !== null)
        {
            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        }

        $fetchResult = [
            'entity' => 'collection',
            'count' => 0,
            'items' => [],
        ];

        switch ($entity)
        {
            case self::BATCH_SERVICE:
                $fetchResult = $this->getBatchesFromBatchServiceAndMerge($fetchResult, $input, $merchant);
                break;

            case self::FILE_STORE:
                $fetchResult = $this->getFileStores($fetchResult, $input);
                break;

            default:
                $fetchResult = [];
        }

        return $fetchResult;
    }

    public function fetch(string $entity, string $id, array $input)
    {
        switch ($entity)
        {
            case self::BATCH_SERVICE:
                $fetchResult = $this->getBatchesFromBatchService($id);

                $this->prepareBatchItemResponse($fetchResult);

                break;

            case self::FILE_STORE:
                $fetchResult = $this->getFileStoreById($id, $input);
                break;

            default:
                $fetchResult = [];
        }

        return $fetchResult;
    }

    public function shouldBatchServiceBeCalled(): bool
    {

        if ($this->batchServiceConfig['mock'] === true)
        {
            return false;
        }

        return true;
    }

    public function cancelBatchInBatchService(string $id)
    {
        $this->trace->info(
            TraceCode::BATCH_SERVICE_CANCEL_BATCH,
            [
                'batch_id' => $id,
            ]
        );

        $relativeUrl = self::BATCH_URLS['batch'] . '/' . Batch\Entity::verifyIdAndStripSign($id) . '/cancel';

        try
        {
            $options['mode'] = $this->mode;

            $this->getResponseFromBatchService($relativeUrl, Requests::POST, $options);
        }
        catch (\Exception $exception)
        {
            $this->trace->error(
                TraceCode::BATCH_SERVICE_CANCEL_BATCH_FAILED,
                [
                    'batch_id' => $id,
                ]
            );
        }
    }
}
