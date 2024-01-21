<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;

use Razorpay\Asv\DbSource;
use Razorpay\Asv\RequestMetadata;
use Accounts\Account\V1\GetByIdRequest;
use Accounts\Account\V1\SaveRequest;
use Accounts\Account\V1\Account as AsvSDKAccount;


class Account extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function saveAccount($accountId, $accountDetail, $fieldList) : string
    {
        $saveAccountRequest = new SaveRequest();

        $fieldMask = new \Google\Protobuf\FieldMask([ 'paths' => $fieldList
            ]
        );

        $account = $this->setAccountDetails($accountId, $accountDetail);

        $saveAccountRequest->setFieldMask($fieldMask);
        $saveAccountRequest->setAccount($account);

        $requestMetadata = new RequestMetadata();
        $requestMetadata->setSourceDatabase(DbSource::AsvWriter);

        list($response, $err) = $this->asvSdkClient->getAccount()->Save($saveAccountRequest,
            $this->getRequestMetaData($requestMetadata));

        return $response->getAccountId();
    }

    public function getAccountByIDAndFieldMask($accountId, $fieldList)
    {
        $getAccountIDRequest = new GetByIdRequest();

        $fieldMask = new \Google\Protobuf\FieldMask([ 'paths' => $fieldList
            ]
        );

        $getAccountIDRequest->setId($accountId);
        $getAccountIDRequest->setFieldMask($fieldMask);

        $requestMetadata = new RequestMetadata();
        $requestMetadata->setSourceDatabase(DbSource::AsvWriter);

        list($response, $err) = $this->asvSdkClient->getAccount()->GetAccountById($getAccountIDRequest,
            $this->getRequestMetaData($requestMetadata));

        if ($err !== null){
            $this->handleError($err);
        }

        return $response->getAccount();
    }

    public function setAccountDetails($accountId,$accountDetail)
    {
        $account = new AsvSDKAccount();

        $account->setId($accountId);
        $account->setAccountDetail($accountDetail);

        return $account;
    }
}
