import React, { useEffect } from 'react';
import { Box, Modal, ModalHeader, ModalBody } from '@razorpay/blade/components';
// Keeping this as it will be used in the future
// import CarouselCard from './CarouselCard';
import BentoBox from './BentoBox';
import {
  versionReleases,
  productUpdates,
  versioningUpgradeConfig,
  versioningCatchUpConfig,
} from './data';
import { trackProductVersioningWidgetLoaded } from './track';
import { DASHBOARD_ZINDEX_MAP } from '@libs/shared-utils';
import { BackroundWrapper } from './styled';
import SectionCard from './SectionCard';

// TODO: use bento cards from API response in future
const versionProductNames = versionReleases.map((card) => card.id).join(' ');
const versionProductCount = versionReleases.length;
const productUpdateNames = productUpdates.map((card) => card.id).join(' ');
const productUpdateCount = productUpdates.length;

const VersioningModal = ({ isOpen, closeModal }) => {
  useEffect(() => {
    if (isOpen) {
      // Track version releases
      trackProductVersioningWidgetLoaded({
        productListVisible: versionProductNames,
        numberOfProducts: versionProductCount,
        cardSetType: 'versionUpdates',
        // screen,
      });

      // Track product updates
      trackProductVersioningWidgetLoaded({
        productListVisible: productUpdateNames,
        numberOfProducts: productUpdateCount,
        cardSetType: 'productUpdates',
        // screen,
      });
    }
  }, [isOpen]);

  return (
    <Modal
      isOpen={isOpen}
      onDismiss={closeModal}
      size="large"
      accessibilityLabel="What's new in Razorpay Summer Edition 2025"
      zIndex={DASHBOARD_ZINDEX_MAP.modal}
    >
      <ModalHeader title="" />
      <ModalBody padding="spacing.0">
        <BackroundWrapper>
          <SectionCard config={versioningUpgradeConfig} marginBottom={true} />
          {/* <Carousel visibleItems={1} navigationButtonPosition="bottom">

            <CarouselItem>
              <CarouselCard
                tag="PAYMENTS"
                title="Razorpay is the best way to go international"
                description="Open up new revenue streams by accepting international payments in nearly 100 foreign currencies."
                image={HeroImage1}
                imageAlt="Hero Card 1"
                buttonLabel="Try it now"
                onButtonClick={() => { }}
              />
            </CarouselItem>

            <CarouselItem>
              <CarouselCard
                tag="RAZORPAYX"
                title="A Fully Integrated Source to Pay Solution"
                description="Connect, automate and integrate your accounts payables and vendor payments."
                buttonLabel="Try it now"
                onButtonClick={() => { }}
                image={HeroImage1}
                imageAlt="Hero Card 2"
              />
            </CarouselItem>
          </Carousel> */}

          <BentoBox bentoCards={versionReleases} cardSetType="versionUpdates" />
          <SectionCard config={versioningCatchUpConfig} marginTop={true} marginBottom={true} />
          <BentoBox bentoCards={productUpdates} cardSetType="productUpdates" />
        </BackroundWrapper>
      </ModalBody>
    </Modal>
  );
};

export default VersioningModal;
