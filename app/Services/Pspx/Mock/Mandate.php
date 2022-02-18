<?php

namespace RZP\Services\Pspx\Mock;

use Carbon\Carbon;
use RZP\Services\Pspx\Routes;
use RZP\Services\Pspx\Mandate as BaseMandate;
use RZP\Models\P2p\Mandate\Entity as MandateEntity;
use RZP\Models\P2p\Mandate\UpiMandate\Entity as UpiMandateEntity;

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

    protected $upiEntitySkeleton = array(
        UpiMandateEntity::NETWORK_TRANSACTION_ID        => 'SeYMXtJ6YSym4A6RgRemZd03IXxcbfbKmwK',
        UpiMandateEntity::GATEWAY_TRANSACTION_ID        => 'SeYMXtJ6YSym4A6RgRemZd03IXxcbfbKmwK',
        UpiMandateEntity::GATEWAY_REFERENCE_ID          => '911416196085',
        UpiMandateEntity::RRN                           => '911416196085',
        UpiMandateEntity::REF_ID                        => '',
        UpiMandateEntity::REF_URL                       => '',
        UpiMandateEntity::MCC                           => '1234',
        UpiMandateEntity::GATEWAY_ERROR_CODE            => '00',
        UpiMandateEntity::GATEWAY_ERROR_DESCRIPTION     => 'Incoming mandate create request"'
    );

    protected $entitySkeleton = array(
        MandateEntity::DEVICE_ID                     => 'Device00123456',
        MandateEntity::CUSTOMER_ID                   => 'Customer001234',
        MandateEntity::AMOUNT_RULE                   => 'EXACT',
        MandateEntity::PAYER_ID                      => 'CustomerVpa001',
        MandateEntity::PAYEE_ID                      => 'CustomerVpa002',
        MandateEntity::TYPE                          => 'collect',
        MandateEntity::FLOW                          => 'debit',
        MandateEntity::MODE                          => 'default',
        MandateEntity::RECURRING_TYPE                => 'WEEKLY',
        MandateEntity::RECURRING_VALUE               => 2,
        MandateEntity::RECURRING_RULE                => 'ON',
        MandateEntity::UMN                           => '123456789012345678901234',
        MandateEntity::STATUS                        => 'requested',
        MandateEntity::INTERNAL_STATUS               => 'requested',
        MandateEntity::GATEWAY                       => 'p2p_upi_axis',
        MandateEntity::EXPIRE_AT                     => 0,
        MandateEntity::START_DATE                    => 0,
        MandateEntity::END_DATE                      => 0,
        MandateEntity::ACTION                        => 'incomingMandate',
        MandateEntity::GATEWAY_DATA                  => [],
        MandateEntity::UPI                           => []
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
                $this->response = $this->mockFetchMandate($payload);
                break;

            case Routes::FETCH_ALL_MANDATE:
                $this->response = $this->mockFetchAllMandate($payload);
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
        $mandateInput = isset($input[MandateEntity::MANDATE]) ? $input[MandateEntity::MANDATE] : [];
        $contextInput = isset($input[MandateEntity::CONTEXT]) ? $input[MandateEntity::CONTEXT] : [];

        $mandate = array_merge($mandateInput, [
            MandateEntity::ID              => MandateEntity::generateUniqueId(),
            MandateEntity::CREATED_AT      => Carbon::now()->getTimestamp(),
            MandateEntity::UPDATED_AT      => Carbon::now()->getTimestamp(),
            MandateEntity::DELETED_AT      => null,
        ]);

        if (empty($contextInput) === false)
        {
            $mandate = array_merge($mandate, [
                MandateEntity::MERCHANT_ID      => $contextInput['client']['id'],
                MandateEntity::DEVICE_ID        => $contextInput['device']['id'],
                MandateEntity::CUSTOMER_ID      => $contextInput[MandateEntity::CUSTOMER_ID],
            ]);
        }

        return array_merge($this->entitySkeleton, $mandate);
    }

    /**
     * This is the method to fetch mandate data from cache
     * @param array $input
     *
     * @return array
     */
    private function mockFetchMandate(array $input): array
    {
        $container = $this->getContainer();

        if (count($container) > 0)
        {
            foreach ($container as $key => $value)
            {
                if($input[MandateEntity::MANDATE][MandateEntity::ID] === $container[$key][MandateEntity::ID])
                {
                    return $container[$key];
                }
            }
        }

        return [];
    }

    /**
     * Mock function which returns an array containing all the created mandates
     *
     * @return array
     */
    private function mockFetchAllMandate(): array
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
            if($input[MandateEntity::MANDATE][MandateEntity::ID] === $container[$key][MandateEntity::ID])
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
            if($input[MandateEntity::MANDATE][MandateEntity::ID] === $container[$key][MandateEntity::ID])
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
