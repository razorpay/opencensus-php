<?php

namespace RZP\Models\PaymentLink;

use RZP\Models\Base;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    public function fetchPayments(string $id, array $input): array
    {
        $paymentLink = $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);

        $payments = $this->repo->payment->fetch([], $this->merchant->getId());

        return $payments->toArrayPublic();
    }
}
