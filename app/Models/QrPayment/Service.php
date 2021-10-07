<?php

namespace RZP\Models\QrPayment;

use RZP\Base\Common;
use RZP\Constants\Es;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\BharatQr;
use RZP\Models\BankTransfer;
use RZP\Models\QrPaymentRequest;
use RZP\Models\QrPaymentRequest\Type;

class Service extends Base\Service
{
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

        return $this->fetchPaymentsForQrPaymentIds($qrPaymentIds);
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

    private function modifyBankTransferInput(array $input, $bankAccount, $provider, $requestPayload)
    {
        $qrCodeId = $bankAccount->qrCode->getId();

        $gatewayResponse['callback_data'] = $requestPayload;

        $gatewayResponse['qr_data'] = [
            BharatQr\GatewayResponseParams::AMOUNT                => $input['amount'],
            BharatQr\GatewayResponseParams::METHOD                => Payment\Method::BANK_TRANSFER,
            BharatQr\GatewayResponseParams::MERCHANT_REFERENCE    => $qrCodeId,
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID => $input[BankTransfer\Entity::REQ_UTR],
            BharatQr\GatewayResponseParams::GATEWAY               => Payment\Gateway::$bankTransferProviderGateway[$provider],
        ];

        return $gatewayResponse;
    }
}
