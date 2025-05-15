import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';
import { useMobile } from '@libs/shared-utils';

function LoaderSection() {
  const isMobile = useMobile();

  return (
    <>
      <Skeleton height="24px" width="210px" />
      <Box
        display="grid"
        gap="spacing.5"
        gridTemplateColumns={isMobile ? undefined : 'repeat(auto-fit, minmax(210px, 1fr));'}
        marginTop="spacing.5"
      >
        {Array.from({ length: 5 }).map((_, index) => (
          <Skeleton
            borderRadius="large"
            height={isMobile ? '66px' : '178px'}
            key={index}
            width="100%"
          />
        ))}
      </Box>
    </>
  );
}

export function Loader() {
  return (
    <Box testID="business-performance-loader">
      <Box marginTop="spacing.8">
        <LoaderSection />
      </Box>
      <Box marginTop="spacing.8">
        <LoaderSection />
      </Box>
    </Box>
  );
}
