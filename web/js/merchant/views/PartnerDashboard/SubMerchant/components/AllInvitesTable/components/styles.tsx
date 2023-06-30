import React from 'react';
import { Box } from '@razorpay/blade/components';
type ContainerProps = { children: React.ReactNode };

export const FilterContainer = ({ children }: ContainerProps): JSX.Element => (
  <Box display="flex" marginBottom="spacing.6" flexWrap="wrap">
    {children}
  </Box>
);
export const InputContainer = ({ children }: ContainerProps): JSX.Element => (
  <Box
    marginTop="spacing.2"
    marginBottom="spacing.2"
    marginRight="spacing.6"
    marginLeft="spacing.0"
    minWidth="180px"
  >
    {children}
  </Box>
);

export const ButtonContainer = ({ children }: ContainerProps): JSX.Element => (
  <Box
    marginTop="30px"
    marginBottom="spacing.2"
    marginRight="spacing.6"
    marginLeft="spacing.0"
    display="flex"
    alignItems="flex-start"
  >
    {children}
  </Box>
);

export const SpinnerContainer = ({ children }: ContainerProps): JSX.Element => (
  <Box minHeight="300px" display="flex" justifyContent="center" alignItems="center">
    {children}
  </Box>
);
