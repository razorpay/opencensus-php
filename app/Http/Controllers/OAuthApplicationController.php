<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\Application;
use Razorpay\OAuth\Application\Entity as App;
use Razorpay\OAuth\Client\Environment as ClientEnv;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Base\JitValidator;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Metric;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Models\Merchant\Validator as MerchantValidator;

class OAuthApplicationController extends Controller
{
    const PURE_PLATFORM_PARTNER_APPLICATIONS_CREATED_TOTAL = 'pure_platform_partner_applications_created_total';
    const PARTNER_APPLICATIONS_CREATED_TOTAL = 'partner_applications_created_total';
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

            $oauthApplication = (new Application\Repository())->findOrFail($data[App::ID]);

            (new MerchantCore)->createPartnerConfig($oauthApplication, $merchant);

            $dimensionsForMerchantApplication = [
                Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                'application_type'   => MerchantApplications\Entity::OAUTH
            ];

            $this->trace->count(Metric::PARTNER_MERCHANT_APPLICATION_CREATE_TOTAL, $dimensionsForMerchantApplication);
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
            $applicationType = (new MerchantApplications\Core())->getDefaultAppTypeForPartner($merchant);

            (new MerchantCore)->createMerchantApplication($merchant, $data[App::ID], $applicationType);

            $dimensionsForMerchantApplication = [
                Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                'application_type'   => $applicationType
            ];

            $this->trace->count(Metric::PARTNER_MERCHANT_APPLICATION_CREATE_TOTAL, $dimensionsForMerchantApplication);
        }

        return ApiResponse::json($data);
    }

    public function createClients(string $appId)
    {
        $app = (new Application\Repository)->findActiveApplicationByIdAndMerchantId($appId, $this->auth->getMerchantId());

        $currentClients = $app->clients->getIds();

        (new Client\Core)->createClientsForApplication($app);

        $data = $app->fresh([Application\Entity::CLIENTS])->toArray();

        $data['old_clients'] = $currentClients;

        return $data;
    }

    public function deleteClient(string $appId, string $clientId)
    {
        $clientRepo = new Client\Repository;

        $client = $clientRepo->findOrFailPublic($clientId);

        if ($client->getApplicationId() !== $appId)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CLIENT_APPLICATION_NOT_MAPPED);
        }

        $clientRepo->deleteOrFail($client);

        $app = (new Application\Repository)->findActiveApplicationByIdAndMerchantId($appId, $this->auth->getMerchantId());

        return ApiResponse::json($app->toArray());
    }

    public function refreshClients(string $appId)
    {
        $merchantId = $this->auth->getMerchantId();

        $app = (new Application\Repository)->findActiveApplicationByIdAndMerchantId($appId, $this->auth->getMerchantId());

        $currentClients = $app->clients->getIds();
        try
        {
            $data = $this->authservice->refreshClients($appId, $merchantId);
        }
        catch (\Exception $e)
        {
            $this->trace->count(Metric::PARTNER_REFRESH_CLIENT_KEYS_FAILURE);

            throw $e;
        }
        $this->processPartnerClientCreds($data);

        $data['old_clients'] = $currentClients;

        $this->trace->count(Metric::PARTNER_REFRESH_CLIENT_KEYS_TOTAL);

        return  $data;
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
     * @throws BadRequestException
     * @throws LogicException
     */
    public function getPartner()
    {
        $merchant = $this->auth->getMerchant();

        $this->merchantValidator->validatePartnerWithSettingsAccess($merchant);

        $application = (new MerchantCore)->fetchPartnerApplication($merchant);

        $data = $this->authservice->getApplication($application->getId(), $merchant->getId());

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

        return ApiResponse::json($data);
    }

    public function update(string $id)
    {
        $input = Request::all();

        $this->addOrUploadImageIfApplicable($input);

        $merchant = $this->auth->getMerchant();

        // application type can only be updated via admin api
        if($this->hasTypeInApplicationUpdateParams($input))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_APPLICATION_TYPE_UPDATE_NOT_SUPPORTED);
        }

        // TODO: Enable below check once all oauth tags are migrated to pure-platform partners
        //$this->merchantValidator->validateIsPurePlatformPartner($merchant);

        $data = $this->authservice->updateApplication($id, $input, $merchant->getId());

        return ApiResponse::json($data);
    }

    public function updateAdmin(string $id)
    {
        $input = Request::all();

        (new JitValidator)->rules([
            'merchant_id'       => 'required|alpha_num|size:14',
            'type'              => 'required|string|in:partner,public,tally',
            'client_details'    => 'required|array'
            ])
            ->input($input)
            ->validate();

        $merchantId = $input["merchant_id"];

        $data = $this->authservice->updateApplication($id, $input, $merchantId);

        return ApiResponse::json($data);
    }

    public function getSubmerchantApplications()
    {
        $merchant = $this->auth->getMerchant();

        $data = $this->authservice->getMerchantAuthorizedApplications($merchant->getId());

        return ApiResponse::json($data);
    }

    public function revokeApplicationAccess(string $id)
    {
        $merchant = $this->auth->getMerchant();

        $data = $this->authservice->revokeApplicationAccess($merchant->getId(), $id);

        return ApiResponse::json($data);
    }

    private function hasTypeInApplicationUpdateParams(array $input)
    {
        if (isset($input[Application\Entity::TYPE]))
        {
            return true;
        }

        if (isset($input[Application\Entity::CLIENT_DETAILS]))
        {
            foreach ($input[Application\Entity::CLIENT_DETAILS] as $client)
            {
                if (isset($client[Client\Entity::TYPE]))
                {
                    return true;
                }
            }
        }

        return false;
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
