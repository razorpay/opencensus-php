import React, { useState } from 'react';
import { Button, Box } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';
import { useModalComponents } from '@libs/shared-ui';
import SelectableOptionCard from '@OnboardingExperienceCommons/components/SelectableOptionCard';
import { PRODUCT_CATEGORIES } from '@FTUX/constants/products';
import { zIndicesMap } from '@OnboardingExperienceCommons/constants/config';

const ProductCategories = ({
  onDismiss,
  makeSelection,
  onExploreAllProducts,
}: {
  onDismiss: () => void;
  makeSelection: (product: number) => void;
  onExploreAllProducts: () => void;
}) => {
  const isMobile = isMobileDevice();
  const { Modal, ModalHeader, ModalFooter, ModalBody } = useModalComponents(isMobile);

  const [selectedProduct, setSelectedProduct] = useState<number | null>(null);

  return (
    <Modal
      isOpen={true}
      onDismiss={onDismiss}
      size="medium"
      snapPoints={[0.5, 0.7, 0.85]}
      zIndex={zIndicesMap.modal}
    >
      <ModalHeader
        title="Select your use-case"
        subtitle="Based on your selection we will recommend the right product for you"
      />
      <ModalBody>
        <Box display="grid" gap="spacing.4" gridTemplateColumns={{ base: '1fr', l: '1fr 1fr' }}>
          {PRODUCT_CATEGORIES.map((product, index) => (
            <SelectableOptionCard
              key={product.description}
              subTitle={product.description}
              cardImageUrl={product.image}
              handleClick={() => {
                setSelectedProduct(index);
              }}
              imageWidth="60px"
              imageHeight="60px"
              containerProps={{
                gap: 'spacing.5',
              }}
              isSelected={selectedProduct === index}
            />
          ))}
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
          <Button
            isFullWidth={isMobile ? true : false}
            isDisabled={selectedProduct === null}
            onClick={() => makeSelection(selectedProduct ?? 0)}
          >
            Suggest the right product
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default ProductCategories;
