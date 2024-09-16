import qs from 'query-string';

import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import {
  disputeDurationOptions,
  disputeDurationOptionsMap,
  disputeDurationSectionOptions,
  filterTagMap,
  filterTitleMap,
  filterValuesMap,
  searchByOptionsMap,
  statusOptions,
  statusSectionOptions,
} from 'merchant/views/Transactions/v2/Disputes/constants';
import {
  AllOptions,
  DefaultStatusAndOptions,
  DefaultValuesAndOptions,
  SelectedFilterType,
} from 'merchant/views/Transactions/v2/Disputes/types';
import {
  getDefaultDateAndOption,
  getDefaultSearchByValueAndOption,
  getDefaultSingleSelectValueAndOption,
} from 'merchant/views/Transactions/v2/common/utils';
import { showNotification } from 'merchant_common/reducers/notifications';

export const isDisputesRevampV2Enabled = (splitz: SpiltzContextState, user: User): boolean => {
  const { abExperiments } = splitz ?? { abExperiments: { Disputes_Revamp_V2: undefined } };

  if (!abExperiments?.Disputes_Revamp_V2) return false;

  if (user.isOrgCurlec) {
    return false;
  }

  return (
    isExperimentEnabled(abExperiments.Disputes_Revamp_V2) &&
    Boolean(user.isINCountry) &&
    user.isOrgRZP
  );
};

export const getDefaultStatusAndOption = (): DefaultStatusAndOptions => {
  const { status } = qs.parse(location.search);
  const { defaultValue, defaultOption } = getDefaultSingleSelectValueAndOption({
    searchValue: status as string,
    options: statusSectionOptions,
  });
  return { defaultStatusValue: defaultValue, defaultStatusOption: defaultOption };
};

export const getDefaultValuesAndOptions = (): DefaultValuesAndOptions => {
  const { defaultDate, defaultDuration: defaultDisputeDuration } = getDefaultDateAndOption({
    customDurationOptionsMap: disputeDurationOptionsMap,
    sectionOptions: disputeDurationSectionOptions,
  });
  const { defaultStatusValue, defaultStatusOption } = getDefaultStatusAndOption();
  const { defaultSearchByValue } = getDefaultSearchByValueAndOption({
    searchByOptionsMap,
  });

  return {
    defaultDisputeDuration,
    defaultDate,
    defaultStatusValue,
    defaultStatusOption,
    defaultSearchByValue,
  };
};

export const getOptions = (isMobile: boolean): AllOptions =>
  isMobile
    ? {
        disputeDurationOptions: disputeDurationSectionOptions,
        statusOptions: statusSectionOptions,
      }
    : {
        disputeDurationOptions,
        statusOptions,
      };

export const getKeyByValue = (object, value) => {
  return Object.keys(object).find((key) => object[key] === value) ?? '';
};

export const getFilterValues = (filter, value) => {
  const key = getKeyByValue(filterTitleMap, filter);

  const filterValues =
    value.split(',') ?? ''.length !== 1
      ? value.split(',').map((item: string) => getKeyByValue(filterValuesMap, item.trim()))
      : [getKeyByValue(filterValuesMap, value.trim())];

  return { key, filterValues };
};

//This util is taking the filter values from the url params and returning the type of object required to be stored in the filter state for setting default state of filters on refresh.
export const getDefaultFilterSelectedOptions = () => {
  const { international, phase } = qs.parse(location.search);
  const result = {};
  if (phase) {
    const { key, filterValues } = getFilterValues('phase', phase);
    if (key) result[key] = filterValues;
  }

  if (international) {
    const { key, filterValues } = getFilterValues('international', international);
    if (key) result[key] = filterValues;
  }

  return result;
};

export const getValidFilterKeys = (selectedFilters: SelectedFilterType) => {
  const filters = {};

  for (const key in selectedFilters) {
    if (selectedFilters.hasOwnProperty(key)) {
      if (
        (filterTitleMap[key] === 'international' && selectedFilters[key].length === 1) ||
        filterTitleMap[key] !== 'international'
      )
        filters[filterTitleMap[key]] = selectedFilters[key]
          .flat()
          .map((value) => filterValuesMap[value as string])
          .join(',');
    }
  }
  return filters;
};

export const getTags = (selectedFilters: SelectedFilterType) => {
  const tags: Record<string, string>[] = [];
  for (const key in selectedFilters) {
    if (selectedFilters.hasOwnProperty(key)) {
      for (const value of selectedFilters[key]) {
        tags.push({
          tagTitle: key,
          tagValue: `${filterTagMap[key]}: ${value}`,
          filterValue: value,
        });
      }
    }
  }
  return tags;
};

export const getErrorMessage = () => {
  showNotification({
    type: 'error',
    message: 'Failed to download the file',
  });
};

export const getDisputePhaseCount = ({
  openDisputesCount,
  underReviewDisputesCount,
  wonDisputesCount,
  lostDisputesCount,
  totalDisputesCount,
}: Record<string, number>) => {
  const openDisputesPercentage = Math.round((openDisputesCount / totalDisputesCount) * 100);
  const underReviewDisputesPercentage = Math.round(
    (underReviewDisputesCount / totalDisputesCount) * 100,
  );
  const wonDisputesPercentage = Math.round((wonDisputesCount / totalDisputesCount) * 100);
  const lostDisputesPercentage = Math.round((lostDisputesCount / totalDisputesCount) * 100);

  return {
    openDisputesPercentage,
    underReviewDisputesPercentage,
    wonDisputesPercentage,
    lostDisputesPercentage,
  };
};

export const getDisputesOverviewValues = ({
  openDisputesCount,
  underReviewDisputesCount,
  wonDisputesCount,
  lostDisputesCount,
}: Record<string, number>) => {
  return [
    {
      title: 'open(needs response)',
      status: 'open',
      value: openDisputesCount,
    },
    {
      title: 'under review',
      status: 'under_review',
      value: underReviewDisputesCount,
    },
    {
      title: 'won',
      status: 'won',
      value: wonDisputesCount,
    },
    {
      title: 'lost',
      status: 'lost',
      value: lostDisputesCount,
    },
  ];
};

export const getGraphValues = ({
  openDisputes,
  underReviewDisputes,
  wonDisputes,
  lostDisputes,
  data,
  currency,
}) => {
  const {
    openDisputesPercentage,
    underReviewDisputesPercentage,
    wonDisputesPercentage,
    lostDisputesPercentage,
  } = getDisputePhaseCount({
    openDisputesCount: openDisputes.count,
    underReviewDisputesCount: underReviewDisputes.count,
    wonDisputesCount: wonDisputes.count,
    lostDisputesCount: lostDisputes.count,
    totalDisputesCount: data.totalDisputesCount,
  });

  return [
    {
      count: openDisputes.count,
      content: `${getFormattedAmountNew(openDisputes.amount, true, currency)} from ${
        openDisputes.count
      } open disputes`,
      status: 'open',
      percent: openDisputesPercentage,
    },
    {
      count: underReviewDisputes.count,
      content: `${getFormattedAmountNew(underReviewDisputes.amount, true, currency)} from ${
        underReviewDisputes.count
      } under review disputes`,
      status: 'under_review',
      percent: underReviewDisputesPercentage,
    },
    {
      count: wonDisputes.count,
      content: `${getFormattedAmountNew(wonDisputes.amount, true, currency)} from ${
        wonDisputes.count
      } won disputes`,
      status: 'won',
      percent: wonDisputesPercentage,
    },
    {
      count: lostDisputes.count,
      content: `${getFormattedAmountNew(lostDisputes.amount, true, currency)} from ${
        lostDisputes.count
      } lost disputes`,
      status: 'lost',
      percent: lostDisputesPercentage,
    },
  ];
};
