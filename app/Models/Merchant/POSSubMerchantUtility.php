<?php

namespace RZP\Models\Merchant;

use RZP\Models\Partner;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Jobs\SubMerchantTaggingJob;
use Illuminate\Support\Facades\Event;
use Neves\Events\TransactionalClosureEvent;

class POSSubMerchantUtility
{
    /***
     * @param string $partnerId
     * @param Entity $subMerchant
     */
    static function addSubMerchantTag(string $partnerId, Entity $subMerchant): void
    {
        $experimentEnable = (new Partner\Core())->isPOSEnabledForPartner($partnerId);

        if ($experimentEnable === true)
        {
            app('trace')->info(
                TraceCode::POS_SUBMERCHANT_TAG,
                [
                    'partner_id'     => $partnerId,
                    'submerchant_id' => $subMerchant->getId(),
                    'tag_prefix'     => Constants::POS_PARTNERSHIP_TAG_PREFIX,
                ]
            );

            Event::dispatch(new TransactionalClosureEvent(function () use ($subMerchant, $partnerId) {
                SubMerchantTaggingJob::dispatch(
                    Mode::LIVE, $partnerId,
                    $subMerchant->getId(),
                    Constants::POS_PARTNERSHIP_TAG_PREFIX
                );

                SubMerchantTaggingJob::dispatch(
                    Mode::TEST,
                    $partnerId,
                    $subMerchant->getId(),
                    Constants::POS_PARTNERSHIP_TAG_PREFIX
                );
            }));
        }

    }
}
