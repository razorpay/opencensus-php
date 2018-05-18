<?php

namespace RZP\Models\PaymentLink;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(array $input): array
    {
        $paymentLink = $this->core()->create($input, $this->merchant);

        return $paymentLink->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $paymentLinks = $this->core()->fetchMultiple($input, $this->merchant);

        return $paymentLinks->toArrayPublic();
    }

    public function update(string $id, array $input): array
    {
        $paymentLink = $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);

        $paymentLink = $this->core()->update($paymentLink, $input);

        return $paymentLink->toArrayPublic();
    }

    public function fetchPayments(string $id): array
    {
        $paymentLink = $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);

        $payments = $this->core()->fetchPayments($paymentLink);

        return $payments->toArrayPublic();
    }
}
