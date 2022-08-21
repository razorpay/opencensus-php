<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification\ReasonComposer;

use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\NeedsClarification\Constants as ClarificationConstants;
use RZP\Models\Merchant\Detail\NeedsClarificationReasonsList;
use RZP\Models\Merchant\Detail\RetryStatus as RetryStatus;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\Detail\Constants as DEConstants;

class DedupeClarificationReasonComposer extends BaseClarificationReasonComposer
{

    /**
     * @var array
     */
    private $noDocData;

    /**
     * @var array
     */
    private $clarificationMetaData;

    public function __construct(array $needsClarificationMetaData, array $noDocData)
    {
        parent::__construct();

        $this->noDocData = $noDocData;

        $this->clarificationMetaData = $needsClarificationMetaData;
    }


    /** By defau
     *
     * @return array
     */
    public function getClarificationReason(): array
    {
        $clarificationReasons = [];

        if (empty($this->clarificationMetaData) === true)
        {
            return [];
        }

        $clarificationReasons[Entity::CLARIFICATION_REASONS] = [];
        $fieldName                                           = $this->clarificationMetaData[DEConstants::DEDUPE_CHECK_KEY];
        if ($this->noDocData['dedupe'][$fieldName]['retryCount'] > 0 and $this->noDocData['dedupe'][$fieldName]['status'] === RetryStatus::PENDING)
        {
            $clarificationReasonsForDedupeFailed = $this->getClarificationReasonsForDedupeFailed($fieldName);
            foreach ($clarificationReasonsForDedupeFailed as $key => $value)
            {
                $clarificationReasons[Entity::CLARIFICATION_REASONS][$key] = $value;
            }
        }

        return $clarificationReasons;
    }

    protected function getClarificationReasonsForDedupeFailed(string $fieldName): array
    {
        $clarificationReasons = [];

        switch ($fieldName)
        {
            case  Entity::CONTACT_MOBILE:
                $clarificationReasons[Entity::CONTACT_MOBILE] = [[
                                                                     MerchantConstants::REASON_TYPE => Constants::PREDEFINED_REASON_TYPE,
                                                                     Constants::FIELD_TYPE          => ClarificationConstants::TEXT,
                                                                     Constants::REASON_CODE         => NeedsClarificationReasonsList::FIELD_ALREADY_EXIST,
                                                                 ]];
                break;
            case Entity::PROMOTER_PAN:
                $clarificationReasons[Entity::PROMOTER_PAN] = [[
                                                                        MerchantConstants::REASON_TYPE => Constants::PREDEFINED_REASON_TYPE,
                                                                        Constants::FIELD_TYPE          => ClarificationConstants::TEXT,
                                                                        Constants::REASON_CODE         => NeedsClarificationReasonsList::FIELD_ALREADY_EXIST
                                                                    ]];
                break;
            case Entity::COMPANY_PAN:
                $clarificationReasons[Entity::COMPANY_PAN] = [[
                                                                  MerchantConstants::REASON_TYPE => Constants::PREDEFINED_REASON_TYPE,
                                                                  Constants::FIELD_TYPE          => ClarificationConstants::TEXT,
                                                                  Constants::REASON_CODE         => NeedsClarificationReasonsList::FIELD_ALREADY_EXIST
                                                              ]];
                break;
            case Entity::GSTIN:
                $clarificationReasons[Entity::GSTIN] = [[
                                                                  MerchantConstants::REASON_TYPE => Constants::PREDEFINED_REASON_TYPE,
                                                                  Constants::FIELD_TYPE          => ClarificationConstants::TEXT,
                                                                  Constants::REASON_CODE         => NeedsClarificationReasonsList::FIELD_ALREADY_EXIST
                                                              ]];
                break;
        }

        return $clarificationReasons;
    }
}
