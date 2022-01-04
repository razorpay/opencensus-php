<?php

namespace RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider;


use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;


class FeeRule
{
    const HUNDRED_PAISE = 100;

    public $data;

    public function __construct($input)
    {
        $this->data = $input;
    }

    public function validate()
    {
        (new Validator)->validateInput('feeRule', $this->data);
        $ruleType = $this->data[Constants::FEE_RULE_TYPE];
        switch ($ruleType)
        {
            case Constants::SLABS_FEE_RULE_TYPE:
                $this->validateSlabs($this->data['slabs']);
        }
    }

    public function validateSlabs($slabs)
    {
        $validator = (new Validator);

        foreach ($slabs as $slab) {
            $validator->validateInput('slab', $slab);
            if ($slab[Constants::GTE] > $slab[Constants::LTE])
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                    null,
                    null,
                    "invalid slabs"
                );
            }
            $this->validateAmountNotDecimalRupee($slab[Constants::GTE]);
            $this->validateAmountNotDecimalRupee($slab[Constants::LTE]);
        }

        usort(
            $slabs,
            function ($s1, $s2)
            {
                return ($s1[Constants::GTE] > $s2[Constants::GTE]) ? 1 : -1;
            }
        );

        $prevSlab = null;
        foreach ($slabs as $slab)
        {
            if ($prevSlab != null)
            {
                if ($slab[Constants::GTE] != $prevSlab[Constants::LTE] + self::HUNDRED_PAISE)
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                        null,
                        null,
                        "invalid slabs, no gaps allowed between slabs"
                    );
                }
            }
            $prevSlab = $slab;
        }
    }

    public function adaptToSlabsTableEntity(): array
    {
        $slabs = $this->data['slabs'];
        usort(
            $slabs,
            function ($s1, $s2)
            {
                return ($s1[Constants::GTE] > $s2[Constants::GTE]) ? 1 : -1;
            }
        );


        $slabsEntities = [];
        $prevSlabEnd = -1;
        foreach ($slabs as $slab)
        {
            if ($slab[Constants::GTE] > $prevSlabEnd + self::HUNDRED_PAISE)
            {
                $slabsEntity = [
                    'amount' => $prevSlabEnd + 1,
                    'fee' => 0
                ];
                array_push($slabsEntities, $slabsEntity);
                $prevSlabEnd = $slab[Constants::GTE] - self::HUNDRED_PAISE;
            }

            $slabsEntity = [
                'amount' =>  $prevSlabEnd + 1,
                'fee' => $slab[Constants::FEE]
            ];
            array_push($slabsEntities, $slabsEntity);
            $prevSlabEnd = $slab[Constants::LTE];
        }
        $slabsEntity = [
            'amount' => $prevSlabEnd + 1,
            'fee' => 0
        ];
        array_push($slabsEntities, $slabsEntity);
        return ['slabs' => $slabsEntities];
    }

    public function isSlabRuleType(): bool
    {
        return $this->data[Constants::FEE_RULE_TYPE] == Constants::SLABS_FEE_RULE_TYPE;
    }

    public function getFee()
    {
        $ruleType = $this->data[Constants::FEE_RULE_TYPE];
        switch ($ruleType)
        {
            case Constants::FREE_FEE_RULE_TYPE:
                return 0;
            case Constants::FLAT_FEE_RULE_TYPE:
                return $this->data[Constants::FLAT_FEE];
        }
        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_VALIDATION_FAILED
        );
    }

    protected function validateAmountNotDecimalRupee($amount)
    {
        if ($amount % self::HUNDRED_PAISE !== 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                null,
                null,
                "invalid amount, can only be in rupee without decimal"
            );
        }
    }

}
