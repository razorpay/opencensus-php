<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Models\P2p\Base\Libraries\ArrayBag;

class S2sDirect extends S2s
{
    const X_MERCHANT_ID                     = 'X-Merchant-Id';

    const X_MERCHANT_CHANNEL_ID             = 'X-Merchant-Channel-Id';

    const X_TIMESTAMP                       = 'X-Timestamp';

    const CONTENT_TYPE                      = 'Content-Type';

    const X_MERCHANT_SIGNATURE              = 'X-Merchant-Signature';

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
        $this->request['method'] = $this->actionMap['direct']['method'];

        $this->request['content'] = $this->content->toJson();

        $this->request['headers'] = $this->getHeaders();

        return parent::finish();
    }

    public function response($response)
    {
        return new ArrayBag(json_decode($response->body, true));
    }

    protected function getHeaders()
    {
        $accessor = $this->accessor;

        $headers = [
            self::X_MERCHANT_ID          => $accessor('getMerchantId'),
            self::X_MERCHANT_CHANNEL_ID  => $accessor('getMerchantChannelId'),
            self::X_TIMESTAMP            => $accessor('getTimeStamp'),
        ];

        $toSign = implode('', $headers) . $this->content->toJson();

        $signer = $accessor('getMerchantSigner');

        $signature = bin2hex($signer->sign($toSign));

        $headers[self::CONTENT_TYPE] = 'application/json';

        $headers[self::X_MERCHANT_SIGNATURE] = $signature;

        return $headers;
    }

}
