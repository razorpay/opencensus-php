<?php

namespace RZP\Http\Controllers;

use Request;
use RZP\Base\JitValidator;
use Trace;
use ApiResponse;
use RZP\Models\Merchant;

use Razorpay\OAuth\Application;

class OAuthApplicationController extends Controller
{
    /**
     * @var \RZP\Http\BasicAuth\BasicAuth
     */
    protected $auth;

    /**
     * External Service Class
     *
     * @var \Razorpay\OAuth\Application\Service
     */
    protected $service = Application\Service::class;

    public function __construct()
    {
        parent::__construct();

        $this->auth = $this->app['basicauth'];
    }

    public function createApplication()
    {
        $input = Request::all();

        $this->addOrUploadImageIfApplicable($input);

        $merchantId = $this->auth->getMerchantId();

        $input[Application\Entity::MERCHANT_ID] = $merchantId;

        $app = $this->service()->createApplication($input);

        return ApiResponse::json($app);
    }

    public function get(string $id)
    {
        $merchantId = $this->auth->getMerchantId();

        $app = $this->service()->fetch($id, $merchantId);

        return ApiResponse::json($app);
    }

    public function getMultiple()
    {
        $input = Request::all();

        $merchantId = $this->auth->getMerchantId();

        $input[Application\Entity::MERCHANT_ID] = $merchantId;

        $apps = $this->service()->fetchMultiple($input);

        return ApiResponse::json($apps);
    }

    public function delete(string $id)
    {
        $merchantId = $this->auth->getMerchantId();

        $this->service()->delete($id, $merchantId);

        return ApiResponse::json([]);
    }

    public function update(string $id)
    {
        $input = Request::all();

        $this->addOrUploadImageIfApplicable($input);

        $merchantId = $this->auth->getMerchantId();

        $input[Application\Entity::MERCHANT_ID] = $merchantId;

        $app = $this->service()->update($id, $input);

        return ApiResponse::json($app);
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
        if (isset($input['logo']) === true)
        {
            (new JitValidator)->rules(['logo' => 'sometimes|file'])
                              ->input($input)
                              ->validate();

            // TODO: Move this out of Merchant namespace, and make generic
            $logoUrl = (new Merchant\Logo)->setUpMerchantLogo($input);

            $input[Application\Entity::LOGO_URL] = $logoUrl;

            unset($input['logo']);
        }
    }
}
