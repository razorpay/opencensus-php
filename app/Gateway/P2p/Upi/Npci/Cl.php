<?php

namespace RZP\Gateway\P2p\Upi\Npci;

use Carbon\Carbon;
use RZP\Gateway\P2p\Base\Request;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * Base version of NPCI Library which is V_1_5 (1.5)
 * Class Cl
 * @package RZP\Gateway\P2p\Upi\Npci
 */
class Cl
{
    /** @var ArrayBag */
    protected $handle;

    /** @var ArrayBag */
    protected $data;


    public function __construct(ArrayBag $handle, array $input)
    {
        $this->handle   = $handle;
        $this->data     = new ArrayBag();

        $this->setData($input);
    }

    public function setData(array $input)
    {
        $allowed = array_only($input, array_keys(ClInput::$allowed));

        // We can run validation on allowed

        $this->data->putMany($allowed);
    }

    /**
     * Whether the CL was ever registered for the device
     * @return bool
     */
    public function shouldRegisterToken(): bool
    {
        // When any of token or expiry is empty
        return (empty($this->data->get(ClInput::CL_TOKEN)) or
                empty($this->data->get(ClInput::CL_EXPIRY)));
    }

    /**
     * Whether the CL token is expiring for the device
     * @return bool
     */
    public function shouldRotateToken(): bool
    {
        $expiry = (int) $this->data->get(ClInput::CL_EXPIRY);

        // if going to expire in next 60 seconds
        return (Carbon::now()->addSeconds(60)->getTimestamp() >= $expiry);
    }

    /**
     * Whether the CL token should be registered or rotated
     * @return bool
     */
    public function shouldRegisterApp(): bool
    {
        return ($this->shouldRegisterToken() or $this->shouldRotateToken());
    }

    /**
     * Device registration request
     * @return Request
     */
    public function registerRequest(): Request
    {
        $request = $this->request(ClAction::GET_CHALLENGE);

        $request->setContent([
            ClOutput::VECTOR => [
                ClOutput::INITIAL,
                $this->data->get(ClInput::DEVICE_ID),
            ],
            ClOutput::COUNT => 2,
        ]);

        $request->setCallback([
            ClOutput::TYPE    => ClOutput::INITIAL,
        ]);

        return $request;
    }

    /**
     * Device rotation request
     * @return Request
     */
    public function rotateRequest(): Request
    {
        $request = $this->request(ClAction::GET_CHALLENGE);

        $request->setContent([
            ClOutput::VECTOR => [
                ClOutput::ROTATE,
                $this->data->get(ClInput::DEVICE_ID),
            ],
            ClOutput::COUNT => 2,
        ]);

        $request->setCallback([
            ClOutput::TYPE    => ClOutput::ROTATE,
        ]);

        return $request;
    }

    /**
     * App registration request
     * @return Request
     */
    public function registerAppRequest(): Request
    {
        $request = $this->request(ClAction::REGISTER_APP);

        $request->setContent([
            ClOutput::VECTOR => [
                $this->data->get(ClInput::APP_ID),
                $this->data->get(ClInput::MOBILE),
                $this->data->get(ClInput::DEVICE_ID),
                $this->generateHmac(),
            ],
            ClOutput::COUNT => 4,
        ]);

        $request->setCallback([
            ClOutput::TOKEN    => $this->data->get(ClInput::CL_TOKEN),
            ClOutput::EXPIRY   => Carbon::now()->addDays(45)->getTimestamp(),
        ]);

        return $request;
    }

    private function request(string $action): Request
    {
        $request = new Request();

        $request->setSdk(ClOutput::NPCI);
        $request->setAction($action);

        return $request;
    }

    private function generateHmac()
    {
        $token = $this->data->get(ClInput::CL_TOKEN);

        $string = $this->data->get(ClInput::APP_ID) . '|' .
                  $this->data->get(ClInput::MOBILE) . '|' .
                  $this->data->get(ClInput::DEVICE_ID);

        $hash = hash('sha256', $string);

        $encrypted = (new ClCrypto($token))->encryptAes256($hash);

        return $encrypted;
    }
}
