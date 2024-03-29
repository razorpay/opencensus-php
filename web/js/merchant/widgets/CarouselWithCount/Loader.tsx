import React from 'react';
import { Box, Carousel, CarouselItem, Heading } from '@razorpay/blade/components';

import { CarouselWithCountWidgetProps } from 'merchant/widgets/CarouselWithCount/types';
import { CarouselWithCountWrapper } from 'merchant/widgets/CarouselWithCount/styled';
import { getSubWidget } from 'merchant/widgets/CarouselWithCount/utils';

export const CarouselWithCountWidgetLoader: React.FC<
  Omit<CarouselWithCountWidgetProps, 'type'>
> = ({ title, background_img, components }): JSX.Element => (
  <CarouselWithCountWrapper background_img={background_img}>
    <Box display="flex" gap="spacing.2" marginBottom="spacing.6">
      <Heading color={background_img ? 'surface.text.staticWhite.normal' : undefined} size="medium">
        {title}
      </Heading>
    </Box>
    <Carousel carouselItemWidth="375px" visibleItems="autofit">
      {Array.from({ length: components.length ? components.length : 4 }, (v, k) => (
        <CarouselItem key={k}>
          {getSubWidget({
            widget: components.length
              ? components[k]
              : {
                  type: 'carousel_item',
                },
            isLoading: true,
          })}
        </CarouselItem>
      ))}
    </Carousel>
  </CarouselWithCountWrapper>
);
