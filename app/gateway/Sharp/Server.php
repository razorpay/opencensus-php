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
        $this->trace = \Trace::getFacadeRoot();
    }

    public function action($input)
    {
        if (isset($input['action']) === false)
        {
            $this->trace->error(
                TraceCode::MISC_TRACE_CODE,
                [
                    'message' => 'Sharp gateway, action field not set',
                    'input' => $input
                ]);

            $input['success'] = 'F';

            return $this->authSubmit($input);
        }

        $action = $input['action'];

        return $this->$action($input);
    }

    protected function enroll($input)
    {
        $req = ($this->requireTwoStep($input));

        return ($req) ? 'Y' : 'N';
    }

    protected function authorize($input)
    {
        if ($this->requireTwoStep($input) === false)
        {
            $input['success'] = 'S';

            return $this->authSubmit($input);
        }

        $data['action'] = 'authorize';
        $data['url'] = \Http\Route::getUrlWithPublicAuth('mock_sharp_payment_submit');
        $data['content'] = array(
            'callback_url' => $input['callback_url'],
        );

        return [$data, null];
    }

    public function authSubmit($input)
    {
        if (isset($input['callback_url']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Input fields not set properly');
        }

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

    public function requireTwoStep($input)
    {
        if (isset($input['card_number']))
        {
            $number = $input['card_number'];

            if ($number === '555555555555558')
            {
                return false;
            }
        }

        return true;
    }
}
