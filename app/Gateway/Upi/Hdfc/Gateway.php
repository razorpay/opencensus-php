<?php

namespace RZP\Gateway\Upi\Hdfc;

use Carbon\Carbon;
use phpseclib\Crypt\AES;
use Request;
use RZP\Exception;
use ErrorException;
use Requests_Response;
use RZP\Constants\Mode;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Utility;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'upi_hdfc';

    const BANK = 'hdfc';

    // Expiry timeout in minutes
    const EXPIRY_TIMEOUT = 5;

    protected $map = array(
        ResponseFields::PAYER_VA          => Entity::VPA,
        ResponseFields::STATUS            => Entity::STATUS_CODE,
        ResponseFields::UPI_TXN_ID        => Entity::GATEWAY_PAYMENT_ID,
    );

    /**
     * Authorizes a payment using UPI Gateway
     * @param  array  $input
     * @return null
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = $this->getGatewayEntityAttributes($input);

        $payment = $this->createGatewayPaymentEntity($attributes);

        $request =  $this->getAuthorizeRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body);

        $this->updateGatewayPaymentResponse($payment, $response);

        $status = $response[ResponseFields::STATUS];

        if ($status !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($status);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ResponseCode::getResponseMessage($status));
        }

        return true;
    }

    /**
     * We only store the VPA because the rest of the fields
     * are filled by the callback
     * @param  array  $input
     * @return Array
     */
    protected function getGatewayEntityAttributes(array $input)
    {
        return [
            Entity::VPA     => $input['payment']['vpa'],
        ];
    }

    /**
     * @param  string $response
     * @param  string $type type of request
     */
    protected function parseGatewayResponse($responseBody, $type = 'collect')
    {
        $response = $this->decrypt($responseBody);

        $fields = [];
        switch ($type) {
            case 'collect':

                // There are lots of additional dummy fields
                // after this, which we ignore
                $fields = [
                    ResponseFields::PAYMENT_ID,
                    ResponseFields::UPI_TXN_ID,
                    ResponseFields::AMOUNT,
                    ResponseFields::STATUS,
                    ResponseFields::STATUS_DESCRIPTION,
                    ResponseFields::PAYER_VA,
                    ResponseFields::PAYEE_VA,
                ];

                break;

            case 'verify':
                $fields = [
                    ResponseFields::UPI_TXN_ID,
                    ResponseFields::PAYMENT_ID,
                    ResponseFields::AMOUNT,
                    ResponseFields::TXN_AUTH_DATE,
                    ResponseFields::STATUS,
                    ResponseFields::STATUS_DESCRIPTION,
                    ResponseFields::RESPCODE,
                    ResponseFields::APPROVAL_NO,
                    ResponseFields::PAYER_VA,
                    ResponseFields::NPCI_UPI_TXN_ID,
                    ResponseFields::REFERENCE_ID,
                ];
                break;

            case 'refund':
                $fields = [
                    ResponseFields::UPI_TXN_ID,
                    ResponseFields::PAYMENT_ID,
                    ResponseFields::AMOUNT,
                    ResponseFields::TXN_AUTH_DATE,
                    ResponseFields::STATUS,
                    ResponseFields::STATUS_DESCRIPTION,
                    ResponseFields::RESPCODE,
                    ResponseFields::APPROVAL_NO,
                    ResponseFields::PAYER_VA,
                    ResponseFields::APPROVAL_NO,
                    ResponseFields::TXN_ID,
                    ResponseFields::CUSTOMER_REFERENCE_ID,
                ];
        }

        $values = explode('|', $response);

        $result = [];

        foreach ($fields as $index => $key)
        {
            $result[$key]     =   $values[$index];
        }

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_RESPONSE,
            [
                'body'              => $responseBody,
                'decrypted'         => $response,
                'parsed'            => $result,
                'gateway'           => $this->gateway,
                'type'              => $type
            ]);

        return $result;
    }

    /**
     * Formats amount to 2 decimal places
     * @param  int $amount amount in paise (100)
     * @return string amount formatted to 2 decimal places in INR (1.00)
     */
    protected function formatAmount($amount)
    {
        return number_format($amount/100, 2, '.', '');
    }

    /**
     * The Merchant ID doesn't change for different
     * merchants since this is the master merchant Id
     * @return string (numeric merchant id)
     */
    protected function getMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $this->config['live_merchant_id'];
    }

    /**
     * This is the key used to encrypt requests
     * @return string public key
     */
    protected function getEncryptionKey()
    {
        $key = $this->config['test_merchant_key'];

        if ($this->mode === Mode::LIVE)
        {
            $key = $this->config['live_merchant_key'];
        }

        return hex2bin($key);
    }

    /**
     * Gets the correct URL from the
     * Url class
     * @param  string $type Action String
     * @return String URL
     */
    protected function getUrl($type = null)
    {
        if ($type === null)
        {
            $type = $this->action;
        }

        return parent::getUrl($type);
    }

    /**
     * Encrypts data before sending it to ICICI
     * @param  string $data
     * @return string
     */
    public function encrypt($data)
    {
        $cipher = $this->getAESInstance();

        return strtoupper(bin2hex($cipher->encrypt($data)));
    }

    protected function getAESInstance()
    {
        $cipher = new AES(AES::MODE_ECB);

        $cipher->setKey($this->getEncryptionKey());

        return $cipher;
    }

    /**
     * Decrypts responses from the ICICI API
     * @param  string $data
     * @return string
     */
    public function decrypt($data)
    {
        $cipher = $this->getAESInstance();

        return $cipher->decrypt(hex2bin($data));
    }

    protected function hex2str($hex)
    {
        $str = '';

        for($i=0;$i<strlen($hex);$i+=2)
        {
           $str .= chr(hexdec(substr($hex,$i,2)));
        }

        return $str;
      }

    protected function getAuthorizeRequestArray($input)
    {
        $payment = $input['payment'];

        // The order is defined in the docs
        // See README.md

        $data = [
            $this->getMerchantId(),
            $input['payment']['id'],
            $input['payment']['vpa'],
            $this->formatAmount($payment['amount']),
            $this->getPaymentRemark($input),
            self::EXPIRY_TIMEOUT,
            $this->getMCCCode($input),
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'decrypted_content' => $data,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
            ]);

        return $request;
    }

    /**
     * Returns the MCC code, based on the merchant category
     * @param  array  $input
     * @return string 4 digit integer as string
     */
    protected function getMCCCode(array $input)
    {
        if ($input['merchant']['category'])
        {
            return $input['merchant']['category'];
        }

        return '0000';
    }

    /**
     * This is same as the payment description, capped
     * to 50 characters
     * @return string
     */
    protected function getPaymentRemark(array $input)
    {
        $description = $input['merchant']->getBillingLabelElseName();

        $remark = ($description ? substr($description, 0, 50) : 'Pay via Razorpay');

        return str_replace('|', ' ', $remark);
    }

    /**
     * Formats a request content array to a proper string
     * that is sent to the server in POST body
     * @param  array  $data request array
     * @return string post body
     */
    protected function transformRequestArrayToContent(array $data)
    {
        // We have space for 10 extra fields that we don't use
        $suffixArray = array_fill(0, 10, 'NA');

        $data = array_merge($data, $suffixArray);

        $data = implode('|', $data);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'data'              => $data,
            ]);


        $msg = $this->encrypt($data);

        $json = [
            'requestMsg'    =>  $msg,
            'pgMerchantId'  =>  $this->getMerchantId(),
        ];

        return json_encode($json);
    }

    protected function updateGatewayPaymentResponse($payment, array $response)
    {
        $attr = $this->getMappedAttributes($response);

        // To mark that we have received a response for this request
        $attr[Entity::RECEIVED] = 1;

        $payment->fill($attr);

        $payment->saveOrFail();
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getPaymentVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getPaymentVerifyRequestArray($input)
    {
        $data = [
            // TODO
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = [
            'Content-Type' => 'text/plain'
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request' => $request,
                'decrypted_content' => $data
            ]);

        return $request;
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $content = $verify->verifyResponseContent;

        if ($content['success'] !== 'true')
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                $content['success'],
                $content['message']);
        }

        $status = VerifyResult::STATUS_MATCH;

        $verify->apiSuccess = true;
        $verify->gatewaySuccess = false;

        $attr = [];

        if ($content['status'] === Status::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }

        $input = $verify->input;

        // If payment status is either failed or created,
        // this is an api failure
        if (($input['payment']['status'] === 'failed') or
            ($input['payment']['status'] === 'created'))
        {
            $verify->apiSuccess = false;
        }

        // If both don't match we have a status mis match
        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->verifyResponseContent = $this->getMappedAttributes($content);

        return $status;
    }

    /**
     * Returns Payment Id
     * @param  string $body Request Body
     * @return string Payment Id
     */
    public function getPaymentIdFromServerCallback(array $response)
    {
        return $response[ResponseFields::PAYMENT_ID];
    }
}
