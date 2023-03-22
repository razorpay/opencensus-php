<?php

namespace RZP\Services;

use App;

use RZP\Jobs\Job;
use RZP\Models\Address\Entity;
use RZP\Services\Kafka;
use RZP\Trace\TraceCode;
use RZP\Models\Address\Type;
use RZP\Models\Customer;
use RZP\Http\Response;
use RZP\Models\RawAddress;
use RZP\Models\Address;
use RZP\Models\Merchant\Account;
use RZP\Models\RawAddress\Constants;

class BulkUploadClient extends Job
{
    const ADDRESS_DEDUPE_REQUEST  = 'address-dedupe-request';
    const RAW_ADDRESS_CONTACTS   = 'raw-address-contacts';
    const STATUS_PENDING         = 'pending';
    const STATUS_PROCESSING      = 'processing';
    const STATUS_PROCESSED       = 'processed';
    const STATUS_INVALID         = 'invalid';
    const PROCESSING_DELAY       = 70;

    protected $trace;

    protected $addressCore;

    protected $customerCore;

    protected RawAddress\Validator $rawAddressValidator;

    public function __construct()
    {
        parent::__construct();
        $this->trace = App::getFacadeRoot()['trace'];
        $this->addressCore = new Address\Core;
        $this->customerCore = new Customer\Core;
        $this->rawAddressValidator = new RawAddress\Validator();
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
                return ;
            }
            sleep(1);
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

            $this->repoManager->raw_address->updateStatus($contactArray,self::STATUS_PENDING,self::STATUS_PROCESSING);
        }
        catch (\Exception $e)
        {
            // push to kafka failed
            $this->trace->error(
                TraceCode::RAW_ADDRESS_KAFKA_FAILED_COUNT,
                ['error' => $e->getMessage()]
            );
            return false;
        }
        return true;
    }

    public function uploadAddressesToKafka(array $input)
    {
        parent::handle();
        foreach ($input as $contact)
        {
            $start = $this->getCurrentTimeInMillis();
            $rawAddresses =  $this->repoManager->raw_address->fetchRawAddressesForContact($contact['contact'],
                                                                                       self::STATUS_PROCESSING);
            $timeTaken = $this->getCurrentTimeInMillis() - $start;
            $this->trace->info(TraceCode::RAW_ADDRESS_TO_ADDRESS_CREATION_WORKER, ["fetchRawAddressesForContact:113" => $timeTaken]);

            if (sizeof($rawAddresses) == 0)
            {
                continue;
            }

            $start = $this->getCurrentTimeInMillis();
            $customer =  $this->repoManager->customer->findByContactAndMerchantId($contact['contact'],Account::SHARED_ACCOUNT);
            $timeTaken = $this->getCurrentTimeInMillis() - $start;
            $this->trace->info(TraceCode::RAW_ADDRESS_TO_ADDRESS_CREATION_WORKER, ["findByContactAndMerchantId:123" => $timeTaken]);
            if ($customer !== null)
            {
                $start = $this->getCurrentTimeInMillis();
                $addresses = $this->repoManager->address->fetchAddressesForEntity($customer,[]);
                $timeTaken = $this->getCurrentTimeInMillis() - $start;
                $this->trace->info(TraceCode::RAW_ADDRESS_TO_ADDRESS_CREATION_WORKER, ["fetchAddressesForEntity:129" => $timeTaken]);
            }
            else
            {
                $details = array('contact' => $contact['contact']);
                try
                {
                    $this->trace->info(TraceCode::CUSTOMER_CREATE_FROM_RAW_ADDRESS,[]);

                    $start = $this->getCurrentTimeInMillis();
                    $customer = $this->customerCore->createGlobalCustomer($details, true);
                    $timeTaken = $this->getCurrentTimeInMillis() - $start;
                    $this->trace->info(TraceCode::RAW_ADDRESS_TO_ADDRESS_CREATION_WORKER, ["createGlobalCustomer:141" => $timeTaken]);

                    $start = $this->getCurrentTimeInMillis();
                    $addresses = $this->repoManager->address->fetchAddressesForEntity($customer,[]);
                    $timeTaken = $this->getCurrentTimeInMillis() - $start;
                    $this->trace->info(TraceCode::RAW_ADDRESS_TO_ADDRESS_CREATION_WORKER, ["fetchAddressesForEntity:146" => $timeTaken]);
                }
                catch (\Exception $e)
                {
                    // push to kafka failed
                    $this->trace->error(TraceCode::KAFKA_UPLOAD_FAILED_AT_CUSTOMER_CREATION, ['error' => $e->getMessage()]);
                    continue;
                }
            }

            $start = $this->getCurrentTimeInMillis();
            $requestStructure = $this->convertToKafkaReqPayload($contact['contact'],$rawAddresses->toArray(),$addresses->toArray());
            $timeTaken = $this->getCurrentTimeInMillis() - $start;
            $this->trace->info(TraceCode::RAW_ADDRESS_TO_ADDRESS_CREATION_WORKER, ["convertToKafkaReqPayload:159" => $timeTaken]);

            $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_PUSH_REQUEST,[]);

            $kafkaStart = $this->getCurrentTimeInMillis();
            try
            {
                (new KafkaProducer(self::ADDRESS_DEDUPE_REQUEST, stringify($requestStructure)))->Produce();
                $this->trace->info(TraceCode::RAW_ADDRESS_TO_ADDRESS_CREATION_WORKER, ["kafkaPushSuccess:167" => $this->getCurrentTimeInMillis() - $kafkaStart]);

                $sleepStart = $this->getCurrentTimeInMillis();
                $this->trace->info(TraceCode::RAW_ADDRESS_TO_ADDRESS_CREATION_WORKER, ["KafkaSleepStartAfterAddressPublish" => $sleepStart]);
                usleep(50000);
                $this->trace->info(TraceCode::RAW_ADDRESS_TO_ADDRESS_CREATION_WORKER, ["KafkaSleepEndAfterAddressPublish" => $this->getCurrentTimeInMillis() - $sleepStart]);
            }
            catch (\Exception $e)
            {
                // push to kafka failed
                $this->trace->error(
                    TraceCode::RAW_ADDRESS_KAFKA_FAILED_COUNT,
                    ['error' => $e->getMessage(),
                     'time_taken' => ($this->getCurrentTimeInMillis() - $kafkaStart)]
                );
                $this->trace->info(TraceCode::RAW_ADDRESS_TO_ADDRESS_CREATION_WORKER, ["kafkaPushFailed:177" => $this->getCurrentTimeInMillis() - $kafkaStart]);
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
                // If batch_id is empty then source type is shopify
                // Else if batch_id is not empty and has value woocommerce then source type is woocommerce
                // Else if batch_id is not empty and has value not equal to woocommerce then source type is bulk_upload
                $sourceType = Constants::ADDRESS_SOURCE_TYPE_SHOPIFY;
                if (strlen($address[RawAddress\Entity::BATCH_ID]) > 0)
                {
                    $sourceType = Constants::ADDRESS_SOURCE_TYPE_BULK_UPLOAD;

                    if ($address[RawAddress\Entity::BATCH_ID] === Constants::ADDRESS_SOURCE_TYPE_WOOCOMMERCE)
                    {
                        $sourceType = Constants::ADDRESS_SOURCE_TYPE_WOOCOMMERCE;
                    }
                }
                $address[Constants::ADDRESS_TYPE]=Constants::ADDRESS_TYPE_RAW;
                $address[Address\Entity::SOURCE_ID] = $address[RawAddress\Entity::ID];
                $address[Address\Entity::SOURCE_TYPE] = $sourceType;
                unset($address[RawAddress\Entity::ID]);
                unset($address[RawAddress\Entity::MERCHANT_ID]);
                unset($address[RawAddress\Entity::BATCH_ID]);
                unset($address[RawAddress\Entity::CONTACT]);
                unset($address[RawAddress\Entity::STATUS]);
                unset($address[RawAddress\Entity::CREATED_AT]);
                unset($address[RawAddress\Entity::UPDATED_AT]);
                unset($address[RawAddress\Entity::DELETED_AT]);
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
                $address[Constants::ADDRESS_TYPE]=Constants::ADDRESS_TYPE_OLD;
                unset($address[Address\Entity::ID]);
                unset($address[Address\Entity::ENTITY_ID]);
                unset($address[Address\Entity::ENTITY_TYPE]);
                unset($address[Address\Entity::TYPE]);
                unset($address[Address\Entity::PRIMARY]);
                unset($address[Address\Entity::CONTACT]);
                unset($address[Address\Entity::CREATED_AT]);
                unset($address[Address\Entity::UPDATED_AT]);
                unset($address[Address\Entity::DELETED_AT]);

                if(is_null($address[Address\Entity::SOURCE_ID]) === true)
                {
                    $address[Address\Entity::SOURCE_ID] = "";
                }
                if(is_null($address[Address\Entity::SOURCE_TYPE]) === true)
                {
                    $address[Address\Entity::SOURCE_TYPE] = "";
                }

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
            $rawAddress = $this->repoManager->raw_address->findOrFail($rawAddressId);
            $rawAddress->setStatus($statusValue);
            $this->repoManager->raw_address->saveOrFail($rawAddress);
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
        parent::handle();
        try
        {
            $this->rawAddressValidator->validateInput('process_kafka_message', $kafkaMessage);

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
                    $this->createNewAddress($firstAddress,$kafkaMessage['contact']);
                    usleep(self::PROCESSING_DELAY*1000);
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

    protected function createNewAddress(array $firstAddress, string $contact)
    {
        //create new Address
        $entity_id = null;
        try
        {
            $firstAddress['type'] = Type::SHIPPING_ADDRESS;
            $firstAddress['contact'] = $contact;
            $source_id = stringify($firstAddress[Address\Entity::SOURCE_ID]);
            $source_type = stringify($firstAddress[Address\Entity::SOURCE_TYPE]);
            $raw_address = null;
            if ($firstAddress[Constants::ADDRESS_TYPE] === Constants::ADDRESS_TYPE_RAW)
            {
                $raw_address = $this->repoManager->raw_address->findOrFail($source_id);
            }

            // new->tw,pp; raw
            if ( (is_null($raw_address) === true && $firstAddress[Constants::ADDRESS_TYPE] === Constants::ADDRESS_TYPE_NEW )
                 || $raw_address[RawAddress\Entity::STATUS] !== BulkUploadClient::STATUS_PROCESSED)
            {
                unset($firstAddress[Constants::ADDRESS_TYPE]);

                $this->trace->info(TraceCode::RAW_ADDRESS_TO_ADDRESS_CREATION,[
                    "source_id" => $source_id,
                    "source_type" => $source_type,
                ]);

                $customer =  $this->repoManager->customer->findByContactAndMerchantId($contact,Account::SHARED_ACCOUNT);
                if ($customer == null)
                {
                    $details = array('contact' => $contact);
                    $customer = $this->customerCore->createGlobalCustomer($details, true);
                }
                $this->addressCore->create($customer, Type::CUSTOMER, $firstAddress,true);
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
            if ($address[Constants::ADDRESS_TYPE] === Constants::ADDRESS_TYPE_OLD)
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
            if ($address[Constants::ADDRESS_TYPE] === Constants::ADDRESS_TYPE_RAW)
            {
                $this->updateStatus(stringify($address[Address\Entity::SOURCE_ID]), self::STATUS_PROCESSED);
            }

        }
    }

    public function getCurrentTimeInMillis()
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

    public function findAndDeleteInvalidAddress(array $message){
        parent::handle();

        if($message['address_id'] === null || strlen($message['address_id']) === 0){

            $this->trace->info(TraceCode::INVALID_ADDRESS_CONSUMER_CLIENT_PROCESSING,[
                "Message" => "address id is empty",
            ]);

            return;
        }

        $this->trace->info(TraceCode::INVALID_ADDRESS_CONSUMER_CLIENT_PROCESSING,[
            "Message" =>$message,
        ]);

        try{

            $address = $this->repoManager->address->findOrFail($message['address_id']);

        }catch (\Exception $ex){

            $this->trace->traceException($ex);

            $this->trace->info(TraceCode::INVALID_ADDRESS_CONSUMER_MESSAGE_FIND_REQUEST,[
                "ExceptionMessage" => $ex->getMessage(),
            ]);

            $this->trace->count(TraceCode::ONE_CC_DELETE_ADDRESS_FIND_FAILED);
            return;

        }

        $deleted_address = null;

        try{

            $deleted_address = $this->addressCore->delete($address);

        } catch (\Exception $ex){

            $this->trace->traceException($ex);

            $this->trace->info(TraceCode::INVALID_ADDRESS_CONSUMER_MESSAGE_DELETE_REQUEST,[
                "ExceptionMessage" => $ex->getMessage(),
            ]);

            $this->trace->count(TraceCode::ONE_CC_DELETE_ADDRESS_DELETE_FAILED);

            return;
        }

        if ($deleted_address === null)
        {
            $this->trace->info(TraceCode::INVALID_ADDRESS_CONSUMER_CLIENT_DELETE_SUCCESSFUL,[
                "Message" =>$message,
            ]);

            $this->trace->count(TraceCode::ONE_CC_DELETE_ADDRESS_SUCCESSFUL);

            return [];
        }

        $this->trace->info(TraceCode::INVALID_ADDRESS_CONSUMER_CLIENT_DELETE_FAILED,[
            "Message" =>$message,
        ]);

        $this->trace->count(TraceCode::ONE_CC_DELETE_ADDRESS_DELETE_PROCESS_FAILED);
    }

}
