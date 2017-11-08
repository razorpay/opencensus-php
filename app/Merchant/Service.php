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
use App\Generic;
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

        $registerSubMerchant = [
            'route_name' => 'merchant_sub_create',
            'body'       => $data,
            'mode'       => $mode,
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('POST', $registerSubMerchant);

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
        $resendConfirmation = [
            'route_name' => 'user_resend_verification',
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('POST', $resendConfirmation);

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
        $getData = [
            'route_name' => 'merchant_fetch_keys',
            'url_params' => [
                '{id}' => $merchantId,
            ],
            'mode' => $mode
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('GET', $getData);

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
        $getData = [
            'route_name' => 'invoice_fetch_multiple',
            'mode' => $mode,
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('GET', $getData);

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
        $createKey = [
            'route_name' => 'merchant_create_key',
            'url_params' => [
                '{id}' => $merchantId,
            ],
            'mode' => $mode
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('POST', $createKey);

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
        $createInvoice = [
            'route_name' => 'invoice_create',
            'mode' => $mode,
            'body' => $input
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('POST', $createInvoice);

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

        $saveActivationData = [
            'route_name' => 'merchant_activation_save',
            'body' => $input
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('POST', $saveActivationData);

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

        $saveActivationFilesData = [
            'route_name' => 'merchant_activation_upload_file',
            'file_name' => $field,
            'file' => current($input)
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('POST', $saveActivationFilesData);

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
        $savePreSignupDetails = [
            'route_name' => 'merchant_edit_pre_signup_details',
            'body'       => $input
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('PUT', $savePreSignupDetails);

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

    public function getMerchantUsers($merchantId) {
        $getMerchantUsers = [
            'route_name' => 'merchant_fetch_users',
            'url_params' => [
                '{id}' => $merchantId,
            ],
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('GET', $getMerchantUsers);

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

    public function getMerchantTags($merchantId) {
        $getTags = [
            'route_name' => 'merchant_get_tags',
            'url_params' => [
                '{id}' => $merchantId,
            ]
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('GET', $getTags);

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

    public function addMerchantTagsOnAPI($merchantId, $tags) {
        $addTags = [
            'route_name' => 'merchant_tag_add',
            'url_params' => [
                '{id}' => $merchantId,
            ],
            'body' => [
                'tags' => $tags
            ],
            'mode'  => 'live'
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('POST', $addTags);
    }
}
