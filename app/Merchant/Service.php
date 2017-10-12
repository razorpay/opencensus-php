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
    const EMAIL_CHANGE_FORBIDDEN                = "Email change forbidden on this account";
    const NAME_CHANGE_FORBIDDEN                 = "Name change forbidden on this account";
    const SELF_REMOVE_FORBIDDEN                 = "You cannot remove yourself.";
    const SUBMERCHANT_NOT_ALLOWED               = "Your account does not have sub-merchant creation privileges. Please contact support@razorpay.com";
    const ACCOUNT_CREATION_NOT_ALLOWED          = "You do not have account creation privileges. Please contact support@razorpay.com";
    const SUBMERCHANT_EMAIL_NOT_UNIQUE          = "Unique email is required to create a new user";
    const NOT_AUTHORIZED_TO_ACCESS_MERCHANT     = "Cannot access merchant";

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
        $currentMerchant = $this->currentUser->currentMerchant();

        $isLinkedAccount = (bool) (\Input::get('account') ?? false);

        $currentMerchant = Merchant\Entity::find($currentMerchant->id);

        $currentMerchantTags = $this->getMerchantTags($currentMerchant->id);

        if ($isLinkedAccount === true)
        {
            //if (in_array(Entity::MARKETPLACE, $currentMerchantTags) === false)
            //{
            //    return [[self::ACCOUNT_CREATION_NOT_ALLOWED], null];
            //}
        }
        else
        {
            /**
             * Checking if the current merchant is an aggregator
             * An aggregator is defined as a merchant
             * which can create other merchants without sending
             * them confirmation emails. All these merchants are also
             * created with the same email address
             */
            if (in_array(Entity::AGGREGATOR, $currentMerchantTags) === false)
            {
                return [[self::SUBMERCHANT_NOT_ALLOWED], null];
            }
        }

        $error = (new Merchant\Validator)->validateInput('create_submerchant', $input)
                                         ->messages();

        if (empty($error))
        {
            $businessName = $input['name'];

            $email = \Input::get('email');

            if (!$email or empty($email))
            {
                $email = $currentMerchant->email;
            }

            $merchant = Entity::createFromMerchant($currentMerchant, $businessName, $email, $isLinkedAccount);

            try
            {
                $this->createSubMerchantOnApi($merchant, $currentMerchant, $isLinkedAccount);
            }
            catch(ApiError $e)
            {
                return [[$e->getMessage()], null];
            }

            $merchant->save();

            if ($isLinkedAccount === false)
            {
                // We tag the merchant as referred from the original merchant on api
                $this->addMerchantTagsOnAPI($merchant->id, ['ref-'.$currentMerchant->id]);

                // Finally attach the current user to the new user's team
                // And also update the session user merchant list.
                list($error, $response) = (new User\Service)->attachMerchantUserOnApi($this->currentUser->id, $merchant->id, 'owner');

                if (empty($error) === true)
                {
                    User\Entity::find($this->currentUser->id)->merchants()->attach([$merchant->id], ['role' => 'owner']);

                    list($error, $genericUser) = (new User\Service)->getUserFromApi($this->currentUser->id);

                    if (empty($error) === true)
                    {
                        Session::put('dashboard_user_payload', $genericUser);
                    }
                }

                return [$error, $merchant->toArray()];
            }

            return [null, $merchant->toArray()];
        }
        else
        {
            return [$error, null];
        }
    }

    public function registerSubMerchantUser(array $input)
    {
        $currentMerchant = $this->currentUser->currentMerchant();

        $currentUser = User\Entity::getUserWithEmail($currentMerchant->email);

        $subMerchant = $this->fetch($input['id']);

        $email = $subMerchant['email'];
        $input['email'] = $email;

        $error = (new Merchant\Validator)->validateInput('create_submerchant_user', $input)->messages();

        if (empty($error) === false)
        {
            return [$error, null];
        }

        if ($email === $currentMerchant->email)
        {
            return [[self::SUBMERCHANT_EMAIL_NOT_UNIQUE], null];
        }

        list($error, $genericUser) = (new User\Service)->getUserFromApi($this->currentUser->id);

        $ownerMerchant = $genericUser->merchants
                                     ->where('role', 'owner')
                                     ->where('id', $subMerchant['id'])
                                     ->first();

        // checks if the main merchant's owner user is the primary
        // owner of the submerchant account
        if ($ownerMerchant === null)
        {
            return [[self::NOT_AUTHORIZED_TO_ACCESS_MERCHANT], null];
        }

        $input['name'] = $subMerchant['name'];
        $input['captcha_disable'] = User\Validator::DISABLE_CAPTCHA_SECRET;

        try
        {
            $user = (new User\Service)->createUserForSubmerchant($input);
            $user->save();

            $userApiData = (new User\Service)->getUserApiData($user);
            (new User\Service)->createUserOnApi($userApiData);

            // Finally attach the new user to the sub merchant
            list($error, $response) = (new User\Service)->attachMerchantUserOnApi($user->id, $input['id'], 'owner');

            if (empty($error) === true)
            {
                $user->joinMerchantByIdWithRole($input['id'], 'owner');
            }

            return [null, $user->toArray()];
        }
        catch(User\RecoverableException $e)
        {
            $error = [$e->getMessage()];

            return [$error, null];
        }
    }

    protected function createSubMerchantOnApi(Entity $merchant, Entity $aggregator, $isLinkedAccount)
    {
        $data = [
            'name'  =>  $merchant->name,
            'id'    =>  $merchant->id
        ];

        // Only send the email field if the email is not
        // the same as the aggregator email
        if ($merchant->email !== $aggregator->email)
        {
            $data['email'] = $merchant->email;
        }

        if ($isLinkedAccount)
        {
            $this->setApiCredentials($aggregator->id, 'test');
        }
        else
        {
            $this->setApiCredentials($aggregator->id);
        }


        $response = $this->api
                         ->merchant
                         ->createSubMerchant($data)
                         ->toArray();

        return $response;
    }
    /**
     * take care when calling this function
     * This is only called from the admin service
     * @param  string $id    Merchant Id
     * @param  array $input  Array with new Merchant Email Address
     */
    public function changeEmail($id, $input)
    {
        $merchant = Merchant\Entity::findorfail($id);

        $originalEmail = $merchant->email;

        if ($merchant->isTestAccount())
        {
            return [[static::EMAIL_CHANGE_FORBIDDEN], null];
        }

        $error = $merchant->changeEmail($input);

        if (empty($error))
        {
            $this->handleUserEmailChange($merchant, $originalEmail, $input); //This handles different cases of 'user' email change.

            $merchant->saveOrFail();
        }

        return [$error, null];
    }

    /**
     * This handles 3 possible cases when changing user email.
     * 1. There exists a team member with the new email
     *    Here, we swap the roles of the team member(manager) with new email and the original owner
     * 2. There exists a user(not team member) with the new email
     *    Here, we change the original owner to manager and then add the user with new email as owner
     * 3. The new email is unique so far
     *    Here, we just change the email of the original user(owner).
     * @param App\Merchant\Entity $merchant Merchant entity for which email is to be changed
     * @param array $input array containing the new email
     */
    protected function handleUserEmailChange($merchant, $originalEmail, $input)
    {
        if ($merchant->hasUsers())
        {
            $teamUser = $merchant->users()->where('email', $input['email'])->first();

            $existingUser = User\Entity::getUserWithEmail($input['email']);

            $selfUser = $merchant->users()->where('email', $originalEmail)->first();

            //The merchant has a team member with new email
            if ($teamUser !== null)
            {
                //swap roles between user with new email and original owner
                $oldOwner = $merchant->users()->where('role', 'owner')->first();

                // removing merchant user mapping entry on both api and dashboard for oldOwner user
                list($error, $response) = (new User\Service)->detachMerchantUserOnApi($oldOwner->id, $merchant->id);

                if (empty($error) === true)
                {
                    $merchant->removeUserById($oldOwner->id);
                }

                // adding merchant user mapping entry on both api and dashboard with manager role for oldOwner user
                list($error, $response) = (new User\Service)->attachMerchantUserOnApi($oldOwner->id, $merchant->id, 'manager');

                if (empty($error) === true)
                {
                    $oldOwner->joinMerchantByIdWithRole($merchant->id, 'manager');
                }

                // removing merchant user mapping entry on both api and dashboard for teamUser user
                list($error, $response) = (new User\Service)->detachMerchantUserOnApi($teamUser->id, $merchant->id);

                if (empty($error) === true)
                {
                    $merchant->removeUserById($teamUser->id);
                }

                // adding merchant user mapping entry on both api and dashboard with owner role for teamUser user
                list($error, $response) = (new User\Service)->attachMerchantUserOnApi($teamUser->id, $merchant->id, 'owner');

                if (empty($error) === true)
                {
                    $teamUser->joinMerchantByIdWithRole($merchant->id, 'owner');
                }
            }
            //There is an existing user with new email but not a team member
            else if ($existingUser !== null)
            {
                //assign owner to existing user and make existing owner a manager.
                $oldOwner = $merchant->users()->where('role', 'owner')->first();

                // removing merchant user mapping entry on both api and dashboard for oldOwner user
                list($error, $response) = (new User\Service)->detachMerchantUserOnApi($oldOwner->id, $merchant->id);

                if (empty($error) === true)
                {
                    $merchant->removeUserById($oldOwner->id);
                }

                // adding merchant user mapping entry on both api and dashboard with manager role for oldOwner user
                list($error, $response) = (new User\Service)->attachMerchantUserOnApi($oldOwner->id, $merchant->id, 'manager');

                if (empty($error) === true)
                {
                    $oldOwner->joinMerchantByIdWithRole($merchant->id, 'manager');
                }

                // adding merchant user mapping entry on both api and dashboard with owner role for existingUser user
                list($error, $response) = (new User\Service)->attachMerchantUserOnApi($existingUser->id, $merchant->id, 'owner');

                if (empty($error) === true)
                {
                    $existingUser->joinMerchantByIdWithRole($merchant->id, 'owner');
                }
            }
            //change email of existing user attached to the merchant as owner
            else if ($selfUser)
            {
                $emailData = [
                    'email' => $input['email']
                ];

                list($error, $response) = (new User\Service)->editUserOnApi($emailData, $selfUser->id);

                if (empty($error) === true)
                {
                    $selfUser->email = $input['email'];

                    $selfUser->saveOrFail();
                }
            }
        }
    }

    public function resendConfirmation()
    {
        $authUser = Auth::user();

        $authUserId = $authUser->id;

        $data = [
            'user_id' => $authUserId
        ];

        $resendConfirmation = [
            'route_name' => 'user_resend_verification',
            'body'       => $data
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

    public function fetch($merchantId)
    {
        list($error, $merchant) = $this->fetchMerchantFromApi($merchantId);

        return $merchant;
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

    /**
     * Remove the team member on the given merchant.
     *
     * @param  string  $userId
     * @return \Illuminate\Http\Response
     */
    public function removeTeamMemberForOwner($userId)
    {
        $error = [];

        if ($userId === $this->currentUser->id)
        {
            return [static::SELF_REMOVE_FORBIDDEN];
        }

        $currentMerchant = $this->currentUser->currentMerchant();

        $removeTeamMemberForOwner = [
            'route_name' => 'user_merchant_mapping_action',
            'url_params' => [
                '{id}'     => $userId,
                '{action}' => 'detach'
            ],
            'body'       => [
                'merchant_id' => $currentMerchant->id
            ]
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('PUT', $removeTeamMemberForOwner);

        return $error;
    }

    /**
     * Update a team member on the given merchant.
     *
     * @param  string  $userId
     * @param  array   $input
     * @return \Illuminate\Http\Response
     */
    public function updateTeamMemberForOwner($userId, $input)
    {
        $error = [];

        if ($userId === $this->currentUser->id)
        {
            $error[] = "You cannot change your role.";

            return [$error, null];
        }

        $validator = (new Merchant\Entity)->validateInput('updateTeamMember',$input);

        if ($validator->fails())
        {
            $error = $validator->messages();

            return [$error, null];
        }

        list($error, $users) = $this->getUsersOfMerchantFromApi($this->currentUser->currentMerchant()->id);

        $updatedUser = $users->where('id', $userId)
                             ->first();

        if ($updatedUser === null)
        {
            $error[] = "The team member you are looking for doesn't exist";

            return [$error, null];
        }

        $newRole = $input['role'];

        list($error, $response) = (new User\Service)->updateMerchantUserMappingOnApi(
                                                        $userId,
                                                        $this->currentUser->currentMerchant()->id,
                                                        $newRole);
        if (empty($error) === true)
        {
            User\Entity::find($userId)
                        ->merchants()
                        ->updateExistingPivot(
                            $this->currentUser->currentMerchant()->id,
                            ['role' => $newRole]);
        }

        return [$error, null];
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
        $error = [];

        $genericUsers = new PublicCollection;

        $this->setApiCredentials();

        try
        {
            $response = $this->api->merchant->getUsers($merchantId)->toArray();

            $genericUsers = (new Helper)->createGenericUsers($response);
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();
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
