<?php

namespace RZP\Services;

use RZP\Trace\TraceCode;
use RZP\Models\Base\Entity;
use Razorpay\Ufh\Client as UfhClient;
use RZP\Exception\IntegrationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UfhService
{
    const FILE_ID       = 'file_id';

    const SIGNED_URL    = 'signed_url';

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
            'X-Merchant-Id' => $this->ba->getMerchantId(),
        ];

        $this->ufhClient = (new UfhClient())->setconfig($config);
    }

    /**
     * @param UploadedFile $file
     * @param string $storageFileName
     * @param string $type
     * @param Entity $entity
     * @return array
     */
    public function uploadFileAndGetUrl(
                                        UploadedFile $file,
                                        string $storageFileName,
                                        string $type,
                                        Entity $entity): array
    {
        // TODO : See if any validation on input arguments is required

        $filePath = $file->getPath() . '/' . $file->getFileName();

        $requestData = [
            'file'          => fopen($filePath, 'r'),
            'name'          => $storageFileName,
            'extension'     => $file->getClientOriginalExtension(),
            'type'          => $type,
            'entity_id'     => $entity->getPublicId(),
            'entity_type'   => $entity->getEntityName(),
            'store'         => $this->getStoreForEnv(),
        ];

        $this->trace->info(
            TraceCode::AWS_FILE_UPLOAD,
            array_except($requestData, ['file']));

        $response = $this->ufhClient->upload($requestData);

        // TODO : Parsing logic to be changed, hence update this
        return $this->validateAndGetParsedResponse($response);
    }

    protected function validateAndGetParsedResponse($response): array
    {
        $status     = $response->getStatusCode();

        $body       = $response->getBody();

        $parsedBody = json_decode($body, true);

        if ($status !== 200)
        {
            throw new IntegrationException(null, $parsedBody);
        }

        return [
            self::FILE_ID    => $parsedBody['id'],
            self::SIGNED_URL => $parsedBody['location'],
        ];
    }

    protected function getStoreForEnv(): string
    {
        return in_array($this->env, ['dev', 'testing'], true) ? 'local' : 's3';
    }
}