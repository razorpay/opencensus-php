import React, { useContext, useState } from 'react';
import { Box, Text, Link, Amount, Title } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import AndroidMiniPosImage from 'assets/pos/main-banner/minipos.webp';
import AddToCartButton from 'merchant/views/POS/Cart/AddToCartButton';
import { PRODUCT_PLANS, ANDROID_MINI_POS } from 'merchant/views/POS/constants';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { getPricingByProduct, getProductFromProductDescriptions } from 'merchant/views/POS/helpers';

import {
  AndroidMiniPosImageContainer,
  AndroidPosMiniEllipse1,
  AndroidPosMiniEllipse2,
  AndroidPosMiniEllipse3,
  AndroidSmartMiniPosContainer,
  StyledProductCardImage,
} from './styles';

const AndroidMiniPos = (): JSX.Element | null => {
  const [isHovered, setIsHovered] = useState(false);
  const { state } = useContext(PosDeviceStoreContext);
  const navigate = useNavigate();

  const { productDescriptions } = state;
  const productDescription = getProductFromProductDescriptions({
    code: ANDROID_MINI_POS.code,
    productDescriptions,
  });

  if (!productDescription || !productDescription.pricing) return null;

  const { monthly, setupFee } = getPricingByProduct({ productDescription });

  return (
    <AndroidSmartMiniPosContainer
      isHovered={isHovered}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      data-testid="android-mini-pos-product-card"
    >
      <Box width="100%" maxWidth="700px" minHeight="100%" position="relative">
        <Box
          height="100%"
          position="absolute"
          left="43%"
          display="flex"
          flexDirection="column"
          justifyContent="center"
          zIndex={4}
          width="55%"
          paddingRight="spacing.5"
        >
          <Box>
            <Title>{productDescription.productTitle}</Title>
            <Text color="surface.text.subtle.lowContrast" weight="bold">
              Feature packed and portable
            </Text>
            <Box marginTop={{ base: 'spacing.4', xl: 'spacing.11' }}>
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
                  productCode={ANDROID_MINI_POS.code}
                  plan={PRODUCT_PLANS.MONTHLY}
                  openCartOnUpdate
                  onCtaClick={() => {
                    analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
                      label: 'Add to Cart',
                      whatsAppUpdates: 'No',
                      l1FunnelStage: 'Device Consideration',
                      l2FunnelStage: 'POS Catalog',
                      section: 'Devices',
                      subSection: 'Android Smart Mini POS',
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
                      subSection: 'Android Smart Mini POS',
                    });

                    navigate(`/pos/catalog/${ANDROID_MINI_POS.code}`);
                  }}
                >
                  Learn More
                </Link>
              </Box>
            </Box>
          </Box>
        </Box>
        <AndroidMiniPosImageContainer isHovered={isHovered}>
          <StyledProductCardImage src={AndroidMiniPosImage} alt="android smart mini image" />
        </AndroidMiniPosImageContainer>
      </Box>
      <Box>
        <AndroidPosMiniEllipse3 isHovered={isHovered} />
        <AndroidPosMiniEllipse2 isHovered={isHovered} />
        <AndroidPosMiniEllipse1 />
      </Box>
    </AndroidSmartMiniPosContainer>
  );
};

export default AndroidMiniPos;
