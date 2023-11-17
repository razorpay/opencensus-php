import {
  getTPVOptions,
  categorizeGateways,
  categorizePaymentMethods,
  addToCategory,
} from 'merchant/views/Navigator/components/AddProvider/util';
import { TPV_OPTIONS } from 'merchant/views/Navigator/constants';

describe('Navigator > AddProvider > util', () => {
  const TPVOptions = getTPVOptions([0, 1]);

  test('should return tpv options having array of object with keys label and value', () => {
    expect(TPVOptions).toEqual([
      { label: TPV_OPTIONS[0], value: 0 },
      { label: TPV_OPTIONS[1], value: 1 },
    ]);
  });

  test('should return tpv options array of same length as the passed parameter length', () => {
    expect(TPVOptions).toHaveLength(2);
  });

  test('should return empty string label if tpv option doesnt support that number array', () => {
    const TPVOptions = getTPVOptions([3]);

    expect(TPVOptions).toEqual([{ label: '', value: 3 }]);
  });

  describe('categorizeGateways', () => {
    // Test to cover the `if (gatewayKeys.length === 0) return {};` branch.
    test('should return empty object if `gatewayKeys` is empty', () => {
      const expectedResult = {};
      const result = categorizeGateways({});
      expect(result).toEqual(expectedResult);
    });

    // Test to cover the `for (const gatewayKey of gatewayKeys)` loop.
    test('should return categorized gateways without international', () => {
      const providersList = {
        payu: {
          'Payment Methods': { data_value: ['card', 'upi', 'netbanking'] },
        },
        checkout_dot_com_optimizer: {
          'Payment Methods': { data_value: ['card'] },
        },
        upi_mindgate: {
          'Payment Methods': { data_value: ['upi'] },
        },
      };

      // Expected result
      const expectedResult = {
        aggregators: { 'Card, Netbanking, and UPI': ['payu'] },
        bank_gateways: { 'UPI only': ['upi_mindgate'] },
      };

      const result = categorizeGateways(providersList);
      expect(result).toEqual(expectedResult);
    });

    // Test to cover the `if (categorizedMethods.length === 1)` branch inside the `categorizePaymentMethods()` function.
    test('should return `method only` if `categorizedMethods` has a length of 1', () => {
      const paymentMethods = ['card'];
      const result = categorizePaymentMethods(paymentMethods);
      const expectedResult = 'Card only';
      expect(result).toEqual(expectedResult);
    });

    // Test to cover the `else if (categorizedMethods.length === 2)` branch inside the `categorizePaymentMethods()` function.
    test('should return `method and method` if `categorizedMethods` has a length of 2', () => {
      const paymentMethods = ['card', 'upi'];
      const result = categorizePaymentMethods(paymentMethods);
      const expectedResult = 'Card and UPI';
      expect(result).toEqual(expectedResult);
    });

    // Test to cover the `else` branch inside the `categorizePaymentMethods()` function.
    test('should return `method, method, and method` if `categorizedMethods` has a length of 3 or more', () => {
      const paymentMethods = ['card', 'upi', 'netbanking'];
      const result = categorizePaymentMethods(paymentMethods);
      const expectedResult = 'Card, Netbanking, and UPI';
      expect(result).toEqual(expectedResult);
    });

    // Test to cover the `addToCategory` branch function.
    test('should add a gateway to the category object', () => {
      const categoryObj = {};
      const categorizedMethods = 'Card, Netbanking, and UPI';
      const gatewayKey = 'payu';

      addToCategory(categoryObj, categorizedMethods, gatewayKey);

      expect(categoryObj[categorizedMethods]).toContain(gatewayKey);
    });
  });
});
