import {
  getDefaultSearchByValueAndOption,
  isTransactionsV2Enabled,
  isSettlementRetryTimelineEnabled,
  i18nifyConvertToMajorUnit,
  i18nifyConvertToMinorUnit,
} from 'apps/self-serve/src/App/Transactions/v2/common/utils';
import { SearchQueryParam } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import qs from 'query-string';

jest.mock('query-string', () => ({
  parse: jest.fn(),
}));

jest.mock('@dashboard/shared-utils/rzp-utils', () => ({
  decodeSensitiveFields: jest.fn((search) => search),
}));

describe('getDefaultSearchByValueAndOption', () => {
  const searchByOptionsMap = {
    notes: 'Notes',
  };

  beforeEach(() => {
    jest.clearAllMocks();
    window.location.search = '';
  });

  it('should return NOTES as default when notes are present', () => {
    const notes = 'Special instructions';
    qs.parse.mockReturnValue({ notes });

    const result = getDefaultSearchByValueAndOption({ searchByOptionsMap });

    expect(result).toEqual({
      defaultSearchByOption: { title: 'Notes', value: SearchQueryParam.NOTES },
      defaultSearchByValue: notes,
    });
  });
});

describe('experiments util', () => {
  it('should return false in case transaction_revamp is undefined or false', () => {
    const result = isTransactionsV2Enabled({ abExperiments: { Transactions_Revamp: undefined } });
    expect(result).toBeFalsy();
  });

  it('should return false in case transactions_retry_timeline is undefined or false', () => {
    const result = isSettlementRetryTimelineEnabled({
      abExperiments: { Transaction_Retry_Timeline: undefined },
    });
    expect(result).toBeFalsy();
  });

  it('TransactionsV2 enabled should return false in case splitz is not passed', () => {
    const result = isTransactionsV2Enabled();
    expect(result).toBeFalsy();
  });

  it(' settlement retry should return false in case splitz is not passed', () => {
    const result = isSettlementRetryTimelineEnabled();
    expect(result).toBeFalsy();
  });

  it('currency cobversions should pick default currency as INR if not passed', () => {
    const majorConversionResult = i18nifyConvertToMajorUnit(100);
    expect(majorConversionResult).toBe(1);

    const minorConversionResult = i18nifyConvertToMinorUnit(100);
    expect(minorConversionResult).toBe(10000);
  });

  it('should return false if merchant is VAS merchant', () => {
    const splitz = { abExperiments: { Transactions_Revamp: { variables: { result: 'on' } } } };
    const user = {
      isOrgRZP: false,
      isVasTestingMerchant: true,
      isFeatureEnabled: () => false,
      isCountryIndia: true,
    };
    const result = isTransactionsV2Enabled(splitz, user);
    expect(result).toBeFalsy();
  });
});
