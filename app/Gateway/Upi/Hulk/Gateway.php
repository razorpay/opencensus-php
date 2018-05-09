<?php

namespace RZP\Gateway\Upi\Hulk;

use Request;
use Carbon\Carbon;
use RZP\Exception;
use ErrorException;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Gateway\Utility;
use RZP\Trace\TraceCode;
use phpseclib\Crypt\RSA;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Base\VerifyResult;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\BharatQr;
use RZP\Models\Payment\Verify\Action as VerifyAction;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    /**
     * Default request timeout duration in seconds.
     * @var  integer
     */
    const TIMEOUT = 20;

    const ACQUIRER = 'hdfc';

    const HASH_ALGO = 'sha256';

    protected $gateway = 'upi_hulk';

    //
    // @todo: Fix the mapping
    //
    protected $map = [
        Fields::ID                        => Entity::GATEWAY_PAYMENT_ID,
        Fields::STATUS                    => Entity::STATUS_CODE,
        Fields::RRN                       => Entity::NPCI_REFERENCE_ID,

        Entity::VPA                       => Entity::VPA,
        Entity::EXPIRY_TIME               => Entity::EXPIRY_TIME,
        Entity::PROVIDER                  => Entity::PROVIDER,
        Entity::BANK                      => Entity::BANK,
        Entity::TYPE                      => Entity::TYPE,
        Entity::RECEIVED                  => Entity::RECEIVED,
        Fields::CALLER_ACCOUNT_NUMBER     => Entity::ACCOUNT_NUMBER,
    ];

    /**
     * Authorizes a payment using UPI Gateway
     * @param  array  $input
     * @return boolean
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        // @todo Enable intent
        if ((isset($input['upi']['flow']) === true) and
            ($input['upi']['flow'] === 'intent'))
        {
            return $this->authorizeIntent($input);
        }

        $attributes = $this->getGatewayEntityAttributes($input);

        $payment = $this->createGatewayPaymentEntity($attributes);

        $request =  $this->getAuthorizeRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $response);

        $this->updateGatewayPaymentResponse($payment, $response);

        if ((isset($response['error']) === true) or
            ($response['status'] !== Status::INITIATED))
        {
            // @todo: Fetch internal error code on proxy auth from hulk and
            // pass it as error code to API
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $response['error']['code'] ?? null,
                $response['error']['description'] ?? null
            );
        }

        return [
            'data'   => [
                'vpa'   => ''
            ]
        ];
    }

    protected function authorizeIntent(array $input)
    {
        $attributes = [
            Entity::TYPE => Base\Type::PAY,
        ];

        $payment = $this->createGatewayPaymentEntity($attributes);

        $request =  $this->getPayAuthorizeRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $response);

        $this->updateGatewayPaymentResponse($payment, $response);

        $this->checkResponseStatus($response, Status::CREATED);

        return $this->getIntentRequest($input, $response);
    }

    protected function getIntentRequest($input, $response)
    {
        $content = [
            Base\IntentParams::PAYEE_ADDRESS => $input['terminal']->getGatewayMerchantId2(),
            Base\IntentParams::PAYEE_NAME    => $this->getFormattedDba($input),
            Base\IntentParams::TXN_REF_ID    => $this->getFormattedRefId($response),
            Base\IntentParams::TXN_NOTE      => $this->getPaymentRemark($input),
            Base\IntentParams::TXN_AMOUNT    => $input['payment']['amount'] / 100,
            Base\IntentParams::TXN_CURRENCY  => $input['payment']['currency'],
            Base\IntentParams::MCC           => '5411',
        ];

        return ['data' => ['intent_url' => $this->generateIntentString($content)]];
    }

    protected function getFormattedDba($input)
    {
        return preg_replace('/\s+/', '', $input['merchant']->getFilteredDba());
    }

    protected function getFormattedRefId(array $p2p)
    {
        // Ref Id is not sent right now, will be sent later
        if (isset($p2p[Fields::REF_ID]) === true)
        {
            return $p2p[Fields::REF_ID];
        }

        return str_replace('p2p_', '', $p2p[Fields::ID]);
    }

    /**
     * Handles the S2S callback
     *
     * @param  array $input
     *
     * @return array
     * @throws Exception\GatewayErrorException
     * @throws Exception\LogicException
     */
    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->validateCallbackSignature($content);

        $p2p = $content[Fields::DATA];

        $repo = $this->getRepository();

        $gatewayPayment = $repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $expectedAmount = $input['payment']['amount'];
        $actualAmount   = $p2p[Fields::AMOUNT];

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->checkResponseStatus($p2p, Status::COMPLETED);

        // Authorization was successful
        $this->updateGatewayPaymentResponse($gatewayPayment, $p2p);

        return [
            'acquirer' => [
                Payment\Entity::VPA => $gatewayPayment->getVpa()
            ]
        ];
    }

    protected function validateCallbackSignature(array $input)
    {
        $signature = $input[Fields::SIGNATURE];

        $content = $input[Fields::RAW];

        $password = $this->getTerminalPassword();

        $hashed = hash_hmac(self::HASH_ALGO, $content, $password);

        if(hash_equals($hashed, $signature) !== true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
                null,
                null,
                [
                    'expected'  => $hashed,
                    'actual'    => $signature,
                ]);
        }
    }

    /**
     * Will inject Signature in input from request
     *
     * @param $input
     * @return array
     */
    public function preProcessServerCallback($input): array
    {
        $input[Fields::SIGNATURE] = array_get($input, 'headers.x-hulk-signature.0');

        // To make sure, we do not use it later in code.
        unset($input['headers']);

        return $input;
    }

    public function getPaymentIdFromServerCallback($input): string
    {
        return $input['data'][Fields::MERCHANT_REFERENCE_ID];
    }

    /**
     * We only store the VPA, bank and provider because the rest of the fields
     * are filled by the callback
     * @param  array  $input
     * @return Array
     */
    protected function getGatewayEntityAttributes(array $input): array
    {
        return [
            Entity::VPA         => $input['payment']['vpa'],
            Entity::TYPE        => Base\Type::COLLECT,
            Entity::EXPIRY_TIME => $input['upi']['expiry_time'],
        ];
    }

    protected function sendGatewayRequest($request)
    {
        $terminal = $this->terminal;

        $request['options']['auth'] = [$this->getMerchantId(), $this->getTerminalPassword()];

        return parent::sendGatewayRequest($request);
    }

    protected function getMerchantId(): string
    {
        if ($this->mode === Mode::TEST)
        {
            return 'rzp_test_' . $this->input['merchant']['id'];
        }

        return 'rzp_live_' . $this->input['merchant']['id'];
    }

    public function getTerminalPassword()
    {
        return $this->input['terminal']['gateway_terminal_password'];
    }

    protected function getAuthorizeRequestArray(array $input): array
    {
        $payment = $input['payment'];

        $expiryTime = $input['upi']['expiry_time'];

        $collectByTimestamp = Carbon::now(Timezone::IST)->addMinutes($expiryTime)->getTimestamp();

        $content = [
            Fields::TYPE                    => Type::PULL,
            Fields::AMOUNT                  => $payment['amount'],
            Fields::CURRENCY                => $input['payment']['currency'],
            Fields::EXPIRE_AT               => $collectByTimestamp,
            Fields::SENDER                  => [
                Fields::ADDRESS => $input['payment']['vpa'],
            ],
            Fields::DESCRIPTION             => $this->getPaymentRemark($input),
            Fields::NOTES                   => [
                'razorpay_payment_id'       => $payment['id'],
            ],
            Fields::MERCHANT_REFERENCE_ID   => $payment['id'],
        ];

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request'           => $request,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
            ]);

        return $request;
    }

    protected function getPayAuthorizeRequestArray(array $input): array
    {
        $payment = $input['payment'];

        $content = [
            Fields::TYPE                    => Type::EXPECTED_PUSH,
            Fields::AMOUNT                  => $payment['amount'],
            Fields::CURRENCY                => $payment['currency'],
            Fields::DESCRIPTION             => $this->getPaymentRemark($input),
            Fields::NOTES                   => [
                'razorpay_payment_id' => $payment['id']
            ],
            Fields::MERCHANT_REFERENCE_ID   => $payment['id'],
        ];

        if ($input['merchant']->isTPVRequired() === true)
        {
            $content[Fields::CALLER_ACCOUNT_NUMBER] = $input['order']['account_number'];
        }

        $request = $this->getStandardRequestArray($content, 'post');

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request'           => $request,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
            ]);

        return $request;
    }

    /**
     * This is same as the payment description, capped
     * to 50 characters
     *
     * @param array $input
     *
     * @return string
     */
    protected function getPaymentRemark(array $input): string
    {
        $paymentDescription = $input['payment']['description'] ?? '';
        $filteredPaymentDescription = Payment\Entity::getFilteredDescription($paymentDescription);

        $description = $input['merchant']->getFilteredDba() . ' ' . $filteredPaymentDescription;

        return ($description ? substr($description, 0, 50) : 'Pay via Razorpay');
    }

    protected function getSubMerchantName(array $input): string
    {
        $dba = preg_replace('/\s+/', '', $input['merchant']->getFilteredDba());

        return ($dba ? substr($dba, 0, 30) : 'Razorpay');
    }

    protected function updateGatewayPaymentResponse($payment, array $response)
    {
        $attr = $this->getMappedAttributes($response);

        // To mark that we have received a response for this request
        $attr[Entity::RECEIVED] = 1;

        $payment->fill($attr);

        $payment->generatePspData($attr);

        $payment->saveOrFail();
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function sendPaymentVerifyRequest(Verify $verify): array
    {
        $input = $verify->input;

        $request = $this->getPaymentVerifyRequestArray($verify);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content'     => $content,
                'gateway'     => $this->gateway,
                'payment_id'  => $input['payment']['id'],
            ]);

        $verify->verifyResponse = $response;

        $verify->verifyResponseBody = $response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getPaymentVerifyRequestArray($verify)
    {
        $input = $verify->input;
        $gatewayEntity = $verify->payment;

        $request = $this->getStandardRequestArray(
            [
                Fields::ID  => $gatewayEntity['gateway_payment_id'],
            ],
            'get');

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'           => $request,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
            ]);

        return $request;
    }

    protected function verifyPayment(Verify $verify): string
    {
        $content = $verify->verifyResponseContent;

        if (isset($content['error']) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                $content['error']['code'],
                $content['error']['description']);
        }

        $status = VerifyResult::STATUS_MATCH;

        $verify->apiSuccess = true;
        $verify->gatewaySuccess = false;

        if ($content['status'] === Status::COMPLETED)
        {
            $verify->gatewaySuccess = true;
        }

        $input = $verify->input;

        if ($verify->gatewaySuccess === true)
        {
            $paymentAmount = $input['payment']['amount'];

            $actualAmount  = $content[Fields::AMOUNT];

            $verify->amountMismatch = ($paymentAmount !== $actualAmount);
        }

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

    public function refund(array $input)
    {
        parent::refund($input);

        throw new Exception\LogicException(
            'Refund not implemented');
    }

    protected function checkResponseStatus(array $p2p, string $successStatus)
    {
        if ($p2p[Fields::STATUS] !== $successStatus)
        {
            $errorCode = ResponseErrorCode::getMappedErrorCode($p2p[Fields::INTERNAL_ERROR_CODE]);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $p2p[Fields::INTERNAL_ERROR_CODE],
                $p2p[Fields::ERROR_DESCRIPTION]);
        }
    }
}
