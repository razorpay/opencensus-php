import { Box, CheckIcon, CloseIcon, Heading, Text } from '@razorpay/blade/components';
import { SettlementPropsInterface, SettlementStatus } from 'merchant/views/Settlements/v3/typings';
import { getTimelineJourneyDetails } from 'merchant/views/Settlements/v3/utils/settlementInfo';
import React from 'react';
import { connect } from 'react-redux';
import { IconBackground, StyledTimeline, StyledVerticalPath, TimelineHeader } from './styled';

const JourneyStatusIcon = ({ status }: { status: SettlementStatus }): JSX.Element => {
  switch (status) {
    case 'failed':
      return <CloseIcon size="small" color="feedback.icon.negative.intense" />;
    case 'processed':
      return <CheckIcon size="small" color="feedback.icon.positive.intense" />;
    default:
      return <CheckIcon size="small" color="feedback.icon.neutral.intense" />;
  }
};

const Timeline = ({
  created_at,
  status,
}: Pick<SettlementPropsInterface, 'created_at' | 'status'>): JSX.Element => {
  const timelineJourney = getTimelineJourneyDetails({
    created_at,
    status,
  });

  return (
    <StyledTimeline>
      <TimelineHeader>
        <Heading weight="semibold" size="small">
          Timeline
        </Heading>
      </TimelineHeader>
      <Box display="flex" alignItems="flex-start" gap="spacing.4" padding="spacing.7">
        <Box display="flex" flexDirection="column" gap="spacing.2" alignItems="center">
          {timelineJourney.map(
            (each, index): JSX.Element => (
              <>
                <IconBackground status={each.id}>
                  <JourneyStatusIcon status={each.id} />
                </IconBackground>
                {index < timelineJourney.length - 1 && <StyledVerticalPath />}
              </>
            ),
          )}
        </Box>
        <Box display="flex" flexDirection="column" gap="spacing.8">
          {timelineJourney.map((each, index) => (
            <Box display="flex" flexDirection="column" gap="spacing.2" key={`${each.id}_${index}`}>
              <Text size="medium" color="surface.text.gray.subtle">
                {each.status}
              </Text>
              {each?.timeline && (
                <Text size="small" color="surface.text.gray.muted">
                  {each.timeline}
                </Text>
              )}
            </Box>
          ))}
        </Box>
      </Box>
    </StyledTimeline>
  );
};

const mapStateToProps = ({ settlement }) => ({
  ...settlement.settlement,
});

export default connect(mapStateToProps, null)(Timeline);
