<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService;

use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\MozartService\BaseResponse;
use RZP\Models\Merchant\AutoKyc\Processor as AutoKycProcessor;

class MccCategorisationProcessor implements AutoKycProcessor
{
    protected $input;

    protected $trace;

    protected $merchant;

    protected $app;

    private $mccCategorisationClient;

    /**
     * @param array $input
     * @param  $merchant
     *
     */

    public function __construct(array $input, $merchant)
    {
        $this->app = App::getFacadeRoot();

        $this->merchant = $merchant;

        $this->trace = $this->app['trace'];

        $this->input = $input;

        $this->config = config('services.ocr_service');

        $mock = $this->config['mock'];

        if ($mock === true)
        {
            //
            // This config is not defined in application config , this is used in test case only
            //
            $mockStatus = $this->config['response'] ?? Constant::SUCCESS;

            $this->mccCategorisationClient = new MccCategorisationClientMock($mockStatus);
        }
        else
        {
            $this->mccCategorisationClient = new MccCategorisationClient;
        }
    }

    /**
     * This function basically aggregates the payload together and push it to BVS client for creation of validation.
     *
     * @return Response
     */
    public function Process($sendEnrichmentDetails = false, $skipAsyncFlow = false): Response
    {
        $validationId = $this->mccCategorisationClient->createCategorisationJob($this->input);
//todo: handle null case
        $validationObject = [
            BvsValidation\Entity::OWNER_ID        => $this->merchant->getMerchantId(),
            BvsValidation\Entity::ARTEFACT_TYPE   => Constant::MCC_CATEGORISATION,
            BvsValidation\Entity::OWNER_TYPE      => Constant::MERCHANT,
            BvsValidation\Entity::PLATFORM        => Constant::PG,
            BvsValidation\Entity::VALIDATION_ID   => $validationId
        ];

        (new BvsValidation\Core)->create($validationObject);

        return new BaseResponse('');
    }
}
