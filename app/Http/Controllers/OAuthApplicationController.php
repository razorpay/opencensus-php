<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use Razorpay\OAuth\Application;
use Razorpay\OAuth\Application\Entity as App;
use Razorpay\OAuth\Client\Environment as ClientEnv;

use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Base\JitValidator;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Models\Merchant\Validator as MerchantValidator;
use RZP\Models\Merchant\Core as MerchantCore;

class OAuthApplicationController extends Controller
{
    /**
     * @var \RZP\Http\BasicAuth\BasicAuth
     */
    protected $auth;

    /**
     * @var \RZP\Services\AuthService
     */
    protected $authservice;

    /**
     * @var MerchantValidator
     */
    protected $merchantValidator;

    public function __construct()
    {
        parent::__construct();

        $this->auth = $this->app['basicauth'];

        $this->authservice = $this->app['authservice'];

        $this->merchantValidator = (new MerchantValidator);
    }

    public function create()
    {
        $input = Request::all();

        $merchant = $this->auth->getMerchant();

        // TODO: Enable below check once all oauth tags are migrated to pure-platform partners
        //$this->merchantValidator->validateIsPurePlatformPartner($merchant);

        $this->addOrUploadImageIfApplicable($input);

        $data = $this->authservice->createApplication($input, $merchant->getId());

        if (array_key_exists(App::ID, $data) === true)
        {
            (new MerchantCore)->createMerchantApplication($merchant, $data[App::ID], MerchantApplications\Entity::OAUTH);
        }

        return ApiResponse::json($data);
    }

    public function createPartner()
    {
        $input = Request::all();

        $merchant = $this->auth->getMerchant();

        $this->merchantValidator->validateIsNonPurePlatformPartner($merchant);

        $data = $this->authservice->createApplication($input, $merchant->getId(), Application\Type::PARTNER);

        if (array_key_exists(App::ID, $data) === true)
        {
            $partnerType = $merchant->getPartnerType();

            $applicationType = ($partnerType === Constants::RESELLER) ? MerchantApplications\Entity::REFERRED : MerchantApplications\Entity::MANAGED;

            (new MerchantCore)->createMerchantApplication($merchant, $data[App::ID], $applicationType);
        }

        return ApiResponse::json($data);
    }

    public function get(string $id)
    {
        $merchant = $this->auth->getMerchant();

        // TODO: Enable below check post all partners migration
        //$this->merchantValidator->validateIsPartner($merchant);

        $data = $this->authservice->getApplication($id, $merchant->getId());

        return ApiResponse::json($data);
    }

    public function getMultiple()
    {
        $input = Request::all();

        $merchant = $this->auth->getMerchant();

        // TODO: Enable below check once all oauth tags are migrated to pure-platform partners
        //$this->merchantValidator->validateIsPurePlatformPartner($merchant);

        $data = $this->authservice->getMultipleApplications($input, $merchant->getId());

        return ApiResponse::json($data);
    }

    /**
     * This uses the getMultiple API on auth-service side but doesn't take
     * any other params as only one partner app is expected.
     *
     * @return mixed
     */
    public function getPartner()
    {
        $merchant = $this->auth->getMerchant();

        $this->merchantValidator->validatePartnerWithSettingsAccess($merchant);

        $data = $this->authservice->getPartnerApplication($merchant->getId());

        $this->processPartnerClientCreds($data);

        return ApiResponse::json($data);
    }

    public function delete(string $id)
    {
        $merchant = $this->auth->getMerchant();

        // TODO: Enable below check post all partners migration. Confirm the order in which
        // it is called for non-pure_platform and if the check will hold true
        //$this->merchantValidator->validateIsPartner($merchant);

        $data = $this->authservice->deleteApplication($id, $merchant->getId());

        (new MerchantCore)->deleteMerchantApplication($id, Merchant\Constants::APPLICATION_ID);

        return ApiResponse::json($data);
    }

    public function update(string $id)
    {
        $input = Request::all();

        $this->addOrUploadImageIfApplicable($input);

        $merchant = $this->auth->getMerchant();

        // TODO: Enable below check once all oauth tags are migrated to pure-platform partners
        //$this->merchantValidator->validateIsPurePlatformPartner($merchant);

        $data = $this->authservice->updateApplication($id, $input, $merchant->getId());

        return ApiResponse::json($data);
    }

    /**
     * Handles logic for Application logo create/update
     *
     * Added here and not Application/Service because
     * the logic for logo resides on API, all the service
     * cares about is the logo_url that needs to be saved
     *
     * @param array $input
     */
    protected function addOrUploadImageIfApplicable(array & $input)
    {
        if (isset($input[Application\Entity::LOGO]) === true)
        {
            $logoInput = ['logo' => Request::file([Application\Entity::LOGO])];

            (new JitValidator)->rules(['logo' => 'sometimes|file|mimes:jpeg,jpg,png'])
                              ->input($logoInput)
                              ->validate();

            $logoUrl = (new Merchant\Logo)->setUpMerchantLogo($logoInput);

            $input[Application\Entity::LOGO_URL] = $logoUrl;

            unset($input[Application\Entity::LOGO]);
        }
    }

    /**
     * We need to display partner client creds in the form of key-secret with
     * `rzp_{mode}_partner` suffix appended. Auth-service has no knowledge of
     * this hence we need to process here only for non-pure_platform partners.
     *
     * @param array $appData
     */
    protected function processPartnerClientCreds(array & $appData)
    {
        $testClientId = & $appData[App::CLIENT_DETAILS][ClientEnv::DEV][App::ID];

        $testClientId = 'rzp_' . Mode::TEST . '_partner_' . $testClientId;

        $liveClientId = & $appData[App::CLIENT_DETAILS][ClientEnv::PROD][App::ID];

        $liveClientId = 'rzp_' . Mode::LIVE . '_partner_' . $liveClientId;
    }
}
