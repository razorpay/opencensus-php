<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Entity;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_icici';

    protected $bank = 'icici';

    protected $openssl_algorithm = 'aes-128-ecb';

    protected $map = array(
        RequestFields::AMOUNT  => 'amount'
    );

    /**
     * @param  array $input
     * @return void
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        // Create payment entity before passing it to gateway payment entity
        $entity = $this->createPaymentEntity($content);

        $payment = $this->createGatewayPaymentEntity($entity);

        $request = $this->getRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    /**
     * @param  array $input
     * @return void
     */
    public function callback(array $input)
    {
        parent::callback($input);

        // Ask ICICI about this -----> Could run into errors? Sometimes wrong ecrypted string returned.
        $content = $this->getDataFromResponse($input['gateway']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $input['gateway']);

        $payment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        // Use maps - Response Fields
        $attrs = $this->getPaymentAttributes($content);

        $payment->fill($attrs);

        $payment->saveOrFail();

        if ($attrs['status'] !== 'Y')
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['content' => $content]);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }


    /**
     * @param  array $input
     * @return void
     */
    public function verify(array $input)
    {
        // Similar to the code above.... Essentially the same thing
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        // Calling Parent class's method - need to write 2 classes on my own
        // sendPaymentVerifyRequest and verifyPayment
        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;

        $content = $this->getPaymentVerifyData($input);

        $payment_date = $this->getPaymentDate($payment);

        // Getting payment date in the specified format
        $content[ResponseFields::PAYMENT_DATE] = $payment_date;

        $request = $this->getResponseArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $request);

        $response = parent::sendGatewayRequest($request);

        sd($response->body);

        $verify->verifyResponse = $response;
        // Why is this body empty?? It definitely shouldn't be empty
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $content;

        // Not sure how the response object is going to be passed in here
        return $response;
    }

    public function verifyPayment($verify)
    {
        $verify_body = explode(' ', $verify->verifyResponseBody);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $verify_body);

        // sd($verify->verifyResponseBody);
    }

    public function getPaymentRequestData($input)
    {
        $encrypted_string = $this->getEncryptedString($input);

        $pid = $this->config['pid'];

        $data = array(
            RequestFields::MODE_OF_OPERATION  => ModeFields::AUTHORIZE,
            RequestFields::PAYEE_ID           => $pid,  // Hardcoding it for now
            RequestFields::ENCRYPTED_STRING   => $encrypted_string,
            RequestFields::AMOUNT             => $input['payment']['amount'] / 100 , // Setting it in paise. Is that correct?
        );

        return $data;
    }

    public function getPaymentVerifyData($input)
    {
        $pid = $this->config['pid'];

        $data = array(
            RequestFields::MODE_OF_OPERATION        => ModeFields::VERIFY,
            RequestFields::PAYEE_ID                 => $pid,
            RequestFields::PAYMENT_REFERENCE_NUBER  => $input['payment']['id'], // payment_id
            RequestFields::ITEM_CODE                => $input['payment']['id'],
            RequestFields::AMOUNT                   => (float) $input['payment']['amount'] / 100 ,
            RequestFields::CURRENCY_CODE            => 'INR',
        );

        return $data;
    }

    public function getEncryptedString($input)
    {
        // Adding & so that URL creation is simple
        $data = $this->getPostData($input);

        $query_string = $this->createUrl($data);

        $master_key = $this->config['master_key'];

        // returning Encrypted String
        return openssl_encrypt($query_string, $this->openssl_algorithm, $master_key, 0);
    }

    public function getPostData($input)
    {
        $callbackUrl = '%22' . $input['callbackUrl'] . '%22'; // ICICI integration docs

        $data = array(
            RequestFields::PAYMENT_REFERENCE_NUBER  => $input['payment']['id'] . '&', // payment_id
            RequestFields::ITEM_CODE                => $input['payment']['id'] . '&',
            RequestFields::AMOUNT                   => (float) $input['payment']['amount'] / 100 . '&',
            RequestFields::CURRENCY_CODE            => 'INR' . '&',
            RequestFields::RETURN_URL               => $callbackUrl . '&',
            RequestFields::ONLINE_CONFIRMATION      => Confirmation::YES,
        );

        return $data;
    }

    public function createUrl($data)
    {
        $url = '';

        foreach ($data as $key => $value)
        {
            $url .= $key . '=' . $value;
        }

        return $url;
    }

    // redundant... could use better logic to solve this
    public function getRequestArray($content)
    {
        // Amount unnecessary for ICICI, ES contains AMT, but encrypted
        unset($content[RequestFields::AMOUNT]);

        return array(
            'url' => Url::LIVE_DOMAIN,
            'method' => 'post',
            'content' => $content
        );
    }

    public function getResponseArray($content)
    {
        return array(
            'url' => Url::LIVE_DOMAIN,
            'method' => 'post',
            'content' => $content
        );
    }

    public function createPaymentEntity($content)
    {
        return array(
            RequestFields::AMOUNT => $content[RequestFields::AMOUNT]
        );
    }

    public function getPaymentDate($payment)
    {
        $timestamp = $payment['original']['created_at'];

        return date('Y-m-d', $timestamp);
    }


    public function getDataFromResponse($data)
    {
        // Decrypting message from ICICI gateway --- get on a call - This is failing sometimes
        $master_key = $this->config['master_key'];

        $decrypted_string = openssl_decrypt($data['ES'], $this->openssl_algorithm, $master_key, 0);

        parse_str($decrypted_string, $content);

        return $content;
    }

    public function getPaymentAttributes($content)
    {
        return array(
            'received' => true,
            'status'   => $content[ResponseFields::STATUS],
            'bank_payment_id' => $content[ResponseFields::BANK_PAYMENT_ID]
        );
    }
}
