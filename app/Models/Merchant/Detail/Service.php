<?php

namespace RZP\Models\Merchant\Detail;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\ValidationFields;

class Service extends Base\Service
{
    public function fetchMerchantDetails()
    {
        $merchantDetails = $this->merchant->merchantDetail;

        return $this->createResponse($merchantDetails);
    }

    public function saveMerchantDetails(array $input)
    {
        $merchantDetails = $this->merchant->merchantDetail;

        if ($merchantDetails->isLocked())
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED);
        }

        $merchantDetails->edit($input);

        $this->repo->saveOrFail($merchantDetails);

        $response = $this->createResponse($merchantDetails);

        if ((empty($input[Detail\Entity::SUBMIT]) === false) and
            ($input[Detail\Entity::SUBMIT] === true) and
            ($response['details_submitted'] === true))
        {
            $submittedAt = Carbon::now('Asia/Kolkata')->timestamp;

            $params = [
                    Entity::SUBMITTED  => 1,
                    Entity::SUBMITTED  => $submittedAt
            ];

            $merchantDetails->fill($params);

            $this->repo->saveOrFail($merchantDetails);
        }

        return $response;
    }

    public function uploadActivationFile(array $input)
    {
        $merchantDetails = $this->merchant->merchantDetail;

        if ($merchantDetails->isLocked())
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED);
        }

        $merchantDetails->edit($input);

        $params = [];

        foreach ($input as $key => $value)
        {
            $fileName = $this->merchant->getId() .'_' . $key;

            $ufh = $this->createFile($merchantDetails,
                                    $value->extension(),
                                    $value,
                                    $fileName,
                                    FileStore\Type::MERCHANT_ACTIVATION);

            $params[$key] = $ufh->get()['id'];
        }

        $merchantDetails->fill($params);

        $this->repo->saveOrFail($merchantDetails);

        return $this->createResponse($merchantDetails);
    }

    public function createMerchantDetails(Merchant\Entity $merchant, array $input = [])
    {
        $merchantDetail = (new Detail\Entity)->build($input);

        $merchantDetail->setContactEmail($merchant->getEmail());

        $merchantDetail->merchant()->associate($merchant);

        $this->repo->saveOrFail($merchantDetail);
    }

    protected function createFile(Detail\Entity $merchantDetail,
                                    string $extension,
                                    $file,
                                    string $fileName,
                                    string $type,
                                    string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $creator->extension($extension)
                ->localFile($file)
                ->name($fileName)
                ->store($store)
                ->type($type)
                ->entity($merchantDetail)
                ->save();

        return $creator;
    }

    protected function createResponse(Detail\Entity $merchantDetails)
    {
        $merchantDetailsArr = $merchantDetails->toArray();

        $response = $merchantDetails->toArrayPublic();

        // List of all the required fields which are not set
        foreach (ValidationFields::DASHBOARD_FIELDS as $key)
        {
            if (isset($merchantDetailsArr[$key]) === false)
            {
                $requiredFields[] = $key;
            }
        }

        if (count($requiredFields) > 0)
        {
            $response['verification'] = [
                                    'status'            => 'disabled',
                                    'disabled_reason'   => 'required_fields',
                                    'required_fields'   =>  $requiredFields
                                ];

            $response['details_submitted'] = false;
        }
        else
        {
            $response['verification'] = [
                                    'status' => 'pending'
                                ];

            $response['details_submitted'] = true;
        }

        return $response;
    }
}
