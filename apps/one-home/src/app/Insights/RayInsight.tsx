import React from 'react';
import { Box, Text, Divider, Badge, RayIcon, Avatar, Tooltip } from '@razorpay/blade/components';
import { staticContent } from './constants';
import { InsightCardRayProp } from './types';
import { generateRayInsightContent } from './utils';

const RayInsight = ({
  cardType,
  insightsStaticData,
  rayInsightsData,
  inputTime,
}: InsightCardRayProp) => {
  const rayInsightContent = generateRayInsightContent({
    cardType,
    insightsStaticData,
    rayInsightsData,
    inputTime,
  });

  return (
    <>
      <Box
        display="flex"
        flexDirection="row"
        alignItems="center"
        gap="spacing.3"
        marginBottom="spacing.5"
        marginTop="spacing.7"
      >
        <Badge color="neutral" icon={RayIcon} size="medium" emphasis="subtle">
          {staticContent.rayInsightBadgeText}
        </Badge>
        <Divider />
      </Box>
      <Box display="flex" alignItems="flex-start" gap="spacing.5">
        <Avatar icon={insightsStaticData.defaultIcon} size="large" />
        <Tooltip content={rayInsightContent} placement="bottom">
          <Text
            truncateAfterLines={3}
            size="medium"
            weight="medium"
            color="surface.text.gray.muted"
          >
            {rayInsightContent}
          </Text>
        </Tooltip>
      </Box>
    </>
  );
};

export default RayInsight;
