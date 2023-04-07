import { Alert, Link, Text } from '@razorpay/blade/components';
import { FaqInterface } from 'merchant/views/Settlements/v3/typings';
import React from 'react';
import { StyledFaqContent, TextLink } from './styled';

const ReduceSettlementCycle = ({ isMobile }: FaqInterface): JSX.Element => {
  return (
    <StyledFaqContent>
      {!isMobile && (
        <Text weight="bold" marginBottom="spacing.2">
          How to reduce my settlement cycle?
        </Text>
      )}
      <Text type="subtle">
        Razorpay Instant Settlements helps you reduce your settlement period from default settlement
        cycle to a few minutes (from the time of the transaction).
      </Text>
      <Text type="subtle">
        It is an on-demand feature. You need to create support ticket using the link below to get
        this feature activated on your Razorpay account.
      </Text>
      <TextLink>
        <Text marginRight="spacing.2" type="subtle">
          To know more about this, check our
        </Text>
        <Link
          href="https://razorpay.com/docs/payments/settlements/instant/"
          target="_blank"
          rel="noreferrer noopener"
        >
          Instant settlements guide
        </Link>
      </TextLink>
      <Alert
        contrast="low"
        description="Note: Default settlement cycle is T+2 working days* for domestic payments, and within T+7 working days* for international payments. *T being the date of payment collection. Working days do not include second and fourth Saturdays, Sundays and Bank Holidays"
        intent="information"
        marginTop="spacing.4"
        isDismissible={false}
      />
    </StyledFaqContent>
  );
};

export default ReduceSettlementCycle;
