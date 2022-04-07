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
        if (isset($input[Entity::COUNTRY]) === true) {
            $input[Entity::COUNTRY] = strtolower($input[Entity::COUNTRY]);
        }
        $raw_address = $this->core()->create($input);

        try
        {
            $this->validateForAddressEntity($input);
            $this->validateForUnicode($input);
            $input[Entity::CONTACT] = $this->checkAndGetValidContact($input[Entity::CONTACT]);
            $raw_address->setContact($input[Entity::CONTACT]);
            $this->repo->raw_address->saveOrFail($raw_address);

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

        $this->trace->info(TraceCode::RAW_ADDRESS_CREATE_REQUEST,[]);
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

    private function checkAndGetValidContact(string $contact)
    {
        $contact = str_replace(' ', '', $contact); // Remove spaces
        $contact =  preg_replace('/[^A-Za-z0-9\-]/', '', $contact); // Removes special chars

        if (is_numeric($contact) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
                                                    null,null,"Not a valid contact number");
        }

        if (str_starts_with($contact,"91") && strlen($contact) === 12)
        {
            return "+".$contact;
        }
        if (str_starts_with($contact, "0") && strlen($contact) == 11 )
        {
            $contact = ltrim($contact, '0');
            return "+91".$contact;
	    }

        if( strlen($contact) == 10 )
        {
		    return "+91".$contact;
        }
        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
                                            null,null,"Not a valid Indian contact number");
    }
}
