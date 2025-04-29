import { getLayoutByMerchantType } from '../homepage';
import {
  PG_PAGE_LAYOUT,
  NO_CODE_PAGE_LAYOUT,
  PG_PLUS_NO_CODE_PAGE_LAYOUT,
} from '@FTUX/constants/homepage';

// Mock the constants to avoid making the test dependent on their actual values
jest.mock('@FTUX/constants/homepage', () => ({
  PG_PAGE_LAYOUT: ['PG_LAYOUT_1', 'PG_LAYOUT_2'],
  NO_CODE_PAGE_LAYOUT: ['NO_CODE_LAYOUT_1', 'NO_CODE_LAYOUT_2'],
  PG_PLUS_NO_CODE_PAGE_LAYOUT: ['COMBINED_LAYOUT_1', 'COMBINED_LAYOUT_2'],
}));

describe('getLayoutByMerchantType', () => {
  test('returns PG layout when only isPgMerchant is true', () => {
    const result = getLayoutByMerchantType({
      isPgMerchant: true,
      isNoCodeMerchant: false,
      hasWebsite: false,
    });

    expect(result).toEqual(PG_PAGE_LAYOUT);
  });

  test('returns No Code layout when only isNoCodeMerchant is true', () => {
    const result = getLayoutByMerchantType({
      isPgMerchant: false,
      isNoCodeMerchant: true,
      hasWebsite: false,
    });

    expect(result).toEqual(NO_CODE_PAGE_LAYOUT);
  });

  test('returns combined layout when both PG and No Code are true', () => {
    const result = getLayoutByMerchantType({
      isPgMerchant: true,
      isNoCodeMerchant: true,
      hasWebsite: false,
    });

    expect(result).toEqual(PG_PLUS_NO_CODE_PAGE_LAYOUT);
  });

  test('returns combined layout when hasWebsite is true', () => {
    const result = getLayoutByMerchantType({
      isPgMerchant: false,
      isNoCodeMerchant: false,
      hasWebsite: true,
    });

    expect(result).toEqual(PG_PLUS_NO_CODE_PAGE_LAYOUT);
  });

  test('returns empty array when no condition is met', () => {
    const result = getLayoutByMerchantType({
      isPgMerchant: false,
      isNoCodeMerchant: false,
      hasWebsite: false,
    });

    expect(result).toEqual([]);
  });
});
