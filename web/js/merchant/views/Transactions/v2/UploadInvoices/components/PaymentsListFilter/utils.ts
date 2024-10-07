import {
  getDefaultDateAndOption,
  getDefaultSearchByValueAndOption,
} from 'merchant/views/Transactions/v2/common/utils';

import {
  searchByOptions,
  paymentDurationSectionOptions,
  paymentDurationOptionsMap,
  paymentDurationOptions,
  searchByOptionsMap,
} from './constants';
import { AllOptions, DefaultValuesAndOptions } from './types';

export const getDefaultValuesAndOptions = (): DefaultValuesAndOptions => {
  const { defaultDate, defaultDuration: defaultPaymentDuration } = getDefaultDateAndOption({
    customDurationOptionsMap: paymentDurationOptionsMap,
    sectionOptions: paymentDurationSectionOptions,
  });
  const { defaultSearchByOption, defaultSearchByValue } = getDefaultSearchByValueAndOption({
    searchByOptionsMap,
  });
  return {
    defaultPaymentDuration,
    defaultDate,
    defaultSearchByOption,
    defaultSearchByValue,
  };
};

export const getOptions = (): AllOptions => {
  return {
    paymentDurationOptions,
    searchByOptions,
  };
};
