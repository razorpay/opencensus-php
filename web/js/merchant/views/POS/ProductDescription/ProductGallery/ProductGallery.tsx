import React, { useState } from 'react';
import { Box } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import Thumbnails from './Thumbnails';
import { SelectedImageContainer } from './styles';

type ProductGallery = {
  gallery: {
    thumbnail: string;
    main: string;
  }[];
  productTitle: string;
};

const ProductGallery = ({ gallery, productTitle }: ProductGallery): JSX.Element => {
  const [selectedImageIndex, setSelectedImageIndex] = useState<number>(0);
  const thumbnailsArr = gallery.map(({ thumbnail }) => thumbnail);
  const selectedImageUrl = gallery[selectedImageIndex].main;
  const handleOnThumbnailClick = (index: number) => {
    analytics.track_EXPERIMENTAL(SignUpEvents.imageViewed, {
      section: 'Device',
      subSection: productTitle,
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Product Description',
    });
    setSelectedImageIndex(index);
  };

  return (
    <Box
      display="flex"
      maxHeight={{ base: '560px', xl: '40vw' }}
      maxWidth={{ base: '50%', m: '545px' }}
      testID="product-gallery-container"
      minWidth="400px"
      marginRight={{ base: '0px', l: 'spacing.8' }}
    >
      <Thumbnails thumbnails={thumbnailsArr} onClick={handleOnThumbnailClick} />
      <SelectedImageContainer>
        <img src={selectedImageUrl} alt={`Product image-${selectedImageIndex}`} />
      </SelectedImageContainer>
    </Box>
  );
};

export default ProductGallery;
