import React from 'react';
import { Box } from '@razorpay/blade/components';

export const InsightItemChartWrapper = ({ children }) => (
  <Box
    backgroundColor="surface.background.gray.subtle"
    paddingTop="spacing.3"
    borderRadius="medium"
    height="fit-content"
  >
    {children}
  </Box>
);
