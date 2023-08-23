import { AppliedFilters, AppliedFiltersParams } from 'merchant/views/Wallet/types';
import { TIME_RANGE_FILTER } from 'merchant/views/Wallet/constants';

export const getFormattedCurrency = (amount = 0, currency = 'INR'): string => {
  amount = amount / 100;
  return amount.toLocaleString('en-IN', {
    maximumFractionDigits: 2,
    style: 'currency',
    currency,
  });
};

export const removeFilter = (filter: string): boolean => {
  return !TIME_RANGE_FILTER.includes(filter);
};

export const getAppliedFilters = ({
  filters,
  skip,
  count,
}: AppliedFiltersParams): AppliedFilters => {
  const filterDataToPost: any = [];
  const keys = Object.keys(filters);
  const modifiedFilters = keys?.filter(removeFilter);
  modifiedFilters?.forEach((key) => {
    if (filters?.[key]) {
      filterDataToPost.push({ key, op: 'eq', value: filters?.[key] });
    }
  });

  if (filters.from) {
    return {
      filters: filterDataToPost,
      time_range: {
        from: filters?.from,
        to: filters?.to,
      },
      pagination: {
        limit: count,
        skip,
      },
    };
  }

  return {
    filters: filterDataToPost,
    pagination: {
      limit: count,
      skip,
    },
  };
};
