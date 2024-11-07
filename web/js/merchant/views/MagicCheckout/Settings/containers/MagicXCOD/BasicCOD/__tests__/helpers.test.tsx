import {
  convertFormDataToPayload,
  convertServerDataToTableData,
  convertTableDataToForm,
  getLastSyncedWithShopifyInMs,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/helpers';
import {
  mockServerData,
  mockTableData,
  mockFormData,
  mockPayload,
  mockShippingProfiles,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/__tests__/mocks/fixtures';

describe('convertServerDataToTableData', () => {
  it('should convert server data into table data', () => {
    const result = convertServerDataToTableData(mockServerData);

    expect(result).toEqual(mockTableData);
  });
});

describe('convertTableDataToForm', () => {
  it('should convert table data to form data and apply major unit conversion', () => {
    const result = convertTableDataToForm(mockTableData[0]);

    expect(result).toEqual(mockFormData);
  });
  it('should initialize cod_fee_rules if not present', () => {
    const formDataWithNoFeeRules = {
      ...mockFormData,
      cod_fee_rules: null,
      allow_cod: true,
    };

    const result = convertTableDataToForm(formDataWithNoFeeRules);

    expect(result.cod_fee_rules).not.toBeNull();
  });
});

describe('convertFormDataToPayload', () => {
  it('should convert form data to payload and remove redundant attributes', () => {
    const result = convertFormDataToPayload(mockFormData);

    expect(result).toEqual(mockPayload);
  });

  it('should set cod_fee_rules to null if allow_cod is false', () => {
    const formDataWithNoCod = {
      ...mockFormData,
      allow_cod: false,
    };

    const result = convertFormDataToPayload(formDataWithNoCod);

    expect(result?.cod_fee_rules).toBeNull();
  });

  it('should set cod_fee_rules to null if both gte & lt are null', () => {
    const formDataWithNoFeeRules = {
      ...mockFormData,
      cod_fee_rules: {
        amount: {
          lt: null,
          gte: null,
        },
      },
      allow_cod: true,
    };

    const result = convertFormDataToPayload(formDataWithNoFeeRules);

    expect(result.cod_fee_rules).toBeNull();
  });
});

describe('Last synced with shopify', () => {
  it('should return correct last synced value', () => {
    const result = getLastSyncedWithShopifyInMs(mockShippingProfiles);
    expect(result).toBe(1729449836998);
  });
});
