import { convertToMinorUnit } from '@razorpay/i18nify-js';
import { convertToMajorUnit } from '@razorpay/i18nify-js/currency';

import { CurrencyCodeEnum } from 'common/typings/graph-types';
import store from 'merchant/store';

export const getConverterForUserCurrency = (converter) => (amount) => {
  const user = store.getState().session.user;
  const currency = user.merchant?.currency as CurrencyCodeEnum;

  return converter(amount, { currency });
};
export const convertToMajorUnitInUserCurrency = getConverterForUserCurrency(convertToMajorUnit);

export const convertToMinorUnitInUserCurrency = getConverterForUserCurrency(convertToMinorUnit);
