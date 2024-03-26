import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';

export const ProductCardWidgetLoader = () => (
  <Box
    width="284px"
    height="416px"
    display="flex"
    flexDirection="column"
    testID="product-card-loader"
    borderColor="surface.border.normal.lowContrast"
    borderWidth="thinner"
    borderRadius="medium"
  >
    <Box
      height="300px"
      backgroundColor="brand.gray.200.lowContrast"
      borderTopLeftRadius="medium"
      borderTopRightRadius="medium"
    />
    <Box
      backgroundColor="surface.background.level2.lowContrast"
      height="116px"
      display="flex"
      flexDirection="column"
      gap="spacing.4"
      padding="spacing.5"
      borderTopColor="surface.border.normal.lowContrast"
      borderBottomLeftRadius="medium"
      borderBottomRightRadius="medium"
    >
      <Skeleton width="156px" height="32px" borderRadius="max" />
      <Skeleton width="110px" height="20px" borderRadius="max" />
      <Skeleton width="110px" height="20px" borderRadius="max" />
    </Box>
  </Box>
);
