import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';

import { ProductCardWidgetLoaderProps } from './types';

export const ProductCardWidgetLoader = ({ styles }: ProductCardWidgetLoaderProps) => {
  const isShownOnHover = styles?.show_on_hover ?? true;
  const isToggleResponsive = styles?.toggle_responsive ?? false;

  return (
    <Box
      width={styles?.width ?? '284px'}
      height={styles?.height ?? '416px'}
      display="flex"
      flexDirection={isToggleResponsive ? { base: 'column', s: 'row', xl: 'column' } : 'column'}
      testID="product-card-loader"
      borderColor="surface.border.gray.muted"
      borderWidth="thinner"
      borderRadius={styles?.border_radius ?? 'medium'}
      overflow="hidden"
    >
      <Box
        height={isToggleResponsive ? { base: '65%', s: 'auto', xl: '75%' } : '300px'}
        width={isToggleResponsive ? { base: '100%', s: '200px', xl: '100%' } : undefined}
        backgroundColor="surface.background.gray.moderate"
      />
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
        {!isShownOnHover && <Skeleton width="136px" height="32px" borderRadius="max" />}
      </Box>
    </Box>
  );
};
