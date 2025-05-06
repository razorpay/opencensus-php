import React from 'react';
import { EmptyCardProp } from './types';
import { InsightCardHeader } from './InsightCard';
import { Box, Heading, Text, Badge, Divider, RayIcon } from '@razorpay/blade/components';
import { staticContent } from './constants';

const EmptyCard = ({ insightsStaticData, isMobile, componentData, analytics }: EmptyCardProp) => {
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
      flexGrow="1"
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
          ---
        </Heading>
        <Box>
          <Box
            display="flex"
            flexDirection="row"
            alignItems="center"
            gap="spacing.3"
            marginBottom="spacing.5"
            marginTop="spacing.3"
          >
            <Badge color="neutral" icon={RayIcon} size="medium" emphasis="subtle">
              {staticContent.rayInsightBadgeText}
            </Badge>
            <Divider />
          </Box>
          <Box>
            <Text size="medium" weight="medium" color="surface.text.gray.muted">
              {insightsStaticData.emptyCardDescription}
            </Text>
          </Box>
        </Box>
      </Box>
    </Box>
  );
};
export default EmptyCard;
