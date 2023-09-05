import {
  paymentMethodOptions,
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
} from './constants';
import qs from 'query-string';
import {
  getDefaultDateAndOption,
  getDefaultSearchByValueAndOption,
  getDefaultSingleSelectValueAndOption,
} from 'merchant/views/Transactions/v2/common/utils';
import {
  AllOptions,
  DefaultMethodAndOption,
  DefaultStatusAndOptions,
  DefaultValuesAndOptions,
} from './types';

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
  const { country_code } = qs.parse(location.search);
  let defaultCountryCodeValue = '+91';
  if (country_code) {
    defaultCountryCodeValue = `+${(country_code as string).trim()}`;
  }
  return defaultCountryCodeValue;
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
    };
  }
  return {
    paymentDurationOptions,
    paymentMethodOptions,
    statusOptions,
    searchByOptions,
    countryCodeOptions,
  };
};
