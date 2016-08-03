<?php

namespace RZP\Models\Settlement\Details;

use RZP\Models\Base;
use RZP\Models\Settlement;
use Trace\TraceCode;

class Core extends Base\Core
{
    public function addSettlementDetailsForOldTxns($input)
    {
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $setls = $this->repo->settlement->fetch($input);

        foreach ($setls as $setl)
        {
            try
            {
                $merchant = $setl->merchant;

                $setlDetails = $this->repo->settlement_details->getSettlementDetails($setl->getId(), $merchant);

                if($setlDetails->count() === 0)
                {
                    (new Settlement\Merchant($merchant, $setl->getChannel(), $this->repo))
                        ->createSettlementDetails($setl);

                    $processed++;
                }
                else
                {
                    $skipped++;
                }
            }
            catch (\Exception $ex)
            {
                $failed++;
            }
        }

        $data = array(
            'skipped'   => $skipped,
            'processed' => $processed,
            'failed'    => $failed,
        );

        return $data;
    }
}