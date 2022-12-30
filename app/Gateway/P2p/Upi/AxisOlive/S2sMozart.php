<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive;

use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\AxisOlive\Actions\Action;

class S2sMozart extends S2s
{
    const CONTENT_TYPE                      = 'Content-Type';

    const X_Task_ID                         =  'X-Task-ID';

    const RESOURCE                          = 'resource';

    /**
     * @var callable
     */
    protected $accessor;

    protected $request;

    public function __construct(callable $accessor, string $url)
    {
        $this->accessor = $accessor;

        $this->request = [
            'url'       => $url
        ];
    }

    public function finish()
    {
        $this->request['method'] = 'POST';

        $this->request['content'] = $this->request['method'] === 'get' ? $this->content :
            $this->content->toJson();

        $this->request['Content-Type'] = 'application/json';

        $this->setHeaders([
                              S2sMozart::CONTENT_TYPE     => 'application/json',
                          ]);

        return parent::finish();
    }

    public function response($response)
    {
        return new ArrayBag(json_decode($response->body, true));
    }
}
