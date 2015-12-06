<?php

namespace Models\Merchant\Webhook;

use Requests;

class Fire
{
    public function fire($event)
    {
        $merchant = $event->merchant;

        $repo = new Repository;
        $webhook = $repo->findByMerchant($merchant);

        $request = array(
            'url' => $webhook->getUrl(),
            'method' => 'post',
            'content' => $payload->toJsonPublic());

        $request['header'] = [];
        $request['options'] = [];

        $response = $this->makeRequest($request);

        if (substr($response->status_code, 0, 1) !== '2')
        {
            $repo->incrementFailureCount($webhook);
        }
    }

    protected function makeRequest($request)
    {
        $response = Requests::$method(
                    $request['url'],
                    $request['header'],
                    $request['content'],
                    $request['options']);

        return $response;
    }
}
