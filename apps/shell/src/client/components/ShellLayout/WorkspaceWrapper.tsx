import React, { ReactNode } from 'react';
import { Box, BoxProps } from '@razorpay/blade/components';

interface WorkspaceWrapperProps {
  children: ReactNode;
  isFullPage?: boolean;
  padding?: BoxProps['padding'];
}

export const WorkspaceWrapper = ({
  children,
  isFullPage = false,
  padding,
}: WorkspaceWrapperProps) => {
  const marginLeftMediumValue = isFullPage ? '0px' : '240px';
  const marginLeftXLValue = isFullPage ? '0px' : '264px';
  return (
    <Box
      marginLeft={{ base: '0px', m: marginLeftMediumValue, xl: marginLeftXLValue }}
      height="100%"
    >
      <Box
        overflowY="scroll"
        height="100%"
        backgroundColor="surface.background.gray.moderate"
        padding={padding}
        position="relative"
      >
        {children}
      </Box>
    </Box>
  );
};
