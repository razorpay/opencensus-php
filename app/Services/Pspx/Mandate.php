<?php

namespace RZP\Services\Pspx;

use RZP\Models\P2p\Mandate\Entity;
use RZP\Models\P2p\Base\Libraries\Context;

class Mandate extends Service
{
    /**
     * Create request to PSPx service
     *
     * @param $context
     * @param array $input
     *
     * @return mixed|null
     *
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\RuntimeException
     * @throws \RZP\Exception\ServerErrorException
     */
    public function create($context, array $input)
    {
        $payload = $this->getPayloadArray($context, $input);

        $response = $this->sendRequest('POST', Routes::CREATE_MANDATE, $payload);

        return $response;
    }

    /**
     * Fetch call to PSPx
     *
     * @param $context
     *
     * @return mixed|null
     *
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\RuntimeException
     * @throws \RZP\Exception\ServerErrorException
     */
    public function fetch($context, array $input)
    {
        $payload = $this->getPayloadArray($context, $input);

        $response = $this->sendRequest('POST', Routes::FETCH_MANDATE, $payload);

        return $response;
    }

    /**
     * @param $context
     * Fetchall call to PSPx
     * @return mixed|null
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\RuntimeException
     * @throws \RZP\Exception\ServerErrorException
     */
    public function fetchAll($context)
    {
        $payload = $this->getPayloadArray($context, Array());

        $response = $this->sendRequest('POST', Routes::FETCH_ALL_MANDATE, $payload);

        return $response;
    }

    /**
     * Update call to PSPx
     *
     * @param $context
     * @param array $input
     *
     * @return mixed|null
     *
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\RuntimeException
     * @throws \RZP\Exception\ServerErrorException
     */
    public function update($context, array $input)
    {
        $payload = $this->getPayloadArray($context, $input);

        $response = $this->sendRequest('POST', Routes::UPDATE_MANDATE, $payload);

        return $response;
    }

    /**
     * Delete call to PSPx
     *
     * @param $context
     * @param array $input
     *
     * @return mixed|null
     *
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\RuntimeException
     * @throws \RZP\Exception\ServerErrorException
     */
    public function delete($context, array $input)
    {
        $payload = $this->getPayloadArray($context, $input);

        $response = $this->sendRequest('POST', Routes::DELETE_MANDATE, $payload);

        return $response;
    }


    /**
     * Fetch call by umn to PSPx
     *
     * @param $context
     *
     * @return mixed|null
     *
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\RuntimeException
     * @throws \RZP\Exception\ServerErrorException
     */
    public function fetchByUMN($context, array $input)
    {
        $payload = $this->getPayloadArray($context, $input);

        $response = $this->sendRequest('POST', Routes::FETCH_MANDATE_BY_UMN, $payload);

        return $response;
    }

    /**
     * Get payload from contexr and input
     *
     * @param Context $context
     * @param array $input
     *
     * @return array
     */
    private function getPayloadArray(Context $context, array $input): array
    {
        return array_merge($input, [
            'context' => [
                'type'              => $context->getContextType(),
                'client'            => $context->getClient()->toArray(),
                'customer_id'       => $context->getDevice()->getCustomerId(),
                Context::MODE       => $context->getMode(),
                Context::DEVICE     => $context->getDevice()->toArray(),
            ],
        ]);
    }
}
