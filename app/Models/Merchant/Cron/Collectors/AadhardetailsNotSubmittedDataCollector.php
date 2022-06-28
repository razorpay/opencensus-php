<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Models\DeviceDetail\Constants;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant;

class AadhardetailsNotSubmittedDataCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        // fetch all merchants who've submitted L1
        $l1SubmittedMerchants = $this->repo->merchant_detail->filterL1MilestoneSubmittedMerchantsOfOrg($startTime, $endTime);

        $l1SubmittedMerchants = $this->repo->user_device_detail->removeSignupCampaignIdsFromMerchantIdList($l1SubmittedMerchants, Constants::EASY_ONBOARDING);

        // fetch all merchants who've submitted the documents
        $documentSubmittedMerchants = $this->repo->merchant_document->filterMerchantIdsWithUploadedDocuments(
            $l1SubmittedMerchants, Merchant\Document\Type::AADHAR_BACK
        );

        // exclude above merchant ids
        $docNotSubmittedMerchants = array_diff($l1SubmittedMerchants, $documentSubmittedMerchants);

        // out of above list, filter those who've completed aadhaar esign
        $esignCompletedMerchants = $this->repo->stakeholder->fetchEsignCompletedMerchants($docNotSubmittedMerchants);

        // exclude above list, to final merchant id list
        $finalMerchantIdList = array_diff($docNotSubmittedMerchants, $esignCompletedMerchants);

        $data["merchantIds"] = $finalMerchantIdList;

        return CollectorDto::create($data);
    }

    protected function getStartInterval(): int
    {
        return $this->lastCronTime - (60 * 60);
    }

    protected function getEndInterval(): int
    {
        return $this->cronStartTime - (60 * 60);
    }
}
