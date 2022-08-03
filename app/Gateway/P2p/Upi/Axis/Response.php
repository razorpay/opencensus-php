<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\Axis\Actions\Action;
use RZP\Exception\P2p\GatewayErrorException;

class Response
{
    const AXIS = 'axis';

    protected $action;

    protected $actionMap;

    /**
     * @var ArrayBag
     */
    protected $content;

    protected $verifier;

    public function setActionMap(string $action, array $map)
    {
        $this->action = $action;

        $this->actionMap = $map;
    }

    public function setVerifier($verifier)
    {
        $this->verifier = $verifier;
    }

    public function setContent(ArrayBag $content)
    {
        $this->content = $content;
    }

    public function finish(bool $isValidateSignature)
    {
        if (isset($this->actionMap[Action::VALIDATOR]))
        {
            $this->validateInput($this->actionMap[Action::VALIDATOR]);
        }

        if (isset($this->actionMap[Action::SIGNATURE]) && $isValidateSignature === true)
        {
            $this->validateSignature($this->actionMap[Action::SIGNATURE]);
        }
    }

    protected function validateInput($rules)
    {
        $input = $this->content->toArray();

        (new JitValidator)->rules($rules)->input($input)->validate();
    }

    protected function validateSignature(array $fields)
    {
        $signature = $this->content->get(Fields::MERCHANT_PAYLOAD_SIGNATURE);

        $string = $this->getSignatureString($fields);

         $verified = $this->verifier->verify($string, hex2bin($signature));

         if (empty($verified))
         {
             $code = ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED;

             $data = [
                 Fields::ACTION                         => $this->action,
                 Fields::MERCHANT_PAYLOAD_SIGNATURE     => $signature,
             ];

             throw new GatewayErrorException($code, null, null, $data);
         }
    }

    protected function getSignatureString(array $fields)
    {
        $str = '';

        foreach ($fields as $key)
        {
            if (isset($this->content[$key]) === true)
            {
                $str = $str . $this->content[$key];
            }
        }

        return $str;
    }
}
