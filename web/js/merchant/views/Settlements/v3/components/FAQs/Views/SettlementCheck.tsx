import { Link, Text } from '@razorpay/blade/components';
import { FaqInterface } from 'merchant/views/Settlements/v3/typings';
import React from 'react';
import { StyledFaqContent, TextLink } from './styled';

const SettlementCheck = ({ isMobile }: FaqInterface): JSX.Element => {
  return (
    <StyledFaqContent>
      {!isMobile && (
        <Text weight="semibold" marginBottom="spacing.2">
          How to check settlement status of my payment ID?
        </Text>
      )}
      <Text color="surface.text.gray.subtle">
        You can view the details of all previous payments using the link below
      </Text>
      <TextLink>
        <Text marginRight="spacing.2" color="surface.text.gray.subtle">
          To know more about transactions, check our
        </Text>
        <Link
          href="https://razorpay.com/docs/payments/payments/dashboard/"
          target="_blank"
          rel="noreferrer noopener"
        >
          Payments guide
        </Link>
      </TextLink>
    </StyledFaqContent>
  );
};

export default SettlementCheck;
