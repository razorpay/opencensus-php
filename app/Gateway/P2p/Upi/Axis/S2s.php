<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\Axis\Actions\Action;

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

    protected $headers;

    public function source()
    {
        return $this->actionMap[Actions\Action::SOURCE];
    }

    public function setActionMap(string $action, array $map, string $id)
    {
        $this->action = $action;

        $this->actionMap = $map;

        $this->udf = [
            Fields::RID => $id,
        ];
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function setSigner($signer)
    {
        $this->signer = $signer;
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;
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

    public function mergeUdf(array $udf)
    {
        $this->udf = array_merge($this->udf, $udf);
    }

    public function finish()
    {
        return $this->request;
    }
}
