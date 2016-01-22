<?php

namespace Models\Settlement\Details;

use Models\Base;
use Models\Settlement;
use Trace\TraceCode;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository;
        $this->setlRepo = new Settlement\Repository;
    }

    public function postSettlementDetailsForOldTxns($input)
    {
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $setls = $this->setlRepo->fetch($input);

        foreach ($setls as $setl) 
        {
            try
            {
                $merchant = $setl->merchant;
                sd($merchant);
                $setlDetails = $this->repo->getSettlementDetails($setl->getId(), $merchant);

                if($setlDetails->count() === 0)
                {
                    (new Settlement\Merchant($merchant, $setl->getChannel()))->createSettlementDetails($setl);
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