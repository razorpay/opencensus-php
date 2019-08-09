<?php

namespace RZP\Models\User;

use Mail;
use Hash;
use Config;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use RZP\Models\Invitation;
use RZP\Models\Admin\Admin;
use RZP\Mail\User as UserMail;
use RZP\Models\Admin\AdminLead;
use RZP\Models\Merchant\Account;
use Illuminate\Hashing\BcryptHasher;

class Service extends Base\Service
{
    public function register(array $input): array
    {
        $data = [];

        $referrer = $input['ref'] ?? '';

        $invitationToken = $input['invitation'] ?? null;

        $businessName = $input['business_name'] ?? '';

        $invitation = null;

        $user = null;

        $tokenData = null;

        $this->trace->count(Merchant\Metric::SIGNUP_TOTAL);

        $this->app->hubspot->trackSignupEvent($input);

        /*
         * If we have an invitation token, the user may have created an account
         * in the meantime. $user will be equal to the user with the same email
         * as the invited user
         */
        if (empty($invitationToken) === false)
        {
            $invitation = (new Invitation\Service)->fetchByToken($invitationToken);

            $user = (new Core)->getUserFromEmail($invitation);

            // Since input would be lacking an email in case of registration via the invitation
            $input[Entity::EMAIL] = $invitation[Invitation\Entity::EMAIL];

            unset($input['invitation']);
        }

        $heimdallInvitationToken = $input['merchant_invitation'] ?? null;

        $adminId = null;

        if (empty($heimdallInvitationToken) === false)
        {
            // Check if this token is valid or not
            $tokenData = (new AdminLead\Service)->verify($heimdallInvitationToken);

            if (isset($tokenData['id']) === true)
            {
                $tokenSignUpInput = [AdminLead\Entity::SIGNED_UP => 1];

                (new AdminLead\Service)->editInvitation(
                    $tokenData[AdminLead\Entity::ORG_ID], $tokenData[AdminLead\Entity::ID], $tokenSignUpInput);
            }

            unset($input['merchant_invitation']);
        }

        /**
         * $user would not be null in a very rare edge case here
         * which happens when two subsequent invitations without either being
         * accepted. Once the second one is accepted, this block
         * is ignored and the $user found above will be used
         */
        if (empty($user) === true)
        {
            $input[Entity::PASSWORD_CONFIRMATION] = $input[Entity::PASSWORD];

            $input[Entity::NAME] = $input[Entity::NAME] ?? '';

            unset($input['ref']);

            unset($input['business_name']);

            $user = $this->create($input);
        }

        /**
         * These two conditions are exclusive
         * One cannot accept an invite and create a merchant account at the same time
         */
        if (empty($invitationToken) === false)
        {
            $invitationAcceptInput = [
                Invitation\Entity::USER_ID => $user[Entity::ID],
                Invitation\Entity::ACTION  => 'accept',
                Invitation\Entity::EMAIL   => $user[Entity::EMAIL],
            ];

            (new Invitation\Service)->action($invitation[Invitation\Entity::ID], $invitationAcceptInput);

            $this->confirm($user[Entity::ID]);

            (new Core)->subscribeToMailingList($user);

             $data['login'] = true;
        }
        else
        {
            $merchantInputData = [
                Merchant\Entity::EMAIL => $user[Entity::EMAIL],
                Merchant\Entity::NAME  => $businessName
            ];

            if (empty($tokenData) === false)
            {
                // Merchant belongs to the same org that the inviting admin does
                $merchantInputData[Merchant\Entity::ORG_ID] = $tokenData[AdminLead\Entity::ORG_ID];
                // Map merchant to the admin that generated his lead (invited merchant to sign up)
                $merchantInputData[Merchant\Entity::ADMINS] = [$tokenData[AdminLead\Entity::ADMIN_ID]];
            }

            $data = $this->createMerchantFromUser($merchantInputData, $user, $referrer);
        }

        return $data;
    }

    /**
     * Creates Merchant for user.
     * @param array  $merchantInputData
     * @param array  $userData
     * @param string $referrer
     *
     * @return array
     */
    protected function createMerchantFromUser(array $merchantInputData, array $userData, string $referrer = '')
    {
        $merchantData = (new Merchant\Service)->create($merchantInputData);

        if (empty($referrer) === false)
        {
            $tagInputData = [
                'tags' => ['ref-'.$referrer],
            ];

            (new Merchant\Service)->addTags($merchantData['id'], $tagInputData);
        }

        $userMerchantMappingInputData = [
            'action'      => 'attach',
            'role'        => 'owner',
            'merchant_id' => $merchantData['id'],
        ];

        $this->updateUserMerchantMapping($userData['id'], $userMerchantMappingInputData);

        $this->sendConfirmationMail($userData['id']);

        return [
            'id'    => $merchantData['id'],
            'name'  => $merchantData['name'],
            'email' => $userData['email'],
        ];
    }

    /**
     * @param $userId
     *
     * @return array
     */
    public function sendConfirmationMail($userId)
    {
        $user = $this->repo->user->findOrFailPublic($userId);

        // Only send the confirmation email if the user isn't already confirmed
        if ($user->getConfirmedAttribute() === false)
        {
            $orgId = $this->auth->getOrgId();

            $org = $this->repo->org->findByPublicId($orgId)->toArrayPublic();

            $org['hostname'] = $this->auth->getOrgHostName();

            $requestOriginProduct = $this->auth->getRequestOriginProduct();

            $confirmationMail = new UserMail\AccountVerification($user, $org, $requestOriginProduct);

            Mail::queue($confirmationMail);
        }
        else
        {
            // if user is already confirmed then sending confirm as true.
            return ['confirm' => true];
        }

        return ['success' => true];
    }

    public function create(array $input): array
    {
        $user = (new Core)->create($input);

        return $user->toArrayPublic();
    }

    public function edit(string $id, array $input): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->edit($user, $input);

        return $user->toArrayPublic();
    }

    /**
     * Edit user action for logged in user (via Dashboard headers).
     *
     * @param  array  $input
     * @return array
     */
    public function editSelf(array $input): array
    {
        $this->core()->edit($this->user, $input);

        return $this->user->toArrayPublic();
    }

    public function confirm(string $id): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->confirm($user);

        return $user->toArrayPublic();
    }

    public function confirmUserByData(array $input): array
    {
        $user = null;

        (new Entity)->getValidator()->validateInput('confirm', $input);

        // need to validate if it is only a confirm_token or an email
        if (empty($input[Entity::CONFIRM_TOKEN]) === false)
        {
            $user = $this->repo->user->findByToken($input[Entity::CONFIRM_TOKEN]);
        }
        else if (empty($input[Entity::EMAIL]) === false and $this->auth->isAdminAuth() === true)
        {
            $user = $this->repo->user->findByEmail($input[Entity::EMAIL]);
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_NOT_FOUND);
        }

        $user = (new Core)->confirm($user);

        $data = $user->toArrayPublic();

        (new Core)->subscribeToMailingList($data);

        return $data;
    }

    public function changePassword(array $input): array
    {
        $user = $this->user;

        (new Core)->edit($user, $input, 'changePassword');

        return $user->toArrayPublic();
    }

    public function updateUserMerchantMapping(string $id, array $input): array
    {
        $input[Merchant\Entity::PRODUCT] = $this->auth->getRequestOriginProduct();

        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->updateUserMerchantMapping($user, $input);

        return $user->toArrayPublic();
    }

    public function login(array $input): array
    {
        return (new Core)->login($input);
    }

    public function checkUserAccess(string $id, array $input)
    {
        $merchantId = Account\Entity::verifyIdAndSilentlyStripSign(
            $input['merchant_id']
        );

        return (new Core)->checkUserAccess($id, $merchantId);
    }

    public function setup2faMobileOnLogin(array $input): array
    {
        return (new Core)->setup2faMobileOnLogin($input);
    }

    public function setup2faVerifyMobileOnLogin(array $input): array
    {
        return (new Core)->setup2faVerifyMobileOnLogin($input);
    }

    public function get(string $id): array
    {
        if ($this->auth->isAdminAuth() === true)
        {
            $user = $this->repo->user->findOrFailPublic($id);
        }
        else
        {
            // Using user context from header to avoid IDOR.
            $user = $this->auth->getUser();
        }

        $response = (new Core)->get($user);

        return $response;
    }

    public function updateMerchantManageTeam(string $userId, array $input): array
    {
        $input['merchant_id'] = $this->merchant->getId();

        $teamData = [
            'merchant_id' => $input['merchant_id'],
            'user_id'     => $userId,
        ];

        (new User\Validator)->validateInput('teamManagement', $teamData);

        return $this->updateUserMerchantMapping($userId, $input);
    }

    /**
     * Upgrades given user to merchant.
     * @param array $input
     *
     * @return array
     */
    public function upgradeUserToMerchant(array $input)
    {
        $userId = $input['user_id'];

        $user = $this->repo->user->findOrFailPublic($userId)->toArrayPublic();

        $merchantData = [
            'name'  => $input['business_name'],
            'email' => $user['email'],
        ];

        $data = $this->createMerchantFromUser($merchantData, $user);

        return $data;
    }

    /**
     * Resend verification mail for not confirmed user.
     * @param string $userId
     *
     * @return array
     */
    public function resendVerificationMail()
    {
        $dashboardHeaders = $this->auth->getDashboardHeaders();

        $data = $this->sendConfirmationMail($dashboardHeaders['user_id']);

        return $data;
    }

    public function postResetPassword(array $input)
    {
        if (isset($input['email']) === true)
        {
            $email = mb_strtolower($input['email']);

            //find or fail public by email.
            $user = $this->repo->user->getUserFromEmail($email);

            if (empty($user) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_USER_NOT_FOUND);
            }

            $orgId = $this->auth->getOrgId();

            //get Org and send it to mailer, deal with other orgs as well.
            $org = $this->repo->org->findByPublicId($orgId)->toArrayPublic();

            $org['hostname'] = $this->auth->getOrgHostName();

            $requestOriginProduct = $this->auth->getRequestOriginProduct();

            $passwordResetMail = new UserMail\PasswordReset($user, $org, $requestOriginProduct);

            Mail::queue($passwordResetMail);
        }

        return ['success' => true];
    }

    /**
     * This email goes to sub-merchant user when the aggregator/partner
     * tries to create a login for him but the user account already
     * exists and we just attach it to the sub-merchant in question.
     *
     * @param  Entity          $user
     * @param  Merchant\Entity $submerchant
     *
     * @return array
     */
    public function postAccountMappedEmail(Entity $user, Merchant\Entity $submerchant)
    {
        $orgId = $this->auth->getOrgId();

        $org = $this->repo->org->findByPublicId($orgId)->toArrayPublic();

        $org['hostname'] = $this->auth->getOrgHostName();

        $submerchantArray = $submerchant->toArrayPublic();

        $accountMappedMail = new UserMail\MappedToAccount($user, $org, $submerchantArray);

        Mail::queue($accountMappedMail);

        return ['success' => true];
    }

    /**
     * Sends Linked Account access email with user password reset link.
     *
     * @param Entity            $user
     * @param Merchant\Entity   $subMerchant
     *
     * @return array
     */
    public function postLinkedAccountAccessEmail(Entity $user, Merchant\Entity $subMerchant): array
    {
        $orgId = $this->auth->getOrgId();

        $org = $this->repo->org->findByPublicId($orgId)->toArrayPublic();

        $org['hostname'] = $this->auth->getOrgHostName();

        $linkedAccountAccessMail = new UserMail\LinkedAccountUserAccess($user, $org, $subMerchant);

        Mail::queue($linkedAccountAccessMail);

        return ['success' => true];
    }

    public function getTokenWithExpiry(string $userId, int $expiry): string
    {
        $userCore = (new User\Core);

        $expiryTime = Carbon::now()->timestamp + $expiry;

        $token = $userCore->generateToken();

        $user = $this->repo->user->findOrFailPublic($userId);

        $userCore->savePasswordResetTokenAndExpiry($user, $token, $expiryTime);

        return $token;
    }

    /**
     * @param  array $input
     *
     * @return array
     *
     * @throws Exception\BadRequestException
     */
    public function changePasswordByToken(array $input)
    {
        (new User\Validator)->validateInput('changePasswordToken', $input);

        $email = mb_strtolower($input['email']);

        /** @var Entity $user */
        $user = $this->repo->user->findByEmail($email);

        $expiry = $user->getPasswordResetExpiry();

        $now = Carbon::now()->getTimestamp();

        if ($expiry < $now)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID);
        }

        $token = $user->getPasswordResetToken();

        if ((empty($token) === true) or (hash_equals($token, $input['token']) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID);
        }
        else
        {
            (new Core)->setNewPassword($user, $input);

            // Password reset via mail essentially confirms the email.
            if ($user->getConfirmedAttribute() === false)
            {
                (new Core)->confirm($user);
            }
        }

        return ['success' => true];
    }

    public function addUtmParameters(& $data)
    {
        if (empty(\Cookie::get('rzp_utm')) === false)
        {
            $utmParams = json_decode(\Cookie::get('rzp_utm'), true);
            $data[Constants::CTA]       = $utmParams[Constants::CTA] ?? '';
            $data[Constants::WEBSITE]   = $utmParams[Constants::WEBSITE] ?? '';

            if (empty($utmParams[Constants::ATTRIBUTIONS]) === false)
            {
                $utmParams[Constants::ATTRIBUTIONS][1] = $utmParams[Constants::ATTRIBUTIONS][1] ??
                    $utmParams[Constants::ATTRIBUTIONS][0];

                foreach (Constants::$attributionList as $attribution)
                {
                    $data['first_' . $attribution] = $utmParams[Constants::ATTRIBUTIONS][0][$attribution] ?? '';
                    $data['final_' . $attribution] = $utmParams[Constants::ATTRIBUTIONS][1][$attribution] ?? '';
                }
            }
        }
    }

    /**
     * If we create a new user we send him a password reset link to start using dasboard
     * The reset flow will also confirm the user in the process.
     * If we find an existing user with the sub-merchant email then we send a mail informing
     * that he has access to sub-merchant account also now.
     *
     * @param User\Entity     $subMerchantUser
     * @param Merchant\Entity $subMerchant
     * @param boolean         $createdNew
     *
     */
    public function sendAccountLinkedCommunicationEmail(
                                                        User\Entity $subMerchantUser,
                                                        Merchant\Entity $subMerchant,
                                                        bool $createdNew)
    {
        if (($createdNew === true) and ($subMerchant->isLinkedAccount() === true))
        {
            $this->postLinkedAccountAccessEmail($subMerchantUser, $subMerchant);
        }
        else if ($createdNew === true)
        {
            $this->postResetPassword([User\Entity::EMAIL => $subMerchantUser[User\Entity::EMAIL]]);
        }
        else
        {
            $this->postAccountMappedEmail($subMerchantUser, $subMerchant);
        }
    }

    public function syncMerchantUserOnProducts(string $merchantId)
    {
        $userRole = null;

        $product = $this->auth->getRequestOriginProduct();

        $user = $this->auth->getUser();

        $switchProduct = ($product === Product::BANKING) ? Product::PRIMARY : Product::BANKING;

        $userMapping = $this->repo->merchant->getMerchantUserMapping($merchantId,
                                                                     $user->getId(),
                                                                     null,
                                                                     $switchProduct);

        if (empty($userMapping) === false)
        {
            $currentUserRole = $userMapping->pivot->role;

            if (in_array($currentUserRole, Role::BANKING_ROLES, true) === true)
            {
                (new Merchant\Service)->switchProductMerchant($product);

                $userRole = $currentUserRole;
            }
        }

        return $userRole;
    }

    /**
     * This will assign applicable role to the product by checking it's origin.
     * PG Owner/Admin role on BB will be Owner/Admin. rest all other roles will be rejected and viceversa.
     *
     * @param $product
     *
     * @return null|\RZP\Models\User\Entity
     */
    public function addProductSwitchRole($product)
    {
        $user = $this->auth->getUser();

        $product = $product ?? $this->auth->getRequestOriginProduct();

        $merchantId = $this->auth->getMerchantId();

        // Check if a role for this user already exists with the existing product merchant user mapping.
        $userMapping = $this->repo->merchant->getMerchantUserMapping($merchantId,
                                                                     $user->getId(),
                                                                     null,
                                                                     $product);

        if (empty($userMapping) === true)
        {
            // Since we have user roles in headers we can get the opposite product easily.
            // In switch we have to assign the role for merchants with only the opposite product side role.
            // Like Owner in PG will be Owner in BB and Admin in BB will be Admin in PG.

            $switchProduct = ($product === Product::BANKING) ? Product::PRIMARY : Product::BANKING;

            $userMapping = $this->repo->merchant->getMerchantUserMapping($merchantId,
                $user->getId(),
                null,
                $switchProduct);

            $productRole = null;

            if (empty($userMapping) === false)
            {
                $productRole = $userMapping->pivot->role;
            }

            $userMerchantMappingInputData = [
                'action'      => 'attach',
                'role'        => $productRole,
                'merchant_id' => $merchantId,
                'product'     => $product,
            ];

            $user = (new User\Core)->updateUserMerchantMapping($user, $userMerchantMappingInputData);
        }

        return $user;
    }

    public function sendOtp(array $input)
    {
        $this->user->getValidator()->validateSendOtpOperation($input);

        return $this->core()->sendOtp($input, $this->merchant, $this->user);
    }

    public function verifyContactWithOtp(array $input): array
    {
        $this->user->getValidator()->validateVerifyContactWithOtpOperation($input);

        $this->core()->verifyContactWithOtp($input, $this->merchant, $this->user);

        return $this->user->toArrayPublic();
    }

    /**
     * Change 2fa setting of user (enable/disable)
     *
     * @param array  $input
     *
     * @return array
     */
    public function change2faSetting(array $input)
    {
        $this->user->getValidator()->validateInput('change2faSetting', $input);

        $isPasswordEqual = (new BcryptHasher)->check($input[Entity::PASSWORD], $this->user->getPassword());

        if ($isPasswordEqual === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PASSWORD);
        }

        return $this->core()->change2faSetting($this->user, $input);
    }

    /**
     * @param  string $id
     * @param  array  $input
     *
     * @return array
     */
    public function resetUserPassword(string $id, array $input)
    {
        /** @var Admin\Entity $admin */
        $admin = $this->auth->getAdmin();

        $merchant = $this->auth->getMerchant();

        $user = $this->repo->user->findOrFailPublic($id);

        $userValidator = (new Validator);

        // TODO: Remove this after handling properly in AdminAccess middleware
        // It does not handle org id as of now, also need to test admin-
        // merchant relations
        (new Merchant\Validator)->validateAdminMerchantAccess($admin, $merchant);

        $userValidator->validateMerchantUserRelation($merchant, $user);

        $userValidator->validateInput('changePasswordAdmin', $input);

        $this->core()->edit($user, $input, 'changePasswordAdmin');

        return ['success' => true];
    }
}
