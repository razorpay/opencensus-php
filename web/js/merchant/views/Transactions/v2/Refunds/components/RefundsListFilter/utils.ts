import qs from 'query-string';

import {
  getDefaultDateAndOption,
  getDefaultSearchByValueAndOption,
  getDefaultSingleSelectValueAndOption,
} from 'merchant/views/Transactions/v2/common/utils';

import {
  statusOptions,
  statusSectionOptions,
  searchByOptions,
  searchBySectionOptions,
  searchByOptionsMap,
  refundsDurationSectionOptions,
  refundsDurationOptionsMap,
  refundsDurationOptions,
} from './constants';
import {
  AllOptions,
  DefaultDateAndOption,
  DefaultStatusAndOption,
  DefaultValuesAndOptions,
} from './types';

const getDefaultStatusAndOption = (): DefaultStatusAndOption => {
  const { public_status } = qs.parse(location.search);
  const { defaultValue, defaultOption } = getDefaultSingleSelectValueAndOption({
    searchValue: public_status as string,
    options: statusSectionOptions,
  });
  return { defaultStatusValue: defaultValue, defaultStatusOption: defaultOption };
};

export const _getDefaultDateAndOption = (): DefaultDateAndOption => {
  const { from, to } = qs.parse(location.search);
  const { all } = refundsDurationOptionsMap;
  if ((!from || !to) && all) {
    return {
      defaultDate: {
        from: null,
        to: null,
      },
      defaultDuration: {
        title: all,
        value: 'all' as const,
      },
    };
  }
  const { defaultDate, defaultDuration } = getDefaultDateAndOption({
    customDurationOptionsMap: refundsDurationOptionsMap,
    sectionOptions: refundsDurationSectionOptions,
  });

  return { defaultDate, defaultDuration };
};

export const getDefaultValuesAndOptions = (): DefaultValuesAndOptions => {
  const { defaultDate, defaultDuration: defaultRefundsDuration } = _getDefaultDateAndOption();
  const { defaultStatusValue, defaultStatusOption } = getDefaultStatusAndOption();
  const { defaultSearchByOption, defaultSearchByValue } = getDefaultSearchByValueAndOption({
    searchByOptionsMap,
  });

  return {
    defaultRefundsDuration,
    defaultDate,
    defaultStatusOption,
    defaultStatusValue,
    defaultSearchByOption,
    defaultSearchByValue,
  };
};

export const getOptions = (isMobile: boolean): AllOptions => {
  if (isMobile) {
    return {
      refundsDurationOptions: refundsDurationSectionOptions,
      statusOptions: statusSectionOptions,
      searchByOptions: searchBySectionOptions,
    };
  }
  return {
    refundsDurationOptions,
    statusOptions,
    searchByOptions,
  };
};
