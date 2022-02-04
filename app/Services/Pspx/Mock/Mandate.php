<?php

namespace RZP\Services\Pspx\Mock;

use Carbon\Carbon;
use RZP\Services\Pspx\Routes;
use RZP\Models\P2p\Mandate\Entity;
use RZP\Services\Pspx\Mandate as BaseMandate;

/**
 * Mock for Mandate class
 *
 * Class Mandate
 *
 * @package RZP\Services\Pspx\Mock
 */
class Mandate extends BaseMandate
{
    const CACHE_KEY          = 'api:p2p:upi:mocked_cache_key';
    const MANDATE_CACHE_SIZE = 5;

    protected $entitySkeleton = array(
        Entity::DEVICE_ID                     => 'Device00123456',
        Entity::CLIENT_ID                     => 'Client00123456',
        Entity::CUSTOMER_ID                   => 'Customer001234',
        Entity::AMOUNT_RULE                   => 'EXACT',
        Entity::PAYER_ID                      => 'CustomerVpa001',
        Entity::PAYEE_ID                      => 'CustomerVpa002',
        Entity::TYPE                          => 'collect',
        Entity::FLOW                          => 'debit',
        Entity::MODE                          => 'default',
        Entity::RECURRING_TYPE                => 'WEEKLY',
        Entity::RECURRING_VALUE               => 2,
        Entity::RECURRING_RULE                => 'ON',
        Entity::UMN                           => '123456789012345678901234',
        Entity::STATUS                        => 'requested',
        Entity::INTERNAL_STATUS               => 'requested',
        Entity::GATEWAY                       => 'p2p_upi_axis',
        Entity::EXPIRE_AT                     => 0,
        Entity::START_DATE                    => 0,
        Entity::END_DATE                      => 0,
        Entity::DETAILS                       => '',
        Entity::ACTION                        => 'incomingMandate',
        Entity::NETWORK_TRANSACTION_ID        => 'SeYMXtJ6YSym4A6RgRemZd03IXxcbfbKmwK',
        Entity::GATEWAY_TRANSACTION_ID        => 'SeYMXtJ6YSym4A6RgRemZd03IXxcbfbKmwK',
        Entity::GATEWAY_REFERENCE_ID          => '911416196085',
        Entity::RRN                           => '911416196085',
        Entity::REF_ID                        => '',
        Entity::REF_URL                       => '',
        Entity::MCC                           => '1234',
        Entity::GATEWAY_ERROR_CODE            => '00',
        Entity::GATEWAY_ERROR_DESCRIPTION     => 'Incoming mandate create request"',
        Entity::GATEWAY_DATA                  => [],
    );

    protected $response = array();

    /**
     * Overriding sendRequest() from base class Service.php
     *
     * @param string $method
     * @param string $path
     * @param array $payload
     * @param array $headers
     * @param array $options
     *
     * @return array|mixed|null
     */
    protected  function sendRequest(string $method, string $path, $payload = [], $headers = [], $options = [])
    {
        switch ($path)
        {
            case Routes::CREATE_MANDATE:
                $this->response = $this->mockCreateMandate($payload);
                break;

            case Routes::FETCH_MANDATE:
                $this->response = $this->mockFetchMandate();
                break;

            case Routes::UPDATE_MANDATE:
                $this->response = $this->mockUpdateMandate($payload);
                break;

            case Routes::DELETE_MANDATE:
                $this->response = $this->mockDeleteMandate($payload);
                break;
        }

        return $this->response;
    }

    /**
     * Mock function for creating new mandate requires an
     *
     * @param array $input
     *
     * @return array
     */
    private function mockCreateMandate(array $input):array
    {
        $container = $this->getContainer();

        if(count($container) === self::MANDATE_CACHE_SIZE)
        {
            array_shift($container);
        }

        array_push($container, $this->getMandateArray($input));

        \Cache::put(self::CACHE_KEY, $container);

        return $container[array_key_last($container)];
    }

    /**
     * Constructs mandate array from input, skeleton and dynamic fields
     *
     * @param $input
     *
     * @return array
     */
    private function getMandateArray($input): array
    {
        $mandateInput = isset($input[Entity::MANDATE]) ? $input[Entity::MANDATE] : [];
        $contextInput = isset($input[Entity::CONTEXT]) ? $input[Entity::CONTEXT] : [];

        $mandate = array_merge($mandateInput, [
            Entity::ID              => Entity::generateUniqueId(),
            Entity::CREATED_AT      => Carbon::now()->getTimestamp(),
            Entity::UPDATED_AT      => Carbon::now()->getTimestamp(),
            Entity::DELETED_AT      => null,
        ]);

        if (empty($contextInput) === false)
        {
            $mandate = array_merge($mandate, [
                Entity::CLIENT_ID   => $contextInput['client']['id'],
                Entity::DEVICE_ID   => $contextInput['device']['id'],
                Entity::CUSTOMER_ID => $contextInput[Entity::CUSTOMER_ID],
            ]);
        }

        return array_merge($this->entitySkeleton, $mandate);
    }

    /**
     * Mock function which returns an array containing all the created mandates
     *
     * @return array
     */
    private function mockFetchMandate(): array
    {
        $container = $this->getContainer();

        if (count($container) > 0)
        {
            return $container;
        }

        return array();
    }

    /**
     * Mock function which helps in updating a mandate based on the id
     *
     * @param array $input
     *
     * @return array
     */
    private function mockUpdateMandate(array $input): array
    {
        $container = $this->getContainer();

        foreach ($container as $key => $value)
        {
            if($input[Entity::ID] === $container[$key][Entity::ID])
            {
                unset($container[$key]);

                array_push($container, $input);

                \Cache::put(self::CACHE_KEY, $container);
            }
        }

        return $this->getContainer();
    }

    /**
     * Mock function which helps in deleting a mandate based on the id
     *
     * @param array $input
     *
     * @return array
     */
    private function mockDeleteMandate(array $input)
    {
        $container = $this->getContainer();

        if (empty($container) === true)
        {
            return [];
        }

        foreach ($container as $key => $value)
        {
            if($input[Entity::ID] === $container[$key][Entity::ID])
            {
                $deletedRecord = $container[$key];

                unset($container[$key]);

                \Cache::put(self::CACHE_KEY, $container);
            }
        }

        return $deletedRecord;
    }

    /**
     * Mock function which fetches and return the mandates from cache memory
     *
     * @return array
     */
    private function getContainer()
    {
        return \Cache::has(self::CACHE_KEY) ? \Cache::get(self::CACHE_KEY) : array();
    }
}
