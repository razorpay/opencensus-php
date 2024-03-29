import { Link, Text } from '@razorpay/blade/components';
import { FaqInterface } from 'merchant/views/Settlements/v3/typings';
import React from 'react';
import { StyledFaqContent, TextLink } from './styled';

const StatusCheck = ({ isMobile }: FaqInterface): JSX.Element => {
  return (
    <StyledFaqContent>
      {!isMobile && (
        <Text weight="semibold" marginBottom="spacing.2">
          How to check status of my settlement ID/settlements?
        </Text>
      )}
      <Text color="surface.text.gray.subtle">
        You can view the details of all previous settlements by going back
      </Text>
      <TextLink>
        <Text marginRight="spacing.2" color="surface.text.gray.subtle">
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
    </StyledFaqContent>
  );
};

export default StatusCheck;
