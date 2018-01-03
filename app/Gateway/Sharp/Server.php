<?php

namespace RZP\Gateway\Sharp;

use Crypt;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Customer\Token;
use RZP\Gateway\Base;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base\Vpa;
use RZP\Models\Payment;

class Server extends Base\Mock\Server
{
    protected $validActions = [
        'enroll',
        'authorize',
    ];

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

        if (in_array($input['action'], $this->validActions, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                null,
                ['action' => $input['action']]
            );
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

        if ($input['method'] === 'netbanking')
        {
            if ((isset($input['recurring']) === true) and
                (boolval($input['recurring']) === true))
            {
                $data['emandate'] = true;
            }
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

        $content['status'] = 'failed';

        if ($input['success'] === 'S')
        {
            $content['status'] = 'authorized';
        }

        $content['token_recurring_status'] = Token\RecurringStatus::REJECTED;

        if ((isset($input['emandate_success']) === true) and
            ($input['emandate_success'] === 'S'))
        {
            $content['token_recurring_status'] = Token\RecurringStatus::CONFIRMED;
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

            if (($number === '555555555555558') or
                ($number === '4000184186218826'))
            {
                return false;
            }
        }

        return true;
    }

    public function s2sRequestContent(array $payment)
    {
        $response = [
            'status' => 'authorized',
        ];

        if (($payment['method'] === Payment\Method::UPI) and
            ($payment['vpa'] === Vpa::FAILURE))
        {
            $response['status'] = 'failed';
        }

        return $response;
    }
}
