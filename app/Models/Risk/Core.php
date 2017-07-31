<?php

namespace RZP\Models\Risk;

use RZP\Models\Base;
use RZP\Models\Payment;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $risk = new Entity;

        $risk->build($input);

        $this->repo->saveOrFail($risk);

        return $risk;
    }

    public function edit(Entity $risk, array $input)
    {
        $oldComment = $risk->getComments();

        // If not set, editRules validator will throw an exception
        if (empty($input[Entity::COMMENTS]) === false)
        {
            $newComment = $input[Entity::COMMENTS];

            $input[Entity::COMMENTS] = $oldComment . " || " . $newComment;
        }

        $risk->edit($input);

        $this->repo->saveOrFail($risk);

        return $risk;
    }

    public function get(string $id)
    {
        return $this->repo->risk->findOrFailPublic($id);
    }

    public function logRiskDataOnPaymentFailure(
        Payment\Entity $payment,
        array $riskData)
    {
        $input = [
            Entity::MERCHANT_ID   => $payment->getMerchantId(),
            Entity::PAYMENT_ID    => $payment->getId(),
        ];

        $input = array_merge($riskData, $input);

        return $this->create($input);
    }
}
