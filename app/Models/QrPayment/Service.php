<?php

namespace RZP\Models\QrPayment;

use RZP\Base\Common;
use RZP\Constants\Es;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\QrCode\NonVirtualAccountQrCode;

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
}
