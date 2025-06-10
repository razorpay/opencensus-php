import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';

const WebsiteStatusLoader = () => (
  <Box display="flex" flexDirection="column" gap="spacing.5">
    <Box display="flex" flexDirection="row" gap="spacing.5">
      <Skeleton width="180px" height="20px" borderRadius="small" />
      <Skeleton width="60px" height="20px" borderRadius="medium" />
    </Box>
    <Box display="flex" flexDirection="column" gap="spacing.2">
      <Skeleton width="95%" height="20px" borderRadius="small" />
      <Skeleton width="25%" height="20px" borderRadius="small" />
    </Box>
    <Skeleton width="100px" height="32px" borderRadius="medium" />
  </Box>
);

export default WebsiteStatusLoader;
