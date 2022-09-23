<?php

namespace RZP\Models\QrPayment;

use RZP\Base\Common;
use RZP\Constants\Es;
use RZP\Constants\HyperTrace;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\BharatQr;
use RZP\Models\BankTransfer;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as QrV2;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Status as QrV2Status;
use RZP\Models\QrPaymentRequest;
use RZP\Models\QrPaymentRequest\Type;
use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use Illuminate\Support\Facades\Cache;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    const UNPROCESSED_RESPONSE = 'unprocessed';
    const RAZORPAY_PAYMENT_ID  = 'razorpay_payment_id';

    public static function getCacheKeyForQRCodeId(string $qrCodeId): string
    {
        QrV2::verifyIdAndSilentlyStripSign($qrCodeId);

        return 'payment:qr_code.polling.' . $qrCodeId . '.status';
    }

    public function fetchPaymentsForQrCode($input, $id)
    {
        $input[Entity::QR_CODE_ID] = $id;

        return $this->fetchMultiplePayments($input);
    }

    public function fetchMultiplePayments($input)
    {
        (new Fetch)->processFetchParams($input);

        $qrPaymentIds = (new EsRepository('qr_payment'))->buildQueryAndSearch($input, $this->merchant->getId());

            $qrPaymentIds = array_map(
                                function($res) {
                                    return $res[ES::_SOURCE] ?? [Common::ID => $res[ES::_ID]];
                                },
                                $qrPaymentIds[ES::HITS][ES::HITS]);

        return Tracer::inspan(['name' => HyperTrace::QR_PAYMENT_FETCH_MULTIPLE_PAYMENTS], function () use ($qrPaymentIds) {
            return $this->fetchPaymentsForQrPaymentIds($qrPaymentIds);
        });

    }

    public function fetchPaymentsForQrPaymentIds(array $qrPaymentIds)
    {
        $paymentIds = $this->repo->qr_payment->getPaymentIdsForQrPaymentIds($qrPaymentIds);

        return $this->repo->payment->getPaymentsSortedByCreatedAt($paymentIds)->toArrayPublic();
    }

    /**
     * @param array       $input
     * @param string|null $provider
     * @param             $requestPayload
     * @param             $bankAccount
     */
    public function processBankTransfer(array $input, $provider, $requestPayload, $bankAccount)
    {
        try
        {
            (new BankTransfer\Validator())->validateInput('create' ,$input);

            $gatewayResponse = $this->modifyBankTransferInput($input, $bankAccount, $provider, $requestPayload);

            $qrPaymentRequest = (new QrPaymentRequest\Service())->create($gatewayResponse, Type::BHARAT_QR);

            $terminal = (new Payment\Processor\TerminalProcessor())->getTerminalForQrBankTransfer($bankAccount, $provider);

            $valid = (new Core)->processPayment($gatewayResponse, $terminal, $qrPaymentRequest);
        }
        catch (\Exception $ex)
        {
            $valid = false;

            $this->trace->traceException($ex);
        }

        return [
            'valid'          => $valid,
            'message'        => null,
            'transaction_id' => $input['transaction_id'] ?? '',
        ];
    }

    private function modifyBankTransferInput(array $modifiedInput, $bankAccount, $provider, $requestPayload)
    {
        $qrCodeId = $bankAccount->qrCode->getId();

        $gatewayResponse['callback_data'] = $modifiedInput;

        $gatewayResponse['original_callback_data'] = $requestPayload;

        $amount = (int) number_format(($modifiedInput['amount'] * 100), 0, '.', '');

        $gatewayResponse['qr_data'] = [
            BharatQr\GatewayResponseParams::AMOUNT                => $amount,
            BharatQr\GatewayResponseParams::METHOD                => Payment\Method::BANK_TRANSFER,
            BharatQr\GatewayResponseParams::MERCHANT_REFERENCE    => $qrCodeId,
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID => $modifiedInput[BankTransfer\Entity::REQ_UTR],
            BharatQr\GatewayResponseParams::GATEWAY               => Payment\Gateway::$bankTransferProviderGateway[$provider],
        ];

        return $gatewayResponse;
    }

    public function fetchPaymentStatusByQrCodeId(string $qrCodeId)
    {
        [$qrCodeStatus, $paymentId] = $this->getQrCodeStatusAndPaymentIdFromQRCodeId($qrCodeId);

        if ($paymentId === '')
        {
            if ($qrCodeStatus === QrV2Status::CLOSED) {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED);
            }

            return [Payment\Entity::STATUS => self::UNPROCESSED_RESPONSE];
        }

        return (new Payment\Service())->fetchStatus(Payment\Entity::getSignedId($paymentId));
    }

    public function isPaymentSuccessful(?Payment\Entity $payment = null): bool
    {
        return $payment &&
            in_array(
                $payment->getStatus(),
                [Payment\Status::CAPTURED, Payment\Status::AUTHORIZED],
                true
            );
    }

    public function setQrCodeStatusAndPaymentIdInCache(QrV2 $qrCode, ?Payment\Entity $payment = null): void
    {
        $paymentId = $payment ? $payment->getId() : '';

        $paymentStatus = '';

        $ttl = Constants::CREATED_STATUS_TTL;

        if ($this->isPaymentSuccessful($payment)) {
            $paymentStatus = $payment->getStatus();

            $ttl = Constants::SUCCESS_STATUS_TTL;
        }

        $this->putQrCodeAndPaymentStatusInCache($qrCode->getId(), $qrCode->getStatus(), $paymentId, $paymentStatus, $ttl);
    }

    /**
     * @param string $cacheValue
     *
     * @return string[]
     */
    private function getQrCodeStatusAndPaymentIdFromCacheValue(string $cacheValue): array
    {
        [$qrCodeStatus, $paymentId, $paymentStatus] = explode(Constants::CACHE_VALUE_SEPARATOR, $cacheValue, 1000);

        return [$qrCodeStatus, $paymentId];
    }

    /**
     * @param string $qrCodeId
     * @return string[]
     */
    private function getQrCodeStatusAndPaymentIdFromQRCodeId(string $qrCodeId): array
    {
        $key = self::getCacheKeyForQRCodeId($qrCodeId);

        $cacheValue = Cache::get($key);

        if ($cacheValue === null) {
            return $this->handleIfCacheExpired($qrCodeId, $this->merchant->getId());
        }

        return $this->getQrCodeStatusAndPaymentIdFromCacheValue($cacheValue);
    }

    protected function handleIfCacheExpired(string $qrCodeId, string $merchantId): array
    {
        $paymentId = $this->getPaymentIdFromDataBase($qrCodeId);

        /** @var QrV2 $qrCode */
        $qrCode = $this->repo->qr_code->findByIdAndMerchantId(QrV2::silentlyStripSign($qrCodeId), $merchantId);

        if (!empty($paymentId)) {
            $payment = $this->repo->payment->findByIdAndMerchantId($paymentId, $merchantId);

            if ($this->isPaymentSuccessful($payment)) {
                $this->setQrCodeStatusAndPaymentIdInCache($qrCode, $payment);

                return [$qrCode->getStatus(), $payment->getId()];
            }
        }

        $this->setQrCodeStatusAndPaymentIdInCache($qrCode);

        return [$qrCode->getStatus(), $paymentId];
    }

    protected function getPaymentIdFromDataBase(string $qrCodeId): string
    {
        try
        {
            return $this->repo->qr_payment->getLatestExpectedPaymentIdForQrCodeId($qrCodeId) ?? '';
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_DB_CALL_FAILED
            );
        }

        return '';
    }

    /**
     * @param string $qrCodeId
     * @param string $qrCodeStatus
     * @param string|null $paymentId
     * @param string|null $paymentStatus
     * @param int $ttl
     * @return void
     */
    protected function putQrCodeAndPaymentStatusInCache(
        string  $qrCodeId,
        string  $qrCodeStatus,
        ?string $paymentId = null,
        ?string $paymentStatus = null,
        int     $ttl = Constants::DEFAULT_CACHE_TTL
    ): void
    {
        $cacheValue = implode(Constants::CACHE_VALUE_SEPARATOR, [
           $qrCodeStatus,
           $paymentId,
           $paymentStatus,
        ]);

        try
        {
            Cache::put(
                self::getCacheKeyForQRCodeId($qrCodeId),
                $cacheValue,
                $ttl
            );
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::QR_PAYMENT_CACHE_UPDATE_FAILED
            );
        }
    }
}
