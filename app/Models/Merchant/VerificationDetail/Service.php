<?php

namespace RZP\Models\Merchant\VerificationDetail;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\BvsValidation\Core as BvsCore;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BVSConstant;
use RZP\Models\Merchant\VerificationDetail\Constants as MVD;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    protected $trace;

    /**
     * @var Core
     */
    protected $core;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->trace = $this->app[MerchantConstants::TRACE];

        $this->mutex = $this->app[MerchantConstants::API_MUTEX];
    }

    public function processWebsitePolicyResponse($payload)
    {
        $validationId = $payload['website_verification_id'];

        $this->trace->info(TraceCode::PROCESS_WEBSITE_POLICY_RESULT, [
            'validation_id' => $validationId,
            'result'        => $payload['verification_result'] ?? []
        ]);

        $validationObj = [
            Entity::VALIDATION_ID       => $validationId,
            Entity::VALIDATION_STATUS   => $this->getValidationStatus($payload[Constants::STATUS]),
            Entity::METADATA            => $payload['verification_result'] ?? []
        ];

        (new BvsCore)->processValidation($validationId, $validationObj);
    }

    public function processNegativeKeywordsResponse($payload)
    {
        $validationId = $payload[Constants::ID];

        $result = $payload[MVD::DOCUMENT_DETAILS];

        $this->trace->info(TraceCode::PROCESS_NEGATIVE_KEYWORDS_RESULT, [
            'validation_id' => $validationId,
            'result'        => $result
        ]);

        $status = $payload[BVSConstant::STATUS] ?? null;

        $validationObj = [
            Entity::VALIDATION_ID       => $payload[Constants::ID],
            Entity::VALIDATION_STATUS   => $this->getValidationStatus($status),
            Entity::METADATA            => $payload[MVD::DOCUMENT_DETAILS] ?? [],
            Entity::ARTEFACT_TYPE       => BVSConstant::NEGATIVE_KEYWORDS,
        ];

        (new BvsCore)->processValidation($validationId, $validationObj);
    }


    public function processMccCategorisationResponse($payload)
    {
        $validationId = $payload[Constants::ID];

        $result = $payload[MVD::CATEGORY_RESULT];

        $this->trace->info(TraceCode::PROCESS_MCC_CATEGORY_RESULT, [
            'validation_id' => $validationId,
            'result'        => $result
        ]);

        foreach ($result as $categoryResult => $value)
        {
            $status = $value[MVD::STATUS] ?? null;

            $validationObj = [
                Entity::VALIDATION_ID       => $payload[Constants::ID],
                Entity::VALIDATION_STATUS   => $this->getValidationStatus($status),
                Entity::METADATA            => $value ?? [],
                Entity::ARTEFACT_TYPE       => $this->getArtefactType($categoryResult),
                Entity::ERROR_CODE          => $value[Constants::ERROR_CODE] ?? '',
                Entity::ERROR_DESCRIPTION   => $value[Constants::ERROR_REASON] ?? '',
            ];

            (new BvsCore)->processValidation($validationId, $validationObj);
        }
    }

    private function getArtefactType($categoryResult)
    {
        switch ($categoryResult)
        {
            case 'website_categorisation':
                return BVSConstant::MCC_CATEGORISATION_WEBSITE;
            case 'gstin_categorisation':
                return BVSConstant::MCC_CATEGORISATION_GSTIN;
            default:
                return BVSConstant::MCC_CATEGORISATION;
        }
    }

    private function getValidationStatus($status): string
    {
        return match ($status) {
            'success', 'completed' => Constants::SUCCESS,
            'failed'    => Constants::FAILED,
            default     => Constants::CAPTURED,
        };
    }
}
