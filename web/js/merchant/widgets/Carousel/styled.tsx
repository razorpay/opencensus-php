import React, { ReactNode } from 'react';
import { Box } from '@razorpay/blade/components';

import { getBackgroundImage } from 'merchant/widgets/utils';

interface CarouselWidgetWrapperProps {
  children: ReactNode;
  backgroundImage?: string;
}

export const CarouselWidgetWrapper = ({
  children,
  backgroundImage,
}: CarouselWidgetWrapperProps) => {
  return (
    <Box
      borderRadius="medium"
      marginX={{ base: 'spacing.0', m: 'spacing.6' }}
      paddingY="spacing.6"
      paddingX="spacing.5"
      backgroundImage={backgroundImage ? getBackgroundImage(backgroundImage) : undefined}
      backgroundColor={backgroundImage ? undefined : 'surface.background.gray.intense'}
      backgroundSize="cover"
      backgroundPosition="center center"
      testID="carousel-widget-wrapper"
      elevation="lowRaised"
    >
      {children}
    </Box>
  );
};
