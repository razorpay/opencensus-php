import React from 'react';
import { Box } from '@razorpay/blade/components';
type ContainerProps = { children: React.ReactNode };

export const SpinnerContainer = ({ children }: ContainerProps): JSX.Element => (
  <Box minHeight="100px" display="flex" justifyContent="center" alignItems="center">
    {children}
  </Box>
);
