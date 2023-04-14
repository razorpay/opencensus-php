<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\BvsValidation;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\AutoKyc\MozartService\BaseResponse;
use RZP\Models\Merchant\AutoKyc\Processor as AutoKycProcessor;
use RZP\Models\Merchant\VerificationDetail\Core as VerificationCore;
use RZP\Models\Merchant\VerificationDetail\Entity as VerificationEntity;
use RZP\Models\Merchant\VerificationDetail\Constants as VerificationConstants;

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

        $mock = $this->app['config']['services.ocr_service.mock'];

        if ($mock === true)
        {
            //
            // This config is not defined in application config , this is used in test case only
            //
            $mockStatus = $this->app['config']['services.response'] ?? Constant::SUCCESS;

            $this->mccCategorisationClient = new MccCategorisationClientMock($mockStatus);
        }
        else
        {
            $this->mccCategorisationClient = new MccCategorisationClient($merchant);
        }
    }

    /**
     * This function basically aggregates the payload together and push it to BVS client for creation of validation.
     *
     * @return Response
     * @throws IntegrationException
     */
    public function Process(): Response
    {
        try
        {
            $validation = $this->mccCategorisationClient->createCategorisationJob($this->input);

            if(empty($validation) === true or empty($validation['id']) === true or empty($validation['status']) === true)
            {
                $this->trace->info(TraceCode::FETCH_MCC_CATEGORY_FAILURE, [
                    'error'         => $validation['error_code'],
                    'error_message' => $validation['error_reason']
                ]);

                throw new BadRequestException('Invalid response from BVS');
            }

            $artefactType = $this->getArtefactType($this->input);

            $validationObject = [
                BvsValidation\Entity::OWNER_ID           => $this->merchant->getMerchantId(),
                BvsValidation\Entity::ARTEFACT_TYPE      => $artefactType,
                BvsValidation\Entity::OWNER_TYPE         => Constant::MERCHANT,
                BvsValidation\Entity::PLATFORM           => Constant::PG,
                BvsValidation\Entity::VALIDATION_ID      => $validation['id'],
                BvsValidation\Entity::VALIDATION_STATUS  => ($validation['status'] === 'initiated') ? BvsValidation\Constants::CAPTURED : $validation['status'],
                BvsValidation\Entity::VALIDATION_UNIT    => Constants::IDENTIFIER,
            ];

            (new BvsValidation\Core)->create($validationObject);

            $verificationObject = [
                VerificationEntity::MERCHANT_ID          => $this->merchant->getMerchantId(),
                VerificationEntity::ARTEFACT_TYPE        => $artefactType,
                VerificationEntity::ARTEFACT_IDENTIFIER  => VerificationConstants::NUMBER,
                VerificationEntity::STATUS               => $validation['status'],
            ];

            (new VerificationCore())->createOrEditVerificationDetail($this->merchant->merchantDetail, $verificationObject);

            $this->trace->info(TraceCode::FETCH_MCC_CATEGORY_SUCCESS, [
                'merchant_id'           => $this->merchant->getMerchantId(),
                'artefact_type'         => $artefactType,
                'validation_id'         => $validation['id'],
                'validation_status'     => $validation['status']
            ]);

            return new BaseResponse('');
        }
        catch(\Throwable $e)
        {
            $this->trace->info(TraceCode::FETCH_MCC_CATEGORY_FAILURE, [
                'error'         => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);

            throw new IntegrationException('Could not receive proper response from BVS service');
        }

    }

    private function getArtefactType($input)
    {
        foreach ($input as $data => $value)
        {
            switch ($data)
            {
                case Constant::WEBSITE_URL:
                    return Constant::MCC_CATEGORISATION_WEBSITE;
                case Constant::GSTIN:
                    return Constant::MCC_CATEGORISATION_GSTIN;
                default:
                    return Constant::MCC_CATEGORISATION;
            }
        }
    }

}
