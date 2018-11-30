<?php

namespace RZP\Gateway\Upi\Yesbank;

use Request;
use Carbon\Carbon;
use Requests_Hooks;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Mindgate;
use RZP\Constants\Mode;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\UniqueIdEntity;

class Gateway extends Mindgate\Gateway
{
    use RequestTrait;
    /**
     * Default request timeout duration in seconds.
     * @var  integer
     */
    const TIMEOUT = 20;

    const ACQUIRER = 'yesbank';

    protected $gateway = 'upi_yesbank';

    const CERTIFICATE_DIRECTORY_NAME = 'cert_dir_name';

    protected $map = [
        Entity::VPA                     => Entity::VPA,
        Entity::EXPIRY_TIME             => Entity::EXPIRY_TIME,
        Entity::PROVIDER                => Entity::PROVIDER,
        Entity::BANK                    => Entity::BANK,
        Entity::TYPE                    => Entity::TYPE,
        Entity::RECEIVED                => Entity::RECEIVED,
        Entity::AMOUNT                  => Entity::AMOUNT,
        Entity::PAYMENT_ID              => Entity::PAYMENT_ID,
        Entity::MERCHANT_REFERENCE      => Entity::MERCHANT_REFERENCE,

        Fields::YBLREFNO                => Entity::GATEWAY_PAYMENT_ID,
        Fields::NPCI_TXN_ID             => Entity::NPCI_TXN_ID,
        Fields::CUST_REF_ID             => Entity::NPCI_REFERENCE_ID,
        Fields::PAYEE_ACC_NAME          => Entity::NAME,
        Fields::PAYEE_ACC_NO            => Entity::ACCOUNT_NUMBER,
        Fields::PAYEE_IFSC              => Entity::IFSC,
        Fields::STATUSCODE              => Entity::STATUS_CODE,
        Fields::TIMED_OUT_TXN_STATUS    => Entity::STATUS_CODE
    ];

    public function authorize(array $input)
    {
        throw new Exception\LogicException('Live payment authorize not available on UPI Yesbank');
    }

    public function callback(array $input) :array
    {
        throw new Exception\LogicException('Live payment callback not available on UPI Yesbank');
    }

    public function refund(array $input)
    {
        throw new Exception\LogicException('Live payment refund not available on UPI Yesbank');
    }

    public function verify(array $input)
    {
        throw new Exception\LogicException('Live payment verify not available on UPI Yesbank');
    }

    public function payoutVpa(array $input)
    {
        parent::action($input, Action::PAYOUT);

        $input[Entity::MERCHANT_REFERENCE] = time() . random_integer(4);

        $request = $this->getPayOutRequest($input);

        $attributes = $this->getGatewayEntityAttributes($input, Action::PAYOUT,Base\Type::PAY);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $decryptedContent = implode('|', $request);
s($decryptedContent);
        $encrypted = $this->encrypt($decryptedContent);
s($encrypted);
        $content = [
            Fields::PGMERCHANTID    => $this->getGatewayMerchantId(),
            Fields::REQUESTMSG      => $encrypted,
        ];sd($content);

        $traceRequest = $request = $this->getStandardRequestArray($content, 'POST', 'payout');

        $traceRequest['decryptedContent'] = $decryptedContent;

        $traceRequest['headers'] = $request['headers'] = [
            'Content-Type' => 'application/json',
        ];

        $this->traceGatewayPaymentRequest($traceRequest, $input, TraceCode::VPA_PAYOUT_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $responseArray = $this->parseGatewayResponse($response->body, Action::PAYOUT);

        $responseArray[Entity::RECEIVED] = 1;

        $this->updateGatewayPaymentEntity($gatewayPayment, $responseArray);

        $this->checkResponseForError($responseArray, $gatewayPayment);

        return [
          'vpa'                => $input[Entity::VPA],
          'merchant_reference' => $input[Entity::MERCHANT_REFERENCE]
        ];
    }

    public function payoutVpaVerify(array $input)
    {
        parent::action($input, Action::PAYOUT_VERIFY);

        $gatewayEntity = $this->repo->fetchByMerchantReference($input[Entity::MERCHANT_REFERENCE]);

        if ($gatewayEntity === null)
        {
            throw new Exception\LogicException(
                'No payout exists with the merchant reference',
                null,
                [
                    'merchant_reference' => $input[Entity::MERCHANT_REFERENCE]
                ]
            );
        }
        else
        {
            $request = $this->getPayoutVerifyRequest($input, $gatewayEntity);

            $decryptedContent = implode('|', $request);

            $encrypted = $this->encrypt($decryptedContent);

            $content = [
                Fields::PGMERCHANTID    => $this->getGatewayMerchantId(),
                Fields::REQUESTMSG      => $encrypted,
            ];

            $traceRequest = $request = $this->getStandardRequestArray($content, 'POST', 'verify_payout');

            $traceRequest['decryptedContent'] = $decryptedContent;

            $traceRequest['headers'] = $request['headers'] = [
                'Content-Type' => 'application/json'
            ];

            $this->traceGatewayPaymentRequest($traceRequest, $input, TraceCode::VPA_PAYOUT_VERIFY_REQUEST);

            $response = $this->sendGatewayRequest($request);

            $responseArray = $this->parseGatewayResponse($response->body, Action::PAYOUT_VERIFY);

            $this->assertAmount($this->getIntegerFormattedAmount($responseArray[Fields::AMOUNT]) ,
                $this->getIntegerFormattedAmount($gatewayEntity[Entity::AMOUNT]));

            if ($responseArray[Fields::ORDERNO] !== $gatewayEntity[Entity::MERCHANT_REFERENCE])
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_VALIDATION_ERROR,
                    null,
                    null,
                    [
                      'response' => $responseArray,
                      'input'    => $input,
                    ]
                );
            }

            $this->updateGatewayPaymentEntity($gatewayEntity, $responseArray);

            $this->checkResponseForError($responseArray, $gatewayEntity);
        }
    }

    protected function getGatewayEntityAttributes( array $input,
        string $action = Action::AUTHORIZE,
        string $type = Base\Type::PAY): array
    {
        return [
            Entity::VPA                 => $input[Entity::VPA],
            Entity::TYPE                => $type,
            Entity::ACTION              => $action,
            Entity::MERCHANT_REFERENCE  => $input[Entity::MERCHANT_REFERENCE],
            Entity::AMOUNT              => $input[Entity::AMOUNT],
            // Upi table has a dependency on payments table but currently no payment is present in the context
            // so adding a dummy value till there is a platform for payout
            Entity::PAYMENT_ID          => $input[Entity::MERCHANT_REFERENCE],
        ];
    }

    protected function getPayoutRequest(array $input)
    {
        $content = [
            Fields::PGMERCHANT_ID       => $this->getGatewayMerchantId(),
            Fields::ORDERNO             => $input[Entity::MERCHANT_REFERENCE],
            Fields::TXN_NOTE            => 'Payout to Razorpay customer VPA',
            Fields::AMOUNT              => $this->formatAmount($input['amount']),
            Fields::CURRENCY            => Currency::INR,
            Fields::PAYMENT_TYPE        => Type::P2P,
            Fields::TXN_TYPE            => Type::PAY,
            Fields::MCC                 => $this->getMerchantCategoryCode($input),
            Fields::EXP_TIME            => '',
            Fields::PAYEE_ACC_NO        => '',
            Fields::PAYEE_IFSC          => '',
            Fields::PAYEE_AADHAR        => '',
            Fields::PAYEE_MB_NO         => '',
            Fields::PAYEE_VPA           => $input[Entity::VPA],
            Fields::SUBMERCHANT_ID      => '',
            Fields::WHITELISTED_ACC     => '',
            Fields::PAYEE_MMID          => '',
            Fields::REF_URL             => 'https://razorpay.com', // not sure about this
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

    protected function getGatewayMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $this->config['live_merchant_id'];
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

        return $this->config['live_mcc'];
    }

    /**
     * This is the key used to encrypt requests
     * @return string public key
     */
    protected function getEncryptionKey()
    {
        return $this->config['test_merchant_key'];
    }

    protected function traceGatewayPaymentRequest(array $request, $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        $this->trace->info(
            TraceCode::VPA_PAYOUT_REQUEST,
            [
                'input'     => $input,
                'request'   => $request,
                'gateway'   => $this->gateway,
            ]);
    }

    protected function createGatewayPaymentEntity($attributes, $action = null)
    {
        $attr = $this->getMappedAttributes($attributes);

        $entity = $this->getNewGatewayPaymentEntity();

        $action = $action ?? $this->action;

        $entity->setAction($action);

        $entity->setAcquirer(self::ACQUIRER);

        $entity->setGateway($this->gateway);

        $entity->generate($attr);

        $entity->fill($attr);

        $this->repo->saveOrFail($entity);

        return $entity;
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

    protected function checkResponseForError(array $responseArray, $gatewayEntity)
    {
        switch ($responseArray[Fields::STATUSCODE])
        {
            case Status::SUCCESS:
                break;

            case Status::TIMEOUT:
                // time out status indicate the actual state of payout, whether it was a success
                // or a return of payout has been initiated, so we are storing the actual
                // time out code. later action can be taken on these codes appropriately.
                $attributes[Entity::STATUS_CODE] = $responseArray[Fields::TIMED_OUT_TXN_STATUS];

                $this->updateGatewayPaymentEntity($gatewayEntity, $attributes);
                break;

            default:
                $gatewayErrorCode = $responseArray[Fields::RESPCODE];

                $apiErrorCode = ResponseCodeMap::getApiErrorCode($gatewayErrorCode);

                $gatewayErrorCodeDesc = ResponseCodes::getResponseMessage($gatewayErrorCode);

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
            Fields::PGMERCHANT_ID       => $this->getGatewayMerchantId(),
            Fields::ORDER_ID            => $input[Entity::MERCHANT_REFERENCE],
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
}
