import { Alert, Link, Text } from '@razorpay/blade/components';
import { FaqInterface } from 'merchant/views/Settlements/v3/typings';
import React from 'react';
import { StyledFaqContent, TextLink } from './styled';

const CurrentSettlementCycle = ({ isMobile, isInstantSettlement }: FaqInterface): JSX.Element => {
  return (
    <StyledFaqContent>
      {!isMobile && (
        <Text weight="bold" marginBottom="spacing.2">
          What is my current settlement cycle?
        </Text>
      )}
      {isInstantSettlement ? (
        <>
          <Text type="subtle">Instant settlements feature is enabled for your account.</Text>
          <Text type="subtle">
            You can settle your current balance (in full or choose to settle a portion of it as per
            your needs) to your bank account instantly 24x7 for a small fee.
          </Text>
        </>
      ) : (
        <Text type="subtle">
          Your current settlement cycle is <b>T+2 working days*</b> for domestic payments, and
          within <b>T+7 working days*</b> for international payments.
        </Text>
      )}
      <TextLink>
        <Text marginRight="spacing.2" type="subtle">
          To know more about {isInstantSettlement ? 'this' : 'settlements'}, check our
        </Text>
        {isInstantSettlement ? (
          <Link
            href="https://razorpay.com/docs/payments/settlements/instant/"
            target="_blank"
            rel="noreferrer noopener"
          >
            Instant settlements guide
          </Link>
        ) : (
          <Link
            href="https://razorpay.com/docs/payments/settlements/"
            target="_blank"
            rel="noreferrer noopener"
          >
            Settlements guide
          </Link>
        )}
      </TextLink>
      <Alert
        contrast="low"
        description={
          isInstantSettlement
            ? 'Note: Instant settlements is an on-demand feature.'
            : 'Note: *T being the date of payment collection. Working days do not include second and fourth Saturdays, Sundays and Bank Holidays'
        }
        intent="information"
        marginTop="spacing.4"
        isDismissible={false}
      />
    </StyledFaqContent>
  );
};

export default CurrentSettlementCycle;
