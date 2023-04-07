import { Alert, Link, Text } from '@razorpay/blade/components';
import { FaqInterface } from 'merchant/views/Settlements/v3/typings';
import React from 'react';
import { StyledFaqContent, TextLink } from './styled';

const SettlementCycle = ({ isMobile }: FaqInterface): JSX.Element => {
  return (
    <StyledFaqContent>
      {!isMobile && (
        <Text weight="bold" marginBottom="spacing.2">
          What is the settlement cycle Razorpay offers?
        </Text>
      )}
      <Text type="subtle">
        Settlements are processed within <b>T+2 working days*</b> for domestic payments, and within
        <b>T+7 working days*</b> for international payments.
      </Text>
      <TextLink>
        <Text marginRight="spacing.2" type="subtle">
          To know more about settlements, check our
        </Text>
        <Link
          href="https://razorpay.com/docs/payments/settlements/"
          target="_blank"
          rel="noreferrer noopener"
        >
          Settlements guide
        </Link>
      </TextLink>
      <Alert
        contrast="low"
        description="Note: *T being the date of payment collection. Working days do not include second and fourth Saturdays, Sundays and Bank Holidays"
        intent="information"
        marginTop="spacing.4"
        isDismissible={false}
      />
    </StyledFaqContent>
  );
};

export default SettlementCycle;
