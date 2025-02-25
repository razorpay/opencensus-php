import {
  getComponentFromStep,
  getFieldFromComponent,
  getProgressFromModularStep,
  getStepsFromModularConfig,
  isPosEnabledForMerchant,
  processFormDataForModularSubmit,
  isAddressPresent,
} from '../modularConfig';
import { MerchantModularOnboardingDetailsSuccessResponse } from '../../types/modular';
import { SUCCESS_MODULAR_RESPONSE } from 'apps/pos/src/services/mocks/fixtures/modularConfig';

describe('modularconfig utils', () => {
  const modularResponse = SUCCESS_MODULAR_RESPONSE.merchantModularOnboardingDetailsAsSales;
  const modularConfig =
    modularResponse as unknown as MerchantModularOnboardingDetailsSuccessResponse;

  describe('getStepsFromModularConfig', () => {
    test('should return step from modular config ', () => {
      const step = getStepsFromModularConfig({
        modularConfig,
      });

      expect(step[0].name).toBe('device_selection_step');
      expect(step[1].name).toBe('pricing_step');
    });
  });

  describe('getComponentFromStep', () => {
    test('should return component from modular config ', () => {
      const component = getComponentFromStep({
        modularConfig,
        component: 'device_catalogue_component',
        step: 'device_selection_step',
      });

      expect(component?.name).toBe('device_catalogue_component');
    });

    test('should return null if component not found', () => {
      const component = getComponentFromStep({
        modularConfig,
        component: 'some_random_component',
        step: 'device_selection_step',
      });

      expect(component).toBe(null);
    });
  });

  describe('getFieldFromComponent', () => {
    test('should return field from component', () => {
      const field = getFieldFromComponent({
        modularConfig,
        component: 'device_catalogue_component',
        fieldName: 'device_order_items_summary_field',
        step: 'device_selection_step',
      });
      expect(field).toStrictEqual(
        expect.objectContaining({
          name: 'device_order_items_summary_field',
        }),
      );
    });

    test('should return null if field not found', () => {
      const field = getFieldFromComponent({
        modularConfig,
        component: 'device_catalogue_component',
        fieldName: 'some_random_field',
        step: 'device_selection_step',
      });
      expect(field).toBe(null);
    });
  });

  describe('processFormDataForModularSubmit', () => {
    test('should remove entries with undefined values', () => {
      const input = {
        name: 'Test Merchant',
        age: undefined,
        email: 'test.merchant@example.com',
      };
      const expectedOutput = {
        name: 'Test Merchant',
        email: 'test.merchant@example.com',
      };
      expect(processFormDataForModularSubmit(input)).toEqual(expectedOutput);
    });

    test('should convert string numbers to actual numbers', () => {
      const input = {
        age: '25',
        height: '175.5',
        name: 'Test Merchant',
      };
      const expectedOutput = {
        age: 25,
        height: 175.5,
        name: 'Test Merchant',
      };
      expect(processFormDataForModularSubmit(input)).toEqual(expectedOutput);
    });
  });

  describe('getProgressFromModularStep', () => {
    test('should return progress from modular step', () => {
      const progress = getProgressFromModularStep({
        modularConfig,
        step: 'device_selection_step',
      });
      expect(progress).toBe('pending');
    });

    test('should return completed as progress from modular step', () => {
      const progress = getProgressFromModularStep({
        modularConfig,
        step: 'pricing_step',
      });
      expect(progress).toBe('completed');
    });
  });
});

describe('isPosEnabledForMerchant', () => {
  it('should return true when the merchant is registered, WHITELISTED, and is a PGOS merchant', () => {
    const states = {
      merchantDetails: {
        business: {
          type: { value: 'REGISTERED' },
        },
        activation: {
          posActivationFlow: 'WHITELIST',
          isPgosMerchant: true,
        },
      },
    };

    expect(isPosEnabledForMerchant({ states })).toBe(true);
  });

  it('should return false when the merchant is unregistered', () => {
    const states = {
      merchantDetails: {
        business: {
          type: { value: 'UNREGISTERED' },
        },
        activation: {
          posActivationFlow: 'WHITELIST',
          isPgosMerchant: true,
        },
      },
    };

    expect(isPosEnabledForMerchant({ states })).toBe(false);
  });

  it('should return true if the posActivationFlow is allowed (WHITELIST or GREYLIST)', () => {
    const states = {
      merchantDetails: {
        business: {
          type: { value: 'REGISTERED' },
        },
        activation: {
          posActivationFlow: 'GREYLIST',
          isPgosMerchant: true,
        },
      },
    };

    expect(isPosEnabledForMerchant({ states })).toBe(true);
  });

  it('should return false if the posActivationFlow is not allowed', () => {
    const states = {
      merchantDetails: {
        business: {
          type: { value: 'REGISTERED' },
        },
        activation: {
          posActivationFlow: 'BLACKLIST',
          isPgosMerchant: true,
        },
      },
    };

    expect(isPosEnabledForMerchant({ states })).toBe(false);
  });

  it('should return false if isPgosMerchant is false', () => {
    const states = {
      merchantDetails: {
        business: {
          type: { value: 'REGISTERED' },
        },
        activation: {
          posActivationFlow: 'WHITELIST',
          isPgosMerchant: false,
        },
      },
    };

    expect(isPosEnabledForMerchant({ states })).toBe(false);
  });

  it('should return true when posActivationFlow is undefined but merchant is registered and is PGOS merchant', () => {
    const states = {
      merchantDetails: {
        business: {
          type: { value: 'REGISTERED' },
        },
        activation: {
          posActivationFlow: undefined,
          isPgosMerchant: true,
        },
      },
    };

    expect(isPosEnabledForMerchant({ states })).toBe(true);
  });

  it('should return false when posActivationFlow is null and merchant is unregistered', () => {
    const states = {
      merchantDetails: {
        business: {
          type: { value: 'UNREGISTERED' },
        },
        activation: {
          posActivationFlow: null,
          isPgosMerchant: true,
        },
      },
    };

    expect(isPosEnabledForMerchant({ states })).toBe(false);
  });

  it('should return true if posActivationFlow is null but the merchant is registered and is PGOS merchant', () => {
    const states = {
      merchantDetails: {
        business: {
          type: { value: 'REGISTERED' },
        },
        activation: {
          posActivationFlow: null,
          isPgosMerchant: true,
        },
      },
    };

    expect(isPosEnabledForMerchant({ states })).toBe(true);
  });
});

describe('isAddressPresent', () => {
  it('should return true if all address fields are present', () => {
    const address = {
      line1: { value: '123, ABC Street' },
      city: { value: 'XYZ' },
      state: { value: 'PQR' },
      zipCode: { value: '123456' },
    } as any;

    expect(isAddressPresent(address)).toBe(true);
  });

  it('should return false if any address field is missing', () => {
    const address = {
      line1: { value: '123, ABC Street' },
      city: { value: 'XYZ' },
      state: { value: 'PQR' },
      zipCode: { value: null },
    } as any;

    expect(isAddressPresent(address)).toBe(false);
  });

  it('should return false if all address fields are missing', () => {
    const address = {
      line1: { value: null },
      city: { value: null },
      state: { value: null },
      zipCode: { value: null },
    } as any;

    expect(isAddressPresent(address)).toBe(false);
  });

  it('should return false if all address fields are missing empty strings', () => {
    const address = {
      line1: { value: '' },
      city: { value: '' },
      state: { value: '' },
      zipCode: { value: '' },
    } as any;

    expect(isAddressPresent(address)).toBe(false);
  });
});
