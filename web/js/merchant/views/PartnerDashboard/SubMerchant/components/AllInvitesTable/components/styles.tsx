import React from 'react';
import { Box } from '@razorpay/blade/components';

export const FilterContainer = ({ children }): JSX.Element => (
  <Box display="flex" marginBottom={'spacing.6'} marginTop={'spacing.6'} flexWrap="wrap">
    {children}
  </Box>
);
export const InputContainer = ({ children }): JSX.Element => (
  <Box
    marginTop={'spacing.2'}
    marginBottom={'spacing.2'}
    marginRight={'spacing.6'}
    marginLeft={'spacing.0'}
    minWidth="180px"
  >
    {children}
  </Box>
);

export const ButtonContainer = ({ children }): JSX.Element => (
  <Box
    marginTop={'spacing.2'}
    marginBottom={'spacing.2'}
    marginRight={'spacing.6'}
    marginLeft={'spacing.0'}
    display="flex"
    alignItems="flex-end"
  >
    {children}
  </Box>
);

export const SpinnerContainer = ({ children }): JSX.Element => (
  <Box minHeight="300px" display="flex" justifyContent="center" alignItems="center">
    {children}
  </Box>
);
