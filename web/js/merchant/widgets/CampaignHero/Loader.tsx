import React from 'react';
import { Box, Carousel, CarouselItem, Skeleton } from '@razorpay/blade/components';

import { calHeightOnAspectRatio, IMAGE_SCALE_MAP } from './utils';

interface CampaignHeroWidgetLoaderProps {
  width: number;
  scale: keyof typeof IMAGE_SCALE_MAP;
}

export const CampaignHeroWidgetLoader = ({ width, scale }: CampaignHeroWidgetLoaderProps) => {
  const isMobile = scale === 'sm';

  return (
    <Carousel visibleItems="autofit" navigationButtonPosition="side">
      {Array.from({ length: 3 }, (_, idx) => (
        <CarouselItem key={idx}>
          <Box
            height={`${calHeightOnAspectRatio(width)}px`}
            display="flex"
            flexDirection={isMobile ? 'column' : 'row'}
            backgroundColor="surface.background.gray.intense"
            borderRadius="medium"
            overflow="hidden"
            testID="campaign-hero-loader"
          >
            <Box
              height={isMobile ? '50%' : 'auto'}
              backgroundColor="surface.background.gray.moderate"
              flex="1"
            />
            <Box
              width={isMobile ? '100%' : '65%'}
              display="flex"
              flexDirection="column"
              justifyContent="space-between"
              gap="spacing.3"
              padding="spacing.5"
            >
              <Skeleton width="50%" height="32px" borderRadius="max" />
              <Skeleton width="80%" height="20px" borderRadius="max" />
              <Skeleton width="20%" height="20px" borderRadius="max" />
              <Skeleton width="35%" height="32px" borderRadius="max" />
            </Box>
          </Box>
        </CarouselItem>
      ))}
    </Carousel>
  );
};
