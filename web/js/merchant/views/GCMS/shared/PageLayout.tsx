import React from 'react';
import { Box, Text, Heading } from '@razorpay/blade/components';
import { PageLayoutProps } from './types';

function GCMSPageLayout({ title, subtitle, leading, children }: PageLayoutProps) {
  return (
    <Box
      marginTop="24px"
      elevation="none"
      display="flex"
      flexDirection="column"
      testID="gcms-page-layout"
    >
      <Box display="flex" flexDirection="row" justifyContent="space-between" alignItems="center">
        <Box>
          <Heading size="large" marginBottom="8px">
            {title}
          </Heading>
          <Text size="medium" color="interactive.text.gray.muted">
            {subtitle}
          </Text>
        </Box>
        {leading && <Box>{leading}</Box>}
      </Box>
      <Box marginTop="24px">{children}</Box>
    </Box>
  );
}

export default GCMSPageLayout;
