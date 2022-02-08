<?php

namespace RZP\Services;

use RZP\Models\Merchant\RazorxTreatment;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\User\Role;
use RZP\Models\Base\Entity;
use RZP\Base\RepositoryManager;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Document\Type;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Core as MerchantCore;

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

    const FILE_IDS          = 'file_ids';

    const CHANNEL           = 'channel';

    const JOB_NAME          = 'job_name';

    const MERCHANT_ID       = 'merchant_id';

    const PREFIX            = 'prefix';

    // razorx flag
    const RAZORX_FLAG_UFH_VALIDATE_USER_ROLE_FOR_ACCESS = 'razorx_flag_ufh_validate_user_role_for_access';

    const BLACK_LISTED_FILE_TYPES_FOR_MERCHANTS_USERS = [
        Role::SUPPORT =>  Type::VALID_DOCUMENTS,
    ];

    protected $config;

    protected $trace;

    protected $env;

    protected $route;

    /** @var UfhClient  */
    protected $ufhClient;

    /** @var $merchantId */
    protected $merchantId;

    /** @var  BasicAuth */
    protected $ba;

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    protected $clientType;

    public function __construct($app, $merchantId = null, $clientType = null)
    {
        $this->trace           = $app['trace'];

        $this->env             = $app['env'];

        $this->ba              = $app['basicauth'];

        $this->repo            = $app['repo'];

        $this->config          = $app['config']['applications.ufh'];

        $this->merchantId      = $this->ba->getMerchantId();

        $this->app             = $app;

        $this->clientType      = $clientType;

        if (($this->ba->isAdminAuth() === true))
        {
            $this->merchantId = $merchantId ?? $this->repo->merchant->getSharedAccount()->getId();
        }

        if(($this->ba->isCron() === true) && ($this->merchantId == null) && ($merchantId == null))
        {
            $this->merchantId = $this->repo->merchant->getSharedAccount()->getId();
        }

        $this->ufhClient = $this->createUfhClient();

        $this->route = $this->app['api.route'];
    }

    protected function createUfhClient()
    {
        /*
         * Client user name is a way of distinguishing different teams in API code base connecting to UFH
         * This is done to migrate merchant onboarding doc uploads to clou front.
         */
        $clientUsername = $this->config['auth']['username'];

        if(empty($this->clientType) === false)
        {
            if($this->merchantId != null) {
                $isExperimentEnabled = (new MerchantCore())->isRazorxExperimentEnable(
                    $this->merchantId, RazorxTreatment::PG_ONBOARDING_CLIENT_CLOUDFRONT_EXP);

                $isExperimentEnabled |= (new MerchantCore())->isRazorxExperimentEnable(
                    $this->merchantId, RazorxTreatment::QR_CODE_UFH_CLOUDFRONT_ONBOARDING);

                if($isExperimentEnabled == true) {
                    $clientUsername = $this->clientType;
                }
            }
        }

        $config = [
            'base_uri'      => $this->config['url'],
            'username'      => $clientUsername,
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

        $requestData = $this->getRequestData($file, $movedFile, $storageFileName, $type, $entity, $metadata);

        $this->trace->info(
            TraceCode::AWS_FILE_UPLOAD,
            array_except($requestData, [self::FILE]));

        try
        {
            $response = $this->ufhClient->upload($requestData);
        }
        catch (\Throwable $e)
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

    /**
     * @param UploadedFile $file
     * @param string       $storageFileName
     * @param string       $type
     * @param              $entity
     * @param array        $metadata
     *
     * @return array
     * @throws Exception\ServerErrorException
     */
    public function uploadFileAndGetResponse(UploadedFile $file,
                                             string $storageFileName,
                                             string $type,
                                             $entity,
                                             array $metadata = []): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        $storageFileName = strtolower($storageFileName);

        $movedFile = $file->move(storage_path('files/filestore'), $storageFileName . '.' . $ext);

        $requestData = $this->getRequestData($file, $movedFile, $storageFileName, $type, $entity, $metadata);

        $this->trace->info(
            TraceCode::UFH_FILE_UPLOAD,
            array_except($requestData, [self::FILE]));

        if($type == 'firs_file')
        {
            $this->merchantId = $requestData[self::ENTITY_ID];
        }

        $this->ufhClient = $this->createUfhClient();

        try
        {
            $response = $this->ufhClient->upload($requestData);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                'Error completing the request',
                ErrorCode::SERVER_ERROR_UFH_SERVICE_FAILURE
            );
        }

        return $response;
    }

    /**
     * This functin would fetches multiple files from UFH for supported queryparams by UFH.
     * If merchantId is passed, we need to fetch files on behalf of that merchant. So creating UFHClient for the merchantId.
     * If merchantId is not passed, the merchant would be the same as merchant in request context
     * @param array $queryParams
     * @param null  $merchantId
     *
     * @return array
     * @throws Exception\ServerErrorException
     */
    public function fetchFiles(array $queryParams, $merchantId = null): array
    {
        $this->trace->info(
            TraceCode::AWS_FILES_FETCH,
            [
                self::QUERY_PARAMS => $queryParams,
            ]);

        try
        {
            if(empty($merchantId) === false)
            {
                $this->merchantId = $merchantId;
                $this->ufhClient = $this->createUfhClient();
            }

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

    public function deleteFile(string $fileId,string $merchantId = null, string $type = null)
    {
        $this->trace->info(
            TraceCode::AWS_FILE_DELETE,
            [
                self::FILE_ID => $fileId,
            ]);

        if($type == 'firs_zip')
        {
            $this->merchantId = $merchantId;
            $this->ufhClient = $this->createUfhClient();
        }

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

        if ($this->isRestrictedFileTypeForUserRole($fileType, $userRole) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }
    }

    protected function isRestrictedFileTypeForUserRole($fileType, $userRole): bool
    {
        $treatment = 'control';

        $merchantId = $this->ba->getMerchantId();

        if($merchantId !== null)
        {
            $treatment = $this->app['razorx']->getTreatment(
                $merchantId,
                self::RAZORX_FLAG_UFH_VALIDATE_USER_ROLE_FOR_ACCESS,
                $this->app['rzp.mode'] ?? Mode::LIVE
            );
        }

        $traceData = [
            'user_role'         => $userRole,
            'file_type'         => $fileType,
            'razorx_treatment'  => $treatment,
            'access_validation' => 'passed'
        ];

        if (($treatment === 'on') and
            (key_exists($userRole, self::BLACK_LISTED_FILE_TYPES_FOR_MERCHANTS_USERS) === true) and
            (in_array($fileType, self::BLACK_LISTED_FILE_TYPES_FOR_MERCHANTS_USERS[$userRole], true) === true))
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

    protected function getRequestData(UploadedFile $file,
                                    File $movedFile,
                                    string $storageFileName,
                                    string $type,
                                    $entity,
                                    array $metadata = []) : array
    {
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

        return $requestData;
    }

    public function downloadFiles(array $fileIds, string $merchantId, string $prefix = "Firs")
    {
        $input = [
            self::FILE_IDS          => $fileIds,
            self::CHANNEL           => "Bulk",
            self::JOB_NAME          => "bulk_job",
            self::MERCHANT_ID       => $merchantId,
            self::PREFIX            => $prefix,
        ];

        $response = $this->ufhClient->download($input);

        return $response;
    }

}
