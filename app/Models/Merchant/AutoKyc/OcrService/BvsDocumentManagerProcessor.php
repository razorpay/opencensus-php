<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService;

use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\MozartService\BaseResponse;
use RZP\Models\Merchant\AutoKyc\Processor as AutoKycProcessor;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient\BvsDocumentManagerClient;

class BvsDocumentManagerProcessor implements AutoKycProcessor
{
    protected $input;

    protected $trace;

    protected $merchant;

    protected $app;


    /**
     * @param array $input
     * @param $merchant
     *
     */

    public function __construct(array $input, $merchant)
    {
        $this->app = App::getFacadeRoot();

        $this->merchant = $merchant;

        $this->trace = $this->app['trace'];

        $this->input = $input;
    }

    /**
     * This function basically aggregates the payload together and push it to BVS client for creation of validation.
     *
     * @return Response
     */
    public function Process($sendEnrichmentDetails = false, $skipAsyncFlow = false): Response
    {
        $validation = (new BvsDocumentManagerClient())->documentRecord($this->input);

        $validationObject = [
            BvsValidation\Entity::OWNER_ID              => $this->merchant->getMerchantId(),
            BvsValidation\Entity::ARTEFACT_TYPE         => Constant::NEGATIVE_KEYWORDS,
            BvsValidation\Entity::OWNER_TYPE            => Constant::MERCHANT,
            BvsValidation\Entity::PLATFORM              => Constant::PG,
            BvsValidation\Entity::VALIDATION_ID         => $validation->getId(),
            BvsValidation\Entity::VALIDATION_STATUS     => $validation->getStatus()
        ];

        (new BvsValidation\Core)->create($validationObject);

        return new BaseResponse('');
    }
}
