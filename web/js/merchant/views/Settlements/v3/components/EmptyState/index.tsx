import React from 'react';
import { Heading, Text, Link, ArrowRightIcon } from '@razorpay/blade/components';
import ShowWhen from 'merchant/components/ShowWhen';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import { StyledEmptySettlementsBox } from './styled';
import Image from 'common/ui/Image';
import ReceiveSettlements from 'assets/receive-settlements.svg';

const EmptySettlementState = () => {
  return (
    <StyledEmptySettlementsBox>
      <Image src={ReceiveSettlements} alt="receive-settlements" width={64} height={64} />

      <Heading size="medium">Receive settlements in your bank account</Heading>

      <Text type="subtle">
        Settlement is the process by which your collected payments get deposited in your bank
        account. They will be shown here
      </Text>
      <ShowWhen
        additionalCondition={(user) =>
          !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Documentation)
        }
      >
        <Link
          href="https://razorpay.com/docs/payments/settlements/"
          target="_blank"
          rel="noopener noreferrer"
          icon={ArrowRightIcon}
          iconPosition="right"
        >
          Settlements guide
        </Link>
      </ShowWhen>
    </StyledEmptySettlementsBox>
  );
};

export default EmptySettlementState;
