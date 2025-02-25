import React from "react";
import { Amount } from '@razorpay/blade/components';
import { convertToMajorUnit } from '@razorpay/i18nify-js/currency';

import {
  i18CurrencyConversionFromCommonUnitToMinorUnit,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
  getFormattedAmount,
} from 'common/utils/rzp-utils';
import { UPI_AVL_LIMIT } from 'merchant/helpers/data';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

import { checkIfAmount } from './RegistrationLinks/components/RegistrationLinksForm/PaymentDetails/utils';
import {
  FREQUENCY,
  CARD_AFA_MAX_AMOUNT,
  CARD_MAX_AMOUNT_ALLOWED,
  BILLING_FREQUENCY,
  CARD_FREQUENCY,
  PAYMENT_METHODS,
  MAX_TOKEN_AMOUNT,
  MAX_TOKEN_AMOUNT_NACH,
  CARD_TOKEN_MAX_AMOUNT,
  CARD_PAYMENT_LABEL,
  UPI_ERROR_DESCRIPTION,
  DEFAULT_TOUCH_N_GO_MAX_LIMIT,
} from './constants';

export const getCardLabelAndLimits = (countryCode) => {
  const cardAfaMaxLimit = CARD_AFA_MAX_AMOUNT[countryCode];
  const cardTokenMaxAmount = CARD_MAX_AMOUNT_ALLOWED[countryCode];
  return { cardAfaMaxLimit, cardTokenMaxAmount };
};

export const getCardLabelText = (customCode) => {
  return CARD_PAYMENT_LABEL[customCode] || CARD_PAYMENT_LABEL[ORG_CUSTOM_CODE_MAP.RAZORPAY];
};

export const shouldHideDebitPattern = (frequency) => {
  return ![FREQUENCY.AS_PRESENTED, FREQUENCY.DAILY].includes(frequency);
};

const getCardErrorDescription = (maxAmount, currency) => (
  <>
    You can <strong>automatically</strong> charge the customer upto{' '}
    <Amount value={Number(maxAmount)} currency={currency} type="body" size="small" />
    for each recurring payment. Payments above{' '}
    <Amount value={Number(maxAmount)} currency={currency} type="body" size="small" />
    will ask for OTP verification from the customer.
  </>
);

export const maxAmountValidator = (amount, maxAllowedLimit, currency) => (value) => {
  const isAmountCheckFiled = checkIfAmount(value);

  if (isAmountCheckFiled) {
    return isAmountCheckFiled;
  }

  const maxBillingAmount = i18CurrencyConversionFromCommonUnitToMinorUnit(Number(value), currency);

  if (maxBillingAmount > maxAllowedLimit) {
    return (
      <>
        Max amount should not be greater than{' '}
        <Amount
          value={Number(i18CurrencyConversionFromMinorUnitToCommonUnit(maxAllowedLimit, currency))}
          currency={currency}
          color="feedback.text.negative.intense"
          type="body"
          size="small"
        />
      </>
    );
  } else if (
    maxBillingAmount < i18CurrencyConversionFromCommonUnitToMinorUnit(Number(amount), currency)
  ) {
    return (
      <>
        The maximum amount should be equal to or greater than{' '}
        <Amount
          value={Number(amount)}
          currency={currency}
          color="feedback.text.negative.intense"
          type="body"
          size="small"
        />
        , which is the minimum for this payment method.
      </>
    );
  }
  return null;
};

export const cardMaxAmountValidator = (maxAllowedAmount, currency) => (value) => {
  if (value > maxAllowedAmount) {
    return (
      <>
        Please enter an amount below
        <Amount
          testID={`${currency}${maxAllowedAmount}`}
          color="feedback.text.negative.intense"
          value={Number(maxAllowedAmount)}
          currency={currency}
          type="body"
          size="small"
        />
      </>
    );
  }
  return null;
};

export const getMaxAmountProps = (method, amount, user, mandateMaxAmount = 0) => {
  const { isCardMultipleFrequencyEnabled, merchant = {} } = user;
  const { country_code: countryCode } = merchant;
  const {
    merchant: { currency },
  } = user;

  let tokenMaxAmount = MAX_TOKEN_AMOUNT;

  if (method === PAYMENT_METHODS.WALLET) {
    tokenMaxAmount = DEFAULT_TOUCH_N_GO_MAX_LIMIT;
  }

  const maxAmountProps = {
    validator: maxAmountValidator(amount, tokenMaxAmount, currency),
    description: (
      <>
        Max Amount for Mandate (Up to{' '}
        <Amount
          currency={currency}
          value={convertToMajorUnit(tokenMaxAmount, { currency })}
          type="body"
          size="small"
        />
        )
      </>
    ),
  };

  switch (method) {
    case PAYMENT_METHODS.CARD: {
      const { cardAfaMaxLimit, cardTokenMaxAmount } = getCardLabelAndLimits(countryCode);
      maxAmountProps.validator = cardMaxAmountValidator(cardTokenMaxAmount, currency);
      let maxAmount = cardAfaMaxLimit;
      if (isCardMultipleFrequencyEnabled) {
        maxAmountProps.required = true;
      }
      if (mandateMaxAmount && mandateMaxAmount <= cardAfaMaxLimit) {
        maxAmount = mandateMaxAmount;
      }

      if (user.isOrgCurlec) {
        maxAmountProps.description = () => '';
      } else {
        maxAmountProps.placeholder = `Max ${CARD_TOKEN_MAX_AMOUNT}`;
        maxAmountProps.description = getCardErrorDescription(maxAmount, currency);
      }
      return maxAmountProps;
    }
    case PAYMENT_METHODS.UPI:
      maxAmountProps.validator = maxAmountValidator(amount, UPI_AVL_LIMIT, currency);
      maxAmountProps.placeholder = `Max ${getFormattedAmount(UPI_AVL_LIMIT)}`;
      maxAmountProps.description = UPI_ERROR_DESCRIPTION;
      if (user?.isDebitPatternEnabled) {
        maxAmountProps.required = true;
      }
      return maxAmountProps;
    case PAYMENT_METHODS.NACH:
      maxAmountProps.validator = maxAmountValidator(amount, MAX_TOKEN_AMOUNT_NACH, currency);
      maxAmountProps.description = (
        <>
          Max Amount for Nach (Up to{' '}
          <Amount value={MAX_TOKEN_AMOUNT_NACH} type="body" size="small" />)
        </>
      );
      return maxAmountProps;
    default:
      return maxAmountProps;
  }
};

export const getBillingFrequencies = (method) => {
  if (method === PAYMENT_METHODS.CARD) {
    return BILLING_FREQUENCY.filter(({ name }) => CARD_FREQUENCY.includes(name));
  }
  return BILLING_FREQUENCY;
};
