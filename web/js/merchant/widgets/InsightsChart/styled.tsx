import React from 'react';
import { Box } from '@razorpay/blade/components';

export const InsightItemChartWrapper = ({ children }) => (
  <Box backgroundColor="surface.background.gray.subtle" borderRadius="medium" height="fit-content">
    {children}
  </Box>
);
