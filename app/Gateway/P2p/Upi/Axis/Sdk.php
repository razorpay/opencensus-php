<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use Illuminate\Support\Collection;

use RZP\Constants\Mode;
use RZP\Gateway\P2p\Base;
use RZP\Base\JitValidator;
use RZP\Gateway\P2p\Upi\Axis\Gateway;
use RZP\Gateway\P2p\Upi\Axis\Actions\Action;
use RZP\Gateway\P2p\Upi\Axis\Actions\DeviceAction;

/*
 * This class is responsible for running validators, generating signature
 * and other request properties for communicating with Axis Sdk.
 */
class Sdk extends Base\Request
{
    const AXIS = 'axis';

    protected $action;

    protected $actionMap;

    protected $content;

    protected $sdkPrivateKey;

    protected $udf;

    public function setActionMap(string $action, $map)
    {
        $this->action = $action;

        $this->actionMap = $map;

        $this->udf = [];
    }

    public function finish()
    {
        if (empty($this->actionMap[Action::VALIDATOR]) === false)
        {
            $this->validateInput($this->actionMap[Action::VALIDATOR]);
        }

        $this->content->put(Fields::UDF_PARAMETERS, json_encode($this->udf));

        if (empty($this->actionMap[Action::SIGNATURE]) === false)
        {
           $str = $this->getSignatureString($this->actionMap[Action::SIGNATURE]);

           $sign = Gateway::generateSignature($str, $this->sdkPrivateKey);

           $this->content->put(Fields::MERCHANT_SIGNATURE, $sign);
        }

        $this->setRequestCommonProperties();

        return parent::finish();
    }

    public function setCallback(array $attributes = [])
    {
        $attributes[Fields::ACTION] = $this->action;

        parent::setCallback($attributes);
    }

    public function merge(array $attributes)
    {
        if ($this->content === null)
        {
            $this->content = new Collection($attributes);
        }

        $this->content = $this->content->merge($attributes);

        return $this;
    }

    public function setSdkPrivateKey($key)
    {
        $this->sdkPrivateKey = $key;
    }

    public function setValidate($deviceFingerPrint)
    {
        $validate = [
            self::ACTION  => DeviceAction::IS_DEVICE_FINGERPRINT_VALID,
            self::CONTENT => [
                Fields::DEVICE_FINGERPRINT => $deviceFingerPrint,
            ],
            self::ID      => str_random(14)
        ];

        parent::setValidate($validate);
    }

    protected function setRequestCommonProperties()
    {
        $this->setSdk(self::AXIS);

        $this->setAction($this->action);

        $this->setContent($this->content->toArray());
    }

    protected function validateInput($rules)
    {
        $input = $this->content->toArray();

        (new JitValidator)->rules($rules)->input($input)->validate();
    }

    protected function getSignatureString($actionMap)
    {
        $str = '';

        foreach ($actionMap as $key)
        {
            $str = $str . $this->content[$key];
        }

        return $str;
    }
}
