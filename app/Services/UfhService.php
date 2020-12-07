<?php

namespace RZP\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\User\Role;
use RZP\Models\Base\Entity;
use RZP\Base\RepositoryManager;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\BadRequestException;

use Razorpay\Ufh\Client as UfhClient;

class UfhService
{
    const ID                = 'id';

    const FILE_ID           = 'file_id';

    const LOCAL_FILE        = 'local_file';

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

    const METADATA          = 'metadata';

    const INVOICES          = 'invoices';

    const PAYMENTS          = 'payments';

    const ORDERS            = 'orders';

    const REFUNDS           = 'refunds';

    const SETTLEMENTS       = 'settlements';

    const WHITE_LISTED_FILE_TYPES_FOR_MERCHANTS_USERS = [
        Role::SELLERAPP => [
            self::INVOICES,
        ],
        Role::SUPPORT =>  [
            self::PAYMENTS,
            self::ORDERS,
            self::REFUNDS,
            self::SETTLEMENTS,
            self::INVOICES,
        ],
    ];

    protected $config;

    protected $trace;

    protected $env;

    /** @var UfhClient  */
    protected $ufhClient;

    /** @var $merchantId */
    protected $merchantId;

    /** @var  BasicAuth */
    protected $ba;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    public function __construct($app, $merchantId = null)
    {
        $this->trace           = $app['trace'];

        $this->env             = $app['env'];

        $this->ba              = $app['basicauth'];

        $this->repo            = $app['repo'];

        $this->config          = $app['config']['applications.ufh'];

        $this->merchantId      = $this->ba->getMerchantId();

        if (($this->ba->isAdminAuth() === true))
        {
            $this->merchantId = $merchantId ?? $this->repo->merchant->getSharedAccount()->getId();
        }

        $this->ufhClient = $this->createUfhClient();
    }

    protected function createUfhClient()
    {
        $config = [
            'base_uri'      => $this->config['url'],
            'username'      => $this->config['auth']['username'],
            'password'      => $this->config['auth']['password'],
            'headers'       => [
                'X-Merchant-Id' => $this->merchantId,
            ],
            'X-Merchant-Id' => $this->merchantId,
        ];

        return new UfhClient($config);
    }

    /**
     * @param UploadedFile $file
     * @param string $storageFileName
     * @param string $type
     * @param Entity $entity
     * @param array  $metadata
     *
     * @return array
     *
     * @throws Exception\ServerErrorException
     */
    public function uploadFileAndGetUrl(UploadedFile $file,
                                        string $storageFileName,
                                        string $type,
                                        $entity,
                                        array $metadata = []): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        $storageFileName = strtolower($storageFileName);

        $movedFile = $file->move(storage_path('files/filestore'), $storageFileName . '.' . $ext);

        $requestData = [
            self::FILE          => fopen($movedFile->getPathname(), 'r'),
            self::NAME          => $storageFileName,
            self::TYPE          => $type,
            self::STORE         => $this->getStoreForEnv(),
            self::DISPLAY_NAME  => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            self::METADATA      => $metadata,
        ];

        if (($entity instanceof Entity) === true)
        {
            $requestData[self::ENTITY_ID]   = $entity->getId();
            $requestData[self::ENTITY_TYPE] = $entity->getEntityName();
        }
        else
        {
            $requestData[self::ENTITY_ID]   = $entity[self::ID] ?? null;
            $requestData[self::ENTITY_TYPE] = $entity[self::TYPE] ?? null;
        }

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
            self::LOCAL_FILE        => $movedFile,
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

    public function getSignedUrl(string $fileId, array $params = [], $merchantId = null)
    {
        //
        // in case of admin auth we are currently fetching using shared merchant but
        // In some cases admin team wants to see document uploaded by a merchant
        //
        if (empty($merchantId) === false)
        {
            $this->merchantId = $merchantId;

            $this->ufhClient = $this->createUfhClient();
        }

        return $this->ufhClient->getSignedUrl($fileId, $params);
    }

    /**
     * validates if user is allowed to fetch file based on user role and file type
     * @param $fileType
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateUserRoleForAccess($fileType)
    {
        $userRole = $this->ba->getUserRole();

        // logging the decision only
        $this->isRestrictedFileTypeForUserRole($fileType, $userRole);

        /*
        if ($this->isRestrictedFileTypeForUserRole($fileType, $userRole) === true)
        {

            throw new Exception\BadRequestValidationFailureException(
                'Invalid access');
        }
        */
    }

    protected function isRestrictedFileTypeForUserRole($fileType, $userRole):bool
    {
        $traceData = [
            'user_role'         => $userRole,
            'file_type'         => $fileType,
            'access_validation' => 'passed'
        ];

        if ((key_exists($userRole, self::WHITE_LISTED_FILE_TYPES_FOR_MERCHANTS_USERS) === true) and
            (in_array($fileType, self::WHITE_LISTED_FILE_TYPES_FOR_MERCHANTS_USERS[$userRole], true) === false))
        {
            $traceData['access_validation'] = 'failed';

            $this->trace->info(TraceCode::UFH_FILE_FETCH, $traceData);

            return true;
        }

        $this->trace->info(TraceCode::UFH_FILE_FETCH, $traceData);

        return false;
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
