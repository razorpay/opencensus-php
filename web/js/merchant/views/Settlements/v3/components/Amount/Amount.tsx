import { Heading, Text } from '@razorpay/blade/components';
import { getCurrencySymbol } from 'common/ui/Amount';
import { getFormattedAmount } from 'common/utils/rzp-utils';
import { AmountPropsInterface, AmountTypeInterface } from 'merchant/views/Settlements/v3/typings';
import React from 'react';
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
  const [rupee, paisa] = getFormattedAmount(amount)?.split('.');
  const symbol = getCurrencySymbol(currency);
  const { rupeeConfig, paisaConfig } = AmountType[type];

  return (
    <StyledAmountValue>
      <rupeeConfig.Component {...(color ? { ...rupeeConfig.props, color } : rupeeConfig.props)}>
        {`${operator} \u00A0 ${symbol} ${rupee}`}
      </rupeeConfig.Component>
      <paisaConfig.Component {...(color ? { ...paisaConfig.props, color } : paisaConfig.props)}>
        {`.${paisa}`}
      </paisaConfig.Component>
    </StyledAmountValue>
  );
};

export default Amount;
