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
    public function fetch($context)
    {
        $response = $this->sendRequest('POST', Routes::FETCH_MANDATE);

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
        $response = $this->sendRequest('POST', Routes::UPDATE_MANDATE, $input);

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
        $response = $this->sendRequest('POST', Routes::DELETE_MANDATE, $input);

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
        return [
            Entity::MANDATE => $input,
            'context' => [
                'type'              => $context->getContextType(),
                'client'            => $context->getClient()->toArray(),
                'customer_id'       => $context->getDevice()->getCustomerId(),
                Context::MODE       => $context->getMode(),
                Context::DEVICE     => $context->getDevice()->toArray(),
            ],
        ];
    }
}
