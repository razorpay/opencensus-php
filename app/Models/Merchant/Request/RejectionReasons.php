<?php

namespace RZP\Models\Merchant\Request;

use RZP\Exception;

class RejectionReasons
{
    const CODE                                    = 'code';
    const DESCRIPTION                             = 'description';

    const INVALID_REJECTION_REASON_CODE           = 'Invalid rejection reason code';

    /*
     * Reason Categories
     */
    const UNSUPPORTED_BUSINESS_MODEL = 'unsupported_business_model';
    const INVALID_USE_CASE           = 'invalid_use_case';
    const OTHERS                     = 'others';

    /*
     * Reason Descriptions
     */
    const UNSUPPORTED_BUSINESS_MODEL_DESCRIPTION = 'Unsupported business model for the specific feature';
    const INVALID_USE_CASE_DESCRIPTION           = 'Invalid use case for request of the feature';
    const OTHERS_DESCRIPTION                     = 'Generic rejection';

    //
    // Reason codes descriptions mapping
    // @todo: Add specific reasons and descriptions after some time when we have the data. Keeping it generic for now
    // by keeping the category and code same.
    //
    const REASON_CODES_DESCRIPTIONS_MAPPING = [
        self::UNSUPPORTED_BUSINESS_MODEL => self::UNSUPPORTED_BUSINESS_MODEL_DESCRIPTION,
        self::INVALID_USE_CASE           => self::INVALID_USE_CASE_DESCRIPTION,
        self::OTHERS                     => self::OTHERS_DESCRIPTION,
    ];

    const REJECTION_REASONS_MAPPING = [
        // Unsupported Business Model
        self::UNSUPPORTED_BUSINESS_MODEL => [
            [
                self::CODE        => self::UNSUPPORTED_BUSINESS_MODEL,
                self::DESCRIPTION => self::UNSUPPORTED_BUSINESS_MODEL_DESCRIPTION,
            ],
        ],

        // Invalid Use Case
        self::INVALID_USE_CASE => [
            [
                self::CODE        => self::INVALID_USE_CASE,
                self::DESCRIPTION => self::INVALID_USE_CASE_DESCRIPTION,
            ],
        ],

        // Others
        self::OTHERS => [
            [
                self::CODE        => self::OTHERS,
                self::DESCRIPTION => self::OTHERS_DESCRIPTION,
            ],
        ],
    ];

    /**
     * Given a rejection reason code, it will return the corresponding rejection reason description
     *
     * @param string $reasonCode
     *
     * @return string $reasonDescription
     * @throws Exception\BadRequestValidationFailureException
     */
    public static function getReasonDescriptionByReasonCode(string $reasonCode): string
    {
        if (isset(self::REASON_CODES_DESCRIPTIONS_MAPPING[$reasonCode]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_REJECTION_REASON_CODE);
        }

        return self::REASON_CODES_DESCRIPTIONS_MAPPING[$reasonCode];
    }
}
