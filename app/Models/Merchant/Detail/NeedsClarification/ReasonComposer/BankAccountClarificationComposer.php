<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification\ReasonComposer;

use App;
use RZP\Exception\LogicException;
use RZP\Models\Merchant;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BVSConstants;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\Detail\BankDetailsVerificationStatus;
use RZP\Models\Merchant\Detail\NeedsClarification\Constants as NCConstants;
use RZP\Models\Merchant\Detail\NeedsClarificationMetaData;
use RZP\Models\Merchant\Detail\NeedsClarificationReasonsList;
use RZP\Trace\TraceCode;

class BankAccountClarificationComposer extends BaseClarificationReasonComposer
{

    protected $merchantDetails;

    protected $repo;

    /**
     * @var array
     */
    private $clarificationMetaData;
    public function __construct(Entity $merchantDetails,array $needsClarificationMetaData)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];

        $this->merchantDetails = $merchantDetails;

        $this->clarificationMetaData = $needsClarificationMetaData;
    }

    public function getLatestBankAccountValidationError()
    {
        $latestBankValidationError = null;

        $merchant = $this->merchantDetails->merchant;

        $merchantId = $merchant->getMerchantId();

        $validation = $this->repo->bvs_validation->getLatestArtefactValidationForOwnerIdAndOwnerType(
            $merchantId,
            Constant::MERCHANT,
            BVSConstants::BANK_ACCOUNT
        );

        $this->trace->info(TraceCode::MERCHANT_DETAIL_VERIFICATION_RESPONSE, [
            '$validation' => $validation,
        ]);

        if(empty($validation) === false)
        {
            $errorCode              = $validation->getErrorCode();

            $errorDescription       = $validation->getErrorDescription();

            $errorDescriptionCode   = substr($errorDescription,0,4);

            $latestBankValidationError =  BVSConstants::BANK_ACCOUNT . BvsValidationConstants::IDENTIFIER . $errorCode . $errorDescriptionCode;
        }

        return $latestBankValidationError;
    }


    public function getClarificationReason(): array
    {
        if (empty($this->clarificationMetaData) === true)
        {
            return [];
        }

        $latestBankValidationError = $this->getLatestBankAccountValidationError();

        If(isset(Constants::VERIFICATION_RESPONSE_ERROR_CODES[$latestBankValidationError]) === true) {
            $response = [];
            foreach ($this->clarificationMetaData[NCConstants::ADDITIONAL_DETAILS][NCConstants::FIELDS] as $field) {
                $response[$field[NCConstants::FIELD_NAME]] = [[
                    NCConstants::REASON_TYPE => Merchant\Constants::PREDEFINED_REASON_TYPE,
                    NCConstants::FIELD_TYPE => $field[NCConstants::FIELD_TYPE],
                    NCConstants::FIELD_VALUE => $this->merchantDetails->getAttribute($field[NCConstants::FIELD_NAME]),
                    NCConstants::REASON_CODE => NeedsClarificationMetaData::BUSINESS_TYPE_REASON_CODE_MAPPING[$this->merchantDetails->getBusinessType()]
                ]];
            }

            return [
                $this->merchantDetails::ADDITIONAL_DETAILS => $response
            ];
        }
        else {
            return [];
        }

    }
}
