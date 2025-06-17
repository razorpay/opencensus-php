import React, { useContext, useState } from 'react';
import { Box, Text, Link, Amount, Heading } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import { useNavigate } from 'react-router-dom';

import MobilePosImage from 'assets/pos/main-banner/mpos.webp';
import { useSplitzService } from 'common/splitz';
import AddToCartButton from 'apps/pos/src/app/views/SelfServe/Cart/AddToCartButton';
import { PartnerExclusivePriceContainer } from 'apps/pos/src/app/views/SelfServe/PartnerExclusiveContainer';
import { PRODUCT_DESCRIPTIONS, PRODUCT_PLANS } from 'apps/pos/src/app/views/SelfServe/constants';
import { PosDeviceStoreContext } from 'apps/pos/src/app/views/SelfServe/context';
import {
  getPricingByProduct,
  getProductFromProductDescriptions,
  fetchProductOffers,
} from 'apps/pos/src/app/views/SelfServe/helpers';

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
  const { abExperiments } = useSplitzService();
  const { isEnabled: isOfferEnabled } = fetchProductOffers({ abExperiments });
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
            <Heading size="large">{productDescription.productTitle}</Heading>
            <Text color="surface.text.gray.subtle" weight="semibold">
              Pocket-sized and affordable
            </Text>
            <Box marginTop={{ xl: 'spacing.11', base: 'spacing.4' }}>
              <PartnerExclusivePriceContainer
                isPartnerPricing={productDescription?.isPartnerPricing && !isOfferEnabled}
                type="PRODUCT_CARD"
              >
                <Text weight="semibold" testID="pricing-details-text">
                  <Amount
                    value={monthly}
                    suffix="none"
                    isAffixSubtle={false}
                    type="body"
                    size="medium"
                    weight="semibold"
                  />{' '}
                  /month +{' '}
                  <Amount
                    value={setupFee}
                    suffix="none"
                    isAffixSubtle={false}
                    type="body"
                    size="medium"
                    weight="semibold"
                  />{' '}
                  setup fee
                </Text>
                <Text color="surface.text.gray.subtle">*Lifetime Pricing also available.</Text>
              </PartnerExclusivePriceContainer>
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
