<?php

namespace RZP\Models\Device;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Customer;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function create(array $input)
    {
        $device = $this->core->create($input);

        return $device->toArrayPublic();
    }

    public function verify(array $input)
    {
        list($verificationToken, $contact) = $this->getRelevantFieldsForVerify($input);

        $device = $this->repo->device->findByVerificationTokenAndMerchant($verificationToken, $this->merchant);
        $customer = (new Customer\Core)->createGlobalCustomer([Customer\Entity::CONTACT => $contact], false);

        $device = $this->core->verify($device, $customer);

        $response = $this->core->sendGetTokenRequestToGateway($device, $customer);

        return ['device' => $device->toArrayPublic(), 'getToken' => $response];
    }

    public function updateUpiToken(string $deviceId, string $upiToken)
    {
        $device = $this->repo->device->findOrFailPublic($deviceId);

        $device = $this->core->updateUpiToken($device, $upiToken);

        return $device->toArrayPublic();
    }

    protected function getRelevantFieldsForVerify(array $input)
    {
        // Msg91 converts the keyword to lowercase
        $keyword = trim(strtoupper($input['keyword']));

        if ($keyword === 'VERIFY')
        {
            if ((isset($input['message']) === false) or
                (isset($input['number']) === false))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_MISSING_FIELDS_MESSAGE,
                    null,
                    $input
                );
            }

            $verificationToken = $input['message'];
            $contact = $input['number'];

            return [$verificationToken, $contact];
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_MESSAGE_KEYWORD,
                null,
                $input
            );
        }
    }

    public function verifyAndGetToken(array $input)
    {
        $response = $this->core->verifyAndGetToken($input);

        return $response;
    }
}
