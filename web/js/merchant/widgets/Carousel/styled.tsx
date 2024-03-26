import React, { ReactNode } from 'react';
import { Box } from '@razorpay/blade/components';
import { getBackgroundImage } from 'merchant/widgets/utils';

export const CarouselWidgetWrapper = ({
  children,
  background_img,
}: {
  children: ReactNode;
  background_img: string;
}): JSX.Element => {
  return (
    <Box
      borderRadius="medium"
      marginX={{ base: 'spacing.0', m: 'spacing.6' }}
      paddingY="spacing.6"
      paddingX="spacing.5"
      backgroundImage={background_img ? getBackgroundImage(background_img) : undefined}
      backgroundColor={background_img ? undefined : 'surface.background.level2.lowContrast'}
      overflowX="hidden"
      backgroundSize="cover"
      backgroundPosition="center center"
      testID="carousel-widget-wrapper"
    >
      {children}
    </Box>
  );
};
