<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\GstinAuth;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;


class GstinValidation extends  Job
{
    /**
    * @param MerchantEntity $merchant
    */
    protected $merchant;

    /**
     * @var Detail\Entity $merchantDetails
     */
    protected $merchantDetails;

    /**
     * @var string
     */
    protected $gst;


    public function __construct(string $mode, MerchantEntity $merchant, string $gst)
    {
        parent::__construct($mode);

        $this->merchant = $merchant;

        $this->merchantDetails = $this->merchant->merchantDetail;

        $this->gst = $gst;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::GSTIN_VALIDATION_JOB_INITIATED_FOR_NO_DOC,
            [
                'merchant_id'   => $this->merchant->getId(),
                'artefact_type' => 'gst',
                'gst'           => $this->gst
            ]
        );

        $this->merchantDetails[Detail\Entity::GSTIN] = $this->gst;

        $this->merchantDetails->setGstinVerificationStatus(BvsValidationConstants::PENDING);

        $requestCreator =  new GstinAuth($this->merchant, $this->merchantDetails);

        try
        {
            if ($requestCreator instanceof requestDispatcher\RequestDispatcher)
            {
                $requestCreator->triggerBVSRequest();
            }

            $merchantDetails = $this->merchantDetails ;

            $this->repoManager->transactionOnLiveAndTest(function() use ($merchantDetails) {

                    $this->repoManager->merchant_detail->saveOrFail($merchantDetails);
            });

            $this->trace->info(
                TraceCode::GSTIN_VALIDATION_BVS_EVENT_TRIGGERED,
                [
                    'merchant_id'    => $this->merchant->getId(),
                    'artefact_type'  => 'gst',
                    'gst'            => $this->gst
                ]
            );

        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::BVS_VERIFICATION_ERROR,
                [
                    'message'           => $e->getMessage(),
                    'merchant_id'       => $this->merchant->getId(),
                    'request_creator'   => get_class($requestCreator),
                    'gst'               => $this->gst
                ]
            );
        }
    }
}
