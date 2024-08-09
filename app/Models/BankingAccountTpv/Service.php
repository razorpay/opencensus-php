<?php

namespace RZP\Models\BankingAccountTpv;

use RZP\Models\Base;
use RZP\Constants\Mode;

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

    public function adminMigrateMerchantTpvs($input)
    {
        $this->validator->validateInput('admin_migrate_tpv', $input);

        return $this->core()->adminMigrateMerchantTpvs($input);
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

    public function createTpvFromXDashboardWithOtpVerification($input)
    {
        $ba = app('basicauth');
        $user = $ba->getUser();

        $user->validateInput('verifyOtp', array_only($input, ['otp','token']));
        (new \RZP\Models\User\Core())->verifyOtp($input + ['action' => 'source_account_create'],
            $this->merchant,
            $user,
            $this->mode === Mode::TEST);

        $updateInput = array_except($input, ['otp', 'token']);

        $this->validator->validateInput('merchant_dashboard_create', $updateInput);

        return $this->core()->createTpvFromXDashboard($updateInput);
    }
}
