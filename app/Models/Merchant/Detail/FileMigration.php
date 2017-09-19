<?php

namespace RZP\Models\Merchant\Detail;

use Config;
use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Base\RuntimeManager;
use RZP\Models\Merchant\Detail;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FileStore\Storage\AwsS3\Handler;

class FileMigration extends Base\Service
{
    const UPLOADED_FIELDS = [
        Entity::BUSINESS_PROOF_URL,
        Entity::BUSINESS_OPERATION_PROOF_URL,
        Entity::BUSINESS_PAN_URL,
        Entity::ADDRESS_PROOF_URL,
        Entity::PROMOTER_PROOF_URL,
        Entity::PROMOTER_PAN_URL,
        Entity::PROMOTER_ADDRESS_URL
    ];

    public function __construct()
    {
        parent::__construct();

        $this->config =  \Config::get('aws');

        $this->s3Client = Handler::getClient();
    }

    public function migrateMerchantDocuments($input)
    {
        $response = [];

        $this->increaseAllowedSystemLimits();

        $count = isset($input['count']) === true ? $input['count'] : 1000;
        $skip = isset($input['skip']) === true ? $input['skip'] : 0;

        $merchantDetails = $this->repo->merchant_detail->getMerchantDetailsToBeMigrated($count, $skip);

        foreach ($merchantDetails as $merchantDetail)
        {
            try
            {
                $this->createFileId($merchantDetail);
            }
            catch(\Exception $ex)
            {
                $this->trace->traceException($ex, Trace::INFO, TraceCode::MERCHANT_DETAIL_MIGRATE_FAILED);

                $response[$merchantDetail->getMerchantId()] = $ex->getMessage();

                continue;
            }
        }

        return $response;
    }

    protected function createFileId(Detail\Entity $merchantDetail)
    {
        $merchantId = $merchantDetail->getMerchantId();

        $fileInfo = $this->getFileInfo($merchantDetail);

        $params = [];

        $creator = new FileStore\Creator;

        foreach ($fileInfo as $key => $value)
        {
            $fileStoreParams = [
                FileStore\Entity::MERCHANT_ID  => $merchantId,
                FileStore\Entity::TYPE         => $key,
                FileStore\Entity::ENTITY_ID    => $merchantId,
                FileStore\Entity::ENTITY_TYPE  => $merchantDetail->getEntity(),
                FileStore\Entity::EXTENSION    => $value['content_type'],
                FileStore\Entity::MIME         => FileStore\Format::VALID_EXTENSION_MIME_MAP[$value['content_type']][0],
                FileStore\Entity::SIZE         => $value['content_length'],
                FileStore\Entity::NAME         => explode('.', $value['aws_key'])[0],
                FileStore\Entity::STORE        => FileStore\Store::S3,
                FileStore\Entity::LOCATION     => $value['aws_key'],
                FileStore\Entity::BUCKET       => $value['bucket']
            ];

            $fileStore = (new FileStore\Entity)->build($fileStoreParams);

            $this->repo->saveOrFail($fileStore);

            $params[$key] = $fileStore->getId();
        }

        $merchantDetail->fill($params);

        $this->repo->saveOrFail($merchantDetail);
    }

    protected function getFileInfo(Detail\Entity $merchantDetail)
    {
        $s3 = $this->s3Client;

        $bucket = $this->config['activation_bucket'];

        $merchantId = $merchantDetail->getMerchantId();

        $fileInfo = [];

        try
        {
            foreach (self::UPLOADED_FIELDS as $key)
            {
                if ($merchantDetail[$key] !== null)
                {
                    $s3url = $merchantDetail[$key];

                    // Its already an UFH, so we don't need to migrate it.
                    if (strlen($s3url) <= 19)
                    {
                        continue;
                    }

                    $fileName = explode($merchantId, $s3url)[1];

                    $fileExtension = explode('.', $fileName)[1];

                    $awsKey = $merchantId . $fileName;

                    $result = $s3->headObject([
                        'Bucket' => $bucket,
                        'Key'    => $awsKey,
                    ]);

                    $fileInfo[$key] = [
                        'bucket'         => $bucket,
                        'aws_key'        => $awsKey,
                        'content_type'   => strtolower($fileExtension),
                        'content_length' => $result['ContentLength'],

                    ];
                }
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::INFO, TraceCode::MERCHANT_DETAIL_MIGRATE_FAILED);

            throw $ex;
        }

        return $fileInfo;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(1000);
    }
}
