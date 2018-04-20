<?php

namespace RZP\Services;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Entity;
use Razorpay\Ufh\Client as UfhClient;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UfhService
{
    const FILE_ID       = 'file_id';

    const RELATIVE_LOCATION    = 'relative_location';

    protected $config;

    protected $trace;

    protected $env;

    protected $ufhClient;

    public function __construct($app)
    {
        $this->trace     = $app['trace'];

        $this->env       = $app['env'];

        $this->ba        = $app['basicauth'];

        $this->config    = $app['config']['applications.ufh'];

        $config = [
            'base_uri'      => $this->config['url'],
            'username'      => $this->config['auth']['username'],
            'password'      => $this->config['auth']['password'],
            'headers'       => [
                'X-Merchant-Id' => $this->ba->getMerchantId(),
            ],
            'X-Merchant-Id' => $this->ba->getMerchantId(),
        ];

        $this->ufhClient = new UfhClient($config);
    }

    /**
     * @param UploadedFile $file
     * @param string $storageFileName
     * @param string $type
     * @param Entity $entity
     *
     * @return array
     *
     * @throws Exception\ServerErrorException
     */
    public function uploadFileAndGetUrl(
                                        UploadedFile $file,
                                        string $storageFileName,
                                        string $type,
                                        Entity $entity): array
    {
        $ext = $file->getClientOriginalExtension();

        $movedFile = $file->move(storage_path('files/filestore'), $storageFileName . '.' . $ext);

        $requestData = [
            'file'          => fopen($movedFile->getPathname(), 'r'),
            'name'          => $storageFileName,
            'type'          => $type,
            'entity_id'     => $entity->getPublicId(),
            'entity_type'   => $entity->getEntityName(),
            'store'         => $this->getStoreForEnv(),
        ];

        $this->trace->info(
            TraceCode::AWS_FILE_UPLOAD,
            array_except($requestData, ['file']));

        try
        {
            $response = $this->ufhClient->upload($requestData);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                'Error completing the request',
                ErrorCode::SERVER_ERROR_UFH_SERVICE_FAILURE
            );
        }

        $this->validateResponse($response);

        return [
            self::FILE_ID           => $response['id'],
            self::RELATIVE_LOCATION => $response['location'],
        ];
    }

    protected function validateResponse(array $res = null)
    {
        if ((empty($res['id']) === true) or (empty($res['location']) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Response not valid',
                'response',
                $res);
        }
    }

    protected function getStoreForEnv(): string
    {
        return in_array($this->env, ['dev', 'testing'], true) ? 'local' : 's3';
    }
}
