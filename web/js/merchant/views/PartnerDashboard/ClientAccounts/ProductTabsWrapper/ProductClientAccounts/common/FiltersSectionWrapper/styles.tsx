import React from 'react';
import { Box, BoxProps } from '@razorpay/blade/components';
type ContainerProps = { children: React.ReactNode };

export const FilterContainer = ({ children }: ContainerProps): JSX.Element => (
  <Box display="flex" marginBottom="spacing.6" flexWrap={{ base: 'wrap', xl: 'nowrap' }}>
    {children}
  </Box>
);

export const InputContainer = ({
  children,
  flexBasis = '150px',
}: Pick<BoxProps, 'flexBasis' | 'children'>): JSX.Element => (
  <Box
    marginTop="spacing.2"
    marginBottom="spacing.2"
    marginRight="spacing.6"
    marginLeft="spacing.0"
    flexBasis={flexBasis}
  >
    {children}
  </Box>
);

export const ButtonContainer = ({ children }: ContainerProps): JSX.Element => (
  <Box
    marginTop="30px"
    marginBottom="spacing.2"
    marginRight="spacing.2"
    marginLeft="spacing.0"
    display="flex"
    gap="spacing.2"
    alignItems="flex-start"
  >
    {children}
  </Box>
);
