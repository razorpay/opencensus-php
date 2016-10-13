<?php

namespace RZP\Gateway\Sharp;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Trace\TraceCode;
use Crypt;

class Server extends Base\Mock\Server
{
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
        // If card number is passed via parameter, expect the encrypt parameter.
        // Set by our gateway.
        if (isset($input['encrypt']) === true)
        {
            $input['card_number'] = Crypt::decrypt($input['card_number']);
            unset($input['encrypt']);
        }

        if ($this->requireTwoStep($input) === false)
        {
            $input['success'] = 'S';

            return $this->authSubmit($input);
        }

        if (isset($input['callback_url']) === false)
        {
            $this->trace->warning(
                TraceCode::MISC_TRACE_CODE,
                [
                    'message' => 'callback_url not set for sharp authorize request',
                    'input' => $input,
                ]);

            throw new Exception\BadRequestValidationFailureException(
                'Input fields not set properly');
        }

        $data['action'] = 'authorize';
        $data['url'] = $this->route->getUrlWithPublicAuth('mock_sharp_payment_submit');
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

        unset($content['card_number']);

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
