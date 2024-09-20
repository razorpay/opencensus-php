/* eslint-disable import/order */
import {
  mockOptions,
  mockToday,
  mockLast7Days,
  mockLast30Days,
  mockLast90Days,
  mockCurrentYearJanTillDate,
  mockThisFinancialYear,
  mockPrevFinancialYearLastQuarter,
  mockPrevFinancialYear,
} from 'merchant/views/Transactions/v2/common/__tests__/mocks/fixtures/utils';
import qs from 'query-string';
import {
  generateOptions,
  getValue,
  getDefaultSingleSelectValueAndOption,
  getFromTime,
  endOfDay,
  getDefaultDateAndOption,
  getDefaultSearchByValueAndOption,
  onSearch,
  onPaginate,
  isTransactionsV2Enabled,
  isSettlementRetryTimelineEnabled,
  i18nifyConvertToMajorUnit,
  i18nifyConvertToMinorUnit,
} from 'merchant/views/Transactions/v2/common/utils';
import {
  durationOptionsMap,
  CURRENT_YEAR_JAN_TILL_DATE,
  CUSTOM,
  LAST_30_DAYS,
  LAST_7_DAYS,
  LAST_90_DAYS,
  THIS_FINANCIAL_YEAR,
  TODAY,
} from 'merchant/views/Transactions/v2/common/constants';
import {
  paymentDurationSectionOptions,
  searchByOptionsMap,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/constants';
import { searchByOptionsMap as refundsSearchByOptionsMap } from 'merchant/views/Transactions/v2/Refunds/components/RefundsListFilter/constants';
import moment from 'moment';

// Mock the analyticsTrack function
jest.mock('common/utils/analytics', () => ({
  analyticsTrack: jest.fn(),
}));

describe('utils', () => {
  describe('generateOptions', () => {
    test('should generate options with correct structure', () => {
      const optionsMap = { option1: 'Option 1', option2: 'Option 2', option3: 'Option 3' };
      expect(generateOptions(optionsMap)).toEqual(mockOptions);
    });
  });

  describe('getValue', () => {
    test('should return a comma-separated string of values', () => {
      expect(getValue(mockOptions)).toEqual('option1,option2,option3');
    });

    test('should return an empty string if "all" value is in the selectedOptions', () => {
      const selectedOptions = [...mockOptions, { title: 'All', value: 'all' }];
      expect(getValue(selectedOptions)).toEqual('');
    });
  });

  describe('getDefaultSingleSelectValueAndOption', () => {
    test('should return the correct default value and option', () => {
      const searchValue = 'option2';
      const expected = {
        defaultValue: 'option2',
        defaultOption: { value: 'option2', title: 'Option 2' },
      };
      expect(getDefaultSingleSelectValueAndOption({ searchValue, options: mockOptions })).toEqual(
        expected,
      );
    });

    test('should return the first option as default if searchValue is not present', () => {
      const searchValue = '';
      const expected = {
        defaultValue: '',
        defaultOption: { value: 'option1', title: 'Option 1' },
      };
      expect(getDefaultSingleSelectValueAndOption({ searchValue, options: mockOptions })).toEqual(
        expected,
      );
    });
  });

  describe('getFromTime', () => {
    test('should return the correct time for different duration options', () => {
      expect(getFromTime(TODAY).unix()).toEqual(moment(mockToday).unix());
      expect(getFromTime(LAST_7_DAYS).unix()).toEqual(moment(mockLast7Days).unix());
      expect(getFromTime(LAST_30_DAYS).unix()).toEqual(moment(mockLast30Days).unix());
      expect(getFromTime(LAST_90_DAYS).unix()).toEqual(moment(mockLast90Days).unix());
      expect(getFromTime(CURRENT_YEAR_JAN_TILL_DATE).unix()).toEqual(
        moment(mockCurrentYearJanTillDate).unix(),
      );
      expect(getFromTime(THIS_FINANCIAL_YEAR).unix()).toEqual(moment(mockThisFinancialYear).unix());
      moment.mockReturnValueOnce(moment(mockPrevFinancialYearLastQuarter));
      expect(getFromTime(THIS_FINANCIAL_YEAR).unix()).toEqual(moment(mockPrevFinancialYear).unix());
    });
  });

  describe('getDefaultDateAndOption', () => {
    test('should return default date and duration when from and to are not present in query string', () => {
      const expected = {
        defaultDate: {
          from: getFromTime(LAST_7_DAYS).unix(),
          to: endOfDay.unix(),
        },
        defaultDuration: { title: 'Last 7 days', value: LAST_7_DAYS },
      };

      expect(
        getDefaultDateAndOption({
          customDurationOptionsMap: durationOptionsMap,
          sectionOptions: paymentDurationSectionOptions,
        }),
      ).toEqual(expected);
    });

    test('should return default date and duration when invalid from and to are present in query string', () => {
      const expected = {
        defaultDate: {
          from: getFromTime(LAST_7_DAYS).unix(),
          to: endOfDay.unix(),
        },
        defaultDuration: { title: 'Last 7 days', value: LAST_7_DAYS },
      };
      qs.parse.mockReturnValue({ from: 'abc', to: 'def' });
      expect(
        getDefaultDateAndOption({
          customDurationOptionsMap: durationOptionsMap,
          sectionOptions: paymentDurationSectionOptions,
        }),
      ).toEqual(expected);
    });

    test('should return default date and duration when from and to are present in query string', () => {
      const mockFromTimestamp = '1671234567';
      const mockToTimestamp = '1672345678';
      qs.parse.mockReturnValue({ from: mockFromTimestamp, to: mockToTimestamp });
      const expected = {
        defaultDate: { from: 1671234567, to: 1672345678 },
        defaultDuration: { title: 'Custom', value: CUSTOM },
      };
      expect(
        getDefaultDateAndOption({
          customDurationOptionsMap: durationOptionsMap,
          sectionOptions: paymentDurationSectionOptions,
        }),
      ).toEqual(expected);
    });

    test('should return default date and duration with the correct option when from and to match a section option', () => {
      const mockFromTimestamp = getFromTime(LAST_30_DAYS).unix();
      const mockToTimestamp = endOfDay.unix();
      qs.parse.mockReturnValue({ from: mockFromTimestamp, to: mockToTimestamp });
      const expected = {
        defaultDate: { from: getFromTime(LAST_30_DAYS).unix(), to: endOfDay.unix() },
        defaultDuration: { title: 'Last 30 days', value: LAST_30_DAYS },
      };
      expect(
        getDefaultDateAndOption({
          customDurationOptionsMap: durationOptionsMap,
          sectionOptions: paymentDurationSectionOptions,
        }),
      ).toEqual(expected);
    });
  });

  describe('getDefaultSearchByValueAndOption', () => {
    describe('With payments searchByOptionsMap', () => {
      test('should return search by option and value based on query params', () => {
        const mockSearch = {
          id: 'pay_123456',
        };
        qs.parse.mockReturnValue(mockSearch);
        const expected = {
          defaultSearchByOption: { title: 'Payment ID', value: 'id' },
          defaultSearchByValue: mockSearch.id,
        };
        expect(getDefaultSearchByValueAndOption({ searchByOptionsMap })).toEqual(expected);
      });

      test('should search by email option and value based on query params', () => {
        const mockSearch = {
          email: 'email@example.com',
        };
        qs.parse.mockReturnValue(mockSearch);
        const expected = {
          defaultSearchByOption: { title: 'Email', value: 'email' },
          defaultSearchByValue: mockSearch.email,
        };
        expect(getDefaultSearchByValueAndOption({ searchByOptionsMap })).toEqual(expected);
      });

      test('should search by contact option and value based on query params', () => {
        const mockSearch = {
          contact: '1234567890',
        };
        qs.parse.mockReturnValue(mockSearch);
        const expected = {
          defaultSearchByOption: { title: 'Mobile number', value: 'contact' },
          defaultSearchByValue: mockSearch.contact,
        };
        expect(getDefaultSearchByValueAndOption({ searchByOptionsMap })).toEqual(expected);
      });

      test('should search by country_code option and value based on query params', () => {
        const mockSearch = {
          country_code: '+91',
        };
        qs.parse.mockReturnValue(mockSearch);
        const expected = {
          defaultSearchByOption: { title: 'Mobile number', value: 'contact' },
          defaultSearchByValue: '',
        };
        expect(getDefaultSearchByValueAndOption({ searchByOptionsMap })).toEqual(expected);
      });

      test('should search by order_id option and value based on query params', () => {
        const mockSearch = {
          order_id: 'order_123456',
        };
        qs.parse.mockReturnValue(mockSearch);
        const expected = {
          defaultSearchByOption: { title: 'Order ID', value: 'order_id' },
          defaultSearchByValue: mockSearch.order_id,
        };
        expect(getDefaultSearchByValueAndOption({ searchByOptionsMap })).toEqual(expected);
      });

      test('should return default search by option and empty value when no query params are present', () => {
        qs.parse.mockReturnValue({});
        const expected = {
          defaultSearchByOption: { title: 'Payment ID', value: 'id' },
          defaultSearchByValue: '',
        };
        expect(getDefaultSearchByValueAndOption({ searchByOptionsMap })).toEqual(expected);
      });
    });

    describe('With refunds searchByOptionsMap', () => {
      test('should return default refunds search by option and value based on query params', () => {
        const mockSearch = {
          payment_id: 'pay_123456',
        };
        qs.parse.mockReturnValue(mockSearch);
        const expected = {
          defaultSearchByOption: { title: 'Payment ID', value: 'payment_id' },
          defaultSearchByValue: mockSearch.payment_id,
        };
        expect(
          getDefaultSearchByValueAndOption({ searchByOptionsMap: refundsSearchByOptionsMap }),
        ).toEqual(expected);
      });

      test('should return default search by option and empty value when no query params are present', () => {
        qs.parse.mockReturnValue({});
        const expected = {
          defaultSearchByOption: { title: 'Refund ID', value: 'id' },
          defaultSearchByValue: '',
        };
        expect(
          getDefaultSearchByValueAndOption({ searchByOptionsMap: refundsSearchByOptionsMap }),
        ).toEqual(expected);
      });
    });
  });

  describe('onSearch', () => {
    test('should update history with encoded args', () => {
      const historyMock = {
        location: {
          pathname: '/some-path',
          hash: '#some-hash',
          state: { prevPath: '/some-path' },
        },
        replace: jest.fn(),
      };
      const args = { key: 'value' };
      onSearch(historyMock)(args);
      expect(historyMock.replace).toHaveBeenCalledWith({
        ...historyMock.location,
        search: '?key=value',
      });
    });
  });

  describe('onPaginate', () => {
    test('should call paginate with combined params', () => {
      const paginateMock = jest.fn();
      const searchParamsMock = {
        existingKey: 'existingValue',
      };
      qs.parse.mockReturnValue(searchParamsMock);
      const params = { newKey: 'newValue' };
      onPaginate(paginateMock)(params);
      expect(paginateMock).toHaveBeenCalledWith({
        existingKey: 'existingValue',
        newKey: 'newValue',
      });
    });
  });

  describe('isTransactionsV2Enabled', () => {
    test('should return false for TransactionsV2Enabled if user isOrgCurlec', () => {
      const splitz = {
        abExperiments: { Transactions_Revamp: { variables: { result: 'on' } } },
      };
      const user = { isOrgCurlec: true, isOrgRZP: true };
      const result = isTransactionsV2Enabled(splitz, user);
      expect(result).toBe(false);
    });

    test('should return false for TransactionsV2Enabled if experiment is not "on"', () => {
      const splitz = {
        abExperiments: { Transactions_Revamp: { variables: { result: 'off' } } },
      };
      const user = { isOrgCurlec: false, isOrgRZP: true };
      const result = isTransactionsV2Enabled(splitz, user);
      expect(result).toBe(false);
    });

    test('should return false for TransactionsV2Enabled if user is not isOrgRZP', () => {
      const splitz = {
        abExperiments: { Transactions_Revamp: { variables: { result: 'on' } } },
      };
      const user = { isOrgCurlec: false, isOrgRZP: false, isVasTestingMerchant: false };
      const result = isTransactionsV2Enabled(splitz, user);
      expect(result).toBe(false);
    });

    test('should return true for valid TransactionsV2Enabled case', () => {
      const splitz = {
        abExperiments: { Transactions_Revamp: { variables: { result: 'on' } } },
      };
      const user = {
        isOrgCurlec: false,
        isOrgRZP: true,
        isINCountry: true,
        isFeatureEnabled: jest.fn(() => false),
      };
      const result = isTransactionsV2Enabled(splitz, user);
      expect(result).toBe(true);
    });

    test('should return true for SG user', () => {
      const splitz = {
        abExperiments: { Transactions_Revamp: { variables: { result: 'on' } } },
      };
      const user = {
        isOrgCurlec: false,
        isOrgRZP: true,
        isSGCountry: true,
        isFeatureEnabled: jest.fn(() => false),
      };
      const result = isTransactionsV2Enabled(splitz, user);
      expect(result).toBe(true);
    });

    test('should return false for Optimizer raas feature check', () => {
      const splitz = {
        abExperiments: { Transactions_Revamp: { variables: { result: 'on' } } },
      };
      const user = {
        isOrgCurlec: false,
        isOrgRZP: true,
        isINCountry: true,
        isFeatureEnabled: jest.fn(() => true),
      };
      const result = isTransactionsV2Enabled(splitz, user);
      expect(result).toBe(false);
    });
  });

  describe('isSettlementRetryTimelineEnabled', () => {
    test('should return false for SettlementRetryTimelineEnabled if user isOrgCurlec', () => {
      const splitz = {
        abExperiments: { Transaction_Retry_Timeline: { variables: { result: 'on' } } },
      };
      const user = { isOrgCurlec: true, isOrgRZP: true };
      const result = isSettlementRetryTimelineEnabled(splitz, user);
      expect(result).toBe(false);
    });

    test('should return false for SettlementRetryTimelineEnabled if experiment is not "on"', () => {
      const splitz = {
        abExperiments: { Transaction_Retry_Timeline: { variables: { result: 'off' } } },
      };
      const user = { isOrgCurlec: false, isOrgRZP: true };
      const result = isSettlementRetryTimelineEnabled(splitz, user);
      expect(result).toBe(false);
    });

    test('should return false for SettlementRetryTimelineEnabled if user is not isOrgRZP', () => {
      const splitz = {
        abExperiments: { Transaction_Retry_Timeline: { variables: { result: 'on' } } },
      };
      const user = { isOrgCurlec: false, isOrgRZP: false };
      const result = isSettlementRetryTimelineEnabled(splitz, user);
      expect(result).toBe(false);
    });

    test('should return true for valid SettlementRetryTimelineEnabled case', () => {
      const splitz = {
        abExperiments: { Transaction_Retry_Timeline: { variables: { result: 'on' } } },
      };
      const user = { isOrgCurlec: false, isOrgRZP: true };
      const result = isSettlementRetryTimelineEnabled(splitz, user);
      expect(result).toBe(true);
    });
  });

  describe('i18nifyConvertToMinorUnit', () => {
    const testCases = [
      { amount: 123.45, currency: 'INR', expected: 12345 },
      { amount: 123.45, currency: 'USD', expected: 12345 },
      { amount: 123.45, currency: 'SGD', expected: 12345 },
      { amount: 123, currency: 'IDR', expected: 12300 },
      { amount: 123.45, currency: 'MYR', expected: 12345 },
      { amount: 123.45, currency: 'XYZ', expected: 12345 },
    ];

    testCases.forEach(({ amount, currency, expected }) => {
      test(`converts amount to minor units for ${currency}`, () => {
        expect(i18nifyConvertToMinorUnit(amount, currency)).toBe(expected);
      });
    });

    const invalidInputs = [
      { amount: '123.45', currency: 'USD', expected: 12345 },
      { amount: 123.45, currency: 123, expected: 12345 },
    ];

    invalidInputs.forEach(({ amount, currency, expected }) => {
      test(`runs fallback for invalid input types: amount=${amount}, currency=${currency}`, () => {
        expect(i18nifyConvertToMinorUnit(amount, currency)).toBe(expected);
      });
    });

    const edgeCases = [
      { amount: 0, currencies: ['INR', 'USD', 'SGD', 'IDR', 'MYR'], expected: 0 },
      { amount: -123.45, currencies: ['INR', 'USD', 'SGD', 'MYR'], expected: -12345 },
      { amount: -123, currencies: ['IDR'], expected: -12300 },
      { amount: 123456789.12, currencies: ['INR', 'USD', 'SGD', 'MYR'], expected: 12345678912 },
      { amount: 123456789, currencies: ['IDR'], expected: 12345678900 },
    ];

    edgeCases.forEach(({ amount, currencies, expected }) => {
      currencies.forEach((currency) => {
        test(`handles edge case for ${currency}: amount=${amount}`, () => {
          expect(i18nifyConvertToMinorUnit(amount, currency)).toBe(expected);
        });
      });
    });
  });

  describe('i18nifyConvertToMajorUnit', () => {
    const testCases = [
      { amount: 100, currency: 'INR', expected: 1 },
      { amount: 500, currency: 'INR', expected: 5 },
      { amount: 100, currency: 'USD', expected: 1 },
      { amount: 250, currency: 'USD', expected: 2.5 },
      { amount: 100, currency: 'SGD', expected: 1 },
      { amount: 750, currency: 'SGD', expected: 7.5 },
      { amount: 100, currency: 'IDR', expected: 1 },
      { amount: 300, currency: 'IDR', expected: 3 },
      { amount: 100, currency: 'MYR', expected: 1 },
      { amount: 450, currency: 'MYR', expected: 4.5 },
      { amount: 450, currency: 'XYZ', expected: 4.5 },
    ];

    testCases.forEach(({ amount, currency, expected }) => {
      test(`converts minor units to major units for ${currency}`, () => {
        expect(i18nifyConvertToMajorUnit(amount, currency)).toBe(expected);
      });
    });

    const invalidInputs = [
      { amount: null, currency: 'USD', expected: 0 },
      { amount: undefined, currency: 'SGD', expected: NaN },
      { amount: 100, currency: 'XYZ', expected: 1 },
    ];

    invalidInputs.forEach(({ amount, currency, expected }) => {
      test(`throws error for invalid input: amount=${amount}, currency=${currency}`, () => {
        expect(i18nifyConvertToMajorUnit(amount, currency)).toBe(expected);
      });
    });

    const edgeCases = [
      { amount: 0, currencies: ['INR', 'USD', 'SGD', 'IDR', 'MYR'], expected: 0 },
      { amount: 100000000, currencies: ['INR', 'USD', 'SGD', 'MYR'], expected: 1000000 },
      { amount: 500000000, currencies: ['IDR'], expected: 5000000 },
      { amount: -100, currencies: ['INR', 'USD', 'SGD', 'MYR', 'IDR'], expected: -1 },
    ];

    edgeCases.forEach(({ amount, currencies, expected }) => {
      currencies.forEach((currency) => {
        test(`handles edge case for ${currency}: amount=${amount}`, () => {
          expect(i18nifyConvertToMajorUnit(amount, currency)).toBe(expected);
        });
      });
    });
  });
});
