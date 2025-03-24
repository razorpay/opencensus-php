import React from 'react';
import { Box } from '@razorpay/blade/components';

export const RightChildrenWrapper = ({ children }) => (
  <Box display="flex" justifyContent="center" alignItems="center" gap="spacing.3" overflow="hidden">
    {children}
  </Box>
);
