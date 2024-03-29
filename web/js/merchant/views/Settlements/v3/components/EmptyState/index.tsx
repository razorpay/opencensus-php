import { ArrowRightIcon, Heading, Link, Text } from '@razorpay/blade/components';
import ReceiveSettlements from 'assets/receive-settlements.svg';
import Image from 'common/ui/Image';
import React from 'react';
import { StyledEmptySettlementsBox } from './styled';

const EmptySettlementState = ({ handleAction }): JSX.Element => {
  return (
    <StyledEmptySettlementsBox>
      <Image src={ReceiveSettlements} alt="receive-settlements" width={64} height={64} />
      <Heading size="small">Get settlements in your bank account</Heading>
      <Text color="surface.text.gray.subtle">
        Collected payments get deposited in your bank account after adjusting for platform fees and
        applicable charges and appear as settlements here
      </Text>
      <Link variant="button" icon={ArrowRightIcon} iconPosition="right" onClick={handleAction}>
        View settlements
      </Link>
    </StyledEmptySettlementsBox>
  );
};

export default EmptySettlementState;
