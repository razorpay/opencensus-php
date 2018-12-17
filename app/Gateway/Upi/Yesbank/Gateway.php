<?php

namespace RZP\Gateway\Upi\Yesbank;

use Request;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Upi\Mindgate;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;

class Gateway extends Mindgate\Gateway
{
    use RequestTrait;

    /**
     * Default request timeout duration in seconds.
     * @var  integer
     */
    const TIMEOUT = 40;

    const ACQUIRER = 'yesb';

    protected $gateway = 'upi_yesbank';

    const CERTIFICATE_DIRECTORY_NAME = 'cert_dir_name';

    protected $map = [
        Entity::VPA                     => Entity::VPA,
        Entity::EXPIRY_TIME             => Entity::EXPIRY_TIME,
        Entity::PROVIDER                => Entity::PROVIDER,
        Entity::BANK                    => Entity::BANK,
        Entity::TYPE                    => Entity::TYPE,
        Entity::RECEIVED                => Entity::RECEIVED,
        Entity::PAYMENT_ID              => Entity::PAYMENT_ID,
        Entity::MERCHANT_REFERENCE      => Entity::MERCHANT_REFERENCE,

        Fields::YBLREFNO                => Entity::GATEWAY_PAYMENT_ID,
        Fields::NPCI_TXN_ID             => Entity::NPCI_TXN_ID,
        Fields::CUST_REF_ID             => Entity::NPCI_REFERENCE_ID,
        Fields::PAYEE_ACC_NAME          => Entity::NAME,
        Fields::PAYEE_ACC_NO            => Entity::ACCOUNT_NUMBER,
        Fields::PAYEE_IFSC              => Entity::IFSC,
        Fields::STATUSCODE              => Entity::STATUS_CODE,
    ];

    public function authorize(array $input)
    {
        throw new Exception\LogicException(
            'Live payment authorize not available on UPI Yesbank');
    }

    public function callback(array $input) :array
    {
        throw new Exception\LogicException(
            'Live payment callback not available on UPI Yesbank');
    }

    public function refund(array $input)
    {
        throw new Exception\LogicException(
            'Live payment refund not available on UPI Yesbank');
    }

    public function verify(array $input)
    {
        throw new Exception\LogicException(
            'Live payment verify not available on UPI Yesbank');
    }

    public function payout(array $input)
    {
        parent::action($input, Action::PAYOUT);

        $gatewayEntity = $this->repo->fetchByMerchantReference($this->input[Fields::GATEWAY_INPUT][Fields::REF_ID]);

        if ($gatewayEntity !== null)
        {
            // a payout with this ref_id already exists
            $this->trace->error(
                TraceCode::DUPLICATE_PAYOUT_REQUEST,
                $input
            );

            return $this->getFailedResponse($gatewayEntity, 'RZP_DUPLICATE_PAYOUT');
        }

        $request = $this->getPayoutRequest($input);

        $attributes = $this->getGatewayEntityAttributes($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $decryptedContent = implode('|', $request);

        $encrypted = $this->encrypt($decryptedContent);

        $content = [
            Fields::PGMERCHANTID => $this->getGatewayMerchantId($input),
            Fields::REQUESTMSG   => $encrypted,
        ];

        $traceRequest = $request = $this->getStandardRequestArray($content);

        $traceRequest['decryptedContent'] = $decryptedContent;

        $this->traceGatewayPaymentRequest($traceRequest, $input, TraceCode::VPA_PAYOUT_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $responseArray = $this->parseGatewayResponse($response->body, Action::PAYOUT);

        $responseArray[Entity::RECEIVED] = 1;

        $this->updateGatewayPaymentEntity($gatewayPayment, $responseArray);

        return $this->generateResponse($responseArray, $gatewayPayment);
    }

    public function payoutVerify(array $input)
    {
        parent::action($input, Action::PAYOUT_VERIFY);

        $gatewayEntity = $this->repo->findByMerchantReference($input[Fields::GATEWAY_INPUT][Fields::REF_ID]);

        $request = $this->getPayoutVerifyRequest($input, $gatewayEntity);

        $decryptedContent = implode('|', $request);

        $encrypted = $this->encrypt($decryptedContent);

        $content = [
            Fields::PGMERCHANTID    => $this->getGatewayMerchantId($input),
            Fields::REQUESTMSG      => $encrypted,
        ];

        $traceRequest = $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($traceRequest, $input, TraceCode::VPA_PAYOUT_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $responseArray = $this->parseGatewayResponse($response->body, Action::PAYOUT_VERIFY);

        if ($this->getIntegerFormattedAmount($responseArray[Fields::AMOUNT]) !==
            $this->getIntegerFormattedAmount($gatewayEntity[Entity::AMOUNT] / 100))
        {
            $this->trace->error(
                TraceCode::GATEWAY_FATAL_ERROR,
                $input
            );

            return $this->getFailedResponse($gatewayEntity, 'RZP_AMOUNT_MISMATCH');
        }

        if ($responseArray[Fields::ORDERNO] !== $gatewayEntity[Entity::MERCHANT_REFERENCE])
        {
            $this->trace->error(
                TraceCode::GATEWAY_FATAL_ERROR,
                $input
            );

            return $this->getFailedResponse($gatewayEntity, 'RZP_REF_ID_MISMATCH');
        }

        $this->updateGatewayPaymentEntity($gatewayEntity, $responseArray);

        return $this->generateResponse($responseArray, $gatewayEntity);
    }

    protected function getGatewayEntityAttributes( array $input,
        string $action = Action::AUTHORIZE,
        string $type = Type::PAY): array
    {
        return [
            Entity::VPA                 => $input[Fields::GATEWAY_INPUT][Entity::VPA],
            Entity::TYPE                => $type,
            Entity::ACTION              => $action,
            Entity::MERCHANT_REFERENCE  => $input[Fields::GATEWAY_INPUT][Fields::REF_ID],
            Entity::AMOUNT              => $input[Fields::GATEWAY_INPUT][Entity::AMOUNT],
            Entity::PAYMENT_ID          => $input[Fields::GATEWAY_INPUT][Fields::REF_ID],
        ];
    }

    protected function getPayoutRequest(array $input)
    {
        $content = [
            Fields::PGMERCHANT_ID       => $this->getGatewayMerchantId($input),
            Fields::ORDERNO             => $input[Fields::GATEWAY_INPUT][Fields::REF_ID],
            Fields::TXN_NOTE            => 'Payout to Razorpay customer VPA',
            Fields::AMOUNT              => $this->formatAmount($input[Fields::GATEWAY_INPUT][Fields::AMOUNT]),
            Fields::CURRENCY            => Currency::INR,
            Fields::PAYMENT_TYPE        => Type::P2P,
            Fields::TXN_TYPE            => Type::PAY,
            Fields::MCC                 => $this->getMerchantCategoryCode($input),
            Fields::EXP_TIME            => '',
            Fields::PAYEE_ACC_NO        => '',
            Fields::PAYEE_IFSC          => '',
            Fields::PAYEE_AADHAR        => '',
            Fields::PAYEE_MB_NO         => '',
            Fields::PAYEE_VPA           => $input[Fields::GATEWAY_INPUT][Entity::VPA],
            Fields::SUBMERCHANT_ID      => '',
            Fields::WHITELISTED_ACC     => '',
            Fields::PAYEE_MMID          => '',
            Fields::REF_URL             => 'https://razorpay.com',
            Fields::TRANSFER_TYPE       => Type::UPI,
            Fields::PAYEE_NAME          => 'Razorpay Customer',
            Fields::PAYEE_ADDRESS       => '',
            Fields::PAYEE_EMAIL         => '',
            Fields::PAYER_ACCNO         => '',
            Fields::PAYER_IFSC          => '',
            Fields::PAYER_MB_NO         => '',
            Fields::PAYYE_VPA_TYPE      => Type::VPA,
            Fields::ADD1                => '',
            Fields::ADD2                => '',
            Fields::ADD3                => '',
            Fields::ADD4                => '',
            Fields::ADD5                => '',
            Fields::ADD6                => '',
            Fields::ADD7                => '',
            Fields::ADD8                => '',
            Fields::ADD9                => 'NA',
            Fields::ADD10               => 'NA',
        ];

        return $content;
    }

    protected function getGatewayMerchantId(array $input)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $input['terminal']['gateway_merchant_id'];
    }

    /**
     * Returns the MCC code, based on the merchant category
     * @param  array  $input
     * @return string 4 digit integer as string.
     */
    protected function getMerchantCategoryCode(array $input)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_mcc'];
        }

        return $input['merchant']['category'];
    }

    /**
     * This is the key used to encrypt requests
     * @return string public key
     */
    protected function getEncryptionKey()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_key'];
        }

        return $this->config['live_merchant_key'];
    }

    protected function traceGatewayPaymentRequest(array $request, $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        $this->trace->info(
            $traceCode,
            [
                'payment_id' => $input[Fields::GATEWAY_INPUT][Fields::REF_ID],
                'request'    => $request,
                'gateway'    => $this->gateway,
            ]);
    }

    protected function parseGatewayResponse($responseBody, $type = Action::PAYOUT)
    {
        $this->trace->info(TraceCode::GATEWAY_RESPONSE, [
            'body'              => $responseBody,
            'encrypted'         => true,
            'gateway'           => $this->gateway,
            'type'              => $type
        ]);

        $response = $this->decrypt($responseBody);

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

    protected function checkResponseForError(array $responseArray, $gatewayEntity)
    {
        switch ($responseArray[Fields::STATUSCODE])

        {
            case Status::SUCCESS:
                break;

            case Status::VERIFY_SUCCESS:
                break;

            case Status::TIMEOUT:
                // time out status indicate the actual state of payout, whether it was a success
                // or a return of payout has been initiated, so we are storing the actual
                // time out code. later action can be taken on these codes appropriately.
                $attributes[Fields::STATUSCODE] = $responseArray[Fields::TIMED_OUT_TXN_STATUS];

                $this->updateGatewayPaymentEntity($gatewayEntity, $attributes);

            default:
                $gatewayErrorCode = $responseArray[Fields::RESPCODE];

                $apiErrorCode = ResponseCodeMap::getApiErrorCode($gatewayErrorCode);

                $gatewayErrorCodeDesc = ResponseCodes::getResponseMessage($gatewayErrorCode);

                if ($gatewayErrorCodeDesc === 'Unknown Gateway Response Code')
                {
                    // many times gateway will not be throwing a mapped error code, so sending the description
                    // in such cases to FTA service, since description will be helpful under such cases
                    $gatewayErrorCodeDesc = $responseArray[Fields::STATUSDESC];
                }
                throw new Exception\GatewayErrorException(
                    $apiErrorCode,
                    $gatewayErrorCode,
                    $gatewayErrorCodeDesc,
                    [
                        'gateway'  => $this->gateway,
                        'response' => $responseArray,
                    ]
                );
        }
    }

    protected function getPayoutVerifyRequest($input, $gatewayEntity)
    {
        $request = [
            Fields::PGMERCHANT_ID       => $this->getGatewayMerchantId($input),
            Fields::ORDER_ID            => $input[Fields::GATEWAY_INPUT][Fields::REF_ID],
            Fields::YBLREFNO            => $gatewayEntity[Entity::GATEWAY_PAYMENT_ID],
            Fields::CUST_REF_ID         => $gatewayEntity[Entity::NPCI_REFERENCE_ID],
            Fields::REFERENCE_ID        => '',
            Fields::ADD1                => '',
            Fields::ADD2                => '',
            Fields::ADD3                => '',
            Fields::ADD4                => '',
            Fields::ADD5                => '',
            Fields::ADD6                => '',
            Fields::ADD7                => '',
            Fields::ADD8                => '',
            Fields::ADD9                => 'NA',
            Fields::ADD10               => 'NA',
        ];

        return $request;
    }

    protected function getGatewayCertDirName()
    {
        return $this->config[self::CERTIFICATE_DIRECTORY_NAME];
    }

    protected function generateResponse($responseArray, $gatewayPayment)
    {
        $response = [];

        try
        {
            $this->checkResponseForError($responseArray, $gatewayPayment);

            $response = [
                Fields::SUCCESS                     => true,
                Fields::ERROR_MESSAGE               => null,
                Fields::BANK_REFERENCE_NUMBER       => $gatewayPayment[Entity::GATEWAY_PAYMENT_ID],
                Fields::STATUS_CODE                 => $responseArray[Fields::RESPCODE],
                Fields::SUB_STATUS_TEXT             => $responseArray[Fields::STATUSDESC],
                Fields::REQUEST_REFERENCE_NUMBER    => $gatewayPayment[Entity::MERCHANT_REFERENCE],
                Fields::UNIQUE_RESPONSE_NUMBER      => $gatewayPayment[Entity::NPCI_REFERENCE_ID],
            ];
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception);

            $error = $exception->getError()->getAttributes();

            $response = [
                Fields::SUCCESS                     => false,
                Fields::ERROR_MESSAGE               => $error['internal_error_code'],
                Fields::BANK_REFERENCE_NUMBER       => $gatewayPayment[Entity::GATEWAY_PAYMENT_ID],
                Fields::STATUS_CODE                 => $error['gateway_error_code'],
                Fields::SUB_STATUS_TEXT             => $error['gateway_error_desc'],
                Fields::REQUEST_REFERENCE_NUMBER    => $gatewayPayment[Entity::MERCHANT_REFERENCE],
                Fields::UNIQUE_RESPONSE_NUMBER      => $gatewayPayment[Entity::NPCI_REFERENCE_ID],
            ];
        }

        return $response;
    }

    protected function getFailedResponse($gatewayPayment, $errorCode)
    {
        $apiErrorCode = ResponseCodeMap::getApiErrorCode($errorCode);

        $gatewayErrorCodeDesc = ResponseCodes::getResponseMessage($errorCode);

        $response = [
            Fields::SUCCESS                     => false,
            Fields::ERROR_MESSAGE               => $apiErrorCode,
            Fields::BANK_REFERENCE_NUMBER       => $gatewayPayment[Entity::GATEWAY_PAYMENT_ID],
            Fields::STATUS_CODE                 => $errorCode,
            Fields::SUB_STATUS_TEXT             => $gatewayErrorCodeDesc,
            Fields::REQUEST_REFERENCE_NUMBER    => $gatewayPayment[Entity::MERCHANT_REFERENCE],
            Fields::UNIQUE_RESPONSE_NUMBER      => $gatewayPayment[Entity::NPCI_REFERENCE_ID],
        ];

        return $response;
    }
}
