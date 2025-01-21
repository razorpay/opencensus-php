import React from 'react';
import { Box, Carousel, CarouselItem, Heading } from '@razorpay/blade/components';

import { CarouselWidgetWrapper } from 'merchant/widgets/Carousel/styled';
import { getSubWidget } from 'merchant/widgets/Carousel/utils';

interface CarouselWidgetLoaderProps {
  title: string;
  components: Array<{ type: string; id: string; styles?: Record<string, any> }>;
}

export const CarouselWidgetLoader = ({ title, components }: CarouselWidgetLoaderProps) => (
  <CarouselWidgetWrapper data-testid="product-card-loader">
    {title ? (
      <Box display="flex" gap="spacing.2" marginBottom="spacing.6">
        <Heading size="medium">{title}</Heading>
      </Box>
    ) : null}
    <Carousel carouselItemWidth="284px" visibleItems="autofit" navigationButtonPosition="side">
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
