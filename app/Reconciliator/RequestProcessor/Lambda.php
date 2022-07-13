<?php

namespace RZP\Reconciliator\RequestProcessor;

use Symfony\Component\HttpFoundation\File\File;

use Config;
use RZP\Exception;
use RZP\Models\FileStore\Utility;
use RZP\Reconciliator\FileProcessor;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Lambda extends Base
{
    use FileHandlerTrait;

    const KEY                       = 'key';
    const BUCKET_CONFIG_KEY         = 'bucket_config_key';
    const REGION                    = 'region';

    const RECON_INPUT_BUCKET        = 'recon_input_bucket';
    const RECON_SFTP_INPUT_BUCKET   = 'recon_sftp_input_bucket';
    const DEFAULT_REGION            = 'us-east-1';
    const SFTP_BUCKET_REGION        = 'ap-south-1';

    // Since March 2020, we are pulling sftp files to another
    // bucket for newly added gateways under sftp automation.
    const SFTP_BUCKET_GATEWAYS = [
        Base::VAS_AXIS,
        Base::UPI_AXIS,
        Base::BT_RBL,
        Base::NETBANKING_PNB,
        Base::CRED,
        Base::NETBANKING_UBI,
        Base::NETBANKING_AUSF,
        Base::NETBANKING_AUSF_CORP,
        Base::NETBANKING_NSDL,
        Base::NETBANKING_AXIS,
        Base::CHECKOUT_DOT_COM,
        Base::NETBANKING_DBS,
    ];

    public function process(array $input): array
    {
        $key = $input[self::KEY] ?? null;

        if (blank($key) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'File key not present in request',
                self::KEY,
                $input);
        }

        $this->setGatewayFromInputOrKey($input, $key);

        $this->setGatewayReconciliatorObject();

        // Adding this to support the migration of the lambda to new indian bucket {recon_sftp_input_bucket}
        // In older lambda the input does not have the bucket and region passed thus
        // it was taking the default bucket
        // following code can be removed once the migration to the new lambda is complete
        // post that only adding the gateway to the SFTP_BUCKET_GATEWAYS array will suffice
        // the requirement
        $bucketConfigKey = self::RECON_INPUT_BUCKET;
        $bucketRegion = self::DEFAULT_REGION;

        if (empty($input['bucket']) === false)
        {
            $bucketConfigKey = $input['bucket'];
        }

        if (empty($input['region']) === false)
        {
            $bucketRegion = $input['region'];
        }

        $file = $this->downloadFileFromAws($key, $bucketConfigKey, $bucketRegion);

        //
        // For lambda request since there will only be one file always, we form
        // the input request like below to preserve consistency between different
        // types of inputs.
        //
        $input = [
            self::ATTACHMENT_HYPHEN_ONE => $file
        ];

        $inputDetails = [
            self::ATTACHMENT_COUNT => 1,
            self::GATEWAY          => $this->gateway,
            self::SOURCE           => self::LAMBDA,
        ];

        $allFilesDetails = $this->getFileDetailsFromInput($inputDetails, $input, FileProcessor::STORAGE);

        return [
            self::FILE_DETAILS  => $allFilesDetails,
            self::INPUT_DETAILS => $inputDetails,
        ];
    }

    public function processForVa(array $input) : array
    {
        $key = $input[self::KEY] ?? null;

        if (blank($key) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'File key not present in request.',
                self::KEY,
                $input
            );
        }

        $this->gateway = $this->getGatewayFromKey($key);

        // Adding this to support the migration of the lambda to new indian bucket {recon_sftp_input_bucket}
        // In older lambda the input does not have the bucket and region passed thus
        // it was taking the default bucket
        // following code can be removed once the migration to the new lambda is complete
        // post that only adding the gateway to the SFTP_BUCKET_GATEWAYS array will suffice
        // the requirement
        $bucketConfigKey = self::RECON_INPUT_BUCKET;
        $bucketRegion    = self::DEFAULT_REGION;

        if (empty($input['bucket']) === false)
        {
            $bucketConfigKey = $input['bucket'];
        }

        if (empty($input['region']) === false)
        {
            $bucketRegion = $input['region'];
        }

        $file = $this->downloadFileFromAws($key, $bucketConfigKey, $bucketRegion);

        $input = [
            self::ATTACHMENT_HYPHEN_ONE => $file
        ];

        $inputDetails = [
            self::ATTACHMENT_COUNT  => 1,
            self::GATEWAY           => $this->gateway,
            self::SOURCE            => self::LAMBDA,
        ];

        $allFilesDetails = $this->getFileDetailsFromInput($inputDetails, $input, FileProcessor::STORAGE);

        return [
            self::FILE_DETAILS  => $allFilesDetails,
            self::INPUT_DETAILS => $inputDetails,
        ];
    }

    /**
     * Based on gateway, returns bucket config key and region
     * @param string $gateway
     * @param string $bucketConfigKey
     * @param string $region
     *
     * @return array
     */
    protected function getBucketConfigDetails(string $gateway, $bucketConfigKey = self::RECON_INPUT_BUCKET, $region = self::DEFAULT_REGION)
    {
        if (in_array($gateway, self::SFTP_BUCKET_GATEWAYS, true) === true)
        {
            $bucketConfigKey = self::RECON_SFTP_INPUT_BUCKET;
            $region = self::SFTP_BUCKET_REGION;
        }

        return [
            self::BUCKET_CONFIG_KEY => $bucketConfigKey,
            self::REGION            => $region,
        ];
    }

    /**
     * Parses the key to get the gateway. The top directory name in the path denotes
     * the gateway. For e.g the key will be like below
     * key => FirstData/some_file.xls
     *
     * @param $input
     * @param string $key
     *
     * @throws Exception\ReconciliationException
     */
    protected function setGatewayFromInputOrKey($input, string $key)
    {
        //
        // In case the file is stored in a way that does not conform to the key format requirements
        // then it would not be possible to get the gateway from the key.
        // In such cases retrieving gateway from input
        //
        if (isset($input[self::GATEWAY]) === true)
        {
            $this->gateway = $input[self::GATEWAY];
        }
        else
        {
            $this->gateway = $this->getGatewayFromKey($key);
        }

        if (array_key_exists($this->gateway, self::GATEWAY_SENDER_MAPPING) === false)
        {
            throw new Exception\ReconciliationException(
                'Invalid gateway param. Not in the allowed list of gateway params.',
                [
                    'gateway' => $this->gateway,
                    'key' => $key
                ]);
        }
    }

    /**
     * Fetches and return Gateway from key
     *
     * @param string $key
     * @return mixed
     */
    protected function getGatewayFromKey(string $key)
    {
        //
        // key will be something like : 'icici/recon/NetbankingIcici/Abc.txt'
        // for stage env              : 'icici/recon/stage/NetbankingIcici/Abc.txt'
        //
        $directoryPath = pathinfo($key, PATHINFO_DIRNAME);

        $explodedArray = explode('/', $directoryPath);

        return end($explodedArray);
    }

    /**
     * Downloads the file from s3 to a local file
     * path and converts that to a File object
     *
     * @param string $key s3 key
     * @param string $bucketConfigKey
     * @param string $bucketRegion
     *
     * @return File
     * @throws \Throwable
     */
    protected function downloadFileFromAws(string $key, $bucketConfigKey = self::RECON_INPUT_BUCKET, $bucketRegion = self::DEFAULT_REGION): File
    {
        $bucketConfig = $this->getBucketConfigDetails($this->gateway, $bucketConfigKey, $bucketRegion);

        $filePath = storage_path('files/filestore') . '/' . $key;

        $dir = dirname($filePath);

        if (file_exists($dir) === false)
        {
            (new Utility)->callFileOperation('mkdir', [$dir, 0777, true]);
        }

        // check if this is AWS S3 mock
        $config = \Config::get('aws');

        $awsS3Mock = $config['mock'];

        if ($awsS3Mock === true)
        {
            //
            // For testcases, file gets created in storage/files/filestore/
            // directory and not in the subfolder named on gateway, so set
            // the path accordingly here (i.e. remove gateway from filepath)
            //
            $fileName = explode('/', $key)[1];

            $filePath = storage_path('files/filestore') . '/'  . $fileName;
        }
        else
        {
            $filePath = $this->getFileFromAws(
                                            $key,
                                            $filePath,
                                            $bucketConfig[self::BUCKET_CONFIG_KEY],
                                            $bucketConfig[self::REGION]);

        }

        return new File($filePath);
    }

    /**
     * Once the file has been downloaded and processed we delete the file from the
     * bucket. We suppress any exception here, as we don't want processing to fail
     * if file delete fails.
     *
     * @param   string  $key
     */
    public function deleteFromAws(string $key)
    {
        $gateway = $this->getGatewayFromKey($key);

        $bucketConfig = $this->getBucketConfigDetails($gateway);

        try
        {
            $this->deleteFileFromAws($key,
                                     $bucketConfig[self::BUCKET_CONFIG_KEY],
                                     $bucketConfig[self::REGION]);
        }
        catch(\Throwable $e)
        {
            return;
        }
    }
}
