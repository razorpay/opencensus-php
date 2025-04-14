import { SalesOnboardedMerchant } from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { MINIMUM_RECENT_SEARCHES_LIMIT } from './constants';

type RecentSearchSalesOnboardedMerchant = SalesOnboardedMerchant & {
  primaryText: string;
};

enum FilterType {
  ALL = 'all',
  MERCHANT_NAME = 'merchantName',
  BUSINESS_NAME = 'businessName',
}

const KEY = 'pos_sales_recent_searches';

export const getRecentSearches = (): RecentSearchSalesOnboardedMerchant[] => {
  const recentSearches = localStorage.getItem(KEY);
  return recentSearches ? JSON.parse(recentSearches) : [];
};

export const storeRecentSearches = (search: RecentSearchSalesOnboardedMerchant) => {
  const recentSearches: Array<RecentSearchSalesOnboardedMerchant> = getRecentSearches();
  const isUniqueSearch = recentSearches.every((item) => item?.merchantId !== search?.merchantId);
  if (!isUniqueSearch) return;

  if (recentSearches.length >= MINIMUM_RECENT_SEARCHES_LIMIT) {
    recentSearches.pop();
  }
  recentSearches.unshift(search);
  localStorage.setItem(KEY, JSON.stringify(recentSearches));
};

export const searchMerchants = (
  input: string,
  filter: FilterType,
  merchants: SalesOnboardedMerchant[],
) => {
  const lowercasedInput = input.toLowerCase();

  return merchants.filter((merchant: SalesOnboardedMerchant) => {
    const merchantName = merchant?.merchantName?.toLowerCase() || '';
    const businessName = merchant?.billingLabel?.toLowerCase() || '';

    if (filter === 'merchantName') {
      return merchantName.includes(lowercasedInput);
    } else if (filter === 'businessName') {
      return businessName.includes(lowercasedInput);
    }
    return merchants;
  });
};
