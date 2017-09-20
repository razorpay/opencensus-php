<?php

namespace RZP\Models\Merchant\Detail;

use Carbon\Carbon;
use Throwable;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\ValidationFields;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Merchant\Action as Action;
use RZP\Models\Merchant\SlackActions as SlackActions;

class Service extends Base\Service
{
    use NotifyTrait;

    public function fetchMerchantDetails()
    {
        $merchantDetails = $this->getMerchantDetails($this->merchant);

        return $this->createResponse($merchantDetails);
    }

    public function fetchActivationFiles(string $id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchantDetails = $this->getMerchantDetails($merchant);

        $signedUrls = [];

        $fileFields = $this->getFileFields($merchant);

        foreach ($fileFields as $key => $value)
        {
            if (isset($merchantDetails[$key]))
            {
                $signedUrls[$value] = $this->getSignedUrl($merchantDetails[$key], $id);
            }
        }

        return ['files' => $signedUrls];
    }

    private function getFileFields($merchant) : array
    {
        return ($merchant->isLinkedAccount() === true) ? Constants::UPLOAD_KEYS_ACCOUNT : Constants::UPLOAD_KEYS;
    }

    protected function getSignedUrl(string $fileStoreId, string $merchantId)
    {
        $accessor = new FileStore\Accessor;

        $signedUrls = $accessor->id($fileStoreId)
                               ->merchantId($merchantId)
                               ->getSignedUrl();

        return $signedUrls[$fileStoreId];
    }

    public function saveMerchantDetails(array $input)
    {
        $this->trace->info(
                TraceCode::MERCHANT_SAVE_ACTIVATION_DETAILS,
                ['input' => $input]);

        $merchantDetails = $this->getMerchantDetails($this->merchant, $input);

        $merchantDetails->getValidator()->validateIsNotLocked();

        $merchantDetails->edit($input);

        $this->repo->saveOrFail($merchantDetails);

        $response = $this->createResponse($merchantDetails);

        $eventAttributes = $this->merchant->toArrayEvent();

        if ($this->canSubmit($input, $response) === true)
        {
            $this->markSubmitted($merchantDetails);

            $this->app['eventManager']->trackEvents($this->merchant, Merchant\Action::SUBMITTED, $eventAttributes);
        }

        $response = $this->createResponse($merchantDetails);

        $activationProgress = $response['verification']['activation_progress'];

        $merchantDetails->setActivationProgress($activationProgress);

        $this->repo->saveOrFail($merchantDetails);

        if ($this->canSubmit($input, $response) === true)
        {
            (new Detail\Core)->fireActivationTrigger($merchantDetails);
        }

        $eventAttributes['activation_progress'] = $activationProgress;

        $this->app['eventManager']
             ->trackEvents($this->merchant, Merchant\Action::ACTIVATION_PROGRESS, $eventAttributes);

        return $response;
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
     * @param  Merchant\Entity      $merchant     Merchant Entity
     * @param  array   $input       Input with the file
     * @param  boolean $validateLock If true, blocks edits if the form is locked. Can be set to false
     *                               to bypass locked forms
     */
    public function uploadActivationFile(Merchant\Entity $merchant, array $input, bool $validateLock = true)
    {
        $merchantDetails = $this->getMerchantDetails($merchant, $input);

        if ($validateLock === true)
        {
            $merchantDetails->getValidator()->validateIsNotLocked();
        }

        $merchantDetails->edit($input);

        $params = [];

        foreach ($input as $key => $value)
        {
            $merchantDetails->getValidator()->validateFileType($value);

            $fileName = 'api/' . $merchant->getId() .'/' .$key;

            $file = $this->createFile(
                $merchantDetails,
                $value->extension(),
                $value,
                $fileName,
                $key,
                $merchant);

            $params[$key] = FileStore\Entity::verifyIdAndSilentlyStripSign($file['id']);
        }

        $merchantDetails->fill($params);

        $response = $this->createResponse($merchantDetails);

        $merchantDetails->setActivationProgress($response['verification']['activation_progress']);

        $this->repo->saveOrFail($merchantDetails);

        return $response;
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

        $merchantDetails = $this->getMerchantDetails($merchant);

        $merchantDetails->edit($input);

        $this->repo->saveOrFail($merchantDetails);

        if (isset($slackAction) === true)
        {
            $this->logActionToSlack($merchant, $slackAction);
        }

        return $this->createResponse($merchantDetails);
    }

    protected function getMerchantDetails(Merchant\Entity $merchant, array $input = [])
    {
        $merchantDetails = $merchant->merchantDetail;

        if ($merchantDetails === null)
        {
            $this->trace->info(
                TraceCode::MERCHANT_DETAIL_DOES_NOT_EXIST,
                [ 'merchant_id'    => $merchant->getId() ]);

            $merchantDetails = $this->createMerchantDetails($merchant, $input);
        }

        return $merchantDetails;
    }

    public function createMerchantDetails(Merchant\Entity $merchant, array $input = [])
    {
        $merchantDetail = (new Detail\Entity)->build($input);

        $merchantDetail->setContactEmail($merchant->getEmail());

        $merchantDetail->merchant()->associate($merchant);

        try
        {
            $this->repo->saveOrFail($merchantDetail);

            $this->trace->info(
                TraceCode::CREATE_MERCHANT_DETAIL,
                [ 'merchant_id'   => $merchant->getId()]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::CREATE_MERCHANT_DETAIL_FAILED,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }

        return $merchantDetail;
    }

    protected function canSubmit($input, $response)
    {
        return (($response['can_submit'] === true) and
                (isset($input[Detail\Entity::SUBMIT]) === true) and
                ($input[Detail\Entity::SUBMIT] === '1'));
    }

    protected function markSubmitted($merchantDetails)
    {
        $submittedAt = Carbon::now()->getTimestamp();

        $input = [
            Entity::SUBMITTED     => 1,
            Entity::SUBMITTED_AT  => $submittedAt
        ];

        $merchantDetails->fill($input);

        $this->repo->saveOrFail($merchantDetails);
    }

    protected function createFile(Detail\Entity $merchantDetail,
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

    protected function createResponse(Detail\Entity $merchantDetails)
    {
        $merchantDetailsArr = $merchantDetails->toArray();

        $response = $merchantDetails->toArrayPublic();

        $requiredFields = [];

        $validationFields = ValidationFields::DASHBOARD_FIELDS;

        if ($merchantDetails->merchant->isLinkedAccount() === true)
        {
            $validationFields = ValidationFields::MARKETPLACE_ACCOUNT_FIELDS;
        }

        $totalFields = count($validationFields);

        foreach ($validationFields as $key)
        {
            if ((array_key_exists($key, $merchantDetailsArr) === false) or
               (is_null($merchantDetailsArr[$key]) === true) or
                ((is_bool($merchantDetailsArr[$key]) !== true) and
                    (empty($merchantDetailsArr[$key]) === true)))
            {
                $requiredFields[] = $key;
            }
        }

        if (count($requiredFields) > 0)
        {
            $remainingFields = count($requiredFields);

            $response['verification'] = [
                'status'              => 'disabled',
                'disabled_reason'     => 'required_fields',
                'required_fields'     => $requiredFields,
                'activation_progress' => 100 - intval($remainingFields * 100 / $totalFields),
            ];

            $response['can_submit'] = false;
        }
        else
        {
            $response['verification'] = [
                'status'              => 'pending',
                'activation_progress' => 100,
            ];

            $response['can_submit'] = true;
        }

        $response['activated'] = (int) $merchantDetails->merchant->isActivated();

        return $response;
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
}
