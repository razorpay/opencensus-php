<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\AutoKyc\Bvs\Core;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

class PersonalPanForExternalRequest extends BaseForExternalRequest
{
    protected $ownerId;

    protected $panNumber;

    protected $name;

    public function __construct(string $ownerId, array $detail)
    {
        $this->ownerId = $ownerId;

        $this->name = $detail[Constant::NAME];

        $this->panNumber = $detail[Constant::PAN_NUMBER];
    }

    public function getRequestPayload(): array
    {
        $payload = [
            Constant::PLATFORM                => Constant::RX,
            Constant::ARTEFACT_TYPE           => Constant::PERSONAL_PAN,
            Constant::CONFIG_NAME             => Constant::PERSONAL_PAN,
            Constant::VALIDATION_UNIT         => BvsValidationConstants::IDENTIFIER,
            Constant::CUSTOM_CALLBACK_HANDLER => 'updateValidationStatusForBAS',
            Constant::OWNER_TYPE              => Constant::BAS_DOCUMENT,
            Constant::OWNER_ID                => $this->ownerId,
            Constant::DETAILS                 => [
                Constant::PAN_NUMBER => $this->panNumber,
                Constant::NAME       => $this->name,
            ],
        ];

        return $payload;
    }
}
