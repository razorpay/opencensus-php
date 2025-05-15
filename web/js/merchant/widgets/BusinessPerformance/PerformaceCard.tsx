import { Box, Display, Heading } from '@razorpay/blade/components';
import React from 'react';

import { GradientBox } from './GardientBox';
import { PerformanceComponent } from './types';

export type PerformanceCard = {
  index: number;
  isMobile: boolean;
  item: PerformanceComponent['data']['cards'][number];
  variant: PerformanceComponent['variant'];
};

export const PerformanceCard = ({ item, variant, index, isMobile }: PerformanceCard) => {
  const amount = item.value;

  return (
    <GradientBox isMobile={isMobile} variant={variant}>
      {isMobile ? (
        <Box display="flex" gap="spacing.5" alignItems="center" justifyContent="space-between">
          <Box>
            <Heading
              as="span"
              color={`feedback.text.${variant}.intense`}
              size="large"
              weight="semibold"
            >
              #{index}
            </Heading>
            <Heading
              as="span"
              color="surface.text.gray.muted"
              marginLeft="spacing.7"
              size="large"
              weight="semibold"
            >
              {item.label}
            </Heading>
          </Box>
          <Heading as="span" color="surface.text.gray.subtle" size="large" weight="semibold">
            {amount}
          </Heading>
        </Box>
      ) : (
        <Box display="grid">
          <Heading
            as="span"
            color={`feedback.text.${variant}.intense`}
            size="large"
            weight="semibold"
          >
            #{index}
          </Heading>
          <Heading
            as="span"
            color="surface.text.gray.muted"
            marginTop="spacing.5"
            size="large"
            weight="semibold"
          >
            {item.label}
          </Heading>
          <Display as="span" color="surface.text.gray.subtle" size="small" weight="semibold">
            {amount}
          </Display>
        </Box>
      )}
    </GradientBox>
  );
};
