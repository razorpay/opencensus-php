import { Box, Skeleton } from '@razorpay/blade/components';
import React from 'react';

const CardShimmer = ({ isMobile }) => (
  <Box
    marginRight="spacing.7"
    marginBottom="spacing.7"
    borderWidth="thin"
    borderColor="surface.border.gray.subtle"
    padding="spacing.4"
    width={isMobile ? '100%' : 'min(362px, 100%)'}
    borderRadius="medium"
  >
    <Box display="flex" justifyContent="space-between" marginBottom="spacing.5">
      <Box
        backgroundColor="surface.background.gray.subtle"
        padding="spacing.3"
        display="flex"
        justifyContent="center"
        alignItems="center"
        borderRadius="medium"
      >
        <Skeleton height="spacing.5" width="spacing.5" />
      </Box>
      <Skeleton height="spacing.5" width="spacing.5" />
    </Box>
    <Skeleton height="spacing.5" width="70%" />
    <Box display="flex" gap="spacing.4" alignItems="center" marginTop="spacing.3">
      <Skeleton height="spacing.5" width="spacing.5" />
      <Skeleton height="spacing.5" width="spacing.11" />
    </Box>
  </Box>
);

const WebsiteCardLoadingState = ({
  isMobile,
  cardsNumber,
}: {
  isMobile: boolean;
  cardsNumber: number;
}) => {
  return (
    <Box display="flex" flexDirection={isMobile ? 'column' : 'row'} flexWrap="wrap">
      {Array.from({ length: cardsNumber }).map((_, idx) => (
        <CardShimmer key={idx} isMobile={isMobile} />
      ))}
    </Box>
  );
};

export default WebsiteCardLoadingState;
