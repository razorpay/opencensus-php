<?php

namespace RZP\Http;

use App;
use RZP\Base\RepositoryManager;
use RZP\Models\Admin\Org;
use RZP\Models\Merchant as MerchantEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Trace\TraceCode;

class CheckoutView{

    /**
     * Application instance
     *
     * @var Application
     */
    protected $app;

    /**
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }


    public function addOrgInformationInResponse($merchant = null, $orgData = false ): array
    {
        $data = [];

        if($orgData === false)
        {
            return $data;
        }

        $customBranding = isset($merchant) ?
                    (new MerchantEntity\Core())->isOrgCustomBranding($merchant):
                    false;

        if ($customBranding === true)
        {
            $orgId = $merchant->getOrgId();

            if (isset($orgId) === true)
            {
                $orgDetails = $this->repo->org->find($orgId);

                if (isset($orgDetails) === true)
                {
                    $data['org_logo'] = $orgDetails->getMainLogo();

                    $data['org_name'] = $orgDetails->getDisplayName();

                    $data['checkout_logo'] = $orgDetails->getCheckoutLogo();
                }
            }
        }
        else
        {
            $razorpayOrg = $this->repo->org->find(Org\Entity::RAZORPAY_ORG_ID);

            if (isset($razorpayOrg) === true)
            {
                $data['org_logo'] = $razorpayOrg->getMainLogo();

                $data['org_name'] = $razorpayOrg->getDisplayName();

                $data['checkout_logo'] = $razorpayOrg->getCheckoutLogo();
            }
        }

        $data['custom_branding'] = $customBranding;

        return $data;
    }

}
