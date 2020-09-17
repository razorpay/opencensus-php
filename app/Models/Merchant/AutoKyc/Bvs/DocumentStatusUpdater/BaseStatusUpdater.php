<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use App;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\BvsValidation\Entity as Validation;

abstract class BaseStatusUpdater implements StatusUpdater
{
    protected $app;

    protected $repo;

    protected $trace;

    /**
     * @var  DetailEntity
     */
    protected $merchantDetails;

    /**
     * @var  string
     */
    protected $merchantId;

    /**
     * @var  string
     */
    protected $artefactType;

    const VALIDATION_STATUS_FUNCTION_MAPPING = [
        Constants::VERIFIED          => 'getVerifiedStatus',
        Constants::INCORRECT_DETAILS => 'getIncorrectDetailStatus',
        Constants::FAILED            => 'getFailedStatus',
        Constants::NOT_MATCHED       => 'getNotMatchedStatus',
    ];

    /**
     * BaseStatusUpdater constructor.
     *
     * @param DetailEntity $merchantDetails
     * @param string       $artefactType
     */
    public function __construct(DetailEntity $merchantDetails, string $artefactType)
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        $this->merchantDetails = $merchantDetails;

        $this->merchantId = $this->merchantDetails->getMerchantId();

        $this->artefactType = $artefactType;
    }

    protected function getVerifiedStatus()
    {
        return Constants::VERIFIED;
    }

    protected function getFailedStatus()
    {
        return Constants::FAILED;
    }

    protected function getIncorrectDetailStatus()
    {
        return Constants::INCORRECT_DETAILS;
    }

    protected function getNotMatchedStatus()
    {
        return Constants::NOT_MATCHED;
    }

    /**
     * Returns document validation status for a validation entity
     *
     * @param Validation $validation
     *
     * @return null|string
     * @throws LogicException
     */
    protected function getDocumentValidationStatus(Validation $validation): ?string
    {
        if ($validation->getValidationStatus() === Constants::SUCCESS)
        {
            return $this->getVerifiedStatus();
        }

        if ($validation->getValidationStatus() === Constants::FAILED)
        {
            foreach (Constants::ERROR_MAPPING as $artifactValidationStatus => $error_codes)
            {
                if (array_search($validation->getErrorCode(), $error_codes, true) !== false)
                {
                    $func = self::VALIDATION_STATUS_FUNCTION_MAPPING[$artifactValidationStatus] ?? '';

                    if (method_exists($this, $func) === true)
                    {
                        return $this->$func();
                    }

                    throw new LogicException(
                        ErrorCode::SERVER_ERROR_UNHANDLED_ARTEFACT_VALIDATION_STATUS,
                        null,
                        [Constant::STATUS => $artifactValidationStatus]);
                }
            }

            //
            // If no error code is mapped then consider it as failed status
            //
            return $this->getFailedStatus();
        }

        //
        // If validation is still in pending status
        //

        return null;
    }
}
