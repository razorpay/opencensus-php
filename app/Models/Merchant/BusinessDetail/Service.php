<?php

namespace RZP\Models\Merchant\BusinessDetail;

use Throwable;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Detail;
use RZP\Services\WhatCmsService;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;
use RZP\Models\Merchant\Website;
use RZP\Exception;

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
     *
     * save a details in MerchantWebsiteDetail table
     *
     * @param string $merchantId
     * @param array  $input
     *
     * @return array
     * @throws LogicException
     * @throws Throwable
     */
    public function saveBusinessDetailsForMerchant(string $merchantId, array $input)
    {
        $startTime = microtime(true);

        $merchantDetails = $this->repo->merchant_detail->findOrFailPublic($merchantId);

        $businessDetail = $merchantDetails->businessDetail;

        (new Validator)->validateMIQSharingAndTestingDate($input, $merchantDetails);

        $this->getMidnightTimestampForMIQSharingAndTestingDate($input);

        if ($businessDetail === null)
        {
            $businessDetail = $this->core->createBusinessDetail($merchantDetails, $input);
        }
        else
        {
            $businessDetail = $this->core->editBusinessDetail($merchantDetails, $input);
        }

        $merchant = $merchantDetails->merchant;

        try{
            if(isset($input[BusinessDetailEntity::MIQ_SHARING_DATE]) or isset($input[BusinessDetailEntity::TESTING_CREDENTIALS_DATE]))
            {
                $this->repo->merchant->syncToEsLiveAndTest($merchant, Merchant\EsRepository::UPDATE);
            }
        }
        catch (\Throwable $e)
        {
            $tracePayload = [
                'entity'    => 'merchant',
                'entity_id' => $merchant->getId(),
            ];

            $this->trace->info(TraceCode::ES_SYNC_PUSH_FAILED,[
                'tracePayload' => $tracePayload,
                'message' => $e->getMessage()
            ]);
        }

        $this->trace->info(TraceCode::MERCHANT_BUSINESS_DETAILS_SAVE_LATENCY, [
            'merchant_id' => $merchantId,
            'duration'    => (microtime(true) - $startTime) * 1000,
            'start_time'  => $startTime
        ]);

        return $businessDetail;
    }

    public function checkForPlugin($merchantId, $businessWebsite)
    {
        if(empty($businessWebsite) === true)
        {
            return;
        }

        list($pluginType, $ecommercePlugin) = (new WhatCmsService())->checkForPluginType($merchantId, $businessWebsite);

        $businessDetailsInput[BusinessDetailEntity::PLUGIN_DETAILS] = [
            'website'          => $businessWebsite,
            'suggested_plugin' => $pluginType,
            'ecommerce_plugin' => $ecommercePlugin
        ];

        $this->saveBusinessDetailsForMerchant($merchantId, $businessDetailsInput);
    }

    /**
     * @param $merchantId
     * @param $input
     *
     * @throws Exception\BadRequestException
     */
    public function saveWebsitePlugin($merchantId, $input)
    {
        $merchantDetails = $this->repo->merchant_detail->findOrFailPublic($merchantId);

        $urls = (new Website\Service())->getUrls($merchantDetails);

        $website = $input['website'];

        $updatedWebsite = trim(strtolower($website), '/');

        if (in_array($updatedWebsite, $urls) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_WEBSITE);
        }

        $businessDetailsInput[BusinessDetailEntity::PLUGIN_DETAILS] = [
            'website'                  => $website,
            'merchant_selected_plugin' => $input['plugin_name'],
        ];

        $this->saveBusinessDetailsForMerchant($merchantId, $businessDetailsInput);

        return [];
    }

    public function getMidnightTimestampForMIQSharingAndTestingDate(&$input)
    {
        if(isset($input[Entity::MIQ_SHARING_DATE]) === true)
        {
            $input[BusinessDetailEntity::MIQ_SHARING_DATE] =  Carbon::createFromTimestamp($input[BusinessDetailEntity::MIQ_SHARING_DATE])
                                                                ->setTimezone(Timezone::IST)
                                                                ->modify('today')
                                                                ->getTimestamp();
        }
        if(isset($input[Entity::TESTING_CREDENTIALS_DATE]) === true)
        {
            $input[BusinessDetailEntity::TESTING_CREDENTIALS_DATE] =  Carbon::createFromTimestamp($input[BusinessDetailEntity::TESTING_CREDENTIALS_DATE])
                                                                        ->setTimezone(Timezone::IST)
                                                                        ->modify('today')
                                                                        ->getTimestamp();
        }
    }
}
