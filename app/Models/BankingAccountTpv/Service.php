<?php

namespace RZP\Models\BankingAccountTpv;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $validator;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator();
    }

    public function adminCreateTpv(array $input)
    {
        $this->validator->validateInput('admin_create', $input);

        return $this->core()->create($input);
    }

    public function adminEditTpv(string $id, array $input)
    {
        $this->validator->validateInput('admin_edit', $input);

        return $this->core()->edit($id, $input);
    }

    public function fetchMerchantTpvs(): array
    {
        return $this->core()->fetchMerchantTpvs();
    }

    public function fetchMerchantTpvsWithFav($input, $mid)
    {
        return $this->core()->getMerchantTpvsWithFavDetails($input, $mid);
    }

    public function manualAutoApproveTpv($input)
    {
        return $this->core()->manualAutoApproveTpv($input);
    }

    public function createTpvFromXDashboard($input)
    {
        $this->validator->validateInput('merchant_dashboard_create', $input);

        return $this->core()->createTpvFromXDashboard($input);
    }
}
