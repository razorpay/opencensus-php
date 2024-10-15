import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';

import { StyledInvoiceStatsCard } from './styled';

const StatsLoader = (): JSX.Element => {
  return (
    <StyledInvoiceStatsCard>
      <Box testID="loading-shimmer" display="flex" flex="1">
        <Box display="flex" flexDirection="column" flex="1" paddingTop="spacing.3">
          <Skeleton width="160px" height="22px" marginBottom="spacing.3" />
          <Skeleton width="80px" height="30px" marginTop="spacing.5" />
        </Box>
      </Box>
    </StyledInvoiceStatsCard>
  );
};

export default StatsLoader;
