import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';

export const ProductCardWidgetLoader = () => {
  return (
    <Box
      width="284px"
      height="416px"
      display="flex"
      flexDirection="column"
      testID="product-card-loader"
      borderColor="surface.border.gray.muted"
      borderWidth="thinner"
      borderRadius="medium"
      overflow="hidden"
    >
      <Box height="300px" backgroundColor="surface.background.gray.moderate" />
      <Box
        width="100%"
        backgroundColor="surface.background.gray.intense"
        display="flex"
        flexDirection="column"
        gap="spacing.4"
        padding="spacing.5"
      >
        <Skeleton width="50%" height="32px" borderRadius="max" />
        <Skeleton width="100%" height="20px" borderRadius="max" />
        <Skeleton width="20%" height="20px" borderRadius="max" />
      </Box>
    </Box>
  );
};
