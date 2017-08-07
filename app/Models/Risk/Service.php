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

    public function fetch(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $risk = (new Core)->get($id);

        return $risk->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $this->cleanPublicIds($input);

        $entities = $this->repo->risk->fetch($input);

        return $entities->toArrayPublic();
    }

    private function cleanPublicIds(array & $input)
    {
        if (isset($input[Entity::PAYMENT_ID]) === true)
        {
            Payment\Entity::verifyIdAndStripSign($input[Entity::PAYMENT_ID]);
        }
    }
}
