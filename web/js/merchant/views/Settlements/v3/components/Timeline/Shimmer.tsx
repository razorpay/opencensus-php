import { Box, Heading } from '@razorpay/blade/components';
import Shimmer from 'common/components/Shimmer';
import React from 'react';
import { StyledTimeline, StyledVerticalPath, TimelineHeader } from './styled';

const timelineJourneyPlaceholder = [
  { width: '120px', widthChild: '80px' },
  { width: '100px', widthChild: '100px' },
];

const TimelineShimmer = (): JSX.Element => {
  return (
    <StyledTimeline>
      <TimelineHeader>
        <Heading size="medium" weight="bold">
          Timeline
        </Heading>
      </TimelineHeader>
      <Box display="flex" alignItems="flex-start" gap="spacing.4" padding="spacing.7">
        <Box display="flex" flexDirection="column" gap="spacing.2" alignItems="center">
          {timelineJourneyPlaceholder.map(
            (_, index): JSX.Element => (
              <>
                <Shimmer height="20px" width="20px" variant="circular" />
                {index < timelineJourneyPlaceholder.length - 1 && <StyledVerticalPath />}
              </>
            ),
          )}
        </Box>
        <Box display="flex" flexDirection="column" gap="spacing.8">
          {timelineJourneyPlaceholder.map((each, index) => (
            <Box display="flex" flexDirection="column" gap="spacing.2" key={index}>
              <Shimmer height="20px" width={each.width} variant="rounded" borderRadius="12px" />
              <Shimmer
                height="12px"
                width={each.widthChild}
                variant="rounded"
                borderRadius="12px"
              />
            </Box>
          ))}
        </Box>
      </Box>
    </StyledTimeline>
  );
};

export const TimelineRevampShimmer = (): JSX.Element => {
  const timelineJourneyPlaceholderData = [
    ...timelineJourneyPlaceholder,
    ...timelineJourneyPlaceholder,
  ];
  return (
    <StyledTimeline style={{ flex: '1' }}>
      <TimelineHeader>
        <Heading size="medium" weight="bold">
          Timeline
        </Heading>
      </TimelineHeader>
      <Box display="flex" alignItems="flex-start" gap="spacing.4" padding="spacing.7">
        <Box display="flex" flexDirection="column" gap="spacing.2" alignItems="center">
          {timelineJourneyPlaceholderData.map(
            (_, index): JSX.Element => (
              <>
                <Shimmer height="20px" width="20px" variant="circular" />
                {index < timelineJourneyPlaceholderData.length - 1 && <StyledVerticalPath />}
              </>
            ),
          )}
        </Box>
        <Box display="flex" flexDirection="column" gap="spacing.8">
          {timelineJourneyPlaceholderData.map((each, index) => (
            <Box display="flex" flexDirection="column" gap="spacing.2" key={index}>
              <Shimmer height="20px" width={each.width} variant="rounded" borderRadius="12px" />
              <Shimmer
                height="12px"
                width={each.widthChild}
                variant="rounded"
                borderRadius="12px"
              />
            </Box>
          ))}
        </Box>
      </Box>
    </StyledTimeline>
  );
};

export default TimelineShimmer;
