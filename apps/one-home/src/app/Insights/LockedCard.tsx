import React from 'react';
import { LockedCardProp } from './types';
import { InsightCardHeader } from './InsightCard';
import { Box, Heading, Text } from '@razorpay/blade/components';

const LockedCard = ({ insightsStaticData, isMobile, componentData, analytics }: LockedCardProp) => {
  return (
    <Box
      margin={{
        base: 'spacing.0',
        s: 'spacing.0',
        m: 'spacing.5',
        l: 'spacing.5',
      }}
      display="flex"
      flexDirection="column"
      flexGrow="1" // To match height of other cards
    >
      <InsightCardHeader
        insightsStaticData={insightsStaticData}
        isMobile={isMobile}
        componentData={componentData}
        analytics={analytics}
        showViewDetails={false}
      />
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.11"
        flexGrow="1"
        justifyContent="space-between"
      >
        <Heading size="2xlarge" color="surface.text.gray.normal" weight="semibold">
          ***
        </Heading>
        <Text
          variant="body"
          color="surface.text.gray.muted"
          size="medium"
          weight="regular"
          marginTop="spacing.3"
        >
          {insightsStaticData.lockCardDescription}
        </Text>
      </Box>
    </Box>
  );
};
export default LockedCard;
