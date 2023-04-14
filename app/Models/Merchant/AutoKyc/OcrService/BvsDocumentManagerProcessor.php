<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService;

use App;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\AutoKyc\MozartService\BaseResponse;
use RZP\Models\Merchant\AutoKyc\Processor as AutoKycProcessor;
use RZP\Models\Merchant\VerificationDetail\Core as VerificationCore;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient\BvsDocumentManagerClient;
use RZP\Models\Merchant\VerificationDetail\Entity as VerificationEntity;
use RZP\Models\Merchant\VerificationDetail\Constants as VerificationConstants;
use RZP\Trace\TraceCode;

class BvsDocumentManagerProcessor implements AutoKycProcessor
{
    protected $input;

    protected $trace;

    protected $merchant;

    protected $app;

    protected $config;

    protected $bvsDocumentManagerClient;


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

        $mock = $this->app['config']['services.ocr_service']['mock'];

        if ($mock === true)
        {
            //
            // This config is not defined in application config , this is used in test case only
            //
            $mockStatus = $this->app['config']['services.response'] ?? Constant::SUCCESS;

            $this->bvsDocumentManagerClient = new BvsDocumentManagerClientMock($mockStatus);
        }
        else
        {
            $this->bvsDocumentManagerClient = new BvsDocumentManagerClient($merchant);
        }
    }

    /**
     * This function basically aggregates the payload together and push it to BVS client for creation of validation.
     *
     * @return Response
     */
    public function Process($sendEnrichmentDetails = false, $skipAsyncFlow = false): Response
    {
        try
        {
            $validation = $this->bvsDocumentManagerClient->createDocumentRecord($this->input);

            if (empty($validation) === true or empty($validation->getId()) === true or empty($validation->getStatus()) === true)
            {
                $this->trace->info(TraceCode::FETCH_NEGATIVE_KEYWORDS_FAILURE, [
                    'merchantId'       =>   $this->merchant->getId(),
                    'response'         =>   $validation
                ]);

                throw new BadRequestException('Invalid response from BVS');
            }

            $validationObject = [
                BvsValidation\Entity::OWNER_ID          => $this->merchant->getId(),
                BvsValidation\Entity::ARTEFACT_TYPE     => Constant::NEGATIVE_KEYWORDS,
                BvsValidation\Entity::OWNER_TYPE        => Constant::MERCHANT,
                BvsValidation\Entity::PLATFORM          => Constant::PG,
                BvsValidation\Entity::VALIDATION_ID     => $validation->getId(),
                BvsValidation\Entity::VALIDATION_STATUS => ($validation->getStatus() === Constants::INITIATED) ? BvsValidation\Constants::CAPTURED : $validation->getStatus(),
                BvsValidation\Entity::VALIDATION_UNIT   => Constants::IDENTIFIER,
            ];

            (new BvsValidation\Core)->create($validationObject);

            $verificationObject = [
                VerificationEntity::MERCHANT_ID          => $this->merchant->getId(),
                VerificationEntity::ARTEFACT_TYPE        => Constant::NEGATIVE_KEYWORDS,
                VerificationEntity::ARTEFACT_IDENTIFIER  => VerificationConstants::NUMBER,
                VerificationEntity::STATUS               => $validation->getStatus(),
            ];

            (new VerificationCore())->createOrEditVerificationDetail($this->merchant->merchantDetail, $verificationObject);

            $this->trace->info(TraceCode::FETCH_NEGATIVE_KEYWORDS_SUCCESS, [
                'merchant_id'           => $this->merchant->getId(),
                'artefact_type'         => Constant::NEGATIVE_KEYWORDS,
                'validation_id'         => $validation->getId(),
                'validation_status'     => $validation->getStatus()
            ]);

            return new BaseResponse('');
        }
        catch(\Throwable $e)
        {
            $this->trace->info(TraceCode::FETCH_NEGATIVE_KEYWORDS_FAILURE, [
                'error'         => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);

            throw new IntegrationException('Could not receive proper response from BVS service');
        }
    }
}
