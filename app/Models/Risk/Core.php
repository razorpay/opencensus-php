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

        $risk->associateRelatedEntites($input);

        $this->repo->saveOrFail($risk);

        return $risk;
    }

    public function edit(Entity $risk, array $input)
    {
        $risk->edit($input);

        $risk->associateRelatedEntites($input);

        $this->repo->saveOrFail($risk);

        return $risk;
    }

    public function logRiskDataOnPaymentFailure(
        Payment\Entity $payment, array $riskData, $errorData)
    {
        $whitelistedFields = [
            Entity::RISK_SCORE,
        ];

        //
        // errorData can comprise of field and data or an array.
        // presently only merging arrays
        // Data in the exception will propogate important information to create
        // the risk entry.For Example: risk_score for maxmind failure
        //
        if (is_array($errorData) === true)
        {
            $attributes = (new Entity)->getFillable();

            foreach ($attributes as $attribute)
            {
                if ((isset($errorData[$attribute]) === true) and
                    (in_array($attribute, $whitelistedFields, true) === true))
                {
                    $riskData[$attribute] = $errorData[$attribute];
                }
            }
        }

        $input = [
            Entity::MERCHANT_ID => $payment->getMerchantId(),
            Entity::PAYMENT_ID  => $payment->getId(),
        ];

        $input = array_merge($riskData, $input);

        return $this->create($input);
    }
}
