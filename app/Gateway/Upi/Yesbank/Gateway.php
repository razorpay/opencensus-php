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
    /**
     * Default request timeout duration in seconds.
     * @var  integer
     */
    const TIMEOUT = 20;

    const ACQUIRER = 'yesbank';

    const HASH_ALGO = 'sha256';

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
        Entity::GATEWAY_MERCHANT_ID     => Entity::MERCHANT_REFERENCE,


        Fields::STATUSCODE              => Entity::STATUS_CODE,
        Fields::YBLREFNO                => Entity::GATEWAY_MERCHANT_ID,
        Fields::NPCI_TXN_ID             => Entity::NPCI_TXN_ID,
        Fields::CUST_REF_ID             => Entity::NPCI_REFERENCE_ID,
        Fields::PAYEE_ACC_NAME          => Entity::NAME,
        Fields::PAYEE_ACC_NO            => Entity::ACCOUNT_NUMBER,
        Fields::PAYEE_IFSC              => Entity::IFSC
    ];

    /**
     * Authorizes a payment using UPI Gateway
     * @param  array  $input
     * @return boolean
     */
    public function authorize(array $input)
    {
        if ($this->isLiveMode() === true)
        {
            throw new Exception\LogicException('Live payment authorize not available on UPI Yesbank');
        }

        parent::authorize($input);

        $attributes = $this->getGatewayEntityAttributes($input);

        $payment = $this->createGatewayPaymentEntity($attributes);

        return [
            'data'   => [
                'vpa'   => ''
            ]
        ];
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
    public function callback(array $input) :array
    {
        if ($this->isLiveMode() === true)
        {
            throw new Exception\LogicException('Live payment callback not available on UPI Yesbank');
        }

        parent::callback($input);

        $gatewayPayment = $this->getRepository()->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $expectedAmount = $input['payment']['amount'];
        $actualAmount   = $input['gateway'][Fields::AMOUNT];

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->checkResponseStatus($input['gateway'], Status::SUCCESS);

        $gatewayPayment->fill($this->getMappedAttributes($input['gateway']));

        $gatewayPayment->saveOrFail();

        return [
            'acquirer' => [
                Payment\Entity::VPA => $gatewayPayment->getVpa()
            ]
        ];
    }


    /**
     * We only store the VPA, bank and provider because the rest of the fields
     * are filled by the response
     * @param  array  $input ,
     * @return Array
     */
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

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $unprocessedRefunds = $this->getUnprocessedRefunds();

        $processedRefunds = $this->getProcessedRefunds();

        if (in_array($input['refund']['id'], $unprocessedRefunds) === true)
        {
            return false;
        }

        if (in_array($input['refund']['id'], $processedRefunds) === true)
        {
            return true;
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $repo = $this->getRepository();

        $gatewayPayment = $repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $rrn = $gatewayPayment[Entity::NPCI_REFERENCE_ID];
        $vpa = $input['payment']['vpa'];

        $content = [
            Fields::PGMERCHANT_ID       => $this->getGatewayMerchantId(),
            Fields::ORDERNO             => $input['refund']['id'],
            Fields::TXN_NOTE            => 'Refund for RRN : ' . $rrn,
            Fields::AMOUNT              => (string) ($input['refund']['amount'] / 100),
            Fields::CURRENCY            => 'INR',
            Fields::PAYMENT_TYPE        => 'P2P',
            Fields::TXN_TYPE            => 'Pay',
            Fields::MCC                 => '7399',
            Fields::EXP_TIME            => '',
            Fields::PAYEE_ACC_NO        => '',
            Fields::PAYEE_AADHAR        => '',
            Fields::PAYEE_MB_NO         => '',
            Fields::PAYEE_VPA           => $vpa,
            Fields::SUBMERCHANT_ID      => '',
            Fields::WHITELISTED_ACC     => '',
            Fields::PAYEE_MMID          => '',
            Fields::REF_URL             => 'https://razorpay.com',
            Fields::TRANSFER_TYPE       => 'UPI',
            Fields::PAYEE_NAME          => 'Razorpay Customer',
            Fields::PAYEE_ADDRESS       => '',
            Fields::PAYEE_EMAIL         => '',
            Fields::PAYER_ACCNO         => '',
            Fields::PAYER_IFSC          => '',
            Fields::PAYER_MB_NO         => '',
            Fields::PAYYE_VPA_TYPE      => 'VPA',
            Fields::MCC                 => '172.21.14.99',
            Fields::ADD1                => '',
            Fields::ADD2                => '',
            Fields::ADD3                => '',
            Fields::ADD4                => '',
            Fields::ADD5                => '',
            Fields::ADD6                => '',
            Fields::ADD7                => '',
            Fields::ADD8                => '',
            Fields::ADD9                => '',
            Fields::ADD10               => '',
        ];

        $decryptedContent = implode('|', $content);

        $encrypted = $this->encryptString($decryptedContent);

        $content = [
            Fields::PGMERCHANTID    => $this->getGatewayMerchantId(),
            Fields::REQUESTMSG      => $encrypted,
        ];

        $traceRequest = $request = $this->getStandardRequestArray($content, 'POST', 'refund');

        $traceRequest['decrypted_content'] = $decryptedContent;
        $traceRequest['headers'] = $request['headers'] = [
            'Content-Type' => 'application/json'
        ];

        $this->traceGatewayPaymentRequest($traceRequest, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $responseArray = $this->jsonToArray($response->body);

        \mc::dd($responseArray);
        $this->traceGatewayPaymentResponse($responseArray, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        if (isset($responseArray['data']) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                $responseArray[Fields::ERROR_CODE],
                $responseArray['message']);
        }

        $decryptedResp = $this->getMgDecryptedContent($responseArray['data']);

        $this->traceGatewayPaymentResponse($decryptedResp, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        if ((isset($decryptedResp[Fields::RESPONSE_CODE]) === false) or
            ($decryptedResp[Fields::RESPONSE_CODE] !== '00'))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                $decryptedResp[Fields::ERROR_CODE] ?? '',
                $decryptedResp['message'] ?? '');
        }

        $decryptedResp['received'] = true;

        $this->createGatewayPaymentEntity($decryptedResp, Action::REFUND);
    }

    public function payoutVpa(array $input)
    {
        parent::action($input, Action::PAYOUT);

        $input[Entity::MERCHANT_REFERENCE] = time() . random_integer(4);

        $request = $this->getPayOutRequest($input);

        $attributes = $this->getGatewayEntityAttributes($input, Action::PAYOUT,Base\Type::PAY);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $decryptedContent = implode('|', $request);

        $encrypted = $this->encrypt($decryptedContent);

        $content = [
            Fields::PGMERCHANTID    => $this->getGatewayMerchantId(),
            Fields::REQUESTMSG      => $encrypted,
        ];

        $traceRequest = $request = $this->getStandardRequestArray($content, 'POST', 'payout');

        $traceRequest['decryptedContent'] = $decryptedContent;

        $traceRequest['headers'] = $request['headers'] = [
            'Content-Type' => 'application/json'
        ];

        $this->traceGatewayPaymentRequest($traceRequest, $input, TraceCode::VPA_PAYOUT_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $responseArray = $this->parseGatewayResponse($response->body, Action::PAYOUT);

        $responseArray[Entity::RECEIVED] = 1;

        $this->updateGatewayPaymentEntity($gatewayPayment, $responseArray);

        $this->checkResponseForError($responseArray);
    }

    protected function getPayoutRequest(array $input)
    {
        $content = [
            Fields::PGMERCHANT_ID       => $this->getGatewayMerchantId(),
            Fields::ORDERNO             => $input[Entity::MERCHANT_REFERENCE],
            Fields::TXN_NOTE            => 'Payout to customer VPA',
            Fields::AMOUNT              => $this->formatAmount($input['amount']),
            Fields::CURRENCY            => Currency::INR,
            Fields::PAYMENT_TYPE        => Type::P2P,
            Fields::TXN_TYPE            => Type::PAY,
            Fields::MCC                 => $this->getMerchantCategoryCode($input),
            Fields::EXP_TIME            => '',
            Fields::PAYEE_ACC_NO        => '',
            Fields::PAYEE_AADHAR        => '',
            Fields::PAYEE_MB_NO         => '',
            Fields::PAYEE_VPA           => $input[Entity::VPA],
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
s($content);
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

    protected function processPayoutResponse()
    {

    }

    protected function createGatewayPaymentEntity($attributes, $action = null)
    {s($attributes);
        $attr = $this->getMappedAttributes($attributes);
s($attr);
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

    protected function parseGatewayResponse($responseBody, $type = Action::COLLECT)
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

    protected function checkResponseForError(array $responseArray)
    {
        if ($responseArray[Fields::STATUSCODE] !== Status::SUCCESS)
        {
            $gatewayErrorCode = $responseArray[Fields::ERROR_CODE];

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
}
