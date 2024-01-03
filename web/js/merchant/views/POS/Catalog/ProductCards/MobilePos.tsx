import React, { useContext, useState } from 'react';
import { Box, Text, Link, Amount, Title } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import MobilePosImage from 'assets/pos/main-banner/mpos.webp';
import AddToCartButton from 'merchant/views/POS/Cart/AddToCartButton';
import { PRODUCT_DESCRIPTIONS, PRODUCT_PLANS } from 'merchant/views/POS/constants';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { getPricingByProduct, getProductFromProductDescriptions } from 'merchant/views/POS/helpers';

import {
  MobilePosCardEllipse1,
  MobilePosCardEllipse2,
  MobilePosCardImage,
  MobilePosContainer,
  StyledProductCardImage,
} from './styles';

const MobilePos = (): JSX.Element | null => {
  const [isHovered, setIsHovered] = useState(false);
  const { state } = useContext(PosDeviceStoreContext);
  const navigate = useNavigate();

  const { productDescriptions } = state;
  const productDescription = getProductFromProductDescriptions({
    code: PRODUCT_DESCRIPTIONS.d180.code,
    productDescriptions,
  });

  if (!productDescription || !productDescription.pricing) return null;

  const { monthly, setupFee } = getPricingByProduct({ productDescription });

  return (
    <MobilePosContainer
      isHovered={isHovered}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      data-testid="mobile-pos-product-card"
    >
      <Box width="100%" maxWidth="500px" minHeight="100%" position="relative">
        <Box
          maxWidth="80%"
          height="100%"
          position="absolute"
          display="flex"
          flexDirection="column"
          justifyContent="center"
          alignItems="end"
          zIndex={4}
          left="spacing.0"
          padding="spacing.5"
        >
          <Box>
            <Title>Mobile POS (mPOS)</Title>
            <Text color="surface.text.subtle.lowContrast" weight="bold">
              Pocket-sized and affordable
            </Text>
            <Box marginTop={{ xl: 'spacing.11', base: 'spacing.4' }}>
              <Text weight="bold" testID="pricing-details-text">
                <Amount
                  value={monthly}
                  suffix="none"
                  size="body-medium-bold"
                  isAffixSubtle={false}
                />{' '}
                /month +{' '}
                <Amount
                  value={setupFee}
                  suffix="none"
                  size="body-medium-bold"
                  isAffixSubtle={false}
                />{' '}
                setup fee
              </Text>
              <Text color="surface.text.subtle.lowContrast">*Lifetime Pricing also available.</Text>
              <Box display="flex" marginTop="spacing.5" alignItems="center">
                <AddToCartButton
                  productCode={PRODUCT_DESCRIPTIONS.d180.code}
                  plan={PRODUCT_PLANS.MONTHLY}
                  openCartOnUpdate
                  onCtaClick={() => {
                    analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
                      label: 'Add to Cart',
                      whatsAppUpdates: 'No',
                      l1FunnelStage: 'Device Consideration',
                      l2FunnelStage: 'POS Catalog',
                      section: 'Devices',
                      subSection: 'Mobile POS(m-POS)',
                    });
                  }}
                />
                <Link
                  marginLeft="spacing.5"
                  onClick={() => {
                    analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
                      label: 'Learn More',
                      whatsAppUpdates: 'No',
                      l1FunnelStage: 'Device Exploration',
                      l2FunnelStage: 'POS Catalog',
                      section: 'Devices',
                      subSection: 'Mobile POS(m-POS)',
                    });

                    navigate(`/pos/catalog/${PRODUCT_DESCRIPTIONS.d180.code}`);
                  }}
                >
                  Learn More
                </Link>
              </Box>
            </Box>
          </Box>
        </Box>
        <MobilePosCardImage isHovered={isHovered}>
          <StyledProductCardImage src={MobilePosImage} alt="mobile pos image" />
        </MobilePosCardImage>
      </Box>
      <Box>
        <MobilePosCardEllipse2 isHovered={isHovered} />
        <MobilePosCardEllipse1 />
      </Box>
    </MobilePosContainer>
  );
};

export default MobilePos;
