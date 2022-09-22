<?php

namespace RZP\Models\Merchant\OneClickCheckout\RtoFeatureReasonProvider;

class Constants {

    // Block List Rule Reasons
    const RuleCustomerPhoneBlockListedByMerchant   = "Customer phone blocklisted by merchant";
    const RuleCustomerIPBlockListedByMerchant      = "Customer IP blocklisted by merchant";
    const RuleCustomerZipcodeBlockListedByMerchant = "Customer zipcode blocklisted by merchant";
    const RuleCustomerEmailBlockListedByMerchant   = "Customer email blocklisted by merchant";

    // Address Rule Reasons
    const RuleShortShippingAddress       = "Short shipping address";
    const RuleGibberishDetectedInAddress = "Gibberish detected in address";
    const RuleIncompleteZipcode          = "Incomplete zipcode";
    const RuleDummyAddressDetected       = "Dummy address detected";
    const RuleDummyNameDetected          = "Dummy name detected";
    const RuleInvalidZipcode             = "Invalid zipcode";
    const RuleWrongStateEntered          = "Wrong state entered";
    const RuleIncompleteShippingAddress  = "Incomplete shipping address";

    // Email Rule Reasons
    const RuleInvalidEmailDomain       = "Invalid email domain";
    const RuleInvalidEmailAddress      = "Invalid email address";
    const RuleGibberishDetectedInEmail = "Gibberish detected in email";

    // RTO Rule Reasons
    const RuleHighRTOPhoneOnYourStore       = "High RTO phone on your store";
    const RuleHighRTOEmailOnYourStore       = "High RTO email on your store";
    const RuleHighRTOIPOnYourStore          = "High RTO IP on your store";
    const RuleHighRTODeviceOnYourStore      = "High RTO device on your store";
    const RuleHighRTOZipcodeOnYourStore     = "High RTO zipcode on your store";
    const RuleHighRTOPhoneAcrossAllStores   = "High RTO phone across all stores";
    const RuleHighRTOEmailAcrossAllStores   = "High RTO email across all stores";
    const RuleHighRTOIPAcrossAllStores      = "High RTO IP across all stores";
    const RuleHighRTODeviceAcrossAllStores  = "High RTO device across all stores";
    const RuleHighRTOZipcodeAcrossAllStores = "High RTO zipcode across all stores";

    // Phone Rule Reasons
    const RuleInvalidPhone = "Invalid phone";

    // Address ML Model Reasons
    const MLModelAddressContainsNoDigits                = "Address contains no digits";
    const MLModelAddressHasVeryFewDigits                = "Address has very few digits";
    const MLModelAddressLine2Missing                    = "Address Line 2 missing";
    const MLModelAddressContainsTooManySymbols          = "Address contains too many symbols";
    const MLModelAddressIsLessDetailed                  = "Address is less detailed";
    const MLModelAddressDoesNotContainCommas            = "Address does not contain commas";
    const MLModelAddressDoesNotContainHouseNameOrNumber = "Address does not contain house name/number";
    const MLModelAddressDoesNotContainBuildingName      = "Address does not contain building name";
    const MLModelAddressDoesNotContainLocalityName      = "Address does not contain locality name";
    const MLModelInCorrectZipcodeEntered                = "Incorrect zipcode entered";
    const MLModelMismatchBetweenZipcodeAndCity          = "Mismatch between zipcode and city";
    const MLModelAddressInComplete                      = "Address incomplete";
    const MLModelAddressDoesNotContainStandardFields    = "Address doesn't contain standard fields";

    // Behavior ML Model Reasons
    const MLModelHighPurchaseAmountInOneHour = "High purchase amount in one hour";
    const MLModelHighPurchaseAmountInOneDay  = "High purchase amount in one day";
    const MLModelNewCustomerOnYourStore      = "New customer on your store";

    // COD ML Model Reasons
    const MLModelFewPrepaidOrdersSeenFromEmailInYourStore = "Few prepaid orders seen from email in your store";
    const MLModelLowPrepaidOrderCustomer                  = "Low prepaid order customer";
    const MLModelHighCODCustomerAcrossAllStores           = "High COD customer across all stores";
    const MLModelHighCODCustomerOnYourStore               = "High COD customer on your store";
    const MLModelHighCODCityAcrossAllStores               = "High COD city across all stores";

    // Delivery ML Model Reasons
    const MLModelLowDeliveredOrdersForCustomer                = "Low delivered orders for customer";
    const MLModelLowDeliveredOrdersForCustomerAcrossAllStores = "Low delivered orders for customer across all stores";
    const MLModelLowDeliveredOrdersOnZipcodeAcrossAllStores   = "Low delivered orders on zipcode across all stores";
    const MLModelLowDeliveredOrdersOnZipcodeOnYourStore       = "Low delivered orders on zipcode on your store";
    const MLModelLowDeliveredOrdersInCityOnYourStore          = "Low delivered orders in city on your store";
    const MLModelLowDeliveredOrdersAcrossCity                 = "Low delivered orders across city";

    // Email ML Model Reasons
    const MLModelEmailIsTooShort       = "Email is too short";
    const MLModelEmailHasTooManyDigits = "Email has too many digits";

    // Return ML Model Reasons
    const MLModelHighReturnZipcodeAcrossAllStores = "High return zipcode across all stores";
    const MLModelHighReturnCityAcrossAllStores    = "High returns city across all stores";
    const MLModelHighReturnStateAcrossAllStores   = "High returns state across all stores";

    // RTO ML Model Reasons
    const MLModelOrderBelongsToHighRTOArea             = "Order belongs to High RTO area";
    const MLModelOrderPlacedDuringStartOrEndOfTheMonth = "Order placed during start/end of the month";
    const MLModelHighCODOrdersFromThisEmailInYourStore = "High COD orders from this email in your store";
    const MLModelHighRTOCustomerAcrossAllStores        = "High RTO customer across all stores";
    const MLModelHighRTOZipcodeAcrossAllStores         = "High RTO zipcode across all stores";
    const MLModelHighRTOZipcodeOnYourStore             = "High RTO zipcode on your store";
    const MLModelHighRTOCityAcrossAllStores            = "High RTO city across all stores";
    const MLModelHighRTOCityOnYourStore                = "High RTO city on your store";

    const FeatureReasonMap = [
        "IsPhoneBlacklisted" => self::RuleCustomerPhoneBlockListedByMerchant,
        "IsIPBlacklisted" => self::RuleCustomerIPBlockListedByMerchant,
        "IsZipcodeBlacklisted" => self::RuleCustomerZipcodeBlockListedByMerchant,
        "IsEmailBlacklisted" => self::RuleCustomerEmailBlockListedByMerchant,

        "ShortShippingAddress" => self::RuleShortShippingAddress,
        "AddressMonkeyTyped" => self::RuleGibberishDetectedInAddress,
        "ShortZipcode" => self::RuleIncompleteZipcode,
        "TestInAddress" => self::RuleDummyAddressDetected,
        "TestInName" => self::RuleDummyNameDetected,
        "InvalidZipcode" => self::RuleInvalidZipcode,
        "ZipcodeAddressStateMismatch" => self::RuleWrongStateEntered,
        "IncompleteShippingAddress" => self::RuleIncompleteShippingAddress,

        "EmailTempDomain" => self::RuleInvalidEmailDomain,
        "InvalidEmail" => self::RuleInvalidEmailAddress,
        "MonkeyTypedEmail" => self::RuleGibberishDetectedInEmail,

        "HighPhoneRTOMerchant" => self::RuleHighRTOPhoneOnYourStore,
        "HighEmailRTOMerchant" => self::RuleHighRTOEmailOnYourStore,
        "HighIpRTOMerchant" => self::RuleHighRTOIPOnYourStore,
        "HighDeviceRTOMerchant" => self::RuleHighRTODeviceOnYourStore,
        "HighPhoneRTOGlobal" => self::RuleHighRTOPhoneAcrossAllStores,
        "HighEmailRTOGlobal" => self::RuleHighRTOEmailAcrossAllStores,
        "HighIpRTOGlobal" => self::RuleHighRTOIPAcrossAllStores,
        "HighDeviceRTOGlobal" => self::RuleHighRTODeviceAcrossAllStores,
        "HighZipcodeRTOMerchant" => self::RuleHighRTOZipcodeOnYourStore,
        "HighZipcodeRTOGlobal" => self::RuleHighRTOZipcodeAcrossAllStores,

        "InvalidPhone" => self::RuleInvalidPhone,

        "addressstaticfeatures_hasdigits" => self::MLModelAddressContainsNoDigits,
        "addressstaticfeatures_countdigits" => self::MLModelAddressHasVeryFewDigits,
        "addressstaticfeatures_lengthtodigitsratio" => self::MLModelAddressHasVeryFewDigits,
        "addressstaticfeatures_hassecondfield" => self::MLModelAddressLine2Missing,
        "addressstaticfeatures_countsymbols" => self::MLModelAddressContainsTooManySymbols,
        "addressstaticfeatures_countwords" => self::MLModelAddressIsLessDetailed,
        "addressstaticfeatures_countcommas" => self::MLModelAddressDoesNotContainCommas,
        "addressstaticfeatures_hashousefield" => self::MLModelAddressDoesNotContainHouseNameOrNumber,
        "addressstaticfeatures_hasbuildingfield" => self::MLModelAddressDoesNotContainBuildingName,
        "addressstaticfeatures_haslocalityfield" => self::MLModelAddressDoesNotContainLocalityName,
        "addressstaticfeatures_isdigitinshipaddress" => self::MLModelAddressContainsNoDigits,
        "addressstaticfeatures_counttokens" => self::MLModelAddressIsLessDetailed,
        "addressstaticfeatures_iszipcodenotgood" => self::MLModelInCorrectZipcodeEntered,
        "addressstaticfeatures_isunitnumericinsociety" => self::MLModelAddressIsLessDetailed,
        "addressstaticfeatures_isinformative" => self::MLModelAddressIsLessDetailed,
        "addressstaticfeatures_iscorrect" => self::MLModelMismatchBetweenZipcodeAndCity,
        "addressstaticfeatures_iscomplete" => self::MLModelAddressInComplete,
        "addressstaticfeatures_maxwordratio" => self::MLModelAddressDoesNotContainStandardFields,
        "addressstaticfeatures_maxwordsuccess" => self::MLModelAddressDoesNotContainStandardFields,
        "addressstaticfeatures_maxseqbiwordcount" => self::MLModelAddressDoesNotContainStandardFields,
        "addressstaticfeatures_maxseqbiwordratio" => self::MLModelAddressDoesNotContainStandardFields,
        "addressstaticfeatures_maxbiwordcount" => self::MLModelAddressDoesNotContainStandardFields,
        "addressstaticfeatures_maxbiwordratio" => self::MLModelAddressDoesNotContainStandardFields,
        "addressstaticfeatures_mergescore" => self::MLModelAddressDoesNotContainStandardFields,

        "emailmerchantfeatures_sumpurchaseamountinonehour" => self::MLModelHighPurchaseAmountInOneHour,
        "emailmerchantfeatures_sumpurchaseamountinoneday" => self::MLModelHighPurchaseAmountInOneDay,
        "generic_createaccounttimebucket" => self::MLModelNewCustomerOnYourStore,
        "phonemerchantfeatures_sumpurchaseamountinoneday" => self::MLModelHighPurchaseAmountInOneDay,
        "emailmerchantfeatures_countprepaidorders" => self::MLModelFewPrepaidOrdersSeenFromEmailInYourStore,

        "emailglobalfeatures_countprepaidorders" => self::MLModelLowPrepaidOrderCustomer,
        "emailglobalfeatures_countcodorders" => self::MLModelHighCODCustomerAcrossAllStores,
        "phoneglobalfeatures_countcodorders" => self::MLModelHighCODCustomerAcrossAllStores,
        "deviceidglobalfeatures_countcodorders" => self::MLModelHighCODCustomerAcrossAllStores,
        "zipcodeglobalfeatures_countprepaidorders" => self::MLModelLowPrepaidOrderCustomer,
        "zipcodeglobalfeatures_countcodorders" => self::MLModelHighCODCustomerOnYourStore,
        "cityglobalfeatures_countcodorders" => self::MLModelHighCODCityAcrossAllStores,

        "phoneglobalfeatures_deliveredpercent" => self::MLModelLowDeliveredOrdersForCustomer,
        "deviceidglobalfeatures_deliveredpercent" => self::MLModelLowDeliveredOrdersForCustomerAcrossAllStores,
        "zipcodeglobalfeatures_deliveredpercent" => self::MLModelLowDeliveredOrdersOnZipcodeAcrossAllStores,
        "zipcodemerchantfeatures_deliveredpercent" => self::MLModelLowDeliveredOrdersOnZipcodeOnYourStore,
        "zipcodemerchantfeatures_countdelivereditems" => self::MLModelLowDeliveredOrdersOnZipcodeOnYourStore,
        "citymerchantfeatures_deliveredpercent" => self::MLModelLowDeliveredOrdersInCityOnYourStore,
        "citymerchantfeatures_countdelivereditems" => self::MLModelLowDeliveredOrdersInCityOnYourStore,
        "cityglobalfeatures_deliveredpercent" => self::MLModelLowDeliveredOrdersAcrossCity,

        "emailstaticfeatures_length" => self::MLModelEmailIsTooShort,
        "emailstaticfeatures_countdigits" => self::MLModelEmailHasTooManyDigits,

        "zipcodeglobalfeatures_returnpercent" => self::MLModelHighReturnZipcodeAcrossAllStores,
        "zipcodeglobalfeatures_countreturnitems" => self::MLModelHighReturnZipcodeAcrossAllStores,
        "cityglobalfeatures_countreturnitems" => self::MLModelHighReturnCityAcrossAllStores,
        "cityglobalfeatures_returnpercent" => self::MLModelHighReturnCityAcrossAllStores,
        "statemerchantfeatures_countreturnitems" => self::MLModelHighReturnStateAcrossAllStores,

        "citystaticfeatures_citytier" => self::MLModelOrderBelongsToHighRTOArea,
        "generic_dayofmonth" => self::MLModelOrderPlacedDuringStartOrEndOfTheMonth,
        "emailmerchantfeatures_countcodorders" => self::MLModelHighCODOrdersFromThisEmailInYourStore,
        "emailglobalfeatures_rtopercent" => self::MLModelHighRTOCustomerAcrossAllStores,
        "phoneglobalfeatures_rtopercent" => self::MLModelHighRTOCustomerAcrossAllStores,
        "zipcodeglobalfeatures_rtopercent" => self::MLModelHighRTOZipcodeAcrossAllStores,
        "zipcodemerchantfeatures_rtopercent" => self::MLModelHighRTOZipcodeOnYourStore,
        "zipcodeglobalfeatures_countrtoitems" => self::MLModelHighRTOZipcodeAcrossAllStores,
        "zipcodemerchantfeatures_countrtoitems" => self::MLModelHighRTOZipcodeOnYourStore,
        "citymerchantfeatures_rtopercent" => self::MLModelHighRTOCityOnYourStore,
        "cityglobalfeatures_countrtoitems" => self::MLModelHighRTOCityAcrossAllStores,
        "citymerchantfeatures_countrtoitems" => self::MLModelHighRTOCityOnYourStore,
        "cityglobalfeatures_rtopercent" => self::MLModelHighRTOCityAcrossAllStores,
    ];
}
