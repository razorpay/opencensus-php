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

    /**
     * Detaches and attaches user to merchant.
     * @param $userId
     * @param $merchantId
     * @param $role
     *
     * @return array
     */
    public function detachAndAttachMerchantUser($userId, $merchantId, $role)
    {
        list($error, $response) = (new User\Service)->detachMerchantUserOnApi($userId, $merchantId);

        if (empty($error) === true)
        {
            list($error, $response) = (new User\Service)->attachMerchantUserOnApi($userId, $merchantId, $role);
        }

        return [$error, $response];
    }

    /**
     * This handles 3 possible cases when changing user email.
     * 1. There exists a team member with the new email
     *    Here, we swap the roles of the team member(manager) with new email and the original owner
     * 2. There exists a user(not team member) with the new email
     *    Here, we change the original owner to manager and then add the user with new email as owner
     * 3. The new email is unique so far
     *    Here, we just change the email of the original user(owner).
     *
     * @param App\Merchant\Entity $merchant Merchant entity for which email is to be changed
     * @param array $input array containing the new email
     */
    public function handleUserEmailChange($merchantId, $originalEmail, $input)
    {
        list($error, $merchantUsers) = $this->getUsersOfMerchantFromApi($merchantId);

        if (empty($error) === true and count($merchantUsers->all()) > 0)
        {
            $teamUser = $merchantUsers->where('email', $input['email'], false)->first();

            $existingUser = null;

            list($error,$existingUserData) = (new User\Service)->getUserByEmail($input['email']);

            if (empty($error) === true)
            {
                $existingUser = (new User\Helper)->createdGenericUser($existingUserData);
            }

            $selfUser = $merchantUsers->where('email', $originalEmail)->first();

            //The merchant has a team member with new email
            if (empty($teamUser) === false)
            {
                //swap roles between user with new email and original owner
                $oldOwner = $merchantUsers->where('role', 'owner')->first();

                // adding merchant user mapping entry on api with manager role for oldowner user
                $this->detachAndAttachMerchantUser($oldOwner->id, $merchantId, 'manager');

                if (empty($error) === true)
                {
                    $oldOwner->refreshCurrentMerchant();
                }

                // adding merchant user mapping entry on api with owner role for teamUser user
                $this->detachAndAttachMerchantUser($teamUser->id, $merchantId, 'owner');

                if (empty($error) === true)
                {
                    $teamUser->refreshCurrentMerchant();
                }
            }
            //There is an existing user with new email but not a team member
            else if (empty($existingUser) === false)
            {
                //assign owner to existing user and make existing owner a manager.
                $oldOwner = $merchantUsers->where('role', 'owner')->first();

                // adding merchant user mapping entry on api with manager role for oldowner user
                $this->detachAndAttachMerchantUser($oldOwner->id, $merchantId, 'manager');

                if (empty($error) === true)
                {
                    $oldOwner->refreshCurrentMerchant();
                }

                // adding merchant user mapping entry on both api and dashboard with owner role for existingUser user
                list($error, $response) = (new User\Service)->attachMerchantUserOnApi($existingUser->id, $merchantId, 'owner');

                $existingUser->refreshCurrentMerchant();
            }
            //change email of existing user attached to the merchant as owner
            else if (empty($selfUser) === false)
            {
                $emailData = [
                    'email' => $input['email']
                ];

                list($error, $response) = (new User\Service)->editUserOnApi($emailData, $selfUser->id);

                $selfUser->refreshCurrentMerchant();
            }
        }
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
            (in_array(key($input), array_keys(MerchantDetails\Entity::UPLOAD_KEYS)) == false))
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                'Invalid parameters.',
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        $field = MerchantDetails\Entity::UPLOAD_KEYS[key($input)];

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

    public function getUsersOfMerchantFromApi($merchantId)
    {
        $genericUsers = new PublicCollection;

        $getUsersOfMerchantFromApi = [
            'route_name' => 'merchant_fetch_users',
            'url_params' => [
                '{id}' => $merchantId,
            ]
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('GET', $getUsersOfMerchantFromApi);

        if (empty($error) === true)
        {
            $genericUsers = (new Helper)->createGenericUsers($data);
        }

        return [$error, $genericUsers];
    }

    public function tagMerchant(array $input)
    {
        if ($this->currentUser === null)
        {
            return [[], []];
        }

        $currentMerchant = $this->currentUser->currentMerchant();

        $merchantTags = $this->getMerchantTags($currentMerchant->id);

        $allTags = [];

        if (empty($merchantTags) === false)
        {
            $allTags = array_map('strtolower', $merchantTags);
        }

        $newAllTags = array_diff($allTags, ['newui']);

        if ((isset($input['newui']) === true) and ($input['newui'] === 'true'))
        {
            $newAllTags[] = 'newui';
        }

        $this->addMerchantTagsOnAPI($currentMerchant->id, $newAllTags);

        return [[], $currentMerchant->toArray()];
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
