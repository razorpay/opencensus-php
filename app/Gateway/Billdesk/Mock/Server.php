<?php

namespace RZP\Gateway\Billdesk\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Http\Request\Requests;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Billdesk;
use RZP\Gateway\Billdesk\AuthStatus;
use RZP\Models\Card;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $request = array(
            'url' => $this->route->getUrl('mock_billdesk_payment'),
            'content' => $input,
            'method' => 'post',
        );

        $this->request($request);

        return $this->makePostResponse($request);
    }

    public function bank(array $input)
    {
        parent::authorize($input);

        // Create request array here
        $input = $this->getContentFromInput($input);

        // in case of terminals procured by merchant, we sent payment id in AdditionalInfo3.
        // AdditionalInfo1 and customerID will have details specific to merchant
        // In normal flow we send payment id in both AdditionalInfo1 and CustomerID
        if ($input['AdditionalInfo1'] !== $input['CustomerID'])
        {
            $paymentId = $input['AdditionalInfo3'];
        }
        else
        {
            $paymentId = $input['CustomerID'];
        }

        $gatewayPayment = $this->getRepo()->findByPaymentIdAndAction(
            $paymentId, Action::AUTHORIZE);

        $payment = $this->repo->payment->findOrFailPublic($gatewayPayment->getPaymentId());

        $accountNo = $input['AccountNumber'];

        $requestTpv = true;

        if ($accountNo === 'NA')
        {
            $requestTpv = false;
        }

        $this->validateAuthorizeInput($input);

        // Format - YYYYMMDD
        $date = Carbon::today(Timezone::IST)->format('d-m-Y H:i:s');

        $content = array(
            'MerchantID'        => $input['MerchantID'],
            'CustomerID'        => $input['CustomerID'],
            'TxnReferenceNo'    => random_alpha_string(10),
            'BankReferenceNo'   => strtoupper(random_alphanum_string(10)),
            'TxnAmount'         => $input['TxnAmount'],
            'BankID'            => $input['BankID'],
            'BankMerchantID'    => $input['BankID'],
            'TxnType'           => 'INR',
            'CurencyName'       => 'INR',
            'ItemCode'          => 'DIRECT',
            'SecurityType'      => 'NA',
            'SecurityID'        => 'NA',
            'SecurityPassword'  => 'NA',
            'TxnDate'           => $date,
            'AuthStatus'        => AuthStatus::SUCCESS,
            'SettlementType'    => 'NA',
            'AdditionalInfo1'   => $input['CustomerID'],
            'AdditionalInfo2'   => 'NA',
            'AdditionalInfo3'   => 'NA',
            'AdditionalInfo4'   => 'NA',
            'AdditionalInfo5'   => 'NA',
            'AdditionalInfo6'   => 'NA',
            'AdditionalInfo7'   => 'NA',
            'ErrorStatus'       => 'NA',
            'ErrorDescription'  => 'NA',
        );

        if ($gatewayPayment->getBankId() === 'ICO')
        {
            $content['AuthStatus'] = AuthStatus::PENDING;
        }

        // procurer terminal case
        if ($input['AdditionalInfo1'] !== $input['CustomerID'])
        {
            $content['AdditionalInfo1'] = 'NA';
            $content['AdditionalInfo3'] = $input['AdditionalInfo3'];
        }

        $this->content($content, 'bank_preprocess');

        $msg = $this->getGatewayInstance()
                    // ->setInput($gatewayInput)
                    ->getMessageStringWithHash($content);

        $gatewayTpv = $this->getGatewayInstance()->isPaymentTpvEnabled($gatewayPayment, $payment);

        assertTrue($gatewayTpv === $requestTpv);

        // // Uncomment below to mock s2s callback
        // $headers = array(
        //                     'User-Agent'    => 'Razorpay-Webhook/v1',
        //             );
        // $url = $this-route->getUrlWithPublicAuth('gateway_payment_callback_post',
        //                                         ['gateway' => 'billdesk']);

        // Requests::post(
        //     $url,
        //     $headers,
        //     ['msg' => $msg]);

        $content = ['msg' => $msg];

        $this->content($content, 'bank');

        $request = array(
            'url' => $input['RU'],
            'content' => $content,
            'method' => 'post',
        );

        return $request;
    }

    public function verify($input)
    {
        parent::verify($input);

        $input = $this->getContentFromInput($input);

        // @todo: Fix below for validation.
        unset($input['Current Date/ Timestamp']);
        $this->validateActionInput($input, 'verify');

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
                        $input['Customer ID'], Action::AUTHORIZE);

        $fields = $this->getGatewayInstance()->getFieldsForAction('verify');

        $content = array_combine($fields, array_fill(0, count($fields), 'NA'));

        $payment = $payment->toArray();

        foreach ($content as $key => $value)
        {
            if (isset($payment[$key]) === true)
                $content[$key] = $payment[$key];
        }

        $content['AuthStatus'] = AuthStatus::SUCCESS;

        $refunds = $this->getRepo()->findRefunds($input['Customer ID']);

        $refundAmount = 0.00;

//        $content['AuthStatus'] = '0200';

        foreach ($refunds as $refund)
        {
            $refundAmount += (double) $refund['RefAmount'];

            $content['TotalRefundAmount'] = $refundAmount;
            $content['LastRefundDate']    = $refund['RefDateTime'];
            $content['LastRefundRefNo']   = $refund['RefundId'];
            $content['RefundStatus']      = $refund['RefStatus]'];
        }

        $content['QueryStatus'] = 'Y';

        unset($content['Checksum']);
        $msg = $this->getGatewayInstance()->getMessageStringWithHash($content);

        return $this->makeResponse($msg);
    }

    public function refund($input)
    {
        parent::refund($input);

        $input = $this->getContentFromInput($input);

        $this->validateActionInput($input, 'refund');

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
                        $input['CustomerID'], Action::AUTHORIZE);

        // Format yyyymmdd24hhmmss (in docs), actually yyyymmdd0hhmmss,
        // hh is in 24 hrs
        $now = Carbon::now(Timezone::IST)->format('Ymd0His');

        $content = array(
            'RequestType'   => '0410',
            'MerchantID'    => $payment['MerchantID'],
            'TxnReferenceNo' => $payment['TxnReferenceNo'],
            'TxnDate'       => $payment['TxnDate'],
            'CustomerID'    => $payment['CustomerID'],
            'TxnAmount'     => $payment['TxnAmount'],
            'RefAmount'     => $input['RefAmount'],
            'RefDateTime'   => $now,
            'RefStatus'     => '0799',
            'RefundId'      => random_alpha_string(15),
            'ErrorCode'     => 'NA',
            'ErrorReason'   => 'NA',
            'ProcessStatus' => 'Y',
        );

        $msg = $this->getGatewayInstance()->getMessageStringWithHash($content);

        return $this->makeResponse($msg);
    }

    protected function getContentFromInput($input)
    {
        $name = $this->action;

        $fields = $this->getGatewayInstance()->getFields($name, 'request');
        $content = explode('|', $input['msg']);
        $input = array_combine($fields, $content);

        $this->input = $input;

        return $input;
    }

    protected function makeRequest($request)
    {
        $method = $request['method'];

        $response = Requests::$method(
            $request['url'],
            $request['headers'],
            $request['content']);

        return $response;
    }
}
