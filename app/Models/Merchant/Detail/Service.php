<?php

namespace RZP\Models\Merchant\Detail;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
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
        //
        // When a linked account is created, mainly, 2 functions are executed -
        // 1. createSubMerchant
        // 2. saveMerchantDetails
        //
        // The first function creates a merchant entity and other supporting
        // entities like MerchantDetail, ScheduleTask, Method, etc. It also creates
        // a BankAccount entity in the Test database with dummy values so that the
        // merchant can start the integration using the test mode immediately.
        //
        // The second function accepts the actual bank account details of the merchant
        // and runs the createOrChangeBankAccount function call. This function creates
        // or updates the bankAccount entity in the database corresponding to the mode
        // that is extracted from the basic auth key used. Hence, if the key used
        // corresponds to live mode, a BankAccount entity will be created in the live
        // mode, but if it is used in the test mode, the entity that is already created
        // with the dummy data will be updated with the actual data and no entity will
        // be created in the Live mode,
        //
        // Hence, forcing the input mode to be live mode here, if not already.
        //
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->core()->setModeAndDefaultConnection($liveMode);

        return $this->core()->saveMerchantDetails($input, $this->merchant);
    }

    public function saveInstantActivationDetails(array $input): array
    {
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->core()->setModeAndDefaultConnection($liveMode);

        return $this->core()->saveInstantActivationDetails($input, $this->merchant);
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

    /**
     * Bulk edits merchant attributes against given CSV input.
     * CSV file contains header as id, {attribute-name-1}, {attribute-name-2}, where attribute-name is name of attribute to be updated.
     * Note: Specific error handling and strict validation is being SKIPPED here, This is internal route and should be run with supervision.
     * @param  array $input
     * @return array
     */
    public function bulkEditMerchantAttributes(array $input): array
    {
        (new Validator)->validateInput(Validator::BULK_EDIT, $input);

        // Reads CSV content as associate array in $rows as [merchant id => <>, attribute-name => <>]
        $file          = $input[Entity::FILE]->getRealPath();
        $lines         = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $heading       = str_getcsv(array_shift($lines));
        $rows          = [];

        foreach ($lines as $line)
        {
            $rows[] = array_combine($heading, str_getcsv($line));
        }

        $total  = 0;
        $failed = 0;
        // Iteratively call core's edit method on each row
        foreach ($rows as $row)
        {
            ++$total;

            $tracePayload = compact('row');

            $this->trace->info(TraceCode::MERCHANT_BULK_EDIT_INPUT, $tracePayload);

            $merchantId = array_pull($row, 'id');

            // Normalizes attribute values - if it is read as null, converts to php's null
            foreach ($row as $k => & $v)
            {
                if (strtolower($v) === "null")
                {
                    $v = null;
                }
            }

            try
            {
                $this->editMerchantDetails($merchantId, $row);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, null, null, $tracePayload);

                ++$failed;
            }
        }

        return compact('total', 'failed');
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

        // Previous $response would become stale while simulataneous uploads. So prepare fresh response.
        $response = $core->createResponse($merchantDetails);

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
     * sub category meta fields will be dependent on auth
     *
     * @return array
     */
    public function getBusinessCategories(): array
    {
        $businessCategoriesMap = BusinessCategory::SUBCATEGORY_MAP;
        $businessCategories    = [];

        foreach ($businessCategoriesMap as $businessCategory => $subCategories)
        {
            $businessCategories[$businessCategory] = [];
            $subCategoriesMetaData                 = [];

            foreach ($subCategories as $subCategory)
            {
                $subCategoriesMetaData[$subCategory] =  $this->getSubCategoryMetaDataFields($subCategory);
            }
            $businessCategories[$businessCategory][BusinessCategory::DESCRIPTION]   = BusinessCategory::DESCRIPTIONS[$businessCategory];
            $businessCategories[$businessCategory][BusinessCategory::SUBCATEGORIES] = $subCategoriesMetaData;
        }

        return $businessCategories;
    }

    /**
     * returns subcategories meta data as per auth
     * for admin all meta data fields(description, category, category2, activation category) will be returned
     * for other then admin description and category2 will be returned
     *
     * @param string $subCategory
     *
     * @return array
     */
    private function getSubCategoryMetaDataFields(string $subCategory): array
    {
        if ($this->auth->isAdminAuth() === true)
        {
            return BusinessSubCategoryMetaData::SUB_CATEGORY_METADATA[$subCategory];
        }

        return array_only(BusinessSubCategoryMetaData::SUB_CATEGORY_METADATA[$subCategory],
                          BusinessSubCategoryMetaData::NORMAL_AUTH_FIELDS);
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
            $businessWebsite = $input[Entity::BUSINESS_WEBSITE] ?? null;

            $inputDetails = ['name' => $input[Entity::BUSINESS_NAME], 'website' => $businessWebsite];

            // Validate Input Name for merchant
            (new Merchant\Validator)->validateInput('edit_pre_signup', $inputDetails);

            (new Merchant\Service)->edit($this->merchant->id, $inputDetails);

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

        $admins = [];

        foreach ($permission->roles as $role)
        {
            foreach ($role->admins as $roleAdmin)
            {
                $admins[] = $roleAdmin->toArrayPublic();
            }
        }

        return multidim_array_unique($admins, Admin\Admin\Entity::ID);
    }
}
