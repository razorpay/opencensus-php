import React from 'react';
import { Box, Carousel, CarouselItem } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import { Gallery } from 'merchant/views/POS/types';

import { SelectedImageContainer } from './styles';

type ProductGalleryCarousel = {
  gallery: Gallery[];
  productTitle: string;
};

const ProductGalleryCarousel = ({ gallery, productTitle }: ProductGalleryCarousel): JSX.Element => {
  const photos: string[] = gallery.map(({ mobile }) => mobile);
  const onCarouselItemChange = (slideIndex: number) => {
    analytics.track_EXPERIMENTAL(SignUpEvents.carouselScrolled, {
      label: slideIndex + 1,
      scrollMax: slideIndex,
      section: 'Device',
      subSection: productTitle,
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Product Description',
    });
  };

  return (
    <Box minHeight="300px">
      {photos?.length && (
        <Carousel onChange={onCarouselItemChange}>
          {photos.map((photo, index) => (
            <CarouselItem key={`${photo}-${index}`}>
              <SelectedImageContainer key={photo} isCarousel>
                <img src={photo} alt="product image carousel" />
              </SelectedImageContainer>
            </CarouselItem>
          ))}
        </Carousel>
      )}
    </Box>
  );
};

export default ProductGalleryCarousel;
