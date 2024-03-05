import React, { useEffect, useState } from 'react';
import { Box, Text } from '@razorpay/blade/components';

import {
  StyledBoxMetedata,
  StyledTimeline,
  StyledTimelineContainer,
  StyledBox,
  StyledText,
  StyledTextContainer,
  StyledIconBackground,
  StyledStatusIcon,
} from './styled';
import { EntityStatus, RefundTimelineJourneyPoint, RefundTimelineType } from './types';
import { makeRefundTimelineData } from './utils';
import { getHumanReadableTimestamp } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/Timeline/utils';
import { IPaymentIdRefundDetail } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/types';

const renderTimelineJourneyMeta = (
  journeyPoint: RefundTimelineJourneyPoint,
  refundStatus: EntityStatus,
): JSX.Element => {
  return (
    <StyledBoxMetedata>
      {journeyPoint.id === 1 ? (
        <Text size="small" color="surface.text.subtle.lowContrast" weight="regular">
          Takes 3-5 working days
        </Text>
      ) : null}
      {journeyPoint.timestamp ? (
        <Text size="small" color="surface.text.subtle.lowContrast" weight="regular">
          {getHumanReadableTimestamp(journeyPoint.timestamp)}
        </Text>
      ) : null}
      {journeyPoint.id === 2 && refundStatus !== 'failed' ? (
        <Box paddingTop="spacing.3">
          <Text size="small" color="surface.text.muted.lowContrast" weight="bold">
            [Amount will be credited to customer’s bank account within 5-7 working days after the
            refund has processed]
          </Text>
        </Box>
      ) : null}
    </StyledBoxMetedata>
  );
};

const RefundMiniTimeline = ({ refund }: { refund: IPaymentIdRefundDetail }): JSX.Element => {
  const [refundTimeline, setrefundTimeline] = useState<RefundTimelineType | null>(null);

  useEffect(() => {
    const timelineData = makeRefundTimelineData(refund);
    setrefundTimeline(timelineData);
  }, [refund]);

  return (
    <StyledTimeline>
      <StyledBox>
        <StyledTimelineContainer>
          {refundTimeline?.timelineJourney?.map((each, index) => (
            <Box
              display="flex"
              flexDirection="column"
              position="relative"
              key={`${each.id}_${index}`}
              marginTop="30px"
            >
              <Box position="absolute" top="-25px" left="-9px">
                <StyledIconBackground status={refundTimeline.refundStatus} step={each.id}>
                  <StyledStatusIcon status={refundTimeline.refundStatus} step={each.id} />
                </StyledIconBackground>
              </Box>
              <StyledTextContainer>
                <StyledText>{each.title}</StyledText>
              </StyledTextContainer>
              {renderTimelineJourneyMeta(each, refundTimeline.refundStatus)}
            </Box>
          ))}
        </StyledTimelineContainer>
      </StyledBox>
    </StyledTimeline>
  );
};

export default RefundMiniTimeline;
