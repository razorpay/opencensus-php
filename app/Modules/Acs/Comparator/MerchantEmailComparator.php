<?php

namespace RZP\Modules\Acs\Comparator;

use RZP\Constants\Metric;
use RZP\Models\Base\PublicCollection;
use RZP\Modules\Acs\ASVEntityMapper;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;

class MerchantEmailComparator extends Base
{
    protected $trace;
    protected $excludedKeys = [
        "created_at" => true,
        "updated_at" => true,
        "verified" => true
    ];
    private $entityName;

    function __construct()
    {
        parent::__construct();
        $this->trace = app('trace');
        $this->entityName = 'merchant_email';
    }

    function compareEmails(array $merchantEmails, array $asvEmails) {
        $emailsMap = [];
        foreach ($merchantEmails as $merchantEmail) {
            $emailsMap[$merchantEmail['id']] = [$merchantEmail];
        }
        foreach ($asvEmails as $asvEmail) {
            if(array_key_exists($asvEmail['id'], $emailsMap) === false){
                $emailsMap[$asvEmail['id']] = [];
            }
            array_push($emailsMap[$asvEmail['id']], $asvEmail);
        }
        foreach ($emailsMap as $emailsToCompare) {
            if(count($emailsToCompare) != 2) {
                $this->trace->info(TraceCode::ASV_COMPARE_MISMATCH, [
                    'entity_name' => $this->entityName,
                    'difference' => 'email found in one source only',
                    'id' => $emailsToCompare[0]['id'],
                    'merchant_id' => $emailsToCompare[0]['merchant_id']
                    ]);
                $this->trace->count(Metric::ASV_COMPARE_MISMATCH, [$this->entityName]);
                continue;
            }
            $difference = $this->getDifference($emailsToCompare[0], $emailsToCompare[1]);
            if(count($difference) > 0) {
                $this->trace->info(TraceCode::ASV_COMPARE_MISMATCH, [
                    'entity_name' => $this->entityName,
                    'difference' => $difference,
                    'id' => $emailsToCompare[0]['id'],
                    'merchant_id' => $emailsToCompare[0]['merchant_id']
                ]);
                $this->trace->count(Metric::ASV_COMPARE_MISMATCH, [$this->entityName]);
            }
        }
    }
}
