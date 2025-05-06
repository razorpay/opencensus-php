import React from 'react';
import { Heading, Text } from '@razorpay/blade/components';
import {
  CurrencyCodeType,
  getCurrencySymbol as i18nifyGetCurrencySymbol,
} from '@razorpay/i18nify-js/currency';

import { ANALYTICS } from '@libs/shared-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getFormattedAmountByParts } from 'common/utils/rzp-utils';
import { AmountPropsInterface, AmountTypeInterface } from 'merchant/views/Settlements/v3/typings';

import { StyledAmountValue } from './styled';

const AmountType: AmountTypeInterface = {
  info: {
    rupeeConfig: {
      Component: Heading,
      props: {
        size: 'medium',
        weight: 'regular',
      },
    },
    paisaConfig: {
      Component: Text,
      props: {
        type: 'subdued',
        size: 'medium',
      },
    },
  },
  breakup: {
    rupeeConfig: {
      Component: Text,
      props: {
        weight: 'bold',
      },
    },
    paisaConfig: {
      Component: Text,
      props: {
        weight: 'bold',
        size: 'small',
      },
    },
  },
  subBreakup: {
    rupeeConfig: {
      Component: Text,
      props: {
        type: 'subtle',
      },
    },
    paisaConfig: {
      Component: Text,
      props: {
        type: 'muted',
        size: 'small',
      },
    },
  },
  net: {
    rupeeConfig: {
      Component: Text,
      props: {
        type: 'subtle',
        weight: 'bold',
      },
    },
    paisaConfig: {
      Component: Text,
      props: {
        type: 'muted',
        size: 'small',
      },
    },
  },
};

const Amount = ({
  amount,
  currency = 'INR',
  type = 'info',
  color,
  operator = '',
}: AmountPropsInterface): JSX.Element => {
  const formattedAmount = getFormattedAmountByParts(amount, currency);
  let currencySymbol;

  try {
    currencySymbol = i18nifyGetCurrencySymbol(currency as CurrencyCodeType);
  } catch (error) {
    currencySymbol = currency;
    analyticsTrack({
      objectName: ANALYTICS.OBJECT.I18N,
      actionName: ANALYTICS.ACTION.CURRENCY,
      screen: ANALYTICS.SCREEN.DASHBOARD,
      properties: {
        input: `currency: ${currency}`,
        error: `${error}`,
      },
    });
  }
  const { rupeeConfig, paisaConfig } = AmountType[type];

  return (
    <StyledAmountValue>
      <rupeeConfig.Component {...(color ? { ...rupeeConfig.props, color } : rupeeConfig.props)}>
        {`${operator} \u00A0 ${formattedAmount?.minusSign || ''} ${currencySymbol} ${
          formattedAmount?.integer
        }`}
      </rupeeConfig.Component>
      <paisaConfig.Component {...(color ? { ...paisaConfig.props, color } : paisaConfig.props)}>
        {`${formattedAmount?.decimal}${formattedAmount?.fraction}`}
      </paisaConfig.Component>
    </StyledAmountValue>
  );
};

export default Amount;
