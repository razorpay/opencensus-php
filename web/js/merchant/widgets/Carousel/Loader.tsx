import React from 'react';
import { Box, Carousel, CarouselItem, Heading } from '@razorpay/blade/components';

import { CarouselWidgetWrapper } from 'merchant/widgets/Carousel/styled';
import { CarouselWidgetProps } from 'merchant/widgets/Carousel/types';
import { getSubWidget } from 'merchant/widgets/Carousel/utils';

import { getWidgetStyles } from '../common/utils';

interface CarouselWidgetLoaderProps {
  title: string;
  components: Array<{ type: string; id: string; styles?: Record<string, any> }>;
  styles: CarouselWidgetProps['styles'];
}

export const CarouselWidgetLoader = ({ title, components, styles }: CarouselWidgetLoaderProps) => (
  <CarouselWidgetWrapper width={styles?.width} hide={!title} data-testid="product-card-loader">
    {title ? (
      <Box display="flex" gap="spacing.2" marginBottom="spacing.6">
        <Heading size="medium">{title}</Heading>
      </Box>
    ) : null}
    <Carousel
      carouselItemWidth={styles?.inherited_styles?.carousel_product_card?.width ?? '284px'}
      visibleItems="autofit"
      navigationButtonPosition="side"
    >
      {Array.from({ length: components.length ? components.length : 4 }, (v, k) => (
        <CarouselItem key={k}>
          {getSubWidget({
            widget: components.length
              ? {
                  ...components[k],
                  styles: getWidgetStyles(
                    styles?.inherited_styles?.[components[k].type],
                    components[k].styles,
                  ),
                }
              : {
                  type: 'carousel_product_card',
                  styles: getWidgetStyles(styles?.inherited_styles?.carousel_product_card),
                },
            isLoading: true,
          })}
        </CarouselItem>
      ))}
    </Carousel>
  </CarouselWidgetWrapper>
);
