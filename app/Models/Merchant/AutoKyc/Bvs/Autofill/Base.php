<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Autofill;

use App;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Store;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Detail\Metric as DetailMetric;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\RequestDispatcher;

abstract class Base
{
    protected $app;

    protected $trace;

    protected $merchant;

    protected $maxAttempt;

    protected $dispatcher;

    protected $merchantDetails;

    protected $attemptsCountCacheKey;

    public function __construct(Merchant\Entity $merchant, Merchant\Detail\Entity $merchantDetails)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->merchant = $merchant;

        $this->merchantDetails = $merchantDetails;
    }

    private function getDispatcher(): RequestDispatcher
    {
        return new $this->dispatcher($this->merchant, $this->merchantDetails);
    }

    public function autofillIfApplicable(): bool
    {
        try
        {
            $data = (new Store\Core())->fetchValuesFromStore($this->merchant->getId(),
                Store\ConfigKey::ONBOARDING_NAMESPACE,
                [$this->attemptsCountCacheKey],
                Store\Constants::INTERNAL);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            return false;
        }

        if ($data[$this->attemptsCountCacheKey] > $this->maxAttempt)
        {
            $this->trace->count(DetailMetric::AUTOFILL_BVS_DETAILS_ATTEMPT_EXHAUSTED, [
                'context' => get_class($this)
            ]);

            $this->trace->info(TraceCode::AUTOFILL_BVS_DETAILS_ATTEMPT_EXHAUSTED, [
                'context' => get_class($this)
            ]);

            return false;
        }

        $details = $this->getDispatcher()->fetchEnrichmentDetails();

        $sendEnrichmentDetails = true;

        $response = optional($details)->getResponseData($sendEnrichmentDetails);

        $name = $response[Constant::ENRICHMENTS][Constant::ONLINE_PROVIDER][Constant::DETAILS][Constant::NAME][Constant::VALUE] ?? '';

        $validationStatus = $response[BvsValidation\Entity::VALIDATION_STATUS] ?? '';

        $this->updateMerchantContext($name);

        (new Store\Core)->incrementKey($this->merchant->getId(), $this->attemptsCountCacheKey, 1);

        if (empty($name) === false and
            $validationStatus === Constant::SUCCESS)
        {
            return true;
        }

        return false;
    }

    abstract protected function updateMerchantContext(string $name);
}
