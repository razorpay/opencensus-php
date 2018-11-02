<?php

namespace RZP\Gateway\Upi\Yesbank;

use Request;
use Carbon\Carbon;
use RZP\Exception;
use Requests_Hooks;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Encryption\PGPEncryption;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\BharatQr\GatewayResponseParams;

class Gateway extends Base\Gateway
{
    use RequestTrait;
    use AuthorizeFailed;

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

        Fields::STATUSCODE              => Entity::STATUS_CODE,
        Fields::RRN                     => Entity::NPCI_REFERENCE_ID,
        Fields::TXNID                   => Entity::NPCI_TXN_ID,
    ];

    protected $forceFillable = [
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
    public function callback(array $input)
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

    public function getPaymentIdFromServerCallback($input): string
    {
        return $input['orderno'];
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

    protected function checkResponseStatus(array $p2p, string $successStatus)
    {
        if ($p2p[Fields::STATUSCODE] !== $successStatus)
        {
            $errorCode = ResponseErrorCode::getMappedErrorCode($p2p[Fields::INTERNAL_ERROR_CODE]);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $p2p[Fields::INTERNAL_ERROR_CODE],
                $p2p[Fields::ERROR_DESCRIPTION]);
        }
    }
}
