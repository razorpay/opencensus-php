import React from 'react';
import { Text, Box, Card, CardBody, Skeleton } from '@razorpay/blade/components';

export const CapturePaymentShimmer = (): JSX.Element => {
  return (
    <Box flex={1} testID="loading-shimmer">
      <Card
        elevation="none"
        padding="spacing.3"
        backgroundColor="surface.background.gray.moderate"
        marginY="spacing.5"
      >
        <CardBody>
          <Box
            display="flex"
            gap="spacing.5"
            flexDirection="column"
            height="200px"
            minWidth="280px"
            padding="spacing.4"
            marginX="spacing.2"
          >
            <Text weight="semibold" size="medium" color="surface.text.gray.subtle">
              Collected Amount
            </Text>
            <Skeleton width="25%" height="28px" borderRadius="max" />
            <Skeleton width="15%" height="16px" borderRadius="max" />
          </Box>
        </CardBody>
      </Card>
    </Box>
  );
};

export const CardShimmer = (): JSX.Element => {
  return (
    <Box flex={1} maxHeight="120px" marginY="spacing.2" testID="loading-shimmer">
      <Card
        elevation="lowRaised"
        padding="spacing.5"
        backgroundColor="surface.background.gray.moderate"
      >
        <CardBody>
          <Box display="flex" gap="spacing.3" flexDirection="column" minWidth="220px" flex={1}>
            <Skeleton width="5%" height="15px" borderRadius="medium" />
            <Skeleton width="30%" height="15px" borderRadius="max" />
            <Skeleton width="50%" height="20px" borderRadius="max" />
            <Skeleton width="20%" height="10px" borderRadius="max" />
          </Box>
        </CardBody>
      </Card>
    </Box>
  );
};

export const CardInfoShimmer = (): JSX.Element => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.4"
      marginY="spacing.4"
      testID="loading-shimmer"
    >
      <Skeleton width="50%" height="22px" borderRadius="max" />
      <Skeleton width="30%" height="14px" borderRadius="max" />
    </Box>
  );
};
