<?php

namespace RZP\Models\Merchant\Detail;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Admin;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\Action as Action;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Merchant\SlackActions as SlackActions;
use RZP\Models\Merchant\Detail\RejectionReasons as RejectionReasons;

class Service extends Base\Service
{
    use NotifyTrait;

    public function fetchMerchantDetails()
    {
        $merchantDetails = (new Core)->getMerchantDetails($this->merchant);

        return (new Core)->createResponse($merchantDetails);
    }

    public function fetchActivationFiles(string $id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchantDetails = (new Core)->getMerchantDetails($merchant);

        $signedUrls = [];

        $fileFields = $this->getFileFields($merchant);

        foreach ($fileFields as $key => $value)
        {
            if (isset($merchantDetails[$key]) === true)
            {
                $signedUrls[$value] = $this->getSignedUrl($merchantDetails[$key], $id);
            }
        }

        return ['files' => $signedUrls];
    }

    public function saveMerchantDetails(array $input)
    {
        return (new Core)->saveMerchantDetails($input, $this->merchant);
    }

    /**
     * This function is used to patch merchant details fields
     * @param array $input
     *
     * @return array
     */
    public function patchMerchantDetails(array $input): array
    {
        $merchantDetails = $this->merchant->merchantDetail;

        $merchantDetails = (new Core)->patchMerchantDetails($merchantDetails, $input);

        return $merchantDetails->toArrayPublic();
    }

    public function uploadActivationFileAdmin(string $merchantId, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        // Do not check if the activation form is locked
        return $this->uploadActivationFile($merchant, $input, false);
    }

    public function uploadActivationFileMerchant(array $input)
    {
        return $this->uploadActivationFile($this->merchant, $input);
    }

    /**
     * Upload the file passed in $input for $merchant
     *
     * @param  Merchant\Entity $merchant          Merchant Entity
     * @param  array           $input
     *                                            Input with the file
     * @param  boolean         $validateLock      If true, blocks edits if the form is locked. Can be set to false
     *                                            to bypass locked forms
     *
     * @return array
     */
    public function uploadActivationFile(Merchant\Entity $merchant, array $input, bool $validateLock = true)
    {
        $core = new Core;

        $merchantDetails = $core->getMerchantDetails($merchant, $input);

        if ($validateLock === true)
        {
            $merchantDetails->getValidator()->validateIsNotLocked();
        }

        $merchantDetails->edit($input);

        $params = $this->storeActivationFile($merchantDetails, $input);

        $merchantDetails->fill($params);

        $response = $core->createResponse($merchantDetails);

        $merchantDetails->setActivationProgress($response['verification']['activation_progress']);

        $this->repo->saveOrFail($merchantDetails);

        $response = $core->createResponse($merchantDetails); // Earlier $response could become stale while simulataneous uploads. So prepare fresh response.

        return $response;
    }

    public function storeActivationFile(
        Entity $merchantDetails,
        array $input)
    {
        $params = [];

        $merchant = $merchantDetails->merchant;

        foreach ($input as $key => $value)
        {
            $merchantDetails->getValidator()->validateFileType($value);

            // Adding a prefix hash for filename to avoid overwrites to the same fileName on S3.
            $partial = substr(bin2hex(random_bytes(6)), 0, 5);

            $fileName = 'api/' . $merchant->getId() .'/' . $partial . '/' . $key;

            $file = $this->createFile(
                $merchantDetails,
                $value->extension(),
                $value,
                $fileName,
                $key,
                $merchant);

            $params[$key] = FileStore\Entity::verifyIdAndSilentlyStripSign($file['id']);
        }

        return $params;
    }

    public function editMerchantDetails($id, array $input)
    {
        $slackAction = null;

        if (isset($input['locked']) === true or
            isset($input['comment']) === true)
        {
            if (isset($input['locked']) === true)
            {
                $action = ($input['locked'] === true) ? Action::LOCK : Action::UNLOCK;

                $slackAction = ($input['locked'] === true) ? SlackActions::LOCK : SlackActions::UNLOCK;
            }
            else
            {
                $action = Action::EDIT_COMMENT;
            }

            $admin = $this->app['basicauth']->getAdmin();

            $admin->hasMerchantActionPermissionOrFail($action);
        }

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchantDetailCore = new Core;

        $merchantDetails = $merchantDetailCore->editMerchantDetailFields($merchant, $input);

        if (isset($slackAction) === true)
        {
            $this->logActionToSlack($merchant, $slackAction);
        }

        return $merchantDetailCore->createResponse($merchantDetails);
    }

    protected function createFile(Entity $merchantDetail,
                                    string $extension,
                                    $file,
                                    string $fileName,
                                    string $type,
                                    Merchant\Entity $merchant,
                                    string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $file = $creator->extension($extension)
                        ->localFile($file)
                        ->name($fileName)
                        ->store($store)
                        ->type($type)
                        ->entity($merchantDetail)
                        ->merchant($merchant)
                        ->save()
                        ->get();

        return $file;
    }

    private function getFileFields(Merchant\Entity $merchant) : array
    {
        return ($merchant->isLinkedAccount() === true) ? Constants::UPLOAD_KEYS_ACCOUNT : Constants::UPLOAD_KEYS;
    }

    protected function getSignedUrl(string $fileStoreId, string $merchantId)
    {
        $core = new FileStore\Core;

        // [ id1 => url1, id2 => url2, ... ]
        $signedUrls = $core->getSignedUrl($fileStoreId, $merchantId);

        return $signedUrls;
    }

    private function getFieldsToStepMap() : array
    {
        // Fetching Action Form details schema based on account type.
        $isLinkedAccount = $this->merchant->isLinkedAccount();

        if ($isLinkedAccount === true)
        {
            return Merchant\Constants::STEP_MAP_ACCOUNT;
        }
        else
        {
            return Merchant\Constants::STEP_MAP;
        }
    }

    private function getStepsList() : array
    {
        $stepsList = array_values($this->getFieldsToStepMap());

        return array_values(array_unique($stepsList));
    }

    private function calculateSteps($merchantDetails) : array
    {
        $stepFinished = [];

        $stepMap = $this->getFieldsToStepMap();

        $requiredFields = $merchantDetails['verification']['required_fields'] ?? [];

        foreach ($requiredFields as $key)
        {
            if (isset($stepMap[$key]) === true)
            {
                $stepFinished[] = $stepMap[$key];
            }
        }

        return $stepFinished;
    }

    /**
     * This function is used for archiving merchant activation form
     * @param string $merchantId
     * @param array $input
     *
     * @return array
     */
    public function updateActivationArchive(string $merchantId, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantDetails = $merchant->merchantDetail;

        $admin = $this->app['basicauth']->getAdmin();

        $merchantDetails = (new Core)->updateActivationArchive($merchantDetails, $input, $admin);

        return $merchantDetails->toArrayPublic();
    }

    /**
     * This function is used for updating merchant activation status
     * @param string $merchantId
     * @param array $input
     *
     * @return array
     */
    public function updateActivationStatus(string $merchantId, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantDetails = $merchant->merchantDetail;

        $admin = $this->app['basicauth']->getAdmin();

        $merchantDetails = (new Core)->updateActivationStatus($merchantDetails, $input, $admin);

        return $merchantDetails->toArrayPublic();
    }

    /**
     * This function is used for getting the activation status change log of a merchant
     * @param string $merchantId
     *
     * @return array
     */
    public function getActivationStatusChangeLog(string $merchantId): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $activationStatusChangeLog = (new Merchant\Core)->getActivationStatusChangeLog($merchant);

        return $activationStatusChangeLog->toArrayPublic();
    }

    /**
     * This function is used for updating website details of a merchant
     * @param array $input
     *
     * @return array
     */
    public function updateWebsiteDetails(array $input): array
    {
        $merchantDetails = $this->merchant->merchantDetail;

        $merchantDetails = (new Core)->updateWebsiteDetails($merchantDetails, $input);

        return $merchantDetails->toArrayPublic();
    }

    /**
     * This function is used for getting business categories subcategories list
     *
     * @return array
     */
    public function getBusinessCategories(): array
    {
        return BusinessCategory::SUBCATEGORY_MAP;
    }

    public function getRejectionReasons()
    {
        return RejectionReasons::REJECTION_REASONS_MAPPING;
    }

    public function getMerchantDetailsForAdmin() : array
    {
        // Formatting the data as required by the controller.
        $merchantDetails = $this->fetchMerchantDetails();

        // Finished steps will be calculated based on required fields.
        $this->calculateFinishedSteps($merchantDetails);

        return $merchantDetails;
    }

    private function calculateFinishedSteps(array & $merchantDetails)
    {
        // Get steps for the current merchant.
        $steps = $this->getStepsList();

        if($merchantDetails['can_submit'] === true)
        {
            $merchantDetails['steps_finished'] = $steps;
        }
        else
        {
            // By checking merchant details unfinished steps will be calculated.
            $unfinishedSteps = $this->calculateSteps($merchantDetails);

            if(count($unfinishedSteps) !== 0)
            {
                $unfinishedSteps = array_unique($unfinishedSteps);

                $finishedSteps = array_values(array_diff($steps, $unfinishedSteps));

                $merchantDetails['steps_finished'] = $finishedSteps;
            }
        }
    }

    /**
     * Will get pre signup details from merchant details.
     *
     * @return array
     */
    public function getPreSignupDetails(): array
    {
        // Referrer merchant doesn't need to complete presignup details.
        $referrerMerchant = $this->merchant->getReferrer();

        $presignupDetails = [];

        // Referrer Merchant check for presignup details.
        if ((empty($referrerMerchant) === true) or
            (Merchant\Entity::verifyUniqueId($referrerMerchant, false) === 0))
        {
            $merchantDetails = $this->fetchMerchantDetails();

            $presignupFields = Constants::PRE_SIGNUP_FIELDS;

            foreach ($presignupFields as $key)
            {
                if (empty($merchantDetails[$key]) === false)
                {
                    $presignupDetails[$key] = (string) $merchantDetails[$key];
                }
                else
                {
                    $presignupDetails[$key] = null;
                }
            }
        }

        return $presignupDetails;
    }

    /**
     * Edit pre signup details.
     *
     * @param array $input
     *
     * @return array
     */
    public function editPreSignupDetails(array $input) : array
    {
        (new Validator)->validateInput('pre_signup', $input);

        $this->saveMerchantDetails($input);

        if (empty($input[Entity::BUSINESS_NAME]) === false)
        {
            $inputName = ['name' => $input[Entity::BUSINESS_NAME]];

            // Validate Input Name for merchant
            (new Merchant\Validator)->validateInput('edit_name', $inputName);

            (new Merchant\Service)->edit($this->merchant->id, $inputName);

            // Save User Information of contact name nad contact Email.

            $user = $this->merchant->primaryOwner();

            $userEditData['contact_mobile'] = $input['contact_mobile'] ?? null;
            $userEditData['name']           = $input['contact_name'] ?? null;

            $userEditData = array_filter($userEditData);

            (new User\Validator)->validateInput('pre_signup', $userEditData);

            (new User\Service)->edit($user->id, $userEditData);

            // Dump data to zapier.

            $zapierData = $this->getZapierData($this->merchant, $input);

            (new Core)->postFormSubmissionToZapier($zapierData, 'signups');
        }

        $preSignupDetails = $this->getPreSignupDetails();

        return $preSignupDetails;
    }

    private function getZapierData($merchant, $input)
    {
        $this->merchant->reload();

        // This is the same format we'll set in the google spreadsheet
        $timestamp = Carbon::createFromTimeStamp(time(), Timezone::IST)->format('Y-m-d\TH:i:s+05:30');

        $userName = $input['contact_name'] ?? '';

        $phoneNumber = $input['contact_mobile'] ?? '';

        $businessType = isset($input['business_type']) ? BusinessType::getType($input['business_type']) : '';

        $transactionVolume = isset($input['transaction_volume']) ?
            TransactionVolume::getVolume($input['transaction_volume']) : '';

        $role = isset($input['role']) ? Role::getType($input['role']) : '';

        $department = isset($input['department']) ? Department::getType($input['department']) : '';

        $referrer = $merchant->referrer ?? '';

        $data = [
            Entity::ID                 => $merchant->id,
            Merchant\Entity::EMAIL     => $merchant->email,
            Constants::INDIVIDUAL      => $userName,
            Merchant\Entity::NAME      => $merchant->name,
            Constants::REF             => $referrer,
            Constants::SIGNUP_DATE     => $timestamp,
            Constants::CONTACT         => $phoneNumber,
            Entity::BUSINESS_TYPE      => $businessType,
            Entity::TRANSACTION_VOLUME => $transactionVolume,
            Entity::ROLE               => $role,
            Entity::DEPARTMENT         => $department,
        ];

        (new User\Service)->addUtmParameters($data);

        return $data;
    }

    /**
     * This function is used to get zapier data for activation
     *
     * @param Merchant\Entity $merchant
     *
     * @return array
     */
    public function getActivationZapierData(Merchant\Entity $merchant): array
    {
        $date = Carbon::createFromTimeStamp(time(), Timezone::IST)->format('j/m/Y');

        $merchantDetails = $merchant->merchantDetail;

        return [
            Constants::DATE          => $date,
            Merchant\Entity::ID      => $merchant->id,
            Merchant\Entity::EMAIL   => $merchant->email,
            Merchant\Entity::NAME    => $merchant->name,
            Entity::CONTACT_NAME     => $merchantDetails->contact_name,
            Entity::BUSINESS_NAME    => $merchantDetails->business_name,
            Entity::BUSINESS_DBA     => $merchantDetails->business_dba,
            Entity::BUSINESS_WEBSITE => $merchantDetails->business_website,
            Constants::REF           => $merchant->referrer,
        ];
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function bulkAssignReviewer(array $input)
    {
        (new Validator)->validateInput('bulk_assign_reviewer', $input);

        $merchants  = $input[Entity::MERCHANTS];

        $reviewerId = $input[Entity::REVIEWER_ID];

        return (new Core)->bulkAssignReviewer($reviewerId, $merchants);
    }

    public function getMerchantActivationReviewers()
    {
        $orgId = $this->auth->getOrgId();

        Org\Entity::verifyIdAndStripSign($orgId);

        $permission = $this->repo
                            ->permission
                            ->findByOrgIdAndPermission($orgId, Admin\Permission\Name::EDIT_ACTIVATE_MERCHANT);

        if (empty($permission) === true)
        {
            throw new Exception\RuntimeException('Missing Permission');
        }

        $admins = new Base\Collection;

        foreach ($permission->roles as $role)
        {
            foreach ($role->admins as $roleAdmin)
            {
                $admins->push($roleAdmin->toArrayPublic());
            }
        }

        return $admins;
    }
}
