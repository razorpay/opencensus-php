<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use Illuminate\Support\Collection;

use RZP\Constants\Mode;
use RZP\Gateway\P2p\Base;
use RZP\Base\JitValidator;
use RZP\Gateway\P2p\Upi\Axis\Gateway;
use RZP\Gateway\P2p\Upi\Axis\GatewayTrait;
use RZP\Gateway\P2p\Upi\Axis\Actions\Action;
use RZP\Gateway\P2p\Upi\Axis\Actions\DeviceAction;

class Request extends Base\Request
{
    use GatewayTrait;

    const AXIS = 'axis';

    protected $action;

    protected $actionMap;

    protected $content;

    protected $gatewayConfig;

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
           $sign = $this->sign($this->actionMap[Action::SIGNATURE]);

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

    public function setGatewayConfig($config)
    {
        $this->gatewayConfig = $config;
    }

    protected function setRequestCommonProperties()
    {
        $this->setSdk(self::AXIS);

        $this->setAction($this->action);

        $this->setContent($this->content->toArray());

        //$this->setValidate($this->actionMap[Action::SDK_VALIDATE]);
    }

    public function setValidate($deviceFingerPrint)
    {
        $validate = [
            self::ACTION      => DeviceAction::IS_DEVICE_FINGERPRINT_VALID,
            self::CONTENT     => [
                Fields::DEVICE_FINGERPRINT => $deviceFingerPrint,
            ],
            self::ID          => str_random()
        ];

        parent::setValidate($validate);
    }

    protected function validateInput($rules)
    {
        $input = $this->content->toArray();

        (new JitValidator)->rules($rules)->input($input)->validate();
    }

    protected function sign($actionMap)
    {
        $str = '';

        foreach ($actionMap as $key)
        {
            $str = $str . $this->content[$key];
        }

        $key = $this->getPrivateKey();

        $signature = $this->generateSignature($str, $key);

        return $signature;
    }

    protected function getPrivateKey()
    {
        return $this->gatewayConfig['private_key'];
    }
}
