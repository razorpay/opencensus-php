<?php

namespace RZP\Gateway\Upi\Mindgate;

use RZP\Exception;
use RZP\Constants\Mode;
use phpseclib\Crypt\AES;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Base\VerifyResult;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const ACQUIRER = 'hdfc';

    protected $gateway = 'upi_mindgate';

    const BANK = 'hdfc';

    /**
     * This is what shows up as the payee
     * on the notification to the customer
     */
    const DEFAULT_PAYEE_VPA = 'razorpay@hdfcbank';

    // Transaction Types
    const P2P = 'P2P';
    const P2M = 'P2M';

    const PAY = 'PAY';

    // Expiry timeout in minutes
    const EXPIRY_TIMEOUT = 5;

    protected $map = [
        Entity::VPA                       => Entity::VPA,
        ResponseFields::PAYER_VA          => Entity::VPA,
        ResponseFields::STATUS            => Entity::STATUS_CODE,
        // This is a 5 digit number that is the reference ID on the HDFC side
        ResponseFields::UPI_TXN_ID        => Entity::GATEWAY_PAYMENT_ID,
        // NPCI provided RRN for the transaction
        ResponseFields::NPCI_UPI_TXN_ID   => Entity::NPCI_REFERENCE_ID,
    ];

    /**
     * Authorizes a payment using UPI Gateway
     * @param  array  $input
     * @return null
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = $this->getGatewayEntityAttributes($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $request =  $this->getAuthorizeRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body);

        $this->updateGatewayEntityResponse($gatewayPayment, $response);

        $status = $response[ResponseFields::STATUS];

        if ($status !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($status);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ResponseCode::getResponseMessage($status));
        }

        $vpa = $this->terminal->getGatewayMerchantId2() ?? self::DEFAULT_PAYEE_VPA;

        return [
            'data'   => [
                'vpa'   => $vpa
            ]
        ];
    }

    /**
     * We only store the VPA because the rest of the fields
     * are filled by the callback
     * @param  array  $input
     * @return Array
     */
    protected function getGatewayEntityAttributes(array $input, string $action = Action::AUTHORIZE)
    {
        $attrs = [
            Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            Entity::VPA                 => $input['payment']['vpa'],
            Entity::ACTION              => $action,
        ];

        if ($action === Action::REFUND)
        {
            $attrs[Entity::REFUND_ID] = $input['refund']['id'];
        }

        return $attrs;
    }

    /**
     * Takes in S2S request input array
     * and returns the parsed response as an array
     * @param  array $input Request Input arrau
     * @return array
     */
    public function preProcessServerCallback($input): array
    {
        $encryptedResponse = $input[ResponseFields::CALLBACK_RESPONSE_KEY];

        return $this->parseGatewayResponse($encryptedResponse, Action::CALLBACK);
    }

    /**
     * @param  string $response
     * @param  string $type type of request
     * @see https://drive.google.com/drive/u/1/folders/0B1MTSXtR53PfN2dIWmE0REI3eWs
     */
    protected function parseGatewayResponse($responseBody, $type = Action::COLLECT)
    {
        $this->trace->info(TraceCode::GATEWAY_RESPONSE, [
            'body'              => $responseBody,
            'encrypted'         => true,
            'gateway'           => $this->gateway,
            'type'              => $type
        ]);

        $response = $this->decrypt($responseBody);

        $type = strtoupper($type);

        $fields = constant(__NAMESPACE__ . "\ResponseFields::$type");

        $values = explode('|', $response);

        $result = [];

        foreach ($fields as $index => $key)
        {
            $result[$key]     =   $values[$index];
        }

        $this->trace->info(TraceCode::GATEWAY_RESPONSE, [
            'body'              => $responseBody,
            'decrypted'         => $response,
            'parsed'            => $result,
            'gateway'           => $this->gateway,
            'type'              => $type
        ]);

        return $result;
    }

    /**
     * Handles the S2S callback
     * @param  array $input
     * @return boolean
     */
    public function callback(array $input): array
    {
        parent::callback($input);

        $content = $input['gateway'];

        $status = $content[ResponseFields::STATUS];

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        assertTrue($content[ResponseFields::UPI_TXN_ID] === $gatewayPayment->getGatewayPaymentId());

        if ($status !== Status::SUCCESS)
        {
            $message = "Payment Failed during callback";

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $status,
                $message);
        }

        // Authorization was successful
        $this->updateGatewayEntityResponse($gatewayPayment, $content);

        // Gateways must return array in callback
        return [];
    }

    /**
     * Formats amount to 2 decimal places
     * @param  int $amount amount in paise (100)
     * @return string amount formatted to 2 decimal places in INR (1.00)
     */
    protected function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    /**
     * The Merchant ID doesn't change for different
     * merchants since this is the master merchant Id
     * @return string (numeric merchant id)
     */
    protected function getMerchantId()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->terminal->getGatewayMerchantId();
        }

        return $this->config['test_merchant_id'];
    }

    /**
     * This is the key used to encrypt requests
     * @return string public key
     */
    protected function getEncryptionKey()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->terminal['gateway_terminal_password'];
        }

        return $this->config['test_merchant_key'];
    }

    /**
     * Encrypts data
     * @param  string $data
     * @return string
     */
    public function encrypt($plaintext)
    {
        return $this->getCipherInstance()
                    ->encrypt($plaintext);
    }

    /**
     * Returns a Crypto instance
     * @return Crypto class instance
     * @return Crypto
     */
    protected function getCipherInstance()
    {
        return new Crypto($this->getEncryptionKey());
    }

    /**
     * Decrypts responses from the Mindgate API
     * @param  string $data
     * @return string
     */
    public function decrypt(string $ciphertext)
    {
        return $this->getCipherInstance()
                    ->decrypt($ciphertext);
    }

    protected function getAuthorizeRequestArray($input)
    {
        $payment = $input['payment'];

        // The order is defined in the docs
        // See README.md

        $data = [
            $this->getMerchantId(),
            $payment['id'],
            $payment['vpa'],
            $this->formatAmount($payment['amount']),
            $this->getPaymentRemark($input),
            self::EXPIRY_TIMEOUT,
            $this->getMerchantCategoryCode($input),
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'decrypted_content' => $data,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'payment_id'        => $payment['id'],
            ]);

        return $request;
    }

    /**
     * Returns the MCC code, based on the merchant category
     * @param  array  $input
     * @return string 4 digit integer as string.
     *                  Default value is 6012, as per HDFC
     *                  (Check pgtech group)
     */
    protected function getMerchantCategoryCode(array $input)
    {
        return $input['merchant']['category'] ?: '6012';
    }

    /**
     * This is same as the payment description, capped
     * to 50 characters
     * @return string
     */
    protected function getPaymentRemark(array $input)
    {
        $description = $input['merchant']->getFilteredDba();

        return ($description ? substr($description, 0, 50) : 'Pay via Razorpay');
    }

    /**
     * Returns a refund description, capped to 50 chars
     * @param  array  $input
     * @return string
     */
    protected function getRefundRemark(array $input): string
    {
        $description = $input['merchant']->getFilteredDba();

        // Using ?: works with empty strings as well
        // (because '' == false) === true
        $description = $description ?: 'Razorpay';

        return "Refund for " . substr($description, 0, 36);
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

        // Drop any `|` in any of the field values
        $data = array_map(function($e)
        {
            return str_replace('|', '', $e);
        }, $data);

        $data = implode('|', $data);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'data'              => $data,
                'gateway'           => $this->gateway,
                'action'            => $this->action
            ]);

        $msg = $this->encrypt($data);

        $json = [
            'requestMsg'    => $msg,
            'pgMerchantId'  => $this->getMerchantId(),
        ];

        return json_encode($json);
    }

    protected function updateGatewayEntityResponse(Entity $upiEntity, array $response)
    {
        $attr = $this->getMappedAttributes($response);

        // To mark that we have received a response for this request
        $attr[Entity::RECEIVED] = 1;

        $upiEntity->fill($attr);

        $upiEntity->saveOrFail();
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $attributes = $this->getGatewayEntityAttributes($input, Action::REFUND);

        $refund = $this->createGatewayPaymentEntity($attributes);

        $request =  $this->getRefundRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body, Action::REFUND);

        $this->updateGatewayEntityResponse($refund, $response);

        $status = $response[ResponseFields::STATUS];

        if ($response[ResponseFields::STATUS] !== Status::REFUND_SUCCESS)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,
                $status,
                $response[ResponseFields::STATUS_DESCRIPTION]);
        }
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

        $content = $this->parseGatewayResponse($response->body, Action::VERIFY);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getValidateVpaRequestArray(string $vpa): array
    {
        $data = [
            $this->getMerchantId(),
            random_alpha_string(10),
            $vpa,
            'T'
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_SUPPORT_REQUEST,
            [
                'decrypted_content' => $data,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'action'            => Action::VALIDATE_VPA,
            ]);

        return $request;
    }

    protected function getRefundRequestArray(array $input): array
    {

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        // The order is defined in the docs
        // See README.md

        $data = [
            $this->getMerchantId(),
            $input['refund']['id'],
            $input['payment']['id'],
            $gatewayPayment->getGatewayPaymentId(),
            $gatewayPayment->getNpciReferenceId(),
            $this->getRefundRemark($input),
            $this->formatAmount($input['refund']['amount']),
            $input['refund']['currency'],
            // Transaction Type
            // Refunds are P2P!
            self::P2P,
            // Type of Payment (Pay or Collect)
            // Refunds are considered "Pay" transactions
            self::PAY,
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'decrypted_content' => $data,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
                'refund_id'         => $input['refund']['id'],
            ]);

        return $request;
    }

    protected function getPaymentVerifyRequestArray($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        $data = [
            $this->getMerchantId(),
            $input['payment']['id'],
            $gatewayPayment->getGatewayPaymentId(),
            // This is the Reference ID field
            // which is supposed to be empty for now
            // Non-empty values give error
            '',
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

        $status = VerifyResult::STATUS_MATCH;

        $verify->apiSuccess = true;
        $verify->gatewaySuccess = false;

        $attr = [];

        if ($content[ResponseFields::STATUS] === Status::SUCCESS)
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
