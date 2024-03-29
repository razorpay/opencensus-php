import React from 'react';
import styled from 'styled-components';
import { Amount } from '@razorpay/blade/components';
import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import { CTATextProps } from './types';

const HeadingWrapper = styled.div(
  ({ theme }) => `
  display: inline-flex;
  color: ${theme.colors.surface.text.gray.normal};
  font-size: ${theme.typography.fonts.size[600]}px;
  font-weight: ${theme.typography.fonts.weight.bold};
`,
);

export const CTAText = ({ value, value_type, currency }: CTATextProps) => {
  // TODO: currency text size should be made larger. currency is not used currently
  const formattedValue =
    value_type === 'percentage'
      ? `${value}%`
      : value_type === 'currency'
      ? i18CurrencyConversionFromMinorUnitToCommonUnit(value, currency)
      : value;
  return (
    <HeadingWrapper>
      {value_type === 'currency' ? (
        <Amount
          value={formattedValue as number}
          currency={currency as any}
          type="heading"
          size="medium"
          weight="semibold"
        />
      ) : (
        formattedValue
      )}
    </HeadingWrapper>
  );
};
