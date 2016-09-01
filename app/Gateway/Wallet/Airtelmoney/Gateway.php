<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

use Carbon\Carbon;

use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Gateway\Wallet\Base;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $canRunOTPFlow = false;

    protected $topup = false;

    protected $gateway = 'wallet_airtelmoney';

    protected $map = [
        'MID'               => 'gateway_merchant_id',
        'CUST_MOBILE'       => 'contact',
        'CUST_EMAIL'        => 'email',
        'TXN_REF_NO'        => 'payment_id',
        'AMT'               => 'amount',
        'TRAN_ID'           => 'gateway_payment_id',
        'MSG'               => 'response_description',
        'STATUS'            => 'status',
        'NEW_FDC_TXN_ID'    => 'gateway_refund_id',
        'TRAN_DATE'         => 'reference1',
        'NEW_FDC_TXN_DATE'  => 'reference2',
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        // E-Comm Transaction URL on success and
        // failure of wallet transaction
        if (isset($input['Status']) && $input['Status'] === Status::SUCCESS)
            $this->callbackDebitSuccessFlow($input);
        else
            $this->callbackDebitFailureFlow($input);
    }

    public function debit(array $input)
    {
        $this->action($input, Action::DEBIT_WALLET);

        $request = $this->getDebitRedirectRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    protected function callbackDebitSuccessFlow(array $input)
    {
        // Create a payment gateway entity and save it.
        $contentToSave = [
            'MID'         => $this->getMerchantId($input['terminal']),
            'CUST_EMAIL'  => $input['payment']['email'],
            'CUST_MOBILE' => $this->getFormattedContact($input['payment']['contact']),
            'STATUS'      => $content['status'],
            'TRAN_ID'     => $content['TRAN_ID'],
            'MSG'         => $content['MSG'],
            'TXN_REF_NO'  => $content['TXN_REF_NO'],
            'TRAN_DATE'   => $content['TRAN_DATE'],
            'received'    => true
        ];

        //  Changing action to AUTHORIZE to keep the action consistent
        $this->action = Action::AUTHORIZE;

        $this->createGatewayPaymentEntity($contentToSave);

        $this->action = Action::DEBIT_WALLET;

    }

    protected function getHashOfString($hashString)
    {
        return strtolower(hash(HashAlgo::SHA512, $hashString, false));
    }

    /*
     * Convert the date to DDMMYYYYhhmmss
     */
    protected function getFormattedDate($date = null)
    {
        if($date === null)
        {
            $date = Carbon::now();
        }

        $format = 'dmYHis';

        return Carbon::createFromFormat($date, $format);
    }

    protected function getDebitRedirectRequestArray($input)
    {
        $payment = $input['payment'];

        $content = [
            'MID'           => $this->getMerchantId($input['terminal']),
            'TXN_REF_NO'    => $payment['id'],
            'SU'            => $input['callbackUrl'],
            'FU'            => $input['callbackUrl'],
            'AMT'           => (string) $input['amount'],
            'CUR'           => 'INR',
            'DATE'          => $this->getFormattedDate($payment['created_at']),
            'CUST_MOBILE'   => $this->getFormattedContact($payment['contact']),
            'CUST_EMAIL'    => $payment['email'],
        ];

        $content['hash'] = $this->generateHashForDebitArray($content);

        return $this->getStandardRequestArray($content);
    }

    protected function generateHashForDebitArray(array $content)
    {
        $hashString = $content['MID'].'#'.$content['TXN_REF_NO'].'#';
        $hashString .= $content['AMT'].$content['DATE'].'#'.$this->getSecretKey();

        return $this->getHashOfString($hashString);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $response);

        $content = $response->body;

        if ($content['CODE'] !== ResponseCode::SUCCESS and
                $content['STATUS'] !== Status::SUCCESS)
        {
            //TODO: Raise Error
        }

        $contentToSave = [
            'payment_id'            => $input['payment']['id'],
            'action'                => $this->action,
            // Amount we are asking to refund or amount refunded by gateway
            'amount'                => $input['refund']['amount'],
            'wallet'                => $input['payment']['wallet'],
            'email'                 => $input['payment']['email'],
            'received'              => 1,
            'contact'               => $this->getFormattedContact($input['payment']['contact']),
            'gateway_merchant_id'   => $this->getMerchantId($input['terminal']),
            'refund_id'             => $input['refund']['id'],
            'response_code'         => '',
            'response_description'  => $content['MSG'],
            'status_code'           => $content['STATUS'],
            'error_message'         => '',
            'gateway_refund_id'     => $content['NEW_FDC_TXN_ID'],
            'reference2'            => $content['NEW_FDX_TXN_DATE'],
        ];

        $this->createGatewayRefundEntity($contentToSave);
    }

    protected function getRefundRequestArray(array $input)
    {
        $wallet = $this->getRepo()->fetchWalletByPaymentId($input['payment']['id']);

        $content = [
            'MID'           => $this->getMerchantId($input['terminal']),
            'TXN_ID'        => $wallet['gateway_payment_id'],
            'AMT'           => (string) $input['amount'],
            'DATE'          => $this->getFormattedDate($wallet['reference1']),
            'REMARKS'       => 'Razorpay Refund',
        ];

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
        );

        return $request;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content' => $content,
                'gateway' => 'airtelmoney',
                'payment_id' => $input['payment']['id'],
            ]);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getVerifyRequestArray($input)
    {
        $wallet = $this->getRepo()->fetchWalletByPaymentId($input['payment']['id']);
        $content = [
            'MID'        => $this->getMerchantId($input['terminal']),
            'TXN_REF_NO' => $wallet['gateway_payment_id'],
            'DATE'       => $this->getFormattedDate($wallet['reference2']),
        ];
        return $this->getStandardRequestArray($content);
    }


    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;
        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;
        // In Airtel, There is no explicit field which says transaction is 
        // successful.
        // Compare the amount and 

        if ($content['STATUS'] !== Status::SUCCESS)
        {
            $verify->gatewaySuccess = false;

            if (($payment === null) or
                (($input['payment']['status'] === 'failed') or
                    ($input['payment']['status'] === 'created')))
            {
                $verify->apiSuccess = false;
            }
            else if (($payment['received'] === false) and
                        (($payment['status_code'] === null) or
                        ($payment['status_code'] !== (string) Status::SUCCESS)))
            {
                $verify->apiSuccess = false;
            }
            else if ($payment['status_code'] === (string) Status::SUCCESS)
            {
                $verify->status = VerifyResult::STATUS_MISMATCH;
                $verify->apiSuccess = true;
            }
        }
        else if ($content['STATUS'] === Status::SUCCESS)
        {
            $verify->gatewaySuccess = true;

            if (($input['payment']['status'] !== 'created') and
                ($input['payment']['status'] !== 'failed'))
            {
                $verify->apiSuccess = true;
            }
            else
            {
                $verify->status = VerifyResult::STATUS_MISMATCH;
                $verify->apiSuccess = false;
            }
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->payment = $this->saveVerifyContentIfNeeded($payment, $content);

        return $verify->status;
    }

    protected function saveVerifyContentIfNeeded($payment, $content)
    {
        $this->action = Action::AUTHORIZE;

        if (isset($content['STATUS']) and $content['STATUS'] === Status::VERIFY_SUCCESS)
        {
            $walletAttributes = $this->getWalletContentFromVerify($payment, $content);

            if ($payment === null)
            {
                $payment = $this->createGatewayPaymentEntity($walletAttributes);
            }
            else if ($payment['received'] === false)
            {
                $payment->fill($walletAttributes);
                $payment->saveOrFail();
            }
        }

        $this->action = Action::VERIFY;

        return $payment;
    }

    protected function getWalletContentFromVerify($payment, array $content)
    {
        $contentToSave = [
            'MID'         => $this->getMerchantId($this->input['terminal']),
            'CUST_EMAIL'  => $this->input['payment']['email'],
            'CUST_MOBILE' => $this->getFormattedContact($this->input['payment']['contact']),
            'STATUS'      => Status::SUCCESS,
            'TXN_REF_NO'  => $content['paymentId'],
            'received'    => true
        ];

        if (isset($payment['amount']) === false)
        {
            $contentToSave['amount'] = $this->input['payment']['amount'];
        }

        return $contentToSave;
    }

}
