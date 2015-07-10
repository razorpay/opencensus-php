<?php

namespace Gateway\Sharp;

use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Server
{
    public function __construct()
    {
        ;
    }

    public function action($input)
    {
        $action = $input['action'];

        return $this->$action($input);
    }

    protected function authorize($input)
    {
        $data['action'] = 'authorize';
        $data['url'] = \Http\Route::getUrlWithPublicAuth('mock_sharp_payment_submit');
        $data['content'] = array(
            'callback_url' => $input['callback_url'],
        );

        return [$data, null];
    }

    public function authSubmit($input)
    {
        $url = $input['callback_url'];

        $authorized = false;

        $content['status'] = 'failed';

        if ($input['success'] === 'S')
        {
            $content['status'] = 'authorized';
        }

        $url = $url . '?' . http_build_query($content);

        return $url;
    }
}
