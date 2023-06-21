<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Exception\LogicException;
use RZP\Models\Merchant\Document\Type;

class ValidationFields
{
    const DEFAULT                   = 'default';
    const REQUIRED_FIELDS           = 'required_field';
    const SELECTIVE_REQUIRED_FIELDS = 'selective_required_fields';
    const OPTIONAL_FIELDS           = 'optional_required_fields';

    const FIELDS_TYPES = [
        self::REQUIRED_FIELDS,
        self::SELECTIVE_REQUIRED_FIELDS,
        self::OPTIONAL_FIELDS,
    ];

    // this specify height of the tree
    const LEVEL_COUNT = 3;

    /**
     *  selective required fields mapping
     * self::DEFAULT => [
     *          document_set_1,
     *          document_set_2,
     *      ]
     *
     *      document_set_1 refer to selective required fields set
     *          document_set_1 : {
     *              "document1": [
     *                  [ "document_type1" , "document_type2"],
     *                  [ "document_type3" , "document_type2"]
     *              ],
     *              "document2": [
     *                  [ "document_type4" , "document_type5"],
     *                  [ "document_type6" , "document_type7"]
     *              ]
     *          }
     *
     * explanation : For submitting L2 form  document1 and document2 fields are required
     * for document1 field user can submit (document_type1, document_type2) or (document_type3, document_type2)
     * for document2 field user can submit (document_type4, document_type5) or (document_type6, document_type7)
     */
    const FINANCIAL_SERVICE_FIELDS = [
        BusinessSubcategory::MUTUAL_FUND       => [
            self::DEFAULT => [
                self::SELECTIVE_REQUIRED_FIELDS => [
                    SelectiveRequiredFields::MUTUAL_FUND,
                ],
            ]
        ],
        BusinessSubcategory::LENDING           => [
            self::DEFAULT => [
                self::SELECTIVE_REQUIRED_FIELDS => [
                    SelectiveRequiredFields::LENDING
                ],
            ],
        ],
        BusinessSubcategory::INSURANCE         => [
            self::DEFAULT => [
                self::SELECTIVE_REQUIRED_FIELDS => [
                    SelectiveRequiredFields::INSURANCE,
                ],
            ],
        ],
        BusinessSubcategory::NBFC              => [
            self::DEFAULT => [
                self::SELECTIVE_REQUIRED_FIELDS => [
                    SelectiveRequiredFields::NBFC,
                ],
            ],
        ],
        BusinessSubcategory::FOREX             => [
            self::DEFAULT => [
                self::SELECTIVE_REQUIRED_FIELDS => [
                    SelectiveRequiredFields::FOREX,
                ],
            ],
        ],
        BusinessSubcategory::SECURITIES        => [
            self::DEFAULT => [
                self::SELECTIVE_REQUIRED_FIELDS => [
                    SelectiveRequiredFields::SECURITIES,
                ],
            ],
        ],
        BusinessSubcategory::COMMODITIES => [
            self::DEFAULT => [
                self::SELECTIVE_REQUIRED_FIELDS => [
                    SelectiveRequiredFields::COMMODITIES,
                ],
            ],
        ],
        BusinessSubcategory::FINANCIAL_ADVISOR => [
            self::DEFAULT => [
                self::SELECTIVE_REQUIRED_FIELDS => [
                    SelectiveRequiredFields::FINANCIAL_ADVISOR,
                ],
            ],
        ],
        BusinessSubcategory::TRADING           => [
            self::DEFAULT => [
                self::SELECTIVE_REQUIRED_FIELDS => [
                    SelectiveRequiredFields::TRADING,
                ],
            ],
        ],
        self::DEFAULT                          => [
        ],
    ];

    //optional fields mapping
    const EDUCATION_OPTIONAL_FIELDS = [
        BusinessSubcategory::SCHOOLS    => self::EDUCATION_INSTITUTE_OPTIONAL_FIELDS,
        BusinessSubcategory::COLLEGE    => self::EDUCATION_INSTITUTE_OPTIONAL_FIELDS,
        BusinessSubcategory::UNIVERSITY => self::EDUCATION_INSTITUTE_OPTIONAL_FIELDS,
    ];

    const TOURS_AND_TRAVEL_OPTIONAL_FIELDS = [
        BusinessSubcategory::AVIATION      => self::TRAVEL_OPTIONAL_FIELDS,
        BusinessSubcategory::OTA           => self::TRAVEL_OPTIONAL_FIELDS,
        BusinessSubcategory::TRAVEL_AGENCY => self::TRAVEL_OPTIONAL_FIELDS,
    ];

    const EDUCATION_INSTITUTE_OPTIONAL_FIELDS = [
        self::DEFAULT => [
            self::OPTIONAL_FIELDS => [
                [Type::AFFILIATION_CERTIFICATE],
            ],
        ],
    ];

    const TRAVEL_OPTIONAL_FIELDS = [
        self::DEFAULT => [
            self::OPTIONAL_FIELDS => [
                [Type::IATA_CERTIFICATE, Type::SLA_IATA_CERTIFICATE],
            ],
        ]
    ];

    /**
     * value of key default is made array of arrays for the backward compatibility
     *
     * structure of default :-
     * self::DEFAULT => [
     *          document_set_1,
     *          document_set_2,
     *      ]
     * and structure of document_set_1:
     *      case 1: if document_set_1 refer to mandatory fields
     *          document_set_1 = [
     *              document_1,
     *              document_2,
     *          ];
     *          same for document_2
     */

    const DEFAULT_REGISTERED_GROUP = [
        BusinessCategory::FINANCIAL_SERVICES => self::FINANCIAL_SERVICE_FIELDS,
        BusinessCategory::EDUCATION          => self::EDUCATION_OPTIONAL_FIELDS,
        BusinessCategory::TOURS_AND_TRAVEL   => self::TOURS_AND_TRAVEL_OPTIONAL_FIELDS,
        self::DEFAULT                        => [
            self::REQUIRED_FIELDS           => [
                RequiredFields::REGISTERED_BUSINESS_FIELDS,
                RequiredFields::BUSINESS_PAN_FIELDS,
                RequiredFields::BUSINESS_PROOF_DOCUMENT,
            ],
            self::SELECTIVE_REQUIRED_FIELDS => [SelectiveRequiredFields::REGISTERED_POA_FIELDS]
        ],
    ];

    const NGO_FIELD_GROUP = [
        BusinessCategory::FINANCIAL_SERVICES => self::FINANCIAL_SERVICE_FIELDS,
        BusinessCategory::EDUCATION          => self::EDUCATION_OPTIONAL_FIELDS,
        BusinessCategory::TOURS_AND_TRAVEL   => self::TOURS_AND_TRAVEL_OPTIONAL_FIELDS,
        self::DEFAULT                        => [
            self::REQUIRED_FIELDS           => [
                RequiredFields::REGISTERED_BUSINESS_FIELDS,
                RequiredFields::NGO_MERCHANT_FIELDS,
                RequiredFields::BUSINESS_PAN_FIELDS,
                RequiredFields::BUSINESS_PROOF_DOCUMENT,
            ],
            self::SELECTIVE_REQUIRED_FIELDS => [SelectiveRequiredFields::REGISTERED_POA_FIELDS]
        ],
    ];

    const PROPRIETORSHIP_FIELD_GROUP = [
        BusinessCategory::FINANCIAL_SERVICES => self::FINANCIAL_SERVICE_FIELDS,
        BusinessCategory::EDUCATION          => self::EDUCATION_OPTIONAL_FIELDS,
        BusinessCategory::TOURS_AND_TRAVEL   => self::TOURS_AND_TRAVEL_OPTIONAL_FIELDS,
        self::DEFAULT                        => [
            self::REQUIRED_FIELDS           => [RequiredFields::REGISTERED_BUSINESS_FIELDS],
            self::SELECTIVE_REQUIRED_FIELDS => [
                SelectiveRequiredFields::REGISTERED_POA_FIELDS,
                SelectiveRequiredFields::PROPRIETORSHIP_PERSONAL_PAN_DOCUMENTS,
                SelectiveRequiredFields::PROPRIETORSHIP_BUSINESS_PROOFS,
            ]
        ],
    ];

    const UNREGISTERED_FIELD_GROUP = [
        self::DEFAULT => [
            self::SELECTIVE_REQUIRED_FIELDS => [SelectiveRequiredFields::UNREGISTERED_POA_FIELDS],
        ],
    ];


    const DEFAULT_REGISTERED_NO_DOC_FIELDS = [
        Entity::BUSINESS_NAME,
        Entity::CONTACT_MOBILE,
        Entity::COMPANY_PAN,
        Entity::PROMOTER_PAN_NAME,
        Entity::PROMOTER_PAN,
        Entity::BANK_ACCOUNT_NAME,
        Entity::BANK_ACCOUNT_NUMBER,
        Entity::BANK_BRANCH_IFSC,
        Entity::BUSINESS_REGISTERED_ADDRESS,
        Entity::BUSINESS_OPERATION_ADDRESS
    ];

    const UNREGISTERED_NO_DOC_FIELDS = [
        Entity::PROMOTER_PAN_NAME,
        Entity::BUSINESS_NAME,
        Entity::CONTACT_MOBILE,
        Entity::PROMOTER_PAN,
        Entity::BANK_ACCOUNT_NAME,
        Entity::BANK_ACCOUNT_NUMBER,
        Entity::BANK_BRANCH_IFSC,
        Entity::BUSINESS_REGISTERED_ADDRESS
    ];

    const PROPRIETORSHIP_NO_DOC_FIELDS = [
        Entity::PROMOTER_PAN_NAME,
        Entity::BUSINESS_NAME,
        Entity::CONTACT_MOBILE,
        Entity::PROMOTER_PAN,
        Entity::BANK_ACCOUNT_NAME,
        Entity::BANK_ACCOUNT_NUMBER,
        Entity::BANK_BRANCH_IFSC,
        Entity::BUSINESS_REGISTERED_ADDRESS,
        Entity::BUSINESS_OPERATION_ADDRESS
    ];

    const REGISTERED_NO_DOC_OPTIONAL_FIELDS = [
        Entity::CONTACT_NAME,
        Entity::BUSINESS_DBA
    ];

    const L1_FIELDS_IA_V2_APIS = [
        Entity::CONTACT_MOBILE,
        Entity::BUSINESS_CATEGORY,
        Entity::BUSINESS_DBA,
        Entity::BUSINESS_MODEL,
        Entity::PROMOTER_PAN,
        Entity::PROMOTER_PAN_NAME
    ];

    protected static $BUSINESS_TYPE_FIELDS = [
        //registered business type
        BusinessType::LLP                    => self::DEFAULT_REGISTERED_GROUP,
        BusinessType::OTHER                  => self::DEFAULT_REGISTERED_GROUP,
        BusinessType::PARTNERSHIP            => self::DEFAULT_REGISTERED_GROUP,
        BusinessType::HUF                    => self::DEFAULT_REGISTERED_GROUP,
        BusinessType::PROPRIETORSHIP         => self::PROPRIETORSHIP_FIELD_GROUP,
        BusinessType::PUBLIC_LIMITED         => self::DEFAULT_REGISTERED_GROUP,
        BusinessType::PRIVATE_LIMITED        => self::DEFAULT_REGISTERED_GROUP,
        BusinessType::TRUST                  => self::DEFAULT_REGISTERED_GROUP,
        BusinessType::SOCIETY                => self::DEFAULT_REGISTERED_GROUP,
        BusinessType::EDUCATIONAL_INSTITUTES => self::DEFAULT_REGISTERED_GROUP,

        BusinessType::NGO                => self::NGO_FIELD_GROUP,

        //unregistered business type
        BusinessType::INDIVIDUAL         => self::UNREGISTERED_FIELD_GROUP,
        BusinessType::NOT_YET_REGISTERED => self::UNREGISTERED_FIELD_GROUP,

        self::DEFAULT => [
            self::REQUIRED_FIELDS           => [RequiredFields::BANK_ACCOUNT_FIELDS, RequiredFields::MERCHANT_DEFAULT_FIELDS],
            self::SELECTIVE_REQUIRED_FIELDS => [],
            self::OPTIONAL_FIELDS           => [],
        ],
    ];

    const ROUTE_NO_DOC_KYC_FIELDS = [
        Constants::UNREGISTERED_AND_PROPRIETORSHIP => [
            Entity::BUSINESS_NAME,
            Entity::PROMOTER_PAN_NAME,
            Entity::PROMOTER_PAN,
            Entity::BANK_ACCOUNT_NAME,
            Entity::BANK_ACCOUNT_NUMBER,
            Entity::BANK_BRANCH_IFSC,
        ],
        Constants::REGISTERED => [
            Entity::PROMOTER_PAN_NAME,
            Entity::BUSINESS_NAME,
            Entity::COMPANY_PAN,
            Entity::BANK_ACCOUNT_NAME,
            Entity::BANK_ACCOUNT_NUMBER,
            Entity::BANK_BRANCH_IFSC,
        ],
    ];

    /**
     *
     * Returns list of document required for a particular field/ document group .
     *
     * @param string $field
     *
     * @return array
     */
    public static function getDocumentsRequired(string $field): array
    {
        if (Type::isValid($field) === true)
        {
            return [$field];
        }

        $allSelectiveFields = self::mergeFirstLevelArrays(SelectiveRequiredFields::ALL_SELECTIVE_FIELDS);

        //
        // explain :(example)
        // In case of unregistered as a poa document merchant can submit multiple documents
        // Like aadhaar , passport , voter id , driver license so returning a default document type (first set in that document type)
        //
        if (array_key_exists($field, $allSelectiveFields) === true)
        {
            return $allSelectiveFields[$field][0];
        }

        return [];
    }

    /**
     * @param array $maps
     *
     * @return array
     */
    protected static function mergeFirstLevelArrays(array $maps)
    {
        $result = [];

        foreach ($maps as $map)
        {
            $result = array_merge($result, $map);
        }

        return $result;
    }

    /**
     * @param Entity $merchantDetails
     *
     * @return array
     */
    public static function getValidationFields(Entity $merchantDetails)
    {
        $levels = [$merchantDetails->getBusinessType(), $merchantDetails->getBusinessCategory(), $merchantDetails->getBusinessSubcategory()];

        $fields = self::getRequiredValidationFields($levels, 0, self::$BUSINESS_TYPE_FIELDS);

        //merge first level of array
        $requiredFields          = self::mergeFirstLevelArrays($fields[self::REQUIRED_FIELDS]);
        $selectiveRequiredFields = self::mergeFirstLevelArrays($fields[self::SELECTIVE_REQUIRED_FIELDS]);
        $optionalFields          = self::mergeFirstLevelArrays($fields[self::OPTIONAL_FIELDS]);

        //
        // if business type is null then we can not determine fields to show , so by default we are showing registered fields
        //
        if (empty($merchantDetails->getBusinessType()) === true)
        {
            $requiredFields = array_merge($requiredFields, RequiredFields::REGISTERED_BUSINESS_FIELDS);
        }

        return [$requiredFields, $selectiveRequiredFields, $optionalFields];
    }

    public static function getRequiredFieldsForNoDocOnboarding(string $businessType, bool $isLinkedAccount = false) : array
    {
        if ($isLinkedAccount === true)
        {
            if(empty($businessType) === true)
            {
                return RequiredFields::MARKETPLACE_ACCOUNT_FIELDS;
            }

            switch ($businessType)
            {
                case BusinessType::NOT_YET_REGISTERED:
                case BusinessType::INDIVIDUAL:
                case BusinessType::PROPRIETORSHIP:
                {
                    return self::ROUTE_NO_DOC_KYC_FIELDS[Constants::UNREGISTERED_AND_PROPRIETORSHIP];
                }

                case BusinessType::PUBLIC_LIMITED:
                case BusinessType::PRIVATE_LIMITED:
                case BusinessType::LLP:
                case BusinessType::PARTNERSHIP:
                case BusinessType::TRUST:
                case BusinessType::NGO:
                case BusinessType::SOCIETY:
                {
                    return self::ROUTE_NO_DOC_KYC_FIELDS[Constants::REGISTERED];
                }

                default:
                {
                    throw new LogicException('Invalid business type for linked account creation.');
                }
            }
        }

        switch ($businessType)
        {
            case BusinessType::NOT_YET_REGISTERED:
                return self::UNREGISTERED_NO_DOC_FIELDS;

            case BusinessType::PROPRIETORSHIP:
                return self::PROPRIETORSHIP_NO_DOC_FIELDS;

            default:
                return self::DEFAULT_REGISTERED_NO_DOC_FIELDS;
        }
    }

    public static function getOptionalFieldsForNoDocOnboarding(string $businessType) : array
    {
        switch ($businessType)
        {
            case BusinessType::PROPRIETORSHIP:
            case BusinessType::PARTNERSHIP:
            case BusinessType::TRUST:
            case BusinessType::NGO:
            case BusinessType::SOCIETY:
                return self::REGISTERED_NO_DOC_OPTIONAL_FIELDS;

            case BusinessType::PUBLIC_LIMITED:
            case BusinessType::PRIVATE_LIMITED:
            case BusinessType::LLP:
                $array = self::REGISTERED_NO_DOC_OPTIONAL_FIELDS;
                array_push($array, Entity::COMPANY_CIN);
                return $array;

            default:
                return [Entity::CONTACT_NAME];
        }
    }

    /**
     * this function will be called recursively
     *
     * @param array $levels                   * contain value of business_type, business_category, business_subcategory
     *                                        sequentially
     * @param int   $index                    * index specify which levels default document me need to add.
     *                                        like default document of business_type or business_category or
     *                                        business_subcategory
     * @param array $mapping                  * $mapping contain document mapping of one upper level
     *                                        meaning if index = 1 means business_type then $documentMapping contain
     *                                        document of that businessType
     *
     *
     * @return array
     */
    protected static function getRequiredValidationFields(array $levels, int $index, array $mapping)
    {
        if (($index > self::LEVEL_COUNT === true) or
            empty($mapping) === true)
        {
            return [];
        }

        $requiredFieldsFromNextLevel = [];

        if ((isset($levels[$index]) === true) and
            isset($mapping[$levels[$index]]) === true)
        {
            $requiredFieldsFromNextLevel = self::getRequiredValidationFields($levels, $index + 1, $mapping[$levels[$index]] ?? []);
        }

        $defaultMappingForLevel = $mapping[self::DEFAULT] ?? [];

        $requiredFields = [];

        foreach (self::FIELDS_TYPES as $field_type)
        {
            $requiredFields[$field_type] = array_merge($defaultMappingForLevel[$field_type] ?? [], $requiredFieldsFromNextLevel[$field_type] ?? []);
        }

        return $requiredFields;
    }

    public static function getPartnerKycValidationFields(string $businessType)
    {
        switch ($businessType)
        {
            case BusinessType::INDIVIDUAL:
            case BusinessType::NOT_YET_REGISTERED:
            case BusinessType::PROPRIETORSHIP:
                return [Entity::PROMOTER_PAN,
                        Entity::PROMOTER_PAN_NAME,
                        Entity::BANK_ACCOUNT_NAME,
                        Entity::BANK_ACCOUNT_NUMBER,
                        Entity::BANK_BRANCH_IFSC,
                        Entity::BUSINESS_OPERATION_ADDRESS,
                        Entity::BUSINESS_OPERATION_PIN,
                        Entity::BUSINESS_OPERATION_CITY,
                        Entity::BUSINESS_OPERATION_STATE];
            default:
                return [Entity::COMPANY_PAN,
                        Entity::BUSINESS_NAME,
                        Entity::BANK_ACCOUNT_NAME,
                        Entity::BANK_ACCOUNT_NUMBER,
                        Entity::BANK_BRANCH_IFSC,
                        Entity::BUSINESS_REGISTERED_ADDRESS,
                        Entity::BUSINESS_REGISTERED_PIN,
                        Entity::BUSINESS_REGISTERED_CITY,
                        Entity::BUSINESS_REGISTERED_STATE];
        }
    }

    public static function getRequiredFieldsForInstantActV2Apis(string $businessType) : array
    {
        switch ($businessType)
        {
            case BusinessType::NOT_YET_REGISTERED:
            case BusinessType::INDIVIDUAL:
                return self::L1_FIELDS_IA_V2_APIS;

            case BusinessType::PROPRIETORSHIP:
                return array_merge(self::L1_FIELDS_IA_V2_APIS, [Entity::BUSINESS_NAME]);

            default:
                return array_merge(self::L1_FIELDS_IA_V2_APIS, [Entity::BUSINESS_NAME, Entity::COMPANY_PAN]);
        }
    }
}
