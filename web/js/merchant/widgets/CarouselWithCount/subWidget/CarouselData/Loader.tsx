import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';

export const CarouselDataWidgetLoader: React.FC = (): JSX.Element => (
  <Box
    display="flex"
    backgroundColor="surface.background.level2.lowContrast"
    borderRadius="large"
    padding="spacing.5"
    borderWidth="thinner"
    borderColor="surface.border.subtle.lowContrast"
    height="120px"
    gap="spacing.4"
    testID="data-widget-loader"
  >
    <Skeleton width="24px" height="24px" borderRadius="medium" />
    <Box flexGrow={1} gap="spacing.5" display="flex" flexDirection="column">
      <Skeleton height="24px" width="40%" borderRadius="max" />
      <Box>
        <Skeleton height="16px" width="80%" borderRadius="large" marginBottom="spacing.3" />
        <Skeleton height="16px" width="80%" borderRadius="large" marginBottom="spacing.3" />
      </Box>
    </Box>
  </Box>
);
