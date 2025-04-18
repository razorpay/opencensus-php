import React from 'react';
import { Box, Heading, Text, Spinner, Skeleton } from '@razorpay/blade/components';

import type { ReconStatsCardProps } from 'merchant/views/Reconciliations/AiIngestion/types';

const ReconStatsCard: React.FC<ReconStatsCardProps> = ({ title, value, subtitle, isLoading }) => (
  <Box
    display="flex"
    flexDirection="column"
    borderRadius="medium"
    borderWidth="thinner"
    borderColor="surface.border.gray.subtle"
    padding="spacing.5"
    gap="spacing.1"
  >
    <Box display="flex" justifyContent="space-between" gap="spacing.6">
      <Text size="medium" weight="semibold" color="surface.text.gray.subtle">
        {title}
      </Text>
      {isLoading && <Spinner accessibilityLabel="Load data" />}
    </Box>
    {isLoading ? (
      <>
        <Skeleton width="100%" height="26px" />
        <Skeleton width="100%" height="18px" />
      </>
    ) : (
      <>
        <Heading size="medium" weight="semibold" color="surface.text.gray.subtle">
          {value}
        </Heading>
        <Text size="small" weight="regular" color="surface.text.gray.muted">
          {subtitle}
        </Text>
      </>
    )}
  </Box>
);

export default ReconStatsCard;
