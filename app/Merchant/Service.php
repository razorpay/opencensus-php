<?php

namespace App\Merchant;

use Auth;
use Hash;
use Queue;
use Session;
use Requests;
use App\Base;
use App\User;
use App\Admin;
use App\Merchant;
use App\Invitation;
use App\User\Helper;
use App\MerchantDetails;
use App\Mailers\UserMailer;
use App\RZP\PublicCollection;
use Razorpay\Api\Errors\BadRequestError;
use Razorpay\Api\Errors\Error as ApiError;

class Service extends Base\Service
{
    const UPLOAD_KEYS = [
        'business_proof'           => 'business_proof_url',
        'business_operation_proof' => 'business_operation_proof_url',
        'business_pan_proof'       => 'business_pan_url',
        'address_proof'            => 'address_proof_url',
        'promoter_proof'           => 'promoter_proof_url',
        'promoter_pan_proof'       => 'promoter_pan_url',
        'promoter_address_proof'   => 'promoter_address_url'
    ];

    public function __construct()
    {
        $this->currentUser = Auth::user();
    }

    /**
     * Registers a sub-merchant account and associates it both ways:
     * 1. Marks the original user as the referral
     * 2. Adds the original user to the new merchant's team
     *
     * This is one scenario where we don't use currentMerchant
     *
     * @param  array  $input
     * @return array
     */
    public function registerSubMerchant(array $input)
    {
        $isLinkedAccount = (bool) ($input['account'] ?? false);

        /*
         * Mode has to be passed in case of creating submerchant via marketplace
         * This is because the route has a feature check on api.
         * Without passing mode, the request is in live mode by default and the route fails
         * as the feature will not be enabled in live mode initially.
         */
        $mode = $input['mode'] ?? null;

        unset($input['mode']);

        $data = array_merge([
            'user_id' => $this->currentUser->id
        ], $input);

        $request = new \App\Admin\ApiRequestAny($mode, 'merchant');

        list($error, $data) = $request->processInput($data)->send('submerchants', 'POST');

        if (($isLinkedAccount === false) and (empty($error) === true))
        {
            list($error, $genericUser) = (new User\Service)->getUserFromApi($this->currentUser->id);

            if (empty($error) === true)
            {
                Session::put('dashboard_user_payload', $genericUser);
            }
        }

        return [$error, $data];
    }

    public function resendConfirmation()
    {
        $request = new \App\Admin\ApiRequestAny();

        list($error, $data) = $request->send('users/resend-verification', 'POST');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        if (empty($data['confirm']) === false)
        {
            return [['User already confirmed. You can login ' .
                    '<a href="'.\URL::to('#/access/signin').'">here</a>'], null];
        }

        return [$error, $data];
    }

    public function fetchMerchantFromApi($merchantId)
    {
        $this->setApiCredentials();

        $error = $response = null;

        $response = $this->api
                         ->merchant
                         ->fetch($merchantId)
                         ->toArray();

        if (empty($response) === false)
        {
            $response =  [
                'id'           => $response['id'],
                'name'         => $response['name'],
                'email'        => $response['email'],
                'activated'    => (int) $response['activated'],
                'created_at'   => $response['created_at'],
                'updated_at'   => $response['updated_at'],
                'archived_at'  => $response['archived_at'],
                'suspended_at' => $response['suspended_at'],
            ];
        }

        return [$error, $response];
    }

    public function fetchKeysFromApi($merchantId, $mode)
    {
        $request = new \App\Admin\ApiRequestAny($mode, 'merchant');

        list($error, $data) = $request->send('keys', 'GET');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function fetchInvoices($mode)
    {
        $request = new \App\Admin\ApiRequestAny($mode, 'merchant');

        list($error, $data) = $request->send('invoices', 'GET');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function createKey($merchantId, $mode)
    {
        $request = new \App\Admin\ApiRequestAny($mode, 'merchant');

        list($error, $data) = $request->send('keys', 'POST');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function getInvoices($mode)
    {
        $merchantId = $this->currentUser->currentMerchant()->id;

        $this->setApiCredentials($merchantId, $mode);

        $errors = $data = null;

        try
        {
            $data = $this->api->invoice->all()->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return [$errors, $data];
    }

    public function createInvoice($mode, $input)
    {
        $request = new \App\Admin\ApiRequestAny($mode, 'merchant');

        list($error, $data) = $request->processInput($input)->send('invoices', 'POST');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function saveActivationData($input)
    {
        if (isset($input['bank_account_number_confirmation']) === true)
        {
            unset($input['bank_account_number_confirmation']);
        }

        $request = new \App\Admin\ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->processInput($input)->send('merchant/activation', 'POST');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function saveActivationFilesData($input)
    {
        if ((count($input) !== 1) or
            (in_array(key($input), array_keys(self::UPLOAD_KEYS)) === false))
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                'Invalid parameters.',
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        $field = self::UPLOAD_KEYS[key($input)];

        $request = new \App\Admin\ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->send('merchant/activation/upload', 'POST');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function sendInvoiceNotification($mode, $invoiceId, $medium)
    {
        $errors = $data = [];

        $merchantId = $this->currentUser->currentMerchant()->id;

        // Fetches keyId from api for given merchant
        list($errors, $data) = $this->fetchKeysFromApi($merchantId, $mode);

        if ($errors)
        {
            return [$errors, $data];
        }

        if ($data['count'] === 0)
        {

            return [
                ['No keyId found for given merchant with id: ' . $merchantId],
                $data
            ];
        }

        $keyId = $data['items'][0]['id'];

        $this->setApiCredentialsForPublicAuth($keyId);

        try
        {
            $data = $this->api->invoice->sendNotification($invoiceId, $medium)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return [$errors, $data];
    }

    public function savePreSignupDetails($input)
    {
        $request = new \App\Admin\ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->processInput($input)->send('pre_signup', 'PUT');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function getReferrerAttribute($merchantId)
    {
        $tags = $this->getMerchantTags($merchantId);

        foreach ($tags as $tag)
        {
            $tag = strtolower($tag);

            if (substr($tag, 0, 4) === 'ref-')
            {
                return substr($tag, 4);
            }
        }

        return null;
    }

    /**
     * returns the presignup data for a merchant
     * if the merchant is referred (submerchant)
     * then returns an empty array
     */
    public function getPreSignupDetails($merchantId): array
    {
        $data = [];

        $referrer = $this->getReferrerAttribute($merchantId);

        if (($referrer === null) or
            (Merchant\Entity::verifyUniqueId($referrer) === 0))
        {
            $data = (new MerchantDetails\Service)->getPresignupDetails($merchantId);
        }

        return $data;
    }

    public function getMerchantUsers($merchantId)
    {
        $request = new \App\Admin\ApiRequestAny();

        list($error, $data) = $request->send("merchants/$merchantId/users", 'GET');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return $data;
    }

    public function getMerchantTags($merchantId)
    {
        $request = new \App\Admin\ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->send("merchants/$merchantId/tags", 'GET');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return $data;
    }

    public function addMerchantTagsOnAPI($merchantId, $tags)
    {
        $body = [
            'tags' => $tags
        ];

        $request = new \App\Admin\ApiRequestAny();

        list($error, $data) = $request->processInput($body)->send("merchants/$merchantId/tags", 'POST');
    }

    public function getCurrentMerchantId()
    {
        $currentMerchant = Auth::user()->currentMerchant();

        if (empty($currentMerchant) === true)
        {
            // This case will happen only if the user has zero merchants and tried to access the merchant route.
            throw new \Razorpay\Api\Errors\BadRequestError(
                'Merchant not found for the user',
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return $currentMerchant->id;
    }
}
