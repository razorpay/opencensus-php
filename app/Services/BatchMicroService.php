<?php

namespace RZP\Services;

use App;
use RZP\Exception;
use RZP\Models\Batch;
use GuzzleHttp\Client;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\ConnectException;

class BatchMicroService
{
    const BATCH_SERVICE = "service";

    const FILE_STORE = "file_store";

    protected $trace;

    protected $mode;

    protected $app;

    protected $batchServiceConfig;

    protected $batchServiceUrl;

    protected $username;

    protected $secret;

    protected $client;

    public static $batchTypeMigrated = [
        Batch\Type::PAYMENT_LINK
    ];

    const BATCH_URLS = [
        'download'  => 'download',
        'batch'     => 'batch',
        'filestore' => 'filestore',
        'notify'    => 'batch/{id}/settings'
    ];

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->mode = (isset($this->app['rzp.mode']) === true) ? $this->app['rzp.mode'] : 'live';

        $this->batchServiceConfig = $this->app['config']->get('applications.batch');

        $this->batchServiceUrl = $this->batchServiceConfig['url'];

        $this->username = $this->batchServiceConfig['username'];

        $this->secret = $this->batchServiceConfig['password'];

        // Timeout if the batch service fails to connect to the api in 1 second.
        $this->client = new Client(['base_uri' => $this->batchServiceUrl,'connect_timeout' => 1]);
    }

    public function forwardToBatchServiceRequest(array $input, Merchant\Entity $merchant, FileStore\Entity $ufhFile = null)
    {
        $data = array(
            'batchTypeId' => $input[Batch\Entity::TYPE],
        );

        $this->checkAndInsert('name', $input, $data);

        if (isset($input['file_id']))
        {
            $multipartData = $this->formAndGetMultipartPayload($input, $merchant);

            $relativeUri = self::BATCH_URLS['batch'];
        }
        else
        {
            $multipartData = array([
                                       'name'     => 'multipartFile',
                                       'contents' => fopen($ufhFile->getFullFilePath(), 'r'),
                                       'filename' => $ufhFile->getName() . "." . $ufhFile->getExtension(),
                                   ]
            );

            $relativeUri = '/'. self::BATCH_URLS['batch'] . '?' . http_build_query($data);
        }

        $response = $this->sendToBatchService($multipartData, $merchant, $relativeUri);

        $batchResponse = (array) json_decode($response->getBody());

        $batchResponse['id'] = 'batch_' . $batchResponse['id'];

        $batchResponse['status'] = $this->statusClusterMapping($batchResponse['status']);

        return $batchResponse;
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
            $response = $this->client->request('POST', $relativeUri, [
                'multipart' =>
                    $multipartData,
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
        catch (ConnectException $connectException)
        {
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

        return ((array) json_decode($response->getBody()));
    }

    public function notifyToBatchService(array $input,Merchant\Entity $merchant, string $relativeUri)
    {
        if ($this->shouldBatchServiceBeCalled() === false)
        {
            throw new Exception\ServerNotFoundException("BatchService is not called" , ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_CALLED);
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
            throw new Exception\ServerNotFoundException("Batch Id Not found",ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_FOUND);
        }

        return $response;
    }

    public function getResponseFromBatchService(string $relativeUrl)
    {
        if ($this->shouldBatchServiceBeCalled() === false)
        {
            throw new Exception\ServerNotFoundException("BatchService is not called",ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_CALLED);
        }

        $response = $this->client->request('GET', $relativeUrl, [
            'auth' => [
                $this->username,
                $this->secret,
            ],
            'headers' => [
                'mode' => $this->mode,
            ],
        ]);

        return $response;
    }

    public function formAndGetMultipartPayload(array $input, Merchant\Entity $merchant)
    {
        $inputFileId = $input[Batch\Entity::FILE_ID];

        // Download the file and store in local
        $accessor = new FileStore\Accessor;

        $accessor->id($inputFileId)
                 ->merchantId($merchant->getId())
                 ->getFile();

        $storeHandler = array(
            'location' => $accessor->get()->getLocation(),
            'store'    => $accessor->get()->getStore(),
            'bucket'   => $accessor->get()->getBucket(),
            'region'   => $accessor->get()->getRegion(),
            'mimeType' => $accessor->get()->getMime(),
            'fileSize' => $accessor->get()->getSize(),
        );

        $multipartData = array(
            [
                'name'     => 'batchTypeId',
                'contents' => $input[Batch\Entity::TYPE],
            ],
            [
                'name'     => 'storeHandler',
                'contents' => json_encode($storeHandler),
            ],
        );

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

        return $multipartData;
    }

    protected function statusClusterMapping(string $status)
    {
        switch ($status)
        {
            case "CREATED":
                return Batch\Status::CREATED;

            case "COMPLETED":
                return Batch\Status::PROCESSED;

            case "FAILED":
                return BATCH\Status::FAILURE;

            default:
                return Batch\Status::PARTIALLY_PROCESSED;
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
        $batchResult =  $this->getBatchesFromBatchService(null, $merchant, $input);

        if ($batchResult === null)
        {
            return $fetchResult;
        }

        $batchServiceArray = (array) $batchResult['data'];

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
        $input['type'] = Batch\Type::PAYMENT_LINK;

        $input['entity'] = 'batch';

        if (isset($input['id']))
        {
            $input['id'] = 'batch_' . $input['id'];
        }

        if (isset($input['status']))
        {
            $input['status'] = $this->statusClusterMapping($input['status']);
        }

        if(array_key_exists("settings",$input))
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
        $this->trace->info(TraceCode::GET_BATCHES_BATCH_SERVICE, ['batchId' => $batchId]);

        $queryParams = [];

        if (isset($inputQueryParams['type']))
        {
            $queryParams = [
                'batchTypeId' => $inputQueryParams['type'],
            ];
        }

        $this->checkAndInsert('from', $inputQueryParams, $queryParams);

        $this->checkAndInsert('to', $inputQueryParams, $queryParams);

        $this->checkAndInsert('status',$inputQueryParams, $queryParams);

        if ($merchant != null)
        {
            $queryParams['entityId'] = $merchant->getId();
        }

        $relativeUrl = ($batchId != null) ? self::BATCH_URLS['batch'] . '/' . Batch\Entity::verifyIdAndStripSign($batchId) : self::BATCH_URLS['batch'];

        try
        {
            $response = $this->getResponseFromBatchService($relativeUrl . '?' . http_build_query($queryParams));
        }
        catch (\Exception $exception)
        {
            // Handling  5xx and 4xx exceptions as one.
            // Returning null as the caller has to take care of the response.

            $this->trace->info(TraceCode::GET_BATCHES_BATCH_SERVICE, ['response' => 'no response']);

            return null;
        }

        return ((array) json_decode($response->getBody()));
    }

    public function isMigratedBatchType(string $type): bool
    {
        return in_array($type, self::$batchTypeMigrated, true);
    }

    /**
     * @param string $id
     * @param string $batchOrFileStore
     *
     * @return mixed
     * @throws Exception\ServerNotFoundException
     */
    public function downloadS3UrlForBatchOrFileStore(string $id, string $batchOrFileStore)
    {
        if ($batchOrFileStore === 'batch')
        {
            $urlComponent = array(self::BATCH_URLS['batch'], Batch\Entity::verifyIdAndStripSign($id), self::BATCH_URLS['download']);
        }
        else
        {
            $urlComponent = array(self::BATCH_URLS['filestore'], $id, self::BATCH_URLS['download']);
        }

        $relativeUrl = implode("/", $urlComponent);

        try
        {
            $response = $this->getResponseFromBatchService($relativeUrl);
        }
        catch(\Exception $exception)
        {
            throw new Exception\ServerNotFoundException("Batch Id Not found",ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_FOUND);
        }

        return  json_decode($response->getBody());
    }

    public function getFileStores(array $fetchResult, array $input, string $merchantId = null): array
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

        $relativeUrl = $relativeUrl . '?' . http_build_query($input);

        try
        {
            $response = $this->getResponseFromBatchService($relativeUrl);
        }
        catch (\Exception $ex)
        {
            return null;
        }

        return ((array)json_decode($response->getBody()));
    }

    public function fetchMultiple(string $entity, array $input)
    {
        $merchantId = $input['merchant_id'] ?? null;

        $fetchResult = array(
            'entity' => 'collection',
            'count' => 0,
            'items' => array(),
        );

        switch ($entity)
        {
            case self::BATCH_SERVICE:
                $fetchResult = $this->getBatchesFromBatchServiceAndMerge($fetchResult, $input, $merchantId);
                break;

            case self::FILE_STORE:
                $fetchResult = $this->getFileStores($fetchResult, $input, $merchantId);
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
        $requestId = $this->app['request']->getId();

        $variant = $this->app->razorx->getTreatment(
            $requestId,
            Merchant\RazorxTreatment::BATCH_SERVICE_BE_CALLED,
            $this->mode
        );

        return (strtolower($variant) === 'on');
    }
}
