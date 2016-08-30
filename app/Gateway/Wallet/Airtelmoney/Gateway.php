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

        if ($input['Status'] === Status::TRANSACTION_SUCCESS)
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
        ];

    }

    protected function getHashOfString($hashString)
    {
        return strtolower(hash(HashAlgo::SHA512, $hashString, false));
    }

    /*
     * Convert the date to DDMMYYYYhhmmss
     */
    protected function getFormattedDate($date)
    {

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

        if ($content['CODE'] !== 000 || $content['STATUS'] !== Status::TRANSACTION_SUCCESS)
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
}
