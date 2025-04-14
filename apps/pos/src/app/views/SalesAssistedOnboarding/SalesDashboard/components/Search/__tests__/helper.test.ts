import { getRecentSearches, storeRecentSearches, searchMerchants } from '../helper';
import {
  SalesOnboardedMerchant,
  SalesMerchantActivationStatusEnum,
} from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE } from 'apps/pos/src/app/views/SalesAssistedOnboarding/SalesDashboard/__tests__/mocks/fixtures';

const KEY = 'pos_sales_recent_searches';

enum FilterType {
  ALL = 'all',
  MERCHANT_NAME = 'merchantName',
  BUSINESS_NAME = 'businessName',
}

const merchant = {
  ...SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE.salesOnboardedMerchants.merchants[0],
  status: SalesMerchantActivationStatusEnum.PENDING,
  primaryText: 'CHIZRINZ INFOWAY PRIVATE LIMITED',
};

describe('Recent Searches Utilities', () => {
  beforeEach(() => {
    localStorage.clear();
    jest.spyOn(Storage.prototype, 'setItem');
    jest.spyOn(Storage.prototype, 'getItem');
  });

  test('getRecentSearches should return an empty array when localStorage is empty', () => {
    expect(getRecentSearches()).toEqual([]);
  });

  test('getRecentSearches should return parsed recent searches from localStorage', () => {
    const mockSearches = [merchant];
    localStorage.setItem(KEY, JSON.stringify(mockSearches));
    expect(getRecentSearches()).toEqual(mockSearches);
  });

  test('storeRecentSearches should add a new unique search item to localStorage', () => {
    const newSearch = merchant;
    storeRecentSearches(newSearch);
    expect(JSON.parse(localStorage.getItem(KEY)!)).toEqual([newSearch]);
  });

  test('storeRecentSearches should not add duplicate searches', () => {
    const existingSearch = merchant;
    localStorage.setItem(KEY, JSON.stringify([existingSearch]));
    storeRecentSearches(existingSearch);
    expect(JSON.parse(localStorage.getItem(KEY)!)).toEqual([existingSearch]);
  });

  test('storeRecentSearches should maintain max history of 5 searches', () => {
    const searches = Array.from({ length: 5 }, (_, i) => ({
      ...merchant,
      merchantId: `${i}`,
      primaryText: `Test ${i}`,
    }));
    localStorage.setItem(KEY, JSON.stringify(searches));

    const newSearch = { ...merchant, merchantId: '6', primaryText: 'New Entry' };
    storeRecentSearches(newSearch);

    const stored = JSON.parse(localStorage.getItem(KEY)!);
    expect(stored.length).toBe(5);
    expect(stored[0]).toEqual(newSearch);
  });
});

describe('searchMerchants function', () => {
  const merchants: SalesOnboardedMerchant[] = [
    {
      ...SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE.salesOnboardedMerchants.merchants[0],
      status: SalesMerchantActivationStatusEnum.PENDING,
    },
    {
      ...SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE.salesOnboardedMerchants.merchants[1],
      status: SalesMerchantActivationStatusEnum.UNDER_REVIEW,
    },
  ];

  test('searchMerchants should filter by merchantName', () => {
    const result = searchMerchants('CHIZRINZ', FilterType.MERCHANT_NAME, merchants);
    expect(result).toHaveLength(1);
    expect(result[0].merchantId).toBe('OLvMDMRFFdl9TU');
  });

  test('searchMerchants should filter by businessName', () => {
    const result = searchMerchants('Raju', FilterType.BUSINESS_NAME, merchants);
    expect(result).toHaveLength(1);
    expect(result[0].merchantId).toBe('OLv6zpwrhtTk7E');
  });

  test('searchMerchants should return all merchants when filter is ALL', () => {
    const result = searchMerchants('', FilterType.ALL, merchants);
    expect(result).toEqual(merchants);
  });
});
