<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive;

use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\AxisOlive\Actions\Action;

/**
 * This is common class for all S2S Call
 *  S2S calls can be direct , via mozart
 * Class S2s
 *
 * @package RZP\Gateway\P2p\Upi\AxisOlive
 */
class S2s
{
    const METHOD = 'method';

    protected $action;

    protected $actionMap;

    protected $content;

    protected $signer;

    protected $request;

    protected $headers;

    protected $options;

    public function source()
    {
        return $this->actionMap[Actions\Action::SOURCE];
    }

    public function setActionMap(string $action, array $map, string $id)
    {
        $this->action = $action;

        $this->actionMap = $map;
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    public function setOptions($options)
    {
        $this->$options = $options;
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
