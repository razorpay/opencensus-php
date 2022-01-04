<?php

namespace RZP\Services;

use App;

use RZP\Services\Kafka;
use RZP\Trace\TraceCode;
use RZP\Models\Address\Type;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\RawAddress;
use RZP\Models\Address;

class BulkUploadClient
{
    const ADDRESS_DEDUPE_REQUEST  = 'func-event.address_dedupe_request.test';
    const ADDRESS_DEDUPE_RESPONSE = 'func-event.address_dedupe_response.test';
    const STATUS_PENDING         = 'pending';
    const STATUS_PROCESSING      = 'processing';
    const STATUS_PROCESSED       = 'processed';
    const STATUS_INVALID         = 'invalid';

    private $trace;

    public function __construct()
    {
        $this->trace = App::getFacadeRoot()['trace'];
    }

    /**
     *Fetch all pending contacts
     * Fetch all addresses for single contact form addresses and raw_addresses
     * Group the address to contact and send to kafka
    **/
    public function uploadAddressesToKafka()
    {
        $contacts = (new RawAddress\Repository())->fetchAllPendingContacts();
        $contactArray = $contacts->toArray();
        $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_PUSH_START,[$contactArray]);
        foreach ($contactArray as $contact)
        {
            $rawAddresses = (new RawAddress\Repository())->fetchRawAddressesForContact($contact['contact'],
                                                                                       self::STATUS_PENDING);

            $addresses = (new Address\Repository())->fetchAddressesForContact($contact['contact']);

            $requestStructure = $this->convertToKafkaReqPayload($contact['contact'],$rawAddresses->toArray(),$addresses->toArray());

            $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_PUSH_REQUEST,["request"=>$requestStructure]);

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
                return false;
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
                $this->updateStatus($address[RawAddress\Entity::ID], self::STATUS_PROCESSING);
                $address['is_raw_address']=true;
                array_push($json["addresses"],$address);
            }
            catch (\Throwable $e)
            {
                $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_PAYLOAD_CONVERSION_FAILED,
                                   ["error"=>$e->getMessage(),"address"=>$address]);

                $this->updateStatus($address[RawAddress\Entity::ID], self::STATUS_INVALID);
                //update status as invalid
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
                                       "error"=>$e->getMessage(),
                                       "address"=>$address
                                   ]);
            }
        }
        return $json;
    }

    public function updateStatus(string $rawAddressId,string $statusValue)
    {
        try
        {
            $rawAddress = (new RawAddress\Repository())->findByPublicId('rawaddr_'.$rawAddressId);
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

            foreach ($kafkaMessage['addresses'] as $addressCluster)
            {
                if (empty($addressCluster))
                {
                    continue;
                }
                $firstAddress = $addressCluster[0];
                $containsAddressEntity = $this->checkAddressEntity($addressCluster);

                $this->trace->info(TraceCode::RAW_ADDRESS_CREATE_REQUEST,[
                    "address" => $firstAddress
                ]);
                if ($containsAddressEntity === false)
                {
                    $this->createNewAddress($firstAddress);
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_CONSUME_FAILED,[
                "error" => $e->getMessage(),
                "kafka_message" => $kafkaMessage
            ]);
        }
    }

    protected function createNewAddress(array $firstAddress)
    {
        //create new Address
        try
        {
            $firstAddress['type'] = Type::SHIPPING_ADDRESS;
            $entity_id = $firstAddress['id'];
            unset($firstAddress['id']);
            unset($firstAddress['merchant_id']);
            unset($firstAddress['batch_id']);
            unset($firstAddress['status']);
            unset($firstAddress['created_at']);
            unset($firstAddress['deleted_at']);
            unset($firstAddress['updated_at']);
            unset($firstAddress['is_raw_address']);

            (new Address\Core)->create((new RawAddress\Repository())->findByPublicId('rawaddr_'.$entity_id), Type::RAW_ADDRESS, $firstAddress,true);
        }
        catch (Exception $e)
        {
            $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_CONSUME_FAILED,[
                "error" => $e->getMessage(),
                "address" => $firstAddress
            ]);
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
                //can't break here as need to mark processed for all raw_addresses
            }
            else
            {
                //update status for raw_address
                $this->updateStatus($address[RawAddress\Entity::ID], self::STATUS_PROCESSED);
            }
        }
        return $addressEntity;
    }

    protected function getCurrentTimeInMillis()
    {
        return round(microtime(true) * 1000);
    }

}
