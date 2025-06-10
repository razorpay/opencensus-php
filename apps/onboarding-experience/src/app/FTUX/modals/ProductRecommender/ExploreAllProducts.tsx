import React from 'react';
import { Button, Box } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';
import { useModalComponents } from '@libs/shared-ui';
import { ALL_PRODUCTS } from '@FTUX/constants/products';
import SelectableOptionCard from '@OnboardingExperienceCommons/components/SelectableOptionCard';
import { zIndicesMap } from '@OnboardingExperienceCommons/constants/config';

interface ExploreAllProductsProps {
  onDismiss: () => void;
  handleMoveToCategories: () => void;
}

const ExploreAllProducts = ({ onDismiss, handleMoveToCategories }: ExploreAllProductsProps) => {
  const isMobile = isMobileDevice();
  const { Modal, ModalHeader, ModalFooter, ModalBody } = useModalComponents(isMobile);

  return (
    <Modal
      isOpen={true}
      onDismiss={onDismiss}
      size="large"
      snapPoints={[0.5, 0.7, 0.85]}
      zIndex={zIndicesMap.modal}
    >
      <ModalHeader title="Explore all products" subtitle="Select the product best for your needs" />
      <ModalBody>
        <Box display="grid" gap="spacing.4" gridTemplateColumns={{ base: '1fr', l: '1fr 1fr' }}>
          {ALL_PRODUCTS.map((product) => (
            <SelectableOptionCard
              key={product.title}
              title={product.title}
              subTitle={product.description}
              cardImageUrl={product.image}
              handleClick={() => {
                if (!product.linkUrl) return;
                window.open(product.linkUrl, '_blank');
              }}
              imageWidth={isMobile ? '60px' : '120px'}
              imageHeight={isMobile ? '60px' : '120px'}
              containerProps={{
                gap: 'spacing.5',
              }}
            />
          ))}
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end" width="100%">
          <Button onClick={handleMoveToCategories} isFullWidth={isMobile ? true : false}>
            Suggest the right product
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default ExploreAllProducts;
