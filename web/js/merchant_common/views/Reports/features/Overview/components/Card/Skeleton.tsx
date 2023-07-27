import React, { useMemo } from 'react';
import { randomInt } from 'merchant_common/views/Reports/utils/commonUtils';
import { Box, Divider, Skeleton } from 'merchant_common/views/Reports/components';

export const CardSkeleton = (): JSX.Element => {
  const width = useMemo(() => [randomInt(6, 10), randomInt(6, 10), randomInt(6, 10)], []);

  return (
    <Box padding="spacing.7" elevation="midRaised">
      <Box display="flex" alignItems="center" marginBottom="spacing.4">
        <Box>
          <Skeleton height="32px" width="32px" borderRadius="max" marginRight="spacing.4" />
        </Box>
        <Skeleton aria-label="Card Title" height="1rem" width={`${width[0]}0%` as 'auto'} />
      </Box>
      <Divider />
      <Box marginTop="spacing.4">
        <Skeleton height="0.7rem" marginBottom="spacing.2" width="100%" />
        <Skeleton height="0.7rem" marginBottom="spacing.2" width={`${width[1]}0%` as 'auto'} />
        <Skeleton height="0.7rem" width={`${width[2]}0%` as 'auto'} />
      </Box>

      <Box marginTop="spacing.8" display="flex" justifyContent="space-between">
        <Skeleton aria-label="Card Link" width="50px" height="0.7rem" />
        <Skeleton aria-label="Card Link" width="50px" height="0.7rem" />
      </Box>
    </Box>
  );
};
//s
