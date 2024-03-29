import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';

export const ProductCardWidgetLoader = () => (
  <Box
    width="284px"
    height="416px"
    display="flex"
    flexDirection="column"
    testID="product-card-loader"
    borderColor="surface.border.gray.muted"
    borderWidth="thinner"
    borderRadius="medium"
  >
    <Box
      height="300px"
      backgroundColor="surface.background.gray.moderate"
      borderTopLeftRadius="medium"
      borderTopRightRadius="medium"
    />
    <Box
      backgroundColor="surface.background.gray.intense"
      height="116px"
      display="flex"
      flexDirection="column"
      gap="spacing.4"
      padding="spacing.5"
      borderTopColor="surface.border.gray.muted"
      borderBottomLeftRadius="medium"
      borderBottomRightRadius="medium"
    >
      <Skeleton width="156px" height="32px" borderRadius="max" />
      <Skeleton width="110px" height="20px" borderRadius="max" />
      <Skeleton width="110px" height="20px" borderRadius="max" />
    </Box>
  </Box>
);
