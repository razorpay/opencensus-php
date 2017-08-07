<?php

namespace RZP\Models\Risk;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;

class Service extends Base\Service
{
    public function create(string $paymentId, array $input)
    {
        $payment = $this->repo->payment->findByPublicId($paymentId);

        $risk = (new Core)->create($payment, $input);

        return $risk->toArrayPublic();
    }

    public function edit(string $id, array $input)
    {
        $risk = $this->repo->risk->findByPublicId($id);

        $risk = (new Core)->edit($risk, $input);

        return $risk->toArrayPublic();
    }

    public function fetch(string $id)
    {
        $risk = $this->repo->risk->findByPublicId($id);

        return $risk->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $entities = $this->repo->risk->fetch($input);

        return $entities->toArrayPublic();
    }
}
