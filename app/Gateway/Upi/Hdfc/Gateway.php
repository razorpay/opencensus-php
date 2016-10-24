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

    protected $map = array(

    );

    /**
     * Authorizes a payment using UPI Gateway
     * @param  array  $input
     * @return null
     */
    public function authorize(array $input)
    {
        parent::authorize($input);
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
    protected function parseGatewayResponse($response, $type = 'collect')
    {
        $fields = [];
        switch ($type) {
            case 'collect':
                $fields = [
                    'OrderNo', 'UPI Txn Id', 'amount', 'status', 'status desc',
                    'payer VA', 'payeeVA', 'add1', 'add2', 'add3', 'add4', 'add5',
                    'add6', 'add7', 'add8', 'add9', 'add10'
                ];

                break;

            case 'verify':
                $fields = [
                    'UPI Txn ID', 'OrderNo', 'Amount', 'Txn Auth Date', 'status',
                    'status desc', 'respcode', 'approval no', 'payerVA', 'NPCI UPI txn id',
                    'referance id', 'add1', 'add2', 'add3', 'add4', 'add5', 'add6', 'add7',
                    'add8', 'add9', 'add10'
                ];
                break;

            case 'refund':
                $fields = [
                    'UPI Txn Id', 'OrderNo', 'Amount', 'Txn Auth Date', 'status',
                    'status desc', 'respcode', 'approvalno', 'payer VA', 'txn id ',
                    'custref id', 'add1', 'add2', 'add3', 'add4', 'add5', 'add6',
                    'add7', 'add8', 'add9', 'add10'
                ];
        }

        $values = explode('|', $response);
        $response = [];

        foreach ($values as $index => $value)
        {
            $response[$fields[$index]] = $value;
        }

        return $response;
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

        $type = "{$this->mode}_{$type}";

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

        $collectByTimestamp = Carbon::now('Asia/Kolkata')->addMinutes(5)->format('d/m/Y h:i A');

        $data = [
            // TODO
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'decrypted_content' => $data,
                'gateway' => 'upi_hdfc',
                'payment_id' => $input['payment']['id'],
            ]);

        return $request;
    }

    /**
     * This is same as the payment description, capped
     * to 50 characters
     * @return string
     */
    protected function getPaymentRemark(array $input)
    {
        $description = $input['merchant']->getBillingLabelElseName();

        return ($description ? substr($description, 0, 50) : 'Pay via Razorpay');
    }

    /**
     * Formats a request content array to a proper string
     * that is sent to the server in POST body
     * @param  array  $data request array
     * @return string post body
     */
    protected function transformRequestArrayToContent(array $data)
    {
        // TODO: Make sure none of the fields contain a `pipe`
        // Then join and return

        return implode('|', $data);
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

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'raw_content' => $response->body,
                'content' => $content,
                'gateway' => 'upi_icici',
                'payment_id' => $input['payment']['id'],
            ]);

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
     * subMerchantId is limited to 10 characters
     * so we send the first 10 characters
     * @return string
     */
    protected function getSubMerchantId(array $input)
    {
        // ICICI docs say that they accept alphanumeric
        // merchant IDs, but they do not. The field is
        // also marked as optional, but it is not.
        return '1234';

        // return substr($input['merchant']['id'], 0, 10);
    }

    /**
     * Returns Payment Id
     * @param  string $body Request Body
     * @return string Payment Id
     */
    public function getPaymentIdFromServerCallback(array $response)
    {
        return $response[ResponseFields::MERCHANT_TRAN_ID];
    }
}
