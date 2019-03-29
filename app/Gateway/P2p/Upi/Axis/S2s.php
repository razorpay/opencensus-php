<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Models\P2p\Base\Libraries\ArrayBag;

class S2s
{
    const AXIS = 'axis';

    const METHOD = 'method';

    protected $action;

    protected $actionMap;

    protected $content;

    protected $signer;

    protected $udf;

    protected $config;

    protected $request;

    public function source()
    {
        return $this->actionMap[Actions\Action::SOURCE];
    }

    public function setActionMap(string $action, $map)
    {
        $this->action = $action;

        $this->actionMap = $map;

        $this->udf = [];
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function setSigner($signer)
    {
        $this->signer = $signer;
    }

    public function merge(array $attributes)
    {
        if ($this->content === null)
        {
            $this->content = new ArrayBag($attributes);
        }

        $this->content = $this->content->merge($attributes);

        return $this;
    }

    public function finish()
    {
        return $this->request;
    }
}
