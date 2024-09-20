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
  searchBySectionOptions,
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
  const { country_code } = qs.parse(location.search);
  let defaultCountryCodeValue = getDialCodeByCountryCode(user.merchant.country_code);
  if (country_code) {
    defaultCountryCodeValue = `+${(country_code as string).trim()}`;
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
  };
};

export const getOptions = (isMobile: boolean): AllOptions => {
  if (isMobile) {
    return {
      paymentDurationOptions: paymentDurationSectionOptions,
      paymentMethodOptions: paymentMethodSectionOptions,
      statusOptions: statusSectionOptions,
      searchByOptions: searchBySectionOptions,
      countryCodeOptions: countryCodeSectionOptions,
      paymentChannelOptions,
    };
  }
  return {
    paymentDurationOptions,
    paymentMethodOptions: paymentMethodSectionOptions,
    statusOptions,
    searchByOptions,
    countryCodeOptions,
    paymentChannelOptions,
  };
};
