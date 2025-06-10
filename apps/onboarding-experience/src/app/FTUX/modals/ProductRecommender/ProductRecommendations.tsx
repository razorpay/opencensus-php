import React from 'react';
import { Button, Box } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';
import { useModalComponents } from '@libs/shared-ui';
import ImagePostCard from '@OnboardingExperienceCommons/components/ImagePostCard';
import { AVAILABLE_PRODUCTS_MAP, PRODUCT_TYPES } from '@FTUX/constants/products';
import { zIndicesMap } from '@OnboardingExperienceCommons/constants/config';

interface ProductRecommendationsProps {
  productsList: PRODUCT_TYPES[];
  onDismiss: () => void;
  onBackClick: () => void;
  onExploreAllProducts: () => void;
}

const ProductRecommendations = ({
  productsList,
  onDismiss,
  onBackClick,
  onExploreAllProducts,
}: ProductRecommendationsProps) => {
  const isMobile = isMobileDevice();
  const { Modal, ModalHeader, ModalFooter, ModalBody } = useModalComponents(isMobile);

  return (
    <Modal
      isOpen={true}
      onDismiss={onDismiss}
      size="medium"
      snapPoints={[0.5, 0.7, 0.85]}
      zIndex={zIndicesMap.modal}
    >
      <ModalHeader
        title="Recommendation for you"
        subtitle="If this isn't a right fit, you can explore all other products"
      />
      <ModalBody>
        <Box display="grid" gap="spacing.4" gridTemplateColumns={{ base: '1fr', l: '1fr 1fr' }}>
          {productsList.map((product) => {
            const productDetails = AVAILABLE_PRODUCTS_MAP[product];

            return (
              <ImagePostCard
                key={productDetails.title}
                imageHeight="215px"
                maxContentHeight="small"
                elevateCard
                {...productDetails}
              />
            );
          })}
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box
          display="flex"
          flexDirection={{ base: 'column', m: 'row' }}
          justifyContent="flex-end"
          width="100%"
          gap="spacing.4"
        >
          <Button
            onClick={onExploreAllProducts}
            isFullWidth={isMobile ? true : false}
            variant="tertiary"
          >
            Explore 10+ Other Products
          </Button>
          <Button isFullWidth={isMobile ? true : false} onClick={onBackClick}>
            Check for a different use-case
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default ProductRecommendations;
