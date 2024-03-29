import { Text } from '@razorpay/blade/components';
import { FaqInterface } from 'merchant/views/Settlements/v3/typings';
import React from 'react';
import { StyledFaqContent } from './styled';

const PricingPlan = ({ isMobile }: FaqInterface): JSX.Element => {
  return (
    <StyledFaqContent>
      {!isMobile && (
        <Text weight="semibold" marginBottom="spacing.2">
          What is the pricing plan for my transactions?
        </Text>
      )}
      <Text color="surface.text.gray.subtle">
        We offer a simple and transparent pricing plan which has no hidden fees:
      </Text>
      <Text color="surface.text.gray.subtle">
        📌 On domestic transactions, we levy a 2% Transaction charge + 0.36% GST* = 2.36% per
        successful transaction.
      </Text>
      <Text color="surface.text.gray.subtle">
        📌 On International / AMEX / EMI transactions, we levy a 3% Transaction charge + 0.54 % GST*
        = 3.54%.
      </Text>
    </StyledFaqContent>
  );
};

export default PricingPlan;
