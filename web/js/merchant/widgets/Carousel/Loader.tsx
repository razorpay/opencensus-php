import React from 'react';
import { Box, Carousel, CarouselItem, Heading } from '@razorpay/blade/components';

import { CarouselWidgetProps } from 'merchant/widgets/Carousel/types';
import { CarouselWidgetWrapper } from 'merchant/widgets/Carousel/styled';
import { getSubWidget } from 'merchant/widgets/Carousel/utils';

export const CarouselWidgetLoader: React.FC<Omit<CarouselWidgetProps, 'type'>> = ({
  title,
  components,
  background_img,
}): JSX.Element => (
  <CarouselWidgetWrapper background_img={background_img} data-testid="product-card-loader">
    <Box display="flex" gap="spacing.2" marginBottom="spacing.6">
      <Heading
        size="large"
        color={background_img ? 'feedback.text.information.highContrast' : undefined}
      >
        {title}
      </Heading>
    </Box>
    <Carousel carouselItemWidth="284px" visibleItems="autofit">
      {Array.from({ length: components.length ? components.length : 4 }, (v, k) => (
        <CarouselItem key={k}>
          {getSubWidget({
            widget: components.length ? components[k] : { type: 'carousel_product_card' },
            isLoading: true,
          })}
        </CarouselItem>
      ))}
    </Carousel>
  </CarouselWidgetWrapper>
);
