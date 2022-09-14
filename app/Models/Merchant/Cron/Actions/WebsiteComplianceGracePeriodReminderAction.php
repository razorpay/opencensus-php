<?php

namespace RZP\Models\Merchant\Cron\Actions;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Utility;
use Illuminate\Support\Facades\File;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Notifications\Onboarding\Events;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\Merchant\Website\Service as WebsiteService;
use RZP\Models\Merchant\Website\Constants as WebsiteConstants;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;

class WebsiteComplianceGracePeriodReminderAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        $collectorData = $data["merchant_notification_data"]; // since data collector is an array

        $data = $collectorData->getData();

        $merchantIds = $data["merchantIds"];

        if (count($merchantIds) === 0)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $filesToDelete = [];

        $successCount = 0;

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $merchantDetails = $merchant->merchantDetail;

                $websiteDetail = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($merchant->getId());

                $files    = [];
                $sections = [];

                foreach (explode(',', WebsiteConstants::VALID_MERCHANT_SECTIONS) as $sectionName)
                {

                    $sectionStatus = $websiteDetail->getSectionStatus($sectionName);

                    $publishedWebsite = $websiteDetail->getPublishedUrl($sectionName);

                    $updatedAt = $websiteDetail->getSectionUpdatedAt($sectionName);

                    $htmlContent = view('merchant.website.policy',
                                        [
                                            "data" => [
                                                'merchant_legal_entity_name' => $merchant->getMerchantLegalEntityName(),
                                                'updated_at'                 => Carbon::createFromTimestamp($updatedAt)->isoFormat('MMM Do YYYY'),
                                                'sectionName'                => $sectionName,
                                                'logo_url'                   => $merchant->getFullLogoUrlWithSize(),
                                                'public'                     => false,
                                                'merchant'                   => $merchant->toArray(),
                                                'merchant_details'           => $merchant->merchantDetail->toArray(),
                                                'website_detail'             => $websiteDetail->toArrayPublic(),
                                            ]
                                        ])->render();

                    $htmlFile = (new WebsiteService())->getFileName($merchant, $sectionName, 'html');

                    $textFile = (new WebsiteService())->getFileName($merchant, $sectionName, 'txt');

                    $this->app['trace']->info(TraceCode::WEBSITE_ADHERENCE_INFO, ["htmlFile" => $htmlFile,
                                                                                  "textFile" => $textFile
                    ]);

                    file_put_contents($htmlFile, $htmlContent);

                    file_put_contents($textFile, Utility::htmlToText($htmlContent));

                    array_push($files, $htmlFile);

                    array_push($files, $textFile);

                    $sections[$sectionName] = true;
                }

                $filesToDelete = array_merge($filesToDelete, $files);

                $emailArgs = [
                    'merchant' => $this->repo->merchant->findOrFailPublic($merchantDetails->getMerchantId()),
                    'params'   => [
                        'sections' => $sections
                    ]
                ];

                (new OnboardingNotificationHandler($emailArgs, $files))->sendForEvent(Events::WEBSITE_ADHERENCE_GRACE_PERIOD_REMINDER);

                $successCount++;
            }
            catch (\Throwable $e)
            {

            }
        }

        foreach ($filesToDelete as $fileName)
        {
            File::delete($fileName);
        }

        if ($successCount === 0)
        {
            $status = Constants::FAIL;
        }
        else
        {
            $status = ($successCount < count($merchantIds)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        return new ActionDto($status);
    }
}
