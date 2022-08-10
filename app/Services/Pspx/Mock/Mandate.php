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
        MandateEntity::NAME                          => 'A Mandate Name',
        MandateEntity::DEVICE_ID                     => 'Device00123456',
        MandateEntity::CUSTOMER_ID                   => 'Customer001234',
        MandateEntity::AMOUNT_RULE                   => 'EXACT',
        MandateEntity::PAYER_ID                      => 'CustomerVpa001',
        MandateEntity::PAYEE_ID                      => 'CustomerVpa002',
        MandateEntity::BANK_ACCOUNT_ID               => 'ALC01bankAc001',
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
        MandateEntity::CYCLES_COMPLETED              => 0,
        MandateEntity::REVOKED_AT                    => null,
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

            case Routes::FETCH_MANDATE_BY_UMN:
                $this->response = $this->mockFetchMandateByUMN($payload);
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
        $upiInput     = isset($input[MandateEntity::UPI]) ? $input[MandateEntity::UPI] : [];

        $mandate = array_merge($mandateInput, [
            MandateEntity::ID              => MandateEntity::generateUniqueId(),
            MandateEntity::CREATED_AT      => Carbon::now()->getTimestamp(),
            MandateEntity::UPDATED_AT      => Carbon::now()->getTimestamp(),
            MandateEntity::DELETED_AT      => null,
            MandateEntity::UPI             => array_merge($this->upiEntitySkeleton, $upiInput),
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
        $mandates = $this->getContainer();

        if (count($mandates) > 0)
        {
            foreach ($mandates as $mandate)
            {
                if($input[MandateEntity::ID] === $mandate[MandateEntity::ID])
                {
                    return $mandate;
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
    private function mockFetchAllMandate($payload): array
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
        $mandates = $this->getContainer();

        unset($input[MandateEntity::CONTEXT]);

        foreach ($mandates as $index => $mandate)
        {
            if ($mandate[MandateEntity::ID] === $input[MandateEntity::ID])
            {
                $updatedMandate = array_merge($mandate, $input);

                $mandates[$index] = $updatedMandate;

                \Cache::put(self::CACHE_KEY, $mandates);

                return $this->getContainer()[$index];
            }
        }

        return [];
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
        $mandates = $this->getContainer();

        if (empty($mandates) === true)
        {
            return [];
        }

        foreach ($mandates as $index => $mandate)
        {
            if($input[MandateEntity::ID] === $mandate[MandateEntity::ID])
            {
                $deletedRecord = $mandate;

                unset($mandates[$index]);

                \Cache::put(self::CACHE_KEY, $mandates);
            }
        }

        return $deletedRecord;
    }

    /**
     * This is the method to fetch mandate data from cache by umn
     * @param array $input
     *
     * @return array
     */
    private function mockFetchMandateByUMN(array $input): array
    {
        $mandates = $this->getContainer();

        if (count($mandates) > 0)
        {
            foreach ($mandates as $mandate)
            {
                if($input[MandateEntity::UMN] === $mandate[MandateEntity::UMN])
                {
                    return $mandate;
                }
            }
        }

        return [];
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
