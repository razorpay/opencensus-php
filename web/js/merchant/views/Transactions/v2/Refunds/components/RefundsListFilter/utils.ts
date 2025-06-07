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
  paymentChannelOptions,
  paymentChannelSectionOptions,
} from './constants';
import {
  AllOptions,
  DefaultChannelAndOption,
  DefaultDateAndOption,
  DefaultStatusAndOption,
  DefaultValuesAndOptions,
} from './types';
import { getDefaultDeviceIdValue, getDefaultStoreIdValue } from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/utils';
import { paymentMethodSectionOptions } from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/constants';
import { DefaultMethodAndOption } from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/types';

const getDefaultStatusAndOption = (): DefaultStatusAndOption => {
  const { public_status } = qs.parse(location.search);
  const { defaultValue, defaultOption } = getDefaultSingleSelectValueAndOption({
    searchValue: public_status as string,
    options: statusSectionOptions,
  });
  return { defaultStatusValue: defaultValue, defaultStatusOption: defaultOption };
};

const getDefaultMethodAndOption = (): DefaultMethodAndOption => {
  const { method } = qs.parse(location.search);
  const { defaultValue, defaultOption } = getDefaultSingleSelectValueAndOption({
    searchValue: method as string,
    options: paymentMethodSectionOptions,
  });
  return { defaultMethodValue: defaultValue, defaultMethodOption: defaultOption };
};

export const _getDefaultDateAndOption = (): DefaultDateAndOption => {
  const { defaultDate, defaultDuration } = getDefaultDateAndOption({
    customDurationOptionsMap: refundsDurationOptionsMap,
    sectionOptions: refundsDurationSectionOptions,
  });

  return { defaultDate, defaultDuration };
};
export const getDefaultChannelAndOption = (): DefaultChannelAndOption => {
  const { source_channel } = qs.parse(location.search);
  const { defaultValue, defaultOption } = getDefaultSingleSelectValueAndOption({
    searchValue: source_channel as string,
    options: paymentChannelSectionOptions,
  });
  return { defaultChannelValue: defaultValue, defaultChannelOption: defaultOption };
};

export const getDefaultValuesAndOptions = (): DefaultValuesAndOptions => {
  const { defaultDate, defaultDuration: defaultRefundsDuration } = _getDefaultDateAndOption();
  const { defaultStatusValue, defaultStatusOption } = getDefaultStatusAndOption();
  const { defaultSearchByOption, defaultSearchByValue } = getDefaultSearchByValueAndOption({
    searchByOptionsMap,
  });
  const { defaultChannelValue, defaultChannelOption } = getDefaultChannelAndOption();

  const { defaultMethodValue, defaultMethodOption } = getDefaultMethodAndOption();
  const defaultDeviceIdValue = getDefaultDeviceIdValue();
  const defaultStoreIdValue = getDefaultStoreIdValue();

  return {
    defaultRefundsDuration,
    defaultDate,
    defaultStatusOption,
    defaultStatusValue,
    defaultSearchByOption,
    defaultSearchByValue,
    defaultChannelValue,
    defaultChannelOption,
    defaultMethodValue,
    defaultMethodOption,
    defaultDeviceIdValue,
    defaultStoreIdValue
  };
};

export const getOptions = (isMobile: boolean): AllOptions => {
  if (isMobile) {
    return {
      refundsDurationOptions: refundsDurationSectionOptions,
      statusOptions: statusSectionOptions,
      searchByOptions: searchBySectionOptions,
      paymentChannelOptions: paymentChannelSectionOptions,
    };
  }
  return {
    refundsDurationOptions,
    statusOptions,
    searchByOptions,
    paymentChannelOptions,
  };
};
