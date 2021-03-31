<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\Axis\Actions\Action;

class S2sDirect extends S2s
{
    const X_MERCHANT_ID                     = 'X-Merchant-Id';

    const X_MERCHANT_CHANNEL_ID             = 'X-Merchant-Channel-Id';

    const X_TIMESTAMP                       = 'X-Timestamp';

    const CONTENT_TYPE                      = 'Content-Type';

    const X_MERCHANT_SIGNATURE              = 'X-Merchant-Signature';

    const SKIP_STATUS_CHECK                 = 'skip_status_check';

    const SKIP_AUTH_HEADERS                 = 'skip_auth_headers';

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

    public function skipStatusCheck()
    {
        return $this->actionMap[Action::DIRECT][self::SKIP_STATUS_CHECK] ?? false;
    }

    public function skipAuthHeaders(array $map)
    {
        return $map[Action::DIRECT][self::SKIP_AUTH_HEADERS] ?? false;
    }

    public function finish()
    {
        $this->request['method'] = $this->actionMap['direct']['method'];

        $this->content->put(Fields::UDF_PARAMETERS, json_encode($this->udf));

        $this->request['content'] = $this->request['method'] === 'get' ? $this->content :
                                                                         $this->content->toJson();

        if($this->skipAuthHeaders($this->actionMap) === false)
        {
            $this->setHeaders([
                S2sDirect::X_MERCHANT_ID            => call_user_func($this->accessor, 'getMerchantId'),
                S2sDirect::X_MERCHANT_CHANNEL_ID    => call_user_func($this->accessor, 'getMerchantChannelId'),
                S2sDirect::X_TIMESTAMP              => call_user_func($this->accessor, 'getTimeStamp'),
            ]);

            $this->request['headers'] = $this->getHeaders();
        }

        return parent::finish();
    }

    public function response($response)
    {
        return new ArrayBag(json_decode($response->body, true));
    }

    protected function getHeaders()
    {
        $toSign = implode('', $this->headers) . $this->content->toJson();

        $signature = bin2hex($this->signer->sign($toSign));

        $this->headers[self::CONTENT_TYPE] = 'application/json';

        $this->headers[self::X_MERCHANT_SIGNATURE] = $signature;

        return $this->headers;
    }

}
