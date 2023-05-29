<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity;

use App;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Base\RepositoryManager;
use RZP\Modules\Acs\Comparator;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class Base
{
    protected $app;

    /** @var Logger */
    protected $trace;

    /** @var RepositoryManager */
    protected $repo;

    protected $merchantId;

    protected $parityCheckMethods;

    /**
     * @var Comparator\Base
     */
    protected $comparator;

    protected $website;

    function __construct(string $merchantId, array $parityCheckMethods)
    {
        $app = App::getFacadeRoot();
        $this->app = $app;
        $this->trace = $app[Constant::TRACE];
        $this->repo = $this->app[Constant::REPO];
        $this->comparator = new Comparator\Base();
        $this->merchantId = $merchantId;
        $this->parityCheckMethods = $parityCheckMethods;
    }

    protected function compareAndLogApiAndAsvResponse(array $differenceRawAttributes, array $differenceArray, array $logDetailMatched,
                                                      array $additionalLogDetailUnmatched): void
    {
        if ($differenceRawAttributes === [] and $differenceArray === []) {
            $this->trace->info(TraceCode::ASV_COMPARE_MATCHED, $logDetailMatched);
        } else {
            $this->trace->info(TraceCode::ASV_COMPARE_MISMATCH, array_merge($logDetailMatched, $additionalLogDetailUnmatched));
        }
    }

    protected function compareAndLogApiAndAsvResponseForNull(?array $apiResponse, ?array $asvResponse, array $logDetailMatched,
                                                             array  $additionalLogDetailUnmatched): void
    {
        if ($apiResponse === null and $asvResponse === null) {
            $this->trace->info(TraceCode::ASV_COMPARE_MATCHED, $logDetailMatched);
        } else {
            $this->trace->info(TraceCode::ASV_COMPARE_MISMATCH, array_merge($logDetailMatched, $additionalLogDetailUnmatched));
        }
    }

}
