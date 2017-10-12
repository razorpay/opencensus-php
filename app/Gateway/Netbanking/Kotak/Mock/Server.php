<?php

namespace RZP\Gateway\Netbanking\Kotak\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Paytm;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        //fot test only
        $content = explode('|',$input['msg']);

        unset($content[7]);

        $input['msg'] = implode('|',$content);
        $input = $this->getContentFromInput($input, 'authorize');

        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = array(
            'MessageCode'         => $input['MessageCode'],
            'DateTimeInGMT'       => $input['DateTimeInGMT'],
            'MerchantId'          => $input['MerchantId'],
            'TraceNumber'         => $input['TraceNumber'],
            'Amount'              => $input['Amount'],
            'AuthorizationStatus' => 'Y',
            'BankReference'       => random_integer(6),
        );

        $this->content($content);

        $msg = $this->getGatewayInstance()->getMessageStringWithHash($content);

        $callbackUrl = $this->route->getUrl('gateway_payment_callback_kotak');

        $request = array(
            'url' => $callbackUrl,
            'content' => ['msg' => $msg],
            'method' => 'get',
        );

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        parent::verify($input);

        $input = $this->getContentFromInput($input);

//        $this->validateActionInput($input,'verify');
        $id = $input['TraceNumber'];

        $payment = (new Netbanking\Base\Repository)->findByTraceIdAndAction(
            $id, Base\Action::AUTHORIZE);

        $content = array(
            'MessageCode'         => $input['MessageCode'],
            'DateTimeInGMT'       => $input['DateTimeInGMT'],
            'MerchantId'          => $input['MerchantId'],
            'TraceNumber'         => $input['TraceNumber'],
            'Amount'              => $payment['Amount'],
            'AuthorizationStatus' => 'Y',
            'BankReference'       => random_integer(6),
        );

        $content = ['msg' => $this->getGatewayInstance()->getMessageStringWithHash($content)];

        return $this->makeResponse($content);
    }

    protected function getContentFromInput($input, $action = null)
    {
        if ($action === null)
        {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $action = $trace[1]['function'];
        }

        $fields = $this->getGatewayInstance()->getFields($action, 'request');

        $content = explode('|', $input['msg']);
        $input = array_combine($fields, $content);

        return $input;
    }
}
