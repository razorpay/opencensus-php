<?php

namespace RZP\Services;

use App;

use RZP\Jobs\Job;
use RZP\Services\Kafka;
use RZP\Trace\TraceCode;
use RZP\Models\Address\Type;
use RZP\Models\Customer;
use RZP\Http\Response;
use RZP\Models\RawAddress;
use RZP\Models\Address;
use RZP\Models\Merchant\Account;

class BulkUploadClient extends Job
{
    const ADDRESS_DEDUPE_REQUEST  = 'address-dedupe-request';
    const RAW_ADDRESS_CONTACTS   = 'raw-address-contacts';
    const STATUS_PENDING         = 'pending';
    const STATUS_PROCESSING      = 'processing';
    const STATUS_PROCESSED       = 'processed';
    const STATUS_INVALID         = 'invalid';

    protected $trace;

    public function __construct()
    {
        parent::__construct();
        $this->trace = App::getFacadeRoot()['trace'];
    }

    /**
     * Process the Raw Address Contacts
     *
     * @var string mode
     * @return mixed
     */
    public function process(string $mode = null)
    {
        $this->mode = $mode;

        parent::__construct($mode);

        parent::handle();

        while (true)
        {
            try
            {
                $response = $this->uploadContactsToKafka();
                if ($response === false)
                {
                    $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_PUSH_REQUEST, ["message" => "no more contacts."]);
                    return;
                }
            }
            catch (\Exception $e)
            {
                $this->trace->info(TraceCode::ERROR_EXCEPTION, ["error" => $e->getMessage()]);
            }
        }
    }

    /**
     *Fetch pending contacts
     *Uploads to kafka
     **/
    public function uploadContactsToKafka()
    {
        $contacts = $this->repoManager->raw_address->fetchPendingContacts();
        $contactArray = $contacts->toArray();

        $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_PUSH_START,["message"=>"contact push started"]);
        if(count($contactArray) == 0)
        {
            return false;
        }

        try
        {
            $topic =  env('APP_MODE', 'prod').'-'. self::RAW_ADDRESS_CONTACTS;

            (new KafkaProducer($topic, stringify($contactArray)))->Produce();

            $this->repoManager->raw_address->updateStatus($contactArray,self::STATUS_PROCESSING);
        }
        catch (\Exception $e)
        {
            // push to kafka failed
            $this->trace->error(
                TraceCode::RAW_ADDRESS_KAFKA_FAILED_COUNT,
                ['error' => $e->getMessage()]
            );
        }
        return true;
    }

    public function uploadAddressesToKafka(array $input)
    {
        foreach ($input as $contact)
        {
            $rawAddresses = (new RawAddress\Repository())->fetchRawAddressesForContact($contact['contact'],
                                                                                       self::STATUS_PROCESSING);

            $customer = (new Customer\Repository())->findByContactAndMerchantId($contact['contact'],Account::SHARED_ACCOUNT);

            if ($customer !== null)
            {
                $addresses = (new Address\Repository())->fetchAddressesForEntity($customer,[]);
            }
            else
            {
                $details = array('contact' => $contact['contact']);
                try
                {
                    $this->trace->info(TraceCode::CUSTOMER_CREATE_FROM_RAW_ADDRESS,[]);

                    $customer = (new Customer\Core)->createGlobalCustomer($details, true);
                    $addresses = (new Address\Repository())->fetchAddressesForEntity($customer,[]);
                }
                catch (\Exception $e)
                {
                    // push to kafka failed
                    $this->trace->error(TraceCode::KAFKA_UPLOAD_FAILED_AT_CUSTOMER_CREATION, ['error' => $e->getMessage()]);
                    continue;
                }
            }

            $requestStructure = $this->convertToKafkaReqPayload($contact['contact'],$rawAddresses->toArray(),$addresses->toArray());

            $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_PUSH_REQUEST,[]);

            $kafkaStart = $this->getCurrentTimeInMillis();
            try
            {
                (new KafkaProducer(self::ADDRESS_DEDUPE_REQUEST, stringify($requestStructure)))->Produce();
            }
            catch (\Exception $e)
            {
                // push to kafka failed
                $this->trace->error(
                    TraceCode::RAW_ADDRESS_KAFKA_FAILED_COUNT,
                    ['error' => $e->getMessage(),
                     'time_taken' => ($this->getCurrentTimeInMillis() - $kafkaStart)]
                );
                continue;
            }
            $this->trace->histogram(
                TraceCode::RAW_ADDRESS_KAFKA_PUSH_DURATION,
                $this->getCurrentTimeInMillis() - $kafkaStart
            );
        }
        return true;
    }

    public function convertToKafkaReqPayload(string $contact, array $rawAddresses, array $addresses)
    {
        $json = array("contact" => $contact, "addresses" => []);
        foreach ($rawAddresses as $address)
        {
            try
            {
                $address['is_raw_address']=true;
                array_push($json["addresses"],$address);
            }
            catch (\Throwable $e)
            {
                $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_PAYLOAD_CONVERSION_FAILED,
                                   ["error"=>$e->getMessage()]);

            }
        }

        foreach ($addresses as $address)
        {
            try
            {
                $address['is_raw_address']=false;
                array_push($json["addresses"],$address);
            }
            catch (\Throwable $e)
            {
                $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_PAYLOAD_CONVERSION_FAILED,
                                   [
                                       "error"=>$e->getMessage()
                                   ]);
            }
        }
        return $json;
    }

    public function updateStatus(string $rawAddressId,string $statusValue)
    {
        try
        {
            $rawAddress = (new RawAddress\Repository())->findOrFail($rawAddressId);
            $rawAddress->setStatus($statusValue);
            (new RawAddress\Repository())->saveOrFail($rawAddress);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::FAILED_TO_UPDATE_RAW_ADDRESS_STATUS,
                               [
                                   'raw_address_id' => $rawAddressId,
                                   'error' => $e->getMessage(),
                                   'statusValue' => $statusValue
                               ]);
        }

    }

    public function pushKafkaMessageToDB(array $kafkaMessage)
    {
        try
        {
            (new RawAddress\Validator())->validateInput('process_kafka_message', $kafkaMessage);

            if ($kafkaMessage['statusCode'] !== Response\StatusCode::SUCCESS)
            {
                $this->handleErrorResponse($kafkaMessage);
                return null;
            }

            foreach ($kafkaMessage['addresses'] as $addressCluster)
            {
                if (empty($addressCluster))
                {
                    continue;
                }
                $firstAddress = $addressCluster[0];
                $containsAddressEntity = $this->checkAddressEntity($addressCluster);

                if ($containsAddressEntity === false)
                {
                    $firstAddress = $this->unsetNullKeys($firstAddress);
                    $this->createNewAddress($firstAddress);
                }
                //new loop to mark raw_addresses as processed
                $this->updateStatusToProcessed($addressCluster);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_CONSUME_FAILED,[
                "error" => $e->getMessage()
            ]);
        }
    }

    protected function createNewAddress(array $firstAddress)
    {
        //create new Address
        $entity_id = null;
        try
        {
            $firstAddress['type'] = Type::SHIPPING_ADDRESS;
            $entity_id = stringify($firstAddress['id']);
            $raw_address = (new RawAddress\Repository())->findOrFail($entity_id);
            $merchantId = $firstAddress['merchant_id'];
            unset($firstAddress['id']);
            unset($firstAddress['merchant_id']);
            unset($firstAddress['batch_id']);
            unset($firstAddress['status']);
            unset($firstAddress['created_at']);
            unset($firstAddress['deleted_at']);
            unset($firstAddress['updated_at']);
            unset($firstAddress['is_raw_address']);

            if ($raw_address['status'] !== BulkUploadClient::STATUS_PROCESSED)
            {
                $this->trace->info(TraceCode::RAW_ADDRESS_TO_ADDRESS_CREATION,[
                    "raw_address_entity_id" => $entity_id,
                    "merchant_id" => $merchantId,
                ]);

                $customer = (new Customer\Repository())->findByContactAndMerchantId($firstAddress['contact'],Account::SHARED_ACCOUNT);
                if ($customer !== null){
                    (new Address\Core)->create($customer, Type::CUSTOMER, $firstAddress,true);
                }else {
                    throw new \InvalidArgumentException("null customer");
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_CONSUME_FAILED,[
                "error" => $e->getMessage()]);
        }
    }

    protected function checkAddressEntity(array $addressCluster)
    {
        $addressEntity = false;
        foreach ($addressCluster as $address)
        {
            if ($address['is_raw_address']===false)
            {
                $addressEntity = true;
                break;
            }
        }
        return $addressEntity;
    }

    protected function updateStatusToProcessed(array $addressCluster)
    {
        foreach ($addressCluster as $address)
        {
            if ($address['is_raw_address'] === true)
            {
                $this->updateStatus(stringify($address[RawAddress\Entity::ID]), self::STATUS_PROCESSED);
            }

        }
    }

    protected function getCurrentTimeInMillis()
    {
        return round(microtime(true) * 1000);
    }

    protected function handleErrorResponse(array $kafkaMessage)
    {
        $this->trace->info(TraceCode::ERROR_EXCEPTION,[
            "status_code" =>$kafkaMessage['statusCode'],
            "error" => $kafkaMessage['message'],
        ]);
    }
    protected function unsetNullKeys(array $firstAddress)
    {
        foreach($firstAddress as $key => $value)
        {
            if($value === null)
            {
                unset($firstAddress[$key]);
            }
        }
        return $firstAddress;
    }

}
