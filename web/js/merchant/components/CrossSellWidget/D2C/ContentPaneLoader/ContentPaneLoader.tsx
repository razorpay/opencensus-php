import { Box, Skeleton } from '@razorpay/blade/components';
import React from 'react';

function ContentPaneLoader() {
  return (
    <Box
      borderRadius="large"
      padding="spacing.0"
      borderColor="surface.border.gray.muted"
      borderWidth="thin"
      display="flex"
      flex="1"
      minHeight="500px"
      testID="content-pane-loader"
    >
      <Box
        width="33.33%"
        flexShrink="0"
        paddingLeft="spacing.8"
        paddingRight="spacing.6"
        paddingY="spacing.11"
      >
        <Skeleton width="100%" borderRadius="large" height="100%" />
      </Box>
      <Box width="100%" paddingLeft="spacing.9" paddingY="spacing.11">
        <Box width="100%">
          <Skeleton width="80%" height="32px" borderRadius="large" />
          <Skeleton width="60%" height="56px" borderRadius="large" marginTop="spacing.2" />
          <Skeleton width="70%" height="32px" borderRadius="large" marginTop="spacing.2" />
        </Box>
        <Box width="100%" marginTop="72px" paddingRight="18%">
          <Skeleton width="100%" height="16px" borderRadius="large" />
          <Skeleton width="45%" height="16px" borderRadius="large" marginTop="spacing.3" />
          <Skeleton width="75%" height="36px" borderRadius="large" marginTop="spacing.5" />
        </Box>
      </Box>
    </Box>
  );
}

export default ContentPaneLoader;
