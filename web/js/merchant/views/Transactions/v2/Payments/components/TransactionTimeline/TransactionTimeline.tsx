import {
  Box,
  Collapsible,
  CollapsibleBody,
  CollapsibleLink,
  Heading,
} from '@razorpay/blade/components';
import moment from 'moment';
import React from 'react';

import ErrorBoundary, { InlineFallbackComponent } from 'common/new-ui/ErrorBoundary';
import { SkipTransactions } from 'merchant/views/Transactions/v2/Payments/components/Timeline/types';
import {
  Container,
  CustomIcon,
  Description,
  LineContainer,
  Time,
} from 'merchant/views/Transactions/v2/Payments/components/TransactionTimeline/styled';
import { trackDetailsClick } from 'merchant/views/Transactions/v2/common/tracking';

interface TransactionTimelineProp {
  skips: Array<SkipTransactions>;
}
interface SkipType {
  time: number;
  reason: string;
}

const SkippedTransaction = ({ time, reason }: SkipType): JSX.Element => (
  <>
    <Box display="flex">
      <CustomIcon>
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="10"
          height="10"
          viewBox="0 0 10 10"
          fill="none"
        >
          <circle cx="5" cy="5" r="5" fill="#D13821" />
        </svg>
      </CustomIcon>
      <Heading size="large">Settlement failed</Heading>
    </Box>
    <LineContainer>
      <Time>{moment.unix(time).format('llll')}</Time>
      <Description>{reason}</Description>
    </LineContainer>
  </>
);

const TransactionTimeline = ({ skips }: TransactionTimelineProp): JSX.Element => (
  <ErrorBoundary resetOnProps FallbackComponent={InlineFallbackComponent}>
    {skips ? (
      <Box
        display="flex"
        alignItems="flex-start"
        gap="spacing.2"
        paddingBottom="spacing.7"
        paddingTop="spacing.2"
        testID="transaction-timeline"
      >
        <Collapsible
          direction="bottom"
          onExpandChange={() => {
            trackDetailsClick({
              objectName: 'View previous retry details',
            });
          }}
        >
          <CollapsibleLink>View previous retry details</CollapsibleLink>
          <CollapsibleBody>
            <Container>
              <Box alignItems="center" padding="spacing.2">
                {skips.map(({ skip_reason, skip_time }) => (
                  <SkippedTransaction
                    key={skip_reason + skip_time}
                    time={skip_time}
                    reason={skip_reason}
                  />
                ))}
              </Box>
            </Container>
          </CollapsibleBody>
        </Collapsible>
      </Box>
    ) : null}
  </ErrorBoundary>
);

export default TransactionTimeline;
