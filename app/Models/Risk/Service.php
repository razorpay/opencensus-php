<?php

namespace RZP\Models\Risk;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;

class Service extends Base\Service
{
    public function create(string $paymentId, array $input)
    {
        $input[Entity::PAYMENT_ID] = $paymentId;

        $this->cleanPublicIds($input);

        $risk = (new Core)->create($input);

        return $risk->toArrayPublic();
    }

    public function edit(string $id, array $input)
    {
        Entity::verifyIdAndStripSign($id);

        $risk = (new Core)->get($id);

        $risk = (new Core)->edit($risk, $input);

        return $risk->toArrayPublic();
    }

    public function getRiskForAllPayments()
    {
        $entities = $this->repo->risk->fetch();

        return $entities->toArrayPublic();
    }

    public function getRiskForPayment(string $paymentId)
    {
        Payment\Entity::verifyIdAndStripSign($paymentId);

        $entities = $this->repo->risk->fetchByPaymentId($paymentId);

        return $entities->toArrayPublic();
    }

    public function getRiskPaymentsForMerchant(string $merchantId)
    {
        Merchant\Entity::verifyIdAndStripSign($merchantId);

        $risk = $this->repo->risk->fetchByMerchantId($merchantId);

        return $risk->toArrayPublic();
    }

    private function cleanPublicIds(array & $input)
    {
        if (isset($input[Entity::PAYMENT_ID]) === true)
        {
            Payment\Entity::verifyIdAndStripSign($input[Entity::PAYMENT_ID]);
        }

        if (isset($input[Entity::MERCHANT_ID]) === true)
        {
            Merchant\Entity::verifyIdAndStripSign(
                $input[Entity::MERCHANT_ID]);
        }
    }
}
