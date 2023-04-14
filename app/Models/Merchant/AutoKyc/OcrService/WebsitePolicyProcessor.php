<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\BvsValidation;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Merchant\AutoKyc\MozartService\BaseResponse;
use RZP\Models\Merchant\AutoKyc\Processor as AutoKycProcessor;

class WebsitePolicyProcessor implements AutoKycProcessor
{
    protected $input;

    protected $trace;

    protected $app;

    protected $config;

    protected $merchant;

    protected $websitePolicyClient;

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

            $this->websitePolicyClient = new WebsitePolicyClientMock($mockStatus);
        }
        else
        {
            $this->websitePolicyClient = new WebsitePolicyClient($merchant);
        }
    }

    /**
     * This function basically aggregates the payload together and push it to OCR client for creation of validation.
     *
     * @return Response
     */
    public function Process(): Response
    {
        try
        {
            $validation = $this->websitePolicyClient->createWebsitePolicyJob($this->input);

            if ($validation === null or $validation['website_verification_id'] === null or $validation['status'] === null)
            {
                $this->trace->info(TraceCode::FETCH_WEBSITE_POLICY_FAILURE, [
                    'error' => $validation['error_code'],
                    'error_message' => $validation['error_reason']
                ]);

                throw new BadRequestException('Invalid response from BVS');
            }

            $validationId = $validation['website_verification_id'];

            $validationObject = [
                BvsValidation\Entity::OWNER_ID           => $this->merchant->getMerchantId(),
                BvsValidation\Entity::ARTEFACT_TYPE      => Constant::WEBSITE_POLICY,
                BvsValidation\Entity::OWNER_TYPE         => Constant::MERCHANT,
                BvsValidation\Entity::PLATFORM           => Constant::PG,
                BvsValidation\Entity::VALIDATION_ID      => $validationId,
                BvsValidation\Entity::VALIDATION_STATUS  => ($validation['status'] === 'initiated') ? BvsValidation\Constants::CAPTURED : $validation['status'],
                BvsValidation\Entity::VALIDATION_UNIT    => BvsValidation\Constants::IDENTIFIER,
            ];

            (new BvsValidation\Core)->create($validationObject);

            $verificationObject = [
                MVD\Entity::MERCHANT_ID         => $this->merchant->getMerchantId(),
                MVD\Entity::ARTEFACT_TYPE       => Constant::WEBSITE_POLICY,
                MVD\Entity::ARTEFACT_IDENTIFIER => MVD\Constants::NUMBER,
                MVD\Entity::STATUS              => $validation['status'],
            ];

            (new MVD\Core)->createOrEditVerificationDetail($this->merchant->merchantDetail, $verificationObject);

            return new BaseResponse('');
        }
        catch(\Throwable $e)
        {
            $this->trace->info(TraceCode::FETCH_WEBSITE_POLICY_FAILURE, [
                'error'         => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);

            throw new IntegrationException('Could not receive proper response from BVS service');
        }
    }
}
