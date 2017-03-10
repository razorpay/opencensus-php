<?php

namespace RZP\Models\Merchant\Detail;

use Carbon\Carbon;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\ValidationFields;

class Service extends Base\Service
{
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

        foreach (Entity::UPLOADED_FIELDS as $key)
        {
            if (isset($merchantDetails[$key]))
            {
                $signedUrls[$key] = $this->getSignedUrl($merchantDetails[$key], $id);
            }
        }

        return $signedUrls;
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
        $merchantDetails = $this->getMerchantDetails($this->merchant, $input);

        $merchantDetails->getValidator()->validateIsNotLocked();

        $merchantDetails->edit($input);

        $this->repo->saveOrFail($merchantDetails);

        $response = $this->createResponse($merchantDetails);

        if ($this->canSubmit($input, $response) === true)
        {
            $this->markSubmitted($merchantDetails);
        }

        $response = $this->createResponse($merchantDetails);

        $merchantDetails->setActivationProgress($response['verification']['activation_progress']);

        $this->repo->saveOrFail($merchantDetails);

        return $response;
    }

    public function uploadActivationFile(array $input)
    {
        $merchantDetails = $this->getMerchantDetails($this->merchant, $input);

        $merchantDetails->getValidator()->validateIsNotLocked();

        $merchantDetails->edit($input);

        $params = [];

        foreach ($input as $key => $value)
        {
            $fileName = 'api/' .$this->merchant->getId() .'/' .$key;

            $file = $this->createFile(
                $merchantDetails,
                $value->extension(),
                $value,
                $fileName,
                $key);

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
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchantDetails = $this->getMerchantDetails($merchant);

        $merchantDetails->edit($input, 'editAfterLock');

        $this->repo->saveOrFail($merchantDetails);

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
            $this->trace->info(
                TraceCode::CREATE_MERCHANT_DETAIL_FAILED,
                [ 'merchant_id'   => $merchant->getId()]);
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
        $submittedAt = Carbon::now('Asia/Kolkata')->timestamp;

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
                                    string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $file = $creator->extension($extension)
                        ->localFile($file)
                        ->name($fileName)
                        ->store($store)
                        ->type($type)
                        ->entity($merchantDetail)
                        ->save()
                        ->get();

        return $file;
    }

    protected function createResponse(Detail\Entity $merchantDetails)
    {
        $merchantDetailsArr = $merchantDetails->toArray();

        $response = $merchantDetails->toArrayPublic();

        // List of all the required fields which are not set
        $detailsKeys = array_keys($merchantDetailsArr);

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

        return $response;
    }
}
