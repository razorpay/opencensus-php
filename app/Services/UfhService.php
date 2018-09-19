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
    const ID                = 'id';

    const FILE_ID           = 'file_id';

    const RELATIVE_LOCATION = 'relative_location';

    const LOCATION          = 'location';

    const QUERY_PARAMS      = 'query_params';

    const FILE              = 'file';

    const NAME              = 'name';

    const TYPE              = 'type';

    const ENTITY_ID         = 'entity_id';

    const ENTITY_TYPE       = 'entity_type';

    const STORE             = 'store';

    const DISPLAY_NAME      = 'display_name';


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
            self::FILE          => fopen($movedFile->getPathname(), 'r'),
            self::NAME          => $storageFileName,
            self::TYPE          => $type,
            self::ENTITY_ID     => $entity->getId(),
            self::ENTITY_TYPE   => $entity->getEntityName(),
            self::STORE         => $this->getStoreForEnv(),
            self::DISPLAY_NAME  => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
        ];

        $this->trace->info(
            TraceCode::AWS_FILE_UPLOAD,
            array_except($requestData, [self::FILE]));

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
            self::FILE_ID           => $response[self::ID],
            self::RELATIVE_LOCATION => $response[self::LOCATION],
        ];
    }

    public function fetchFiles(array $queryParams): array
    {
        $this->trace->info(
            TraceCode::AWS_FILES_FETCH,
            [
                self::QUERY_PARAMS => $queryParams,
            ]);

        try
        {
            return $this->ufhClient->all($queryParams);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                'Error completing the request',
                ErrorCode::SERVER_ERROR_UFH_FETCH_SERVICE_FAILURE
            );
        }
    }

    public function deleteFile(string $fileId)
    {
        $this->trace->info(
            TraceCode::AWS_FILE_DELETE,
            [
                self::FILE_ID => $fileId,
            ]);

        try
        {
            $this->ufhClient->delete($fileId);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                'Error completing the request',
                ErrorCode::SERVER_ERROR_UFH_DELETE_SERVICE_FAILURE
            );
        }
    }

    protected function validateResponse(array $res = null)
    {
        if ((empty($res[self::ID]) === true) or (empty($res[self::LOCATION]) === true))
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
