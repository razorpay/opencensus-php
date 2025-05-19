import {
  paymentMethodSectionOptions,
  statusOptions,
  statusSectionOptions,
  searchByOptions,
  paymentDurationSectionOptions,
  paymentDurationOptionsMap,
  paymentDurationOptions,
  countryCodeSectionOptions,
  countryCodeOptions,
  searchBySectionOptions as _searchBySectionOptions,
  searchByOptionsMap,
  paymentChannelOptions,
} from './constants';
import qs from 'query-string';
import {
  getDefaultDateAndOption,
  getDefaultSearchByValueAndOption,
  getDefaultSingleSelectValueAndOption,
} from 'merchant/views/Transactions/v2/common/utils';
import {
  AllOptions,
  DefaultChannelAndOption,
  DefaultMethodAndOption,
  DefaultStatusAndOptions,
  DefaultValuesAndOptions,
  OptionsType,
} from './types';
import { getUser } from 'merchant/store';
import { getDialCodeByCountryCode } from '@razorpay/i18nify-js/phoneNumber';

const getDefaultStatusAndOption = (): DefaultStatusAndOptions => {
  const { status } = qs.parse(location.search);
  const { defaultValue, defaultOption } = getDefaultSingleSelectValueAndOption({
    searchValue: status as string,
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

export const getDefaultCountryCodeValue = (): string => {
  const user = getUser();
  const url = new URL(window.location.href);
  let defaultCountryCodeValue = getDialCodeByCountryCode(user.merchant.country_code);

  // Extract query parameters
  const params = new URLSearchParams(url.search);
  const encodedCountryCode = params.get('country_code');

  // Decode and set the country code
  if (encodedCountryCode) {
    const decodedCode = decodeURIComponent(encodedCountryCode).trim();
    return decodedCode.startsWith('+') ? decodedCode : `+${decodedCode}`;
  }

  return defaultCountryCodeValue;
};

export const getDefaultChannelAndOption = (): DefaultChannelAndOption => {
  const { source_channel } = qs.parse(location.search);
  const { defaultValue, defaultOption } = getDefaultSingleSelectValueAndOption({
    searchValue: source_channel as string,
    options: paymentChannelOptions,
  });
  return { defaultChannelValue: defaultValue, defaultChannelOption: defaultOption };
};

export const getDefaultDeviceIdValue = (): string => {
  const { device_id } = qs.parse(location.search);
  return device_id ? (device_id as string).trim() : '';
};

export const getDefaultStoreIdValue = (): string[] => {
  const { 'store_ids[]': storeId } = qs.parse(location.search);
  if (!storeId) return [];
  return Array.isArray(storeId) ? storeId : [storeId as string];
};

export const getDefaultValuesAndOptions = (): DefaultValuesAndOptions => {
  const { defaultDate, defaultDuration: defaultPaymentDuration } = getDefaultDateAndOption({
    customDurationOptionsMap: paymentDurationOptionsMap,
    sectionOptions: paymentDurationSectionOptions,
  });
  const { defaultMethodValue, defaultMethodOption } = getDefaultMethodAndOption();
  const { defaultStatusValue, defaultStatusOption } = getDefaultStatusAndOption();
  const { defaultSearchByOption, defaultSearchByValue } = getDefaultSearchByValueAndOption({
    searchByOptionsMap,
  });
  const defaultCountryCodeValue = getDefaultCountryCodeValue();
  const { defaultChannelValue, defaultChannelOption } = getDefaultChannelAndOption();
  const defaultDeviceIdValue = getDefaultDeviceIdValue();
  const defaultStoreIdValue = getDefaultStoreIdValue();

  return {
    defaultPaymentDuration,
    defaultDate,
    defaultMethodValue,
    defaultMethodOption,
    defaultStatusValue,
    defaultStatusOption,
    defaultSearchByOption,
    defaultSearchByValue,
    defaultCountryCodeValue,
    defaultChannelValue,
    defaultChannelOption,
    defaultDeviceIdValue,
    defaultStoreIdValue,
  };
};

const curlecPaymentMethods = ['all', 'card', 'wallet', 'fpx', 'paylater'];
const jnkPaymentMethods = ['upi'];
const batchIdOption = { title: 'Batch ID', value: 'batch_id' };

export const getOptions = (
  { isMobile, isOrgCurlec, isJnKOmniEnabled, showBatchIdFilter }: OptionsType = {
    isMobile: false,
    isOrgCurlec: false,
    isJnKOmniEnabled: false,
    showBatchIdFilter: false,
  },
): AllOptions => {
  let paymentMethodOptions = paymentMethodSectionOptions;
  const searchBySectionOptions = [
    ..._searchBySectionOptions,
    ...(showBatchIdFilter ? [batchIdOption] : []),
  ];
  const searchBySectionOption = searchByOptions;
  searchBySectionOption[0].section.options = searchBySectionOptions;

  if (isOrgCurlec) {
    paymentMethodOptions = paymentMethodSectionOptions.filter(({ value }) =>
      curlecPaymentMethods.includes(value),
    );
  }

  if (isJnKOmniEnabled) {
    paymentMethodOptions = paymentMethodSectionOptions.filter(({ value }) =>
      jnkPaymentMethods.includes(value),
    );
  }

  if (isMobile) {
    return {
      paymentDurationOptions: paymentDurationSectionOptions,
      paymentMethodOptions,
      statusOptions: statusSectionOptions,
      searchByOptions: searchBySectionOptions,
      countryCodeOptions: countryCodeSectionOptions,
      paymentChannelOptions,
    };
  }
  return {
    paymentDurationOptions,
    paymentMethodOptions,
    statusOptions,
    searchByOptions: searchBySectionOption,
    countryCodeOptions,
    paymentChannelOptions,
  };
};
