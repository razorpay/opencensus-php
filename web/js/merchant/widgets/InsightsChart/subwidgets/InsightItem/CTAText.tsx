import React from 'react';
import { Amount, Box, Heading } from '@razorpay/blade/components';
import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import { CTATextProps } from './types';

export const CTAText = ({ value, value_type, currency }: CTATextProps) => {
  // TODO: currency text size should be made larger. currency is not used currently
  const formattedValue =
    value_type === 'percentage'
      ? `${value}%`
      : value_type === 'amount'
      ? i18CurrencyConversionFromMinorUnitToCommonUnit(value, currency)
      : value ?? 0;

  return (
    <Box display="inline-flex">
      {value_type === 'amount' ? (
        <Amount
          value={formattedValue as number}
          currency={currency as any}
          type="heading"
          size="medium"
          weight="semibold"
        />
      ) : (
        <Heading color="surface.text.gray.normal" weight="semibold" size="large">
          {formattedValue}
        </Heading>
      )}
    </Box>
  );
};
