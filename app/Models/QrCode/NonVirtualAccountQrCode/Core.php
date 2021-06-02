<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use Carbon\Carbon;
use RZP\Models\QrCode;

class Core extends QrCode\Core
{
    public function buildQrCode(array $input)
    {
        $customer = $this->getCustomerIfGiven($input);

        $qrCode = (new Entity())->build($input);

        $qrCode->merchant()->associate($this->merchant);

        $qrCode->customer()->associate($customer);

        $qrCode->generateQrString();

        $this->setShortUrl($qrCode);

        $this->repo->transaction(function() use ($qrCode)
        {
            $this->repo->saveOrFail($qrCode);

            $this->generateQrCodeFile($qrCode);
        });

        return $qrCode;
    }

    public function close($qrCode, $closeReason)
    {
        $qrCode->setStatus(Status::CLOSED);

        $currentTime = Carbon::now()->getTimestamp();

        $qrCode->setClosedAt($currentTime);

        $qrCode->setCloseReason($closeReason);

        $this->repo->saveOrFail($qrCode);

        return $qrCode;
    }

    protected function getCustomerIfGiven(array $input)
    {
        $customer = null;

        if (isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $customerId = $input[Entity::CUSTOMER_ID];

            $customer = $this->repo
                             ->customer
                             ->findByPublicIdAndMerchant($customerId, $this->merchant);
        }

        return $customer;
    }
}
