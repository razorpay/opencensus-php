import { Alert, Link, Text } from '@razorpay/blade/components';
import { FaqInterface } from 'merchant/views/Settlements/v3/typings';
import React from 'react';
import { StyledFaqContent, TextLink } from './styled';

const InstantSettlement = ({ isMobile }: FaqInterface): JSX.Element => {
  return (
    <StyledFaqContent>
      {!isMobile && (
        <Text weight="semibold" marginBottom="spacing.2">
          How do I enable instant settlement?
        </Text>
      )}
      <Text color="surface.text.gray.subtle">
        Instant settlements is an on-demand feature. You need to create support ticket using the
        link below to get this feature activated on your Razorpay account.
      </Text>
      <TextLink>
        <Text marginRight="spacing.2" color="surface.text.gray.subtle">
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
        emphasis="subtle"
        description="Note: Instant Settlement feature allows you to settle your current balance to your bank account instantly 24x7 for a small fee. You can either settle your current balance in full or choose to settle a portion of it as per your needs."
        marginTop="spacing.4"
        isDismissible={false}
        color="information"
      />
    </StyledFaqContent>
  );
};

export default InstantSettlement;
