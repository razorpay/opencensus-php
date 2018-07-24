<?php

namespace RZP\Gateway\Upi\Axis;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Gateway\Mpi\Enstage\Field;
use RZP\Models\Payment;
use phpseclib\Crypt\AES;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Base\VerifyResult;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Base\AuthorizeFailed;
use phpseclib\Crypt\RSA;


class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const ACQUIRER = 'axis';

    protected $gateway = Payment\Gateway::UPI_AXIS;

    protected $response;

    const BANK = 'axis';

    const TIMEOUT = 20;

    /**
     * This is what shows up as the payee
     * on the notification to the customer
     */
    const DEFAULT_PAYEE_VPA = 'razaorpay@axis';

    const FIELD_LENGTH = [
        Action::AUTHORIZE => 17,
        Action::REFUND => 20,
        Action::VERIFY => 14,
    ];

    protected $map = [
        Entity::VPA => Entity::VPA,
        Entity::RECEIVED => Entity::RECEIVED,
        Entity::EXPIRY_TIME => Entity::EXPIRY_TIME,
        Entity::TYPE => Entity::TYPE,
        Fields::UNQ_TXN_ID => Entity::PAYMENT_ID,
        Fields::UNQ_CUST_ID => Entity::PAYMENT_ID,
        Fields::AMOUNT => Entity::AMOUNT,
        Fields::MERCH_ID => Entity::GATEWAY_MERCHANT_ID,
        Fields::EXPIRY => Entity::EXPIRY_TIME,
        Fields::CUSTOMER_VPA => Entity::VPA,
        Fields::MOB_NO => Entity::CONTACT,
        Fields::TXN_REFUND_ID => Entity::REFUND_ID,
    ];

    /**
     * Authorizes a payment using UPI Gateway
     * @param array $input
     * @return array
     * @throws Exception\GatewayErrorException
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = $this->getGatewayEntityAttributes($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        parent::action($input, Action::AUTHORIZE);

        $request =  $this->getAuthorizeRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body);

        s($response);


        $this->updateGatewayPaymentEntity($gatewayPayment, $response);

//        $this->checkResponseStatus($response[Fields::CODE]);

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
     *
     * @param  array $input
     * @param string $action
     *
     * @return array
     */
    protected function getGatewayEntityAttributes(array $input, string $action = Action::AUTHORIZE)
    {
        $attrs = [
            Entity::GATEWAY_MERCHANT_ID => 'RAZAORPAY',
            Entity::VPA                 => $input['payment']['vpa'],
            Entity::ACTION              => $action,
            Entity::TYPE                => Base\Type::COLLECT,
        ];

        if ($action === Action::REFUND)
        {
            $attrs[Entity::REFUND_ID] = $input['refund']['id'];
        }

        if ($action === Action::AUTHORIZE)
        {
            $attrs[Entity::EXPIRY_TIME] = $input['upi']['expiry_time'];
        }

        return $attrs;
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
     * Formats a request content array to a proper string
     * that is sent to the server in POST body
     * @param  array  $data request array
     * @return string post body
     */
    protected function transformRequestArrayToContent(array $data): string
    {
        $json = json_encode($data);

        return $json;
    }

    /**
     * @param $responseBody
     * @param string $type
     * @return array
     * @see https://drive.google.com/drive/u/0/folders/0B1MTSXtR53PfYldqNUIyLXlnSjA
     */
    protected function parseGatewayResponse($responseBody, $type = Action::COLLECT)
    {
        $this->trace->info(TraceCode::GATEWAY_RESPONSE, [
            'body'              => $responseBody,
            'encrypted'         => true,
            'gateway'           => $this->gateway,
            'type'              => $type
        ]);
        return $this->jsonToArray($responseBody);
    }

    private function checkResponseStatus(string $status, string $successStatus = Status::SUCCESS)
    {
        if ($status !== $successStatus)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($status);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ResponseCode::getResponseMessage($status));
        }
    }

    protected function getAuthorizeRequestArray($input)
    {
        $payment = $input['payment'];

        //$checksumdata='RAZAORPAY'.'RAZAORPAYAPP'.$payment['id'].$payment['id'].$this->formatAmount($payment['amount']).$this->getPaymentRemark($input).'INR'.'ORDERID'.$payment['vpa'].(string) $input['upi']['expiry_time'];

        $data = [
            Fields::MERCH_ID => 'RAZAORPAY',
            Fields::MERCH_CHAN_ID => 'RAZAORPAYAPP',
            Fields::UNQ_TXN_ID => $payment['id'],
            Fields::UNQ_CUST_ID => $payment['id'],
            Fields::AMOUNT => $this->formatAmount($payment['amount']),
            Fields::TXN_DTL => $this->getPaymentRemark($input),
            Fields::CURRENCY => 'INR',
            Fields::ORDER_ID => 'ORDERID',
            Fields::CUSTOMER_VPA => 'vijay@axis',//$payment['vpa'],
            Fields::EXPIRY => (string) $input['upi']['expiry_time'],
            Fields::S_ID => '',
        ];

        $dataStr = implode('', $data);

        $checksum = $this->encrypt($dataStr);

        $data[Fields::CHECKSUM] = bin2hex($checksum);
        s($data);


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
        s($request);
        return $request;
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
     * This is same as the payment description, capped
     * to 50 characters
     *
     * @param array $input
     *
     * @return string
     */
    protected function getPaymentRemark(array $input)
    {
        $paymentDescription = $input['payment']['description'] ?? '';
        $filteredPaymentDescription = Payment\Entity::getFilteredDescription($paymentDescription);

        $description = $input['merchant']->getFilteredDba() . ' ' . $filteredPaymentDescription;


        $description = trim($description);
        return ($description ? substr($description, 0, 50) : 'Pay via Razorpay');
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
     * Handles the S2S callback
     * @param  array $input
     * @return boolean
     */
    public function callback(array $input): array
    {
        parent::callback($input);

        $content = $input['gateway'];

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        if ($gatewayPayment->getType() !== Base\Type::PAY)
        {
            assertTrue($content[ResponseFields::UPI_TXN_ID] === $gatewayPayment->getGatewayPaymentId());
        }

        assertTrue($input['payment']['id'] === $content[ResponseFields::PAYMENT_ID]);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        $actualAmount = number_format($content[ResponseFields::AMOUNT], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->checkResponseStatus($content[ResponseFields::STATUS]);

        $this->updateGatewayPaymentResponse($gatewayPayment, $content);

        // Gateways must return array in callback
        return [
            'acquirer' => [
                Payment\Entity::VPA => $gatewayPayment->getVpa()
            ]
        ];
    }

    protected function updateGatewayPaymentResponse($payment, array $response)
    {
        $attributes = $this->getMappedAttributes($response);

        // To mark that we have received a response for this request
        $attributes[Entity::RECEIVED] = 1;

        $payment->fill($attributes);

        $payment->generatePspData($attributes);

        $this->repo->saveOrFail($payment);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function runPaymentVerifyFlow($verify)
    {
        // This payment is the gateway entity payment.
        // Also sets this gateway payment in the verify object's payment.
        $gatewayPayment = $this->getPaymentToVerify($verify);

        if (($gatewayPayment === null) and
            ($this->shouldReturnIfPaymentNullInVerifyFlow($verify)))
        {
            $this->trace->warning(
                TraceCode::GATEWAY_PAYMENT_VERIFY,
                [
                    'payment_id' => $verify->input['payment']['id'],
                    'message'    => 'payment id not found in the gateway database',
                    'gateway'    => $this->gateway
                ]
            );

            return null;
        }

        $this->sendPaymentVerifyRequest($verify);
        $this->verifyPayment($verify);

        if (($verify->amountMismatch === true) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\RuntimeException(
                'Payment amount verification failed.',
                [
                    'payment_id' => $this->input['payment']['id'],
                    'gateway'    => $this->gateway
                ]
            );
        }
        if (($verify->match === false) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify);
        }

        return $verify->getDataToTrace();
    }

    protected function getPaymentVerifyRequestArray($input)
    {
        $payment = $input['payment'];

//        $checksumdata='RAZAORPAY'.'RAZAORPAYAPP'.$payment['id'];
//        $checksum = "";
//        openssl_public_encrypt($checksumdata,$checksum,'');

        $data = [
            Fields::MERCH_ID => 'RAZAORPAY',
            Fields::MERCH_CHAN_ID => 'RAZAORPAYAPP',
            Fields::UNQ_TXN_ID => $payment['id'],
            Fields::CHECKSUM => 'to be done',
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

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;
        $request = $this->getPaymentVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body, Action::VERIFY);

//        $bankDetails = $this->parseBankAccountDetails($content[Fields::BANK_REFERENCE]);

//        $content = array_merge($content, $bankDetails);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function parseBankAccountDetails($bankReference)
    {
        $fields = constant(__NAMESPACE__ . '\ResponseFields::BANK_DETAILS');

        $values = explode(Fields::BANK_REFERENCE_SEPARATOR, $bankReference);

        $bankReferenceArray = [];

        $index = 0;

        if (empty($values) === false)
        {
            foreach ($fields as $key)
            {
                if ($values[$index] !== Fields::NO_BANK_DETAIL)
                {
                    $bankReferenceArray[$key] = $values[$index];
                }

                $index++;
            }

        }

        return $bankReferenceArray;
    }

    protected function verifyPayment($verify)
    {
        $content = $verify->verifyResponseContent;

        $this->checkApiSuccess($verify);

//        $this->checkGatewaySuccess($verify);

        $status = VerifyResult::STATUS_MATCH;

        // If both don't match we have a status mis match
        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $input = $verify->input;

        if ($verify->gatewaySuccess === true)
        {
            $paymentAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

            $actualAmount = number_format($content[Fields::AMOUNT], 2, '.', '');

            $verify->amountMismatch = ($paymentAmount !== $actualAmount);
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $content[Entity::RECEIVED] = 1;

        $this->updateGatewayPaymentEntity($verify->payment, $content);
    }

    private function checkGatewaySuccess(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->gatewaySuccess = ($content[Fields::STATUS] === Status::SUCCESS);
    }

    protected function getCipherInstance(): RSA
    {

        $rsa = new RSA();

        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return $rsa;
    }

    /**
     * Encrypts data before sending it to Axis
     * @param  string $data
     * @return string
     */
    protected function encrypt(string $data): string
    {
        $rsa = $this->getCipherInstance();

        $rsa->loadKey('-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAl3x5rqnoQtzCsVJsrdS+
LO10KSVL76T5y5lr4Q3ci2kPwIsh/oA5tE+fhYyLjDDi+jOwSvwcOS2ZexWpImOp
OCAmxq5dowGF4dHc4AwaihV4+SjkNiRhDyyzTwhafntsbpqfLLL/f6Tk79xstIKf
lSLzCW1RQ1sUuCe/VZvYqYquDiFucW2ZI36A0XO2JrwuOkwuSXUOv1SApoVN6gLT
e2PwSyyNtQPoXO4+u3b9pXUvx5wcYO4uTpA2Ym9S6/EJm5+DjaN1c1DGdwbUITZC
nb+ZuF4oIUB8xCqmWDXZYy7Q0aO0tviHg6sFOs2MrxwjXAa2NaQbrcCnQ8bXu0qe
UQIDAQAB
-----END PUBLIC KEY-----');

        return $rsa->encrypt($data);
    }

}