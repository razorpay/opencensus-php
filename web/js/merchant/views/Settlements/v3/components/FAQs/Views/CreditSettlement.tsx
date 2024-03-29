import { Alert, Link, Text } from '@razorpay/blade/components';
import { FaqInterface } from 'merchant/views/Settlements/v3/typings';
import React from 'react';
import { StyledFaqContent, TextLink } from './styled';

const CreditSettlement = ({ isMobile }: FaqInterface): JSX.Element => {
  return (
    <StyledFaqContent>
      {!isMobile && (
        <Text weight="semibold" marginBottom="spacing.2">
          How to know if settlements are credited to my bank account?
        </Text>
      )}
      <Text color="surface.text.gray.subtle">
        You can track your settlement or contact your bank using the UTR number as a reference. It
        is in the settlement section alongside each settlement ID and when you download settlement
        reports from the ‘Reports’ section.
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
      <Alert
        emphasis="subtle"
        description="Note: UTR is a unique transaction reference number available across banks, which can be used to track a specific settlement in your bank account."
        marginTop="spacing.4"
        isDismissible={false}
        color="information"
      />
    </StyledFaqContent>
  );
};

export default CreditSettlement;
