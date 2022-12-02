<?php

namespace RZP\Gateway\P2p\Base;

use Carbon\Carbon;
use RZP\Models\P2p;
use RZP\Gateway\Base;
use RZP\Error\P2p\ErrorCode;
use RZP\Exception\RuntimeException;
use RZP\Exception\P2p\BadRequestException;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Models\P2p\Base\Libraries\Context;

class Gateway extends Base\Gateway
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ArrayBag
     */
    protected $input;

    /**
     * @var string
     */
    protected $entity;

    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    public function getContextDevice()
    {
        return $this->context->getDevice()->toArrayBag();
    }

    public function getContextDeviceToken()
    {
        return $this->context->getDeviceToken()->toArrayBag();
    }

    public function getContextHandle()
    {
        return $this->context->getHandle()->toArrayBag();
    }

    public function getContextClient()
    {
        return $this->context->getClient()->toArrayBag();
    }

    public function getClientConfig()
    {
        return $this->context->getClient()->getConfig();
    }

    public function getClientGatewayData()
    {
        return $this->context->getClient()->getGatewayData();
    }

    public function getClientSecrets()
    {
        return $this->context->getClient()->getSecrets();
    }

    public function getRequestId()
    {
        return $this->context->getRequestId();
    }

    public function getHandlePrefix()
    {
        return $this->context->handlePrefix();
    }

    public function getCurrentTimestamp(): int
    {
        return Carbon::now()->getTimestamp();
    }

    public function setActionAndInput(string $action, ArrayBag $input)
    {
        $this->action = $action;

        $this->input = $input;
    }

    public function setEntity(string $entity)
    {
        $this->entity = $entity;
    }

    protected function handleGatewaySwitch(Gateway $gateway, string $entity)
    {
        $gateway->setMode($this->mode);

        $gateway->setEntity($entity);
    }

    protected function getRepository()
    {
        // There is no repository required in P2P UPI
    }

    protected function makeResponse(): Response
    {
        $mock       = $this->shouldMockResponse();
        $success    = $mock ? $this->shouldMockSuccessResponse() : true;

        return (new Response($mock, $success));
    }

    protected function shouldMockResponse(): bool
    {
        return false;
    }

    protected function shouldMockSuccessResponse(): bool
    {
        return true;
    }

    protected function response(): Response
    {
        $action = $this->action;

        $response = $this->makeResponse();

        $this->$action($response);

        // Here we can add logic to check for synchronisation

        return $response;
    }

    protected function getContextHandleCode()
    {
        return $this->context->handleCode();
    }

    /**
     * @param array $data 1D flatten array containing key-value data to be validated
     * @param array $keys attribute => index mapping of $data
     *
     * @throws BadRequestException
     * @throws RuntimeException
     */
    protected function validateFields(array $data, array $keys)
    {
        foreach ($keys as $attribute => $index)
        {
            switch ($attribute)
            {
                case P2p\Device\Entity::CONTACT:
                    $contact = $data[$index];
                    $this->validateContact($contact);
                    break;
            }
        }
    }

    /**
     * @param string $contact
     *
     * @throws BadRequestException
     * @throws RuntimeException
     */
    protected function validateContact(string $contact)
    {
        $contactRegex = '/^91[\d*]{10}$/';

        $isValid = preg_match($contactRegex, $contact);

        if ($isValid === 0)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CUSTOMER_CONTACT_REQUIRED);
        }

        // preg_match returns false if any error is occurred.
        if (($isValid === false) or
            ($isValid !== 1))
        {
            throw new RuntimeException('invalid response from preg_match');
        }
    }
}
