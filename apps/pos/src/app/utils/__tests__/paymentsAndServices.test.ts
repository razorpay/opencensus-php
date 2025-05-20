import { getMockModularConfigWithPricingStep } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/__tests__/mocks/fixtures';
import {
  MODULAR_PRICING_FIELDS,
  PricingStepComponents,
} from 'apps/pos/src/app/types/PaymentsAndService';
import { ModularOnboardingField } from 'apps/pos/src/app/types/modular';
import {
  getAllAddedBrands,
  getAllBrandEmiFields,
  getBrandEmiCcDcEnabledStatus,
  getBrandEmiField,
  getSelectedStoreName,
  getStandardPosPricingRates,
  getStoreTypes,
  hasEditedStandardRates,
  replaceEmptyValues,
  updateValuesForUncheckedRates,
  validatePricingRates,
} from 'apps/pos/src/app/utils/paymentsAndServices';

describe('getBrandEmiField', () => {
  test('should return null if modularConfig is not provided', () => {
    expect(getBrandEmiField({ modularConfig: null, fieldName: 'brand_name_field' })).toBeNull();
  });
  test('should return the correct field name', () => {
    expect(
      getBrandEmiField({
        modularConfig: getMockModularConfigWithPricingStep({}) as any,
        fieldName: MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD,
      }),
    ).toEqual(expect.objectContaining({ name: 'store_type_field' }));
  });
});

describe('getSelectedStoreName', () => {
  const options = [
    { value: 'large_retail', label: 'Large retail' },
    { value: 'exclusive_outlet', label: 'Exclusive Outlet' },
  ];
  test('should return an empty string if storeTypeField is undefined', () => {
    expect(getSelectedStoreName()).toBe('');
  });

  test('should return an empty string if storeTypeField is not a string value', () => {
    expect(getSelectedStoreName({} as ModularOnboardingField)).toBe('');
  });

  test('should return the matching label when a valid store type exists', () => {
    const storeTypeField = {
      stringValue: 'large_retail',
      meta: {
        options: options,
        dataType: 'string',
      },
      name: 'storeType',
      isDisabled: false,
      isRequired: false,
    };
    expect(getSelectedStoreName(storeTypeField)).toBe('Large retail');
  });

  test('should return an empty string if no matching option is found', () => {
    const storeTypeField = {
      stringValue: 'new_store_type',
      meta: {
        options: options,
        dataType: 'string',
      },
      name: 'storeType',
      isDisabled: false,
      isRequired: false,
    };
    expect(getSelectedStoreName(storeTypeField)).toBe('');
  });

  test('should return an empty string if options are missing', () => {
    const storeTypeField = {
      stringValue: 'new_store_type',
      name: 'storeType',
      isDisabled: false,
      isRequired: false,
    };
    expect(getSelectedStoreName(storeTypeField)).toBe('');
  });
});

describe('updateValuesForUncheckedRates', () => {
  const mockPaymentMethodsFieldKeyNames = {
    BRAND_EMI_RATE_ENABLED_FIELD: 'brand_emi_rate_enabled_field',
    BRAND_EMI_CC_RATE_FIELD: 'brand_emi_cc_rate_field',
    BRAND_EMI_DC_RATE_FIELD: 'brand_emi_dc_rate_field',
    EMI_PLUS_RATE_ENABLED_FIELD: 'emi_plus_rate_enabled_field',
    EMI_PLUS_CC_RATE_FIELD: 'emi_plus_cc_rate_field',
    EMI_PLUS_DC_RATE_FIELD: 'emi_plus_dc_rate_field',
  };

  test('should return unchanged values when all enabled fields are true', () => {
    const input = {
      vas_cc_emi_rate_enabled_field: true,
      vas_cc_emi_rate_field: 2.5,
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD]: true,
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_CC_RATE_FIELD]: 3.0,
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_DC_RATE_FIELD]: 3.5,
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_RATE_ENABLED_FIELD]: true,
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_CC_RATE_FIELD]: 4.0,
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_DC_RATE_FIELD]: 4.5,
    };

    const result = updateValuesForUncheckedRates(input);
    expect(result).toEqual(input);
  });

  test('should set rate fields to null when corresponding enabled fields are false', () => {
    const input = {
      vas_cc_emi_rate_enabled_field: false,
      vas_cc_emi_rate_field: 2.5,
      other_rate_enabled_field: false,
      other_rate_field: 1.5,
      normal_field: 'value',
    };

    const expected = {
      vas_cc_emi_rate_enabled_field: false,
      vas_cc_emi_rate_field: null,
      other_rate_enabled_field: false,
      other_rate_field: null,
      normal_field: 'value',
    };

    const result = updateValuesForUncheckedRates(input);
    expect(result).toEqual(expected);
  });

  test('should set BRAND_EMI rate fields to null when BRAND_EMI_RATE_ENABLED_FIELD is false', () => {
    const input = {
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD]: false,
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_CC_RATE_FIELD]: 3.0,
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_DC_RATE_FIELD]: 3.5,
    };

    const expected = {
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD]: false,
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_CC_RATE_FIELD]: null,
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_DC_RATE_FIELD]: null,
    };

    const result = updateValuesForUncheckedRates(input);
    expect(result).toEqual(expected);
  });

  test('should set EMI_PLUS rate fields to null when EMI_PLUS_RATE_ENABLED_FIELD is false', () => {
    const input = {
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_RATE_ENABLED_FIELD]: false,
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_CC_RATE_FIELD]: 4.0,
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_DC_RATE_FIELD]: 4.5,
    };

    const expected = {
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_RATE_ENABLED_FIELD]: false,
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_CC_RATE_FIELD]: null,
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_DC_RATE_FIELD]: null,
    };

    const result = updateValuesForUncheckedRates(input);
    expect(result).toEqual(expected);
  });

  test('should handle all cases correctly in a comprehensive example', () => {
    const input = {
      vas_cc_emi_rate_enabled_field: false,
      vas_cc_emi_rate_field: 2.5,
      dc_rate_enabled_field: true,
      dc_rate_field: 1.8,
      some_other_field: 'value',
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD]: false,
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_CC_RATE_FIELD]: 3.0,
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_DC_RATE_FIELD]: 3.5,
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_RATE_ENABLED_FIELD]: true,
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_CC_RATE_FIELD]: 4.0,
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_DC_RATE_FIELD]: 4.5,
    };

    const expected = {
      vas_cc_emi_rate_enabled_field: false,
      vas_cc_emi_rate_field: null,
      dc_rate_enabled_field: true,
      dc_rate_field: 1.8,
      some_other_field: 'value',
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD]: false,
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_CC_RATE_FIELD]: null,
      [mockPaymentMethodsFieldKeyNames.BRAND_EMI_DC_RATE_FIELD]: null,
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_RATE_ENABLED_FIELD]: true,
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_CC_RATE_FIELD]: 4.0,
      [mockPaymentMethodsFieldKeyNames.EMI_PLUS_DC_RATE_FIELD]: 4.5,
    };

    const result = updateValuesForUncheckedRates(input);
    expect(result).toEqual(expected);
  });

  // Test 6: Empty object case
  test('should handle empty object correctly', () => {
    const input = {};
    const result = updateValuesForUncheckedRates(input);
    expect(result).toEqual({});
  });

  // Test 7: Object with no relevant fields
  test('should not modify object with no relevant fields', () => {
    const input = {
      irrelevant_field: 'value',
      another_field: 42,
    };

    const result = updateValuesForUncheckedRates(input);
    expect(result).toEqual(input);
  });

  // Test 8: Object with missing base fields
  test('should handle case when enabled field exists but base field is missing', () => {
    const input = {
      some_rate_enabled_field: false,
      // some_rate_field is missing
    };

    const result = updateValuesForUncheckedRates(input);
    expect(result).toEqual(input);
  });

  // Test 9: Check that function doesn't modify original object
  test('should not modify the original object', () => {
    const input = {
      vas_cc_emi_rate_enabled_field: false,
      vas_cc_emi_rate_field: 2.5,
    };

    const inputCopy = { ...input };
    updateValuesForUncheckedRates(input);

    expect(input).toEqual(inputCopy);
  });
});

describe('getAllAddedBrands', () => {
  test('should return empty array when no brand summary is present', () => {
    const result = getAllAddedBrands({
      modularConfig: getMockModularConfigWithPricingStep({
        hasBrandSummaryField: false,
      }),
    } as any);
    expect(result).toEqual([]);
  });

  test('should return empty array when no brand options is present', () => {
    const result = getAllAddedBrands({
      modularConfig: getMockModularConfigWithPricingStep({
        hasBrandSummaryField: true,
        hasBrandOptions: false,
      }),
    } as any);
    expect(result).toEqual([]);
  });

  test('should return added brands', () => {
    const result = getAllAddedBrands({
      modularConfig: getMockModularConfigWithPricingStep({
        hasBrandSummaryField: true,
        hasBrandOptions: true,
      }),
    } as any);
    expect(result).toEqual([
      {
        label: 'elanpro',
        name: 'elanpro',
        dealerCode: null,
        distributorCode: null,
        stateCode: null,
        verificationDetailsId: 'PoyJlI9QCcTQAv',
        verificationStatus: 'failed',
        merchantGst: null,
      },
    ]);
  });
});

describe('getBrandEmiCcDcEnabledStatus', () => {
  test('should return false if modular config is absent', () => {
    expect(getBrandEmiCcDcEnabledStatus(null)).toBe(false);
  });
  test('should return false if acquisiton model field is absent', () => {
    const mockModularConfig = getMockModularConfigWithPricingStep({
      hasAcquisitionModelField: false,
    });
    expect(getBrandEmiCcDcEnabledStatus(mockModularConfig as any)).toBe(false);
  });
  test('should return false if brand emi cc and dc both are disabled', () => {
    const mockModularConfig = getMockModularConfigWithPricingStep({
      hasAcquisitionModelField: true,
      isBrandEmiCcDisabled: true,
      isBrandEmiDcDisabled: true,
    });
    expect(getBrandEmiCcDcEnabledStatus(mockModularConfig as any)).toBe(false);
  });
  test('should return false if either brand emi cc or dc field is absent', () => {
    const mockModularConfig = getMockModularConfigWithPricingStep({
      hasAcquisitionModelField: true,
      hasBrandEmiCcRateField: false,
    });
    expect(getBrandEmiCcDcEnabledStatus(mockModularConfig as any)).toBe(false);
  });
  test('should return true if either brand emi cc or dc field is enabled', () => {
    const mockModularConfig = getMockModularConfigWithPricingStep({
      hasAcquisitionModelField: true,
      isBrandEmiCcDisabled: false,
      isBrandEmiDcDisabled: true,
    });
    expect(getBrandEmiCcDcEnabledStatus(mockModularConfig as any)).toBe(true);
  });
  test('should return true if neither brand emi cc or dc field is disabled', () => {
    const mockModularConfig = getMockModularConfigWithPricingStep({
      isBrandEmiCcDisabled: false,
      isBrandEmiDcDisabled: false,
      hasBrandEmiComponent: true,
    });
    expect(getBrandEmiCcDcEnabledStatus(mockModularConfig as any)).toBe(true);
  });
});

describe('getAllBrandEmiFields', () => {
  test('should return empty array when modular config is absent', () => {
    expect(getAllBrandEmiFields({ modularConfig: null })).toEqual([]);
  });
  test('should return empty array when brand emi component is absent', () => {
    expect(
      getAllBrandEmiFields({
        modularConfig: getMockModularConfigWithPricingStep({ hasBrandEmiComponent: false }) as any,
      }),
    ).toEqual([]);
  });
  test('should return all brand emi fields when brand emi component is present', () => {
    expect(
      getAllBrandEmiFields({
        modularConfig: getMockModularConfigWithPricingStep({ hasBrandEmiComponent: true }) as any,
      }).length,
    ).toBeGreaterThanOrEqual(1);
  });
});

describe('getStoreTypes', () => {
  test('should return an empty array if modularConfig is null', () => {
    expect(getStoreTypes({ modularConfig: null })).toEqual([]);
  });

  test('should return an empty array if store type field is not found', () => {
    expect(
      getStoreTypes({
        modularConfig: getMockModularConfigWithPricingStep({ hasBrandEmiComponent: false }) as any,
      }),
    ).toEqual([]);
  });

  test('should return an empty array if store type field exists but has no options', () => {
    expect(
      getStoreTypes({
        modularConfig: getMockModularConfigWithPricingStep({ hasStoreTypeOptions: false }) as any,
      }),
    ).toEqual([]);
  });

  test('should return store type options if brandEmiField has options', () => {
    expect(
      getStoreTypes({ modularConfig: getMockModularConfigWithPricingStep({}) as any }).length,
    ).toBeGreaterThanOrEqual(1);
  });
});

describe('validatePricingRates', () => {
  test('should return an empty error field when all rates are valid', () => {
    const rates = {
      rate1: '10',
      rate2: '5.99',
      rate3: '0.5',
    };
    const result = validatePricingRates(rates);
    expect(result.errFieldName).toBe('');
  });

  test('should return the first invalid field when a rate is not a number', () => {
    const rates = {
      rate1: '10',
      rate2: 'invalid',
      rate3: '5.99',
    };
    const result = validatePricingRates(rates);
    expect(result.errFieldName).toBe('rate2');
  });

  test('should return the first invalid field when a rate has an incorrect format', () => {
    const rates = {
      rate1: '10',
      rate2: '.5.9', // More than 2 decimal places
      rate3: '7',
    };
    const result = validatePricingRates(rates);
    expect(result.errFieldName).toBe('rate2');
  });

  test('should return the first invalid field when a rate is empty', () => {
    const rates = {
      rate1: '',
      rate2: '5.99',
    };
    const result = validatePricingRates(rates);
    expect(result.errFieldName).toBe('rate1');
  });

  test('should return an empty error field when given an empty object', () => {
    const rates = {};
    const result = validatePricingRates(rates);
    expect(result.errFieldName).toBe('');
  });
});

describe('hasEditedStandardRates', () => {
  test('should return isStdRateEdited as false and empty differences when stdRates is null', () => {
    const result = hasEditedStandardRates({ stdRates: null, currentRates: {} });
    expect(result).toEqual({ differences: {}, isStdRateEdited: false });
  });

  test('should return isStdRateEdited as false and empty differences when stdRates is undefined', () => {
    const result = hasEditedStandardRates({ stdRates: undefined, currentRates: {} });
    expect(result).toEqual({ differences: {}, isStdRateEdited: false });
  });

  test('should return isStdRateEdited as false when stdRates and currentRates are identical', () => {
    const rates = { rate1: '10', rate2: '5.5', rate3: '7' };
    const result = hasEditedStandardRates({ stdRates: rates, currentRates: rates });
    expect(result).toEqual({ differences: {}, isStdRateEdited: false });
  });

  test('should detect differences when at least one rate is edited', () => {
    const stdRates = { rate1: '10', rate2: '5.5', rate3: '7' };
    const currentRates = { rate1: '10', rate2: '6.0', rate3: '8' };

    const result = hasEditedStandardRates({ stdRates, currentRates });

    expect(result.isStdRateEdited).toBe(true);
    expect(result.differences).toEqual({
      rate2: { stdRateValue: '5.5', currentRateValue: '6.0' },
      rate3: { stdRateValue: '7', currentRateValue: '8' },
    });
  });

  test('should ignore extra keys in currentRates that are not in stdRates', () => {
    const stdRates = { rate1: '10', rate2: '5.5' };
    const currentRates = { rate1: '10', rate2: '6.0', rate3: '8' }; // rate3 is extra

    const result = hasEditedStandardRates({ stdRates, currentRates });

    expect(result.isStdRateEdited).toBe(true);
    expect(result.differences).toEqual({
      rate2: { stdRateValue: '5.5', currentRateValue: '6.0' },
    });
  });

  test('should return isStdRateEdited as false when currentRates has all matching values but contains extra keys', () => {
    const stdRates = { rate1: '10', rate2: '5.5' };
    const currentRates = { rate1: '10', rate2: '5.5', rate3: '8' }; // rate3 is extra

    const result = hasEditedStandardRates({ stdRates, currentRates });

    expect(result.isStdRateEdited).toBe(false);
    expect(result.differences).toEqual({});
  });
});

describe('getStandardPosPricingRates', () => {
  test('should return empty object when standard rates are not present', () => {
    const result = getStandardPosPricingRates({
      modularConfig: getMockModularConfigWithPricingStep({ hasStdRates: false }),
    } as any);
    expect(result).toEqual(null);
  });
  test('should return default rates when present', () => {
    const result = getStandardPosPricingRates({
      modularConfig: getMockModularConfigWithPricingStep({}),
      componentName: PricingStepComponents.MDR_VAS_RATES_COMPONENT,
    } as any);
    expect(result).toEqual({
      credit_card_mdr_rate_field: '0.5',
      debit_card_rupay_mdr_rate_field: '1.2',
      debit_card_visa_mastercard_maestro_greater_than_2k_mdr_rate_field: '0.5',
      debit_card_visa_mastercard_maestro_less_than_2k_mdr_rate_field: '0.66',
      prepaid_b2b_corporate_channel_international_card_mdr_rate_field: '0.5',
      upi_mdr_rate_field: '0.5',
      vas_cc_emi_rate_field: '1.5',
      vas_dc_emi_rate_field: '0.5',
    });
  });
});
