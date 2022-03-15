<?php

namespace RZP\Models\Merchant\BusinessDetail;
use Throwable;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Constants as MerchantConstants;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    protected $trace;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->trace = $this->app[MerchantConstants::TRACE];

        $this->mutex = $this->app[MerchantConstants::API_MUTEX];

        $this->entityRepo = $this->repo->merchant_business_detail;
    }

    /**
     * save a details in MerchantWebsiteDetail table
     *
     * @param string $merchantId
     *
     * @return Entity
     * @throws LogicException|Throwable
     */
    public function fetchBusinessDetailsForMerchant(string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantDetailCore = new Detail\Core();

        $merchantDetails = $merchantDetailCore->getMerchantDetails($merchant);

        $businessDetail = $merchantDetails->businessDetail;

        if (isset($businessDetail) === false)
        {
            $businessDetail = $this->repo->merchant_business_detail->getBusinessDetailsForMerchantId($merchantDetails->getMerchantId());
        }
        if (isset($businessDetail) === false)
        {
            $businessDetail = $this->core->createBusinessDetail($merchantDetails, [Entity::WEBSITE_DETAILS => Entity::getDefaultWebsiteDetails()]);
        }

        return $businessDetail;
    }

    /**
     * save a details in MerchantWebsiteDetail table
     *
     * @param string $merchantId
     * @param array  $input
     *
     * @return array
     */
    public function saveBusinessDetailsForMerchant(string $merchantId, array $input)
    {
        $startTime = microtime(true);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantDetailCore = new Detail\Core();

        $merchantDetails = $merchantDetailCore->getMerchantDetails($merchant);

        $businessDetail = $merchantDetails->businessDetail;

        if ($businessDetail === null)
        {
            $businessDetail = $this->core->createBusinessDetail($merchantDetails, $input);
        }
        else
        {
            $businessDetail = $this->core->editBusinessDetail($merchantDetails, $input);
        }

        $this->trace->info(TraceCode::MERCHANT_BUSINESS_DETAILS_SAVE_LATENCY, [
            'merchant_id' => $merchantId,
            'duration'    => (microtime(true) - $startTime) * 1000,
            'start_time'  => $startTime
        ]);

        return $businessDetail;
    }
}
