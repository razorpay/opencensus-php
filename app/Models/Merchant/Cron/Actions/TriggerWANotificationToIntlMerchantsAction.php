<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Exception;
use RZP\Services\Stork;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;

class TriggerWANotificationToIntlMerchantsAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            $this->app['trace']->info(TraceCode::CB_SIGNUP_JOURNEY_SKIP_ACTION, [
                'args'  => $this->args,
                'msg'   => 'No data to execute',
            ]);

            return new ActionDto(Constants::SKIPPED);
        }

        $collectorData = $data["merchant_data"]; // since data collector is an array

        $merchantData = $collectorData->getData();

        if (count($merchantData) === 0)
        {
            $this->app['trace']->info(TraceCode::CB_SIGNUP_JOURNEY_SKIP_ACTION, [
                'args'  => $this->args,
                'msg'   => 'No merchants found',
            ]);

            return new ActionDto(Constants::SKIPPED);
        }

        $successCount = 0;
        $failureCount = 0;
        $failureTrace = [];

        foreach ($merchantData as $record)
        {
            try
            {
                $response = $this->sendWhatsappToMerchant($record);

                empty($response) ? $failureCount++ : $successCount++;
            }
            catch (\Throwable $e)
            {
                $traceData = [
                    'merchant_id'   => $record['id'] ?? "",
                    'error_code'    => $e->getCode() ?? "",
                    'error_message' => $e->getMessage() ?? "",
                ];

                array_push($failureTrace, $traceData);

                $failureCount++;
            }
        }

        $this->app['trace']->info(TraceCode::CB_SIGNUP_JOURNEY_STORK_REPORT, [
            'total_mids'     => count($merchantData),
            'success_count'  => $successCount,
            'failure_count'  => $failureCount,
            'failure_trace'  => $failureTrace,
        ]);

        if ($successCount === 0)
        {
            $status = Constants::FAIL;
        }
        else
        {
            $status = ($successCount < count($merchantData)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        return new ActionDto($status);
    }

    protected function sendWhatsappToMerchant($data)
    {
        $mode = 'live';
        if (isset($this->app['rzp.mode']) === true)
        {
            $mode = $this->app['rzp.mode'];
        }

        $storkTemplateEvent     = $this->args["event_name"];
        $storkTemplateName      = Constants::WHATSAPP_TEMPLATE_NAME[$storkTemplateEvent];
        $storkTemplateText      = Constants::WHATSAPP_TEMPLATE_TEXT[$storkTemplateEvent];
        $storkTemplateHeader    = Constants::WHATSAPP_TEMPLATE_HEADER[$storkTemplateEvent];
        $multimediaLink         = Constants::WHATSAPP_MULTIMEDIA_LINK[$storkTemplateEvent];

        $whatsAppPayload = [
            "ownerId"       => $data['id'],
            "ownerType"     => "merchant",
            "template_name" => $storkTemplateName,
            "params"        => [],

            "is_multimedia_template" => true,
            "multimedia_payload"     => [
                "business_account"   => "crossborder",
                "destination"        => $data['mobile'],
                "header"             => $storkTemplateHeader,
                "text"               => $storkTemplateText,
                "is_cta_template"    => true,
            ]
        ];

        // (new Stork)->sendWhatsappMessage(
        $response = $this->app['stork_service']->sendWhatsappMessage(
            $mode,
            $storkTemplateText,
            $data['mobile'],
            $whatsAppPayload
        );

        $this->app['trace']->info(TraceCode::CB_SIGNUP_JOURNEY_STORK_WA_RESPONSE, [
            "merchant_id"    => $data['id'],
            'response'       => $response,
        ]);

        return $response;
    }
}
