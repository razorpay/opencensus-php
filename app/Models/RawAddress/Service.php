<?php

namespace RZP\Models\RawAddress;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Models\{Base,Merchant};
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Http\RequestHeader;
use RZP\Exception;
use RZP\Models\Address;
use RZP\Models\RawAddress;
use RZP\Models\Batch;
use RZP\Services\BulkUploadClient;
use RZP\Models\Address\Type;

class Service extends Base\Service
{
    const NO_FAILED_ADDRESSES_FOUND = "NO FAILED ADDRESSES FOUND YET.";

    protected $mutex;
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new RawAddress\Core;

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input)
    {
        $raw_address = $this->core()->create($input);

        try
        {
            $this->validateForAddressEntity($input);
            $this->validateForUnicode($input);

            return $raw_address->toArrayPublic();
        }catch (\Exception $e)
        {
            (new BulkUploadClient())->updateStatus($raw_address['id'],BulkUploadClient::STATUS_INVALID);
            throw $e;
        }
    }

    /**
     * @param array $input
     *
     * @return \Exception
     */
    public function createBatch(array $input)
    {
        $this->trace->info(
            TraceCode::RAW_ADDRESS_BULK_CREATE_REQUEST,
            [
                'input'      => $input,
            ]);

        //TODO convert default merchantId to null
        $merchantId = $this->app['request']->header(RequestHeader::X_ENTITY_ID) ?? null;
        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id) ?? null;

        $input['merchant_id'] = $merchantId;
        $input['batch_id'] = $batchId;

        return $this->create($input);
    }


    public function uploadAddressesToKafka()
    {
        return (new BulkUploadClient())->uploadAddressesToKafka();
    }

    public function consumeAddressesFromKafkaTest(array $input)
    {
        (new BulkUploadClient())->pushKafkaMessageToDB($input);
        return true;
    }

    public function getFailedAddressFile(string $batch_id)
    {
        $data = $this->core->getFailedAddresses($batch_id);
        if ($data === [] )
        {
            return ["message"=>self::NO_FAILED_ADDRESSES_FOUND];
        }
        return $this->generateFile($data,'FAILED_ADDRESSES');
    }
    /**
     * Generates XLSX file base on file data and stores in file store as type bulk_failed_raw_address_file
     *
     * @param array $fileData
     * @param string $fileName
     * @return mixed
     * @throws Exception\LogicException
     */
    public function generateFile(array $fileData, string $fileName)
    {
        $extension = FileStore\Format::XLSX;

        $creator = new FileStore\Creator;

        $newFileName = $this->getDynamicFileName($fileName);

        $creator->extension($extension)
                ->content($fileData)
                ->name($newFileName)
                ->store(FileStore\Store::S3)
                ->type(FileStore\Type::BULK_RAW_ADDRESS_FILE)
                ->save();

        $signedFileUrl = $creator->getSignedUrl();

        return ["url"=>$signedFileUrl['url']];
    }

    public function getDynamicFileName(string $file_name)
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y_H:i:s');

        return $file_name . '_' . $this->mode . '_' . $time;
    }

    public function validateForAddressEntity(array $input)
    {
        //addressObject is created for validating the inputs for address Entity before sending for dedupe
        $addressObject = $input;
        $addressObject['type'] = Type::SHIPPING_ADDRESS;
        unset($addressObject['id']);
        unset($addressObject['merchant_id']);
        unset($addressObject['batch_id']);
        unset($addressObject['status']);
        unset($addressObject['created_at']);
        unset($addressObject['deleted_at']);
        unset($addressObject['updated_at']);

        $this->trace->info(TraceCode::RAW_ADDRESS_CREATE_REQUEST,$addressObject);
        (new RawAddress\Validator())->validateInput("create_for_address",$addressObject);
    }

    private function validateForUnicode(array $input)
    {
        foreach ($input as $key => $value)
        {
            if(strlen(stringify($value)) != strlen(utf8_decode(stringify($value))))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
            }
        }
    }
}
