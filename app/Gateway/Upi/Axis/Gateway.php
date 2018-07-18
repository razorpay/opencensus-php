<?php

namespace RZP\Gateway\Upi\Axis;

use RZP\Exception;
use RZP\Constants\Mode;
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
    const DEFAULT_PAYEE_VPA = 'razorpaypg@axisbank';

    const FIELD_LENGTH = [
        Action::AUTHORIZE    => 17,
        Action::VALIDATE_VPA => 14,
        Action::REFUND       => 20,
        Action::VERIFY       => 14,
    ];

    protected $map = [
        Entity::VPA                       => Entity::VPA,
        Entity::RECEIVED                  => Entity::RECEIVED,
        Entity::EXPIRY_TIME               => Entity::EXPIRY_TIME,
        Entity::TYPE                      => Entity::TYPE,
        Fields::CUSTOMER_VPA              => Entity::VPA,
        Fields::MOB_NO                    => Entity::CONTACT,
        Fields::MERCH_ID                  => Entity::GATEWAY_MERCHANT_ID,
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
        s($input);
        $attributes = $this->getGatewayEntityAttributes($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $this->validateVpa($input['payment']);

        parent::action($input, Action::AUTHORIZE);

        $request =  $this->getAuthorizeRequestArray($input);
        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body);

        $response[Entity::RECEIVED] = 1;

        $this->updateGatewayPaymentEntity($gatewayPayment, $response);

        $this->checkResponseStatus($response[ResponseFields::STATUS]);

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
            Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
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

    protected function getMerchantId()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->terminal->getGatewayMerchantId();
        }

//        return $this->config['test_merchant_id'];
        return "RAZAORPAY";
    }

    /**
     * We need to validate that the user's VPA is valid before proceeding with the payment
     * @param array $input
     */

    public function validateVpa(array $input)
    {
        parent::action($input, Action::VALIDATE_VPA);

        $request = $this->getValidateVpaRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body, Action::VALIDATE_VPA);

        $this->checkResponseStatus($response[ResponseFields::VPA_STATUS], Status::VPA_AVAILABLE);
    }

    protected function getValidateVpaRequestArray(array $input): array
    {
        $data = [
            $this->getMerchantId(),
            random_alpha_string(10),
            $input['vpa'],
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

    protected function transformRequestArrayToContent(array $data)
    {
        $extraFields = self::FIELD_LENGTH[$this->action] - count($data);

        // We have space for 10 extra fields that we don't use
        $suffixArray = array_fill(0, $extraFields, 'NA');

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
            $input['upi']['expiry_time'],
            $this->getMerchantCategoryCode($input),
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
        ];

//        if ($input['merchant']->isTPVRequired() === true)
//        {
//            // MEBR is the request type for TPV
//            $data[12] = 'MEBR';
//            $data[13] = $input['order']['account_number'];
//        }

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
//        s($responseBody);
//        $response = $this->decrypt($responseBody);
        $response = $responseBody;
        $this->trace->info(TraceCode::GATEWAY_RESPONSE, [$response]);

        $type = strtoupper($type);

        $fields = constant(__NAMESPACE__ . "\Fields::$type");

        $values = explode('|', $response);

        $result = [];

        foreach ($fields as $index => $key)
        {
            $result[$key] = $values[$index];
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
     *
     * @param string $cipherText
     *
     * @return string
     */
    public function decrypt(string $cipherText)
    {
        return $this->getCipherInstance()
            ->decrypt($cipherText);
    }

    /**
     * This is the key used to encrypt requests
     * @return string public key
     */
    protected function getEncryptionKey()
    {
        return $this->config['gateway_encryption_key'];
    }

    /**
     * Encrypts data
     *
     * @param $plaintext
     *
     * @return string
     */
    public function encrypt($plaintext)
    {
        return $this->getCipherInstance()
            ->encrypt($plaintext);
    }
}