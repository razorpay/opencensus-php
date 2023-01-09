<?php

namespace RZP\Models\P2p\Vpa\Handle;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $editRules;
    protected static $addRules;
    protected static $updateRules;

    public function rules()
    {
        $rules = [
            Entity::CODE         => 'string|',
            Entity::MERCHANT_ID  => 'string|size:14',
            Entity::ACQUIRER     => 'string|in:p2p_upi_axis,p2p_upi_sharp,p2m_upi_axis_olive',
            Entity::ACTIVE       => 'boolean',
            Entity::BANK         => 'string|size:4',
        ];

        return $rules;
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::CODE         => 'sometimes',
            Entity::MERCHANT_ID  => 'sometimes',
            Entity::ACQUIRER     => 'sometimes',
            Entity::ACTIVE       => 'sometimes',
            Entity::BANK         => 'sometimes',
        ]);

        return $rules;
    }

    public function makeEditRules()
    {
        $rules = $this->makeRules([
            Entity::MERCHANT_ID  => 'sometimes',
            Entity::ACQUIRER     => 'sometimes',
            Entity::ACTIVE       => 'sometimes',
            Entity::BANK         => 'sometimes',
            Entity::CLIENT       => 'sometimes',
        ]);

        return $rules;
    }

    public function makeFetchAllRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeAddRules()
    {
        $rules = $this->makeRules([
            Entity::CODE         => 'required',
            Entity::MERCHANT_ID  => 'required',
            Entity::ACQUIRER     => 'required',
            Entity::BANK         => 'required',
            Entity::ACTIVE       => 'required',
        ]);

        return $rules;
    }

    public function makeUpdateRules()
    {
        $rules = $this->makeRules([
            Entity::CODE         => 'required',
            Entity::MERCHANT_ID  => 'sometimes',
            Entity::ACQUIRER     => 'sometimes',
            Entity::BANK         => 'sometimes',
            Entity::ACTIVE       => 'sometimes',
            Entity::CLIENT       => 'sometimes',
        ]);

        return $rules;
    }
}
