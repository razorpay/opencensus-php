<?php

namespace RZP\Services;

use Requests;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use Symfony\Component\HttpFoundation\File\File;

class ReconService
{
    protected $baseUrl;

    const REQUEST_TIMEOUT = 60;

    const MERCHANT_ID = 'merchant_id';

    const WORKSPACE_ID = 'workspace_id';

    const FILE_TYPE_ID = 'file_type_id';

    const FILE = 'file';


    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.recon');

        $this->trace = $app['trace'];

        $this->ba = $app['basicauth'];

        $this->requestHeader = [];

        $this->baseUrl = $this->config['url'];

        $this->key = $this->config['key'];

        $this->auth = $app['basicauth'];

        $this->secret = $this->config['secret'];

    }

    public function sendAnyRequest($url, $method, $data)
    {
        return $this->sendRequest($url, $method, $data);
    }

    public function uploadFile($input)
    {
        $merchant_id = $input[self::MERCHANT_ID];

        $workspace_id = $input[self::WORKSPACE_ID];

        $file_type_id = $input[self::FILE_TYPE_ID];

        $file = $input[self::FILE];

        $prefix = $merchant_id . '/' . $workspace_id . '/' . $file_type_id;

        $localSaveDir = $this->getLocalSaveDir($prefix);

        $ufh = $this->saveInputFile($file, $localSaveDir);

        $filepath = $ufh->getFullFilePath();

        $signed_url = $ufh->getSignedUrl();

        $input['file_path'] = $filepath;

        $input['signed_url'] = $signed_url;

        return $this->sendRequest('upload_file', 'post', $input);
    }

    protected function sendRequest($url, $method, $data = null)
    {
        $headers['Content-Type'] = 'application/json';

        $requestPayload = $this->getPayload($data ?? [], $method);

        $url = $this->baseUrl . $url;

        $options = array(
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$this->key, $this->secret],
        );

        try {
            $response = Requests::request(
                $url,
                $headers,
                $requestPayload,
                $method,
                $options);

            $this->trace->info(
                TraceCode::RECON_SERVICE_REQUEST,
                [
                    'url'     => $url,
                    'body'    => $requestPayload,
                    'method'  => $method,
                ]);

            return $this->handleResponse($response);
        }
        catch(\Requests_Exception $exception)
        {
            throw new ServerErrorException(
                'Unable to connect to recon service',
                ErrorCode::SERVER_ERROR_RECON_REQUEST_FAILURE,
                compact('headers', 'method', 'url'));
        }

    }

    protected function handleResponse($response)
    {
        $this->trace->info(
            TraceCode::RECON_SERVICE_RESPONSE,
            [
                'status_code'     => $response->status_code,
            ]);

        $responseBody = json_decode($response->body);

        if ($response->status_code >= 500)
        {
            throw new ServerErrorException(
                'Received Server Error in recon service response',
                ErrorCode::SERVER_ERROR_IN_RECON_RESPONSE,
                [ 'recon_error'    => $responseBody ]);
        }
        else if($response->status_code >= 400)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_IN_RECON_RESPONSE,
                null,
                [
                    'recon_error'    => $responseBody,
                ]);
        }

        return $responseBody;
    }

    protected function getPayload($data, $method)
    {
        if ($method === 'GET') return $data;

        return json_encode($data, JSON_FORCE_OBJECT);
    }

    protected function saveInputFile(File $inputFile, $localSaveDir)
    {
        $filename = $inputFile->getClientOriginalName();

        $movedFile = $inputFile->move($localSaveDir, $filename);

        $ufh = $this->saveFile($movedFile->getPathname(), 'recon_input');

        return $ufh;
    }

    protected function getLocalSaveDir($prefix='') : string
    {
        return storage_path('files/filestore') . '/' . $prefix;
    }

    protected function saveFile(string $filePath, string $type): FileStore\Creator
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $name = pathinfo($filePath, PATHINFO_FILENAME);

        $ufh = new FileStore\Creator;

        $ufh->addBucketConfigForBatchService('recon_data_lake_bucket');

        $ufh->localFilePath($filePath)
            ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[$ext][0])
            ->extension($ext)
            ->name($name)
            ->type($type);

        return $ufh->save();
    }

}
