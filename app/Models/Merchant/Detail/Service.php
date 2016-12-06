<?php

namespace RZP\Models\Merchant\Detail;

use Carbon\Carbon;
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

    public function saveMerchantDetails(array $input)
    {
        $merchantDetails = $this->getMerchantDetails($this->merchant, $input);

        if ($merchantDetails->isLocked())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED);
        }

        $merchantDetails->edit($input);

        $this->repo->saveOrFail($merchantDetails);

        $response = $this->createResponse($merchantDetails);

        if ((isset($input[Detail\Entity::SUBMIT]) === true) and
            ($input[Detail\Entity::SUBMIT] === true) and
            ($response['can_submit'] === true))
        {
            $submittedAt = Carbon::now('Asia/Kolkata')->timestamp;

            $params = [
                Entity::SUBMITTED     => 1,
                Entity::SUBMITTED_AT  => $submittedAt
            ];

            $merchantDetails->fill($params);

            $this->repo->saveOrFail($merchantDetails);
        }

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

            $ufh = $this->createFile($merchantDetails,
                                    $value->extension(),
                                    $value,
                                    $fileName,
                                    $key);

            $params[$key] = $ufh->get()['id'];
        }

        $merchantDetails->fill($params);

        $this->repo->saveOrFail($merchantDetails);

        return $this->createResponse($merchantDetails);
    }

    public function lockMerchantDetails($id, array $input)
    {
        if (isset($input[Entity::LOCKED]) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_LOCKED_NOT_SET);
        }

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchantDetails = $this->getMerchantDetails($merchant);

        $merchantDetails->edit($input);

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
                [ 'merchant_id'    => $this->merchant->getId() ]);

            $merchantDetails = $this->createMerchantDetails($this->merchant, $input);
        }

        return $merchantDetails;
    }

    public function createMerchantDetails(Merchant\Entity $merchant, array $input = [])
    {
        $merchantDetail = (new Detail\Entity)->build($input);

        $merchantDetail->setContactEmail($merchant->getEmail());

        $merchantDetail->merchant()->associate($merchant);

        $this->repo->saveOrFail($merchantDetail);

        $this->trace->info(
                TraceCode::CREATE_MERCHANT_DETAIL,
                [ 'merchant_id'   => $merchant->getId()]);

        return $merchantDetail;
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
        $detailsKeys = array_keys($merchantDetailsArr);
        $requiredFields = array_diff($detailsKeys, ValidationFields::DASHBOARD_FIELDS);

        if (count($requiredFields) > 0)
        {
            $response['verification'] = [
                'status'            => 'disabled',
                'disabled_reason'   => 'required_fields',
                'required_fields'   =>  $requiredFields
            ];

            $response['can_submit'] = false;
        }
        else
        {
            $response['verification'] = ['status' => 'pending'];

            $response['can_submit'] = true;
        }

        return $response;
    }
}
