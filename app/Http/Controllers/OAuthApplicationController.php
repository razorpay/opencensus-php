<?php

namespace RZP\Http\Controllers;

use Request;

use ApiResponse;
use RZP\Models\Merchant;
use RZP\Base\JitValidator;

use Razorpay\OAuth\Application;

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

    public function __construct()
    {
        parent::__construct();

        $this->auth = $this->app['basicauth'];

        $this->authservice = $this->app['authservice'];
    }

    public function create()
    {
        $input = Request::all();

        $this->addOrUploadImageIfApplicable($input);

        $merchantId = $this->auth->getMerchantId();

        $data = $this->authservice->createApplication($input, $merchantId);

        return ApiResponse::json($data);
    }

    public function get(string $id)
    {
        $merchantId = $this->auth->getMerchantId();

        $data = $this->authservice->getApplication($id, $merchantId);

        return ApiResponse::json($data);
    }

    public function getMultiple()
    {
        $input = Request::all();

        $merchantId = $this->auth->getMerchantId();

        $data = $this->authservice->getMultipleApplications($input, $merchantId);

        return ApiResponse::json($data);
    }

    public function delete(string $id)
    {
        $merchantId = $this->auth->getMerchantId();

        $data = $this->authservice->deleteApplication($id, $merchantId);

        return ApiResponse::json($data);
    }

    public function update(string $id)
    {
        $input = Request::all();

        $this->addOrUploadImageIfApplicable($input);

        $merchantId = $this->auth->getMerchantId();

        $data = $this->authservice->updateApplication($id, $input, $merchantId);

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
            $logoInput = array_only($input, [Application\Entity::LOGO]);

            (new JitValidator)->rules(['logo' => 'sometimes|file'])
                              ->input($logoInput)
                              ->validate();

            $logoUrl = (new Merchant\Logo)->setUpMerchantLogo($logoInput);

            $input[Application\Entity::LOGO_URL] = $logoUrl;

            unset($input[Application\Entity::LOGO]);
        }
    }
}
