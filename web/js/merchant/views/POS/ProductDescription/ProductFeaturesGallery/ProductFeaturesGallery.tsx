import React from 'react';
import { Box, Heading, Title } from '@razorpay/blade/components';

import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import { FeatureGallery } from 'merchant/views/POS/types';
import { useScrollObserver } from 'merchant/views/POS/utils/ScrollObserver';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

type ProductFeatureGallery = {
  featureGallery: FeatureGallery[];
};

const ProductFeaturesGallery = ({ featureGallery }: ProductFeatureGallery): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();
  const foldRef = React.useRef<HTMLDivElement>(null);

  useScrollObserver(foldRef, () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageSectionViewed, {
      sectionNumber: 2,
      section: 'Product Features Gallery',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Product Description',
    });
  });

  return (
    <Box marginTop="spacing.10" ref={foldRef}>
      {featureGallery.map(({ image, title, description, isImageFirst }) => (
        <Box
          key={title}
          display={{ base: 'block', l: 'flex' }}
          flexDirection={isImageFirst ? 'row' : 'row-reverse'}
        >
          <Box flex="0 0 50%">
            <img src={image} height="100%" width="100%" alt={title} />
          </Box>
          <Box
            flex="0 0 50%"
            paddingY="spacing.11"
            paddingX={{ base: 'spacing.2', l: 'spacing.11' }}
            display="flex"
            alignItems="center"
            justifyContent="center"
          >
            <Box marginX="spacing.4">
              <Title size="large" type="subtle" textAlign={isMobile ? 'center' : 'left'}>
                {title}
              </Title>
              <Heading
                weight="regular"
                size="medium"
                marginTop="spacing.4"
                type="subtle"
                textAlign={isMobile ? 'center' : 'left'}
              >
                {description}
              </Heading>
            </Box>
          </Box>
        </Box>
      ))}
    </Box>
  );
};

export default ProductFeaturesGallery;
