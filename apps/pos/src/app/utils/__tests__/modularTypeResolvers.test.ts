import {
  isArrayOfDocumentsUpload,
  isBrandItem,
  isDeviceCharges,
  isDocumentUpload,
  isOrderSummaryItem,
  isStringValue,
} from 'apps/pos/src/app/utils/modularTypeResolvers';
import {
  ModularOnboardingFieldForDeviceCharges,
  ModularOnboardingFieldForOrderSummaryItem,
  ModularOnboardingFieldForArrayOfDocumentsUpload,
  ModularOnboardingFieldWithStringValue,
  ModularOnboardingFieldForDocumentUpload,
  DeviceCharges,
} from 'apps/pos/src/app/types/modular';

describe('isStringValue', () => {
  test('should return true when field has a stringValue property of type string', () => {
    const field: ModularOnboardingFieldWithStringValue = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      stringValue: 'testString',
    };

    expect(isStringValue(field)).toBe(true);
  });

  test('should return false when field is null', () => {
    expect(isStringValue(null)).toBe(false);
  });

  test('should return false when stringValue property is not a string', () => {
    const field = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      stringValue: 123,
    } as unknown as ModularOnboardingFieldWithStringValue;

    expect(isStringValue(field)).toBe(false);
  });
});

describe('isDeviceCharges', () => {
  test('should return true when field has an orderSummary property with advanceRentalCharge', () => {
    const field: ModularOnboardingFieldForDeviceCharges = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      orderSummary: {
        advanceRentalCharge: 100,
        deviceCharge: 200,
        gst: 18,
        orderId: '123',
        paperRollCharge: 50,
        rentalCharge: [],
        shippingCharge: 10,
        totalOrderCharge: 378,
        totalRentalCharge: 100,
      },
    };

    expect(isDeviceCharges(field)).toBe(true);
  });

  test('should return false when field is null', () => {
    expect(isDeviceCharges(null)).toBe(false);
  });

  test('should return false when orderSummary does not have advanceRentalCharge', () => {
    const field = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      orderSummary: {
        deviceCharge: 200,
        gst: 18,
        orderId: '123',
        paperRollCharge: 50,
        rentalCharge: [],
        shippingCharge: 10,
        totalOrderCharge: 278,
        totalRentalCharge: 100,
      } as DeviceCharges,
    } as ModularOnboardingFieldForDeviceCharges;

    expect(isDeviceCharges(field)).toBe(false);
  });

  test('should return false when orderSummary is not an object', () => {
    const field = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      orderSummary: null,
    } as unknown as ModularOnboardingFieldForDeviceCharges;

    expect(isDeviceCharges(field)).toBe(false);
  });
});

describe('isOrderSummaryItem', () => {
  test('should return true when field has an addedDevices property with deviceName', () => {
    const field: ModularOnboardingFieldForOrderSummaryItem = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      addedDevices: [
        {
          deviceName: 'Device 1',
          itemId: '1',
          quantity: 1,
          rentalCharge: 100,
          setupCharge: 50,
        },
      ],
    };

    expect(isOrderSummaryItem(field)).toBe(true);
  });

  test('should return false when field is null', () => {
    expect(isOrderSummaryItem(null)).toBe(false);
  });

  test('should return false when addedDevices is not an array', () => {
    const field = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      addedDevices: null,
    } as unknown as ModularOnboardingFieldForOrderSummaryItem;

    expect(isOrderSummaryItem(field)).toBe(false);
  });

  test('should return false when addedDevices array does not contain objects with deviceName', () => {
    const field = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      addedDevices: [{}],
    } as unknown as ModularOnboardingFieldForOrderSummaryItem;

    expect(isOrderSummaryItem(field)).toBe(false);
  });

  test('should return false when addedDevices array is empty', () => {
    const field = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      addedDevices: [],
    } as ModularOnboardingFieldForOrderSummaryItem;

    expect(isOrderSummaryItem(field)).toBe(undefined);
  });
});

describe('isArrayOfDocumentsUpload', () => {
  test('should return true when field has an arrayOfDocumentsUploadValue property with fileStoreId', () => {
    const field: ModularOnboardingFieldForArrayOfDocumentsUpload = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      arrayOfDocumentsUploadValue: [
        {
          name: 'Document 1',
          size: 1234,
          fileStoreId: 'file-store-1',
        },
      ],
    };

    expect(isArrayOfDocumentsUpload(field)).toBe(true);
  });

  test('should return false when field is null', () => {
    expect(isArrayOfDocumentsUpload(null)).toBe(false);
  });

  test('should return false when arrayOfDocumentsUploadValue is not an array', () => {
    const field = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      arrayOfDocumentsUploadValue: null,
    } as unknown as ModularOnboardingFieldForArrayOfDocumentsUpload;

    expect(isArrayOfDocumentsUpload(field)).toBe(false);
  });

  test('should return false when arrayOfDocumentsUploadValue array does not contain objects with fileStoreId', () => {
    const field = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      arrayOfDocumentsUploadValue: [{}],
    } as unknown as ModularOnboardingFieldForArrayOfDocumentsUpload;

    expect(isArrayOfDocumentsUpload(field)).toBe(false);
  });

  test('should return false when arrayOfDocumentsUploadValue array is empty', () => {
    const field = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      arrayOfDocumentsUploadValue: [],
    } as ModularOnboardingFieldForArrayOfDocumentsUpload;

    expect(isArrayOfDocumentsUpload(field)).toBe(undefined);
  });
});

describe('isDocumentUpload', () => {
  test('should return true if field is a ModularOnboardingFieldForDocumentUpload with a fileStoreId', () => {
    const field: ModularOnboardingFieldForDocumentUpload = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      documentUploadValue: {
        fileStoreId: '12345',
      },
    };
    expect(isDocumentUpload(field)).toBe(true);
  });

  test('should return false if field is a ModularOnboardingFieldForDocumentUpload without a fileStoreId', () => {
    const field: ModularOnboardingFieldForDocumentUpload = {
      name: 'testField',
      isDisabled: false,
      isRequired: true,
      documentUploadValue: {},
    };
    expect(isDocumentUpload(field)).toBe(false);
  });

  test('should return false if field is null', () => {
    const field = null;
    expect(isDocumentUpload(field)).toBe(false);
  });
});

describe('isBrandItem', () => {
  it('should return false when field is null', () => {
    const result = isBrandItem(null);
    expect(result).toBe(false);
  });

  it('should return false when field does not have addedBrands array', () => {
    const mockField = { someOtherProperty: 'test' }; // Not a valid ModularOnboardingFieldForBrands
    const result = isBrandItem(mockField as any);
    expect(result).toBe(false);
  });

  it('should return false when addedBrands array is empty', () => {
    const mockField = { addedBrands: [] }; // Valid field but no brands inside
    const result = isBrandItem(mockField as any);
    expect(result).toBe(undefined);
  });

  it('should return false when first brand does not have verificationDetailsId', () => {
    const mockField = {
      addedBrands: [{ name: 'Brand1' }], // No verificationDetailsId in the brand
    };
    const result = isBrandItem(mockField as any);
    expect(result).toBe(false);
  });

  it('should return true for a valid ModularOnboardingFieldForBrands with verificationDetailsId', () => {
    const mockField = {
      addedBrands: [{ verificationDetailsId: '12345', name: 'Brand1' }],
    };
    const result = isBrandItem(mockField as any);
    expect(result).toBe(true);
  });
});
