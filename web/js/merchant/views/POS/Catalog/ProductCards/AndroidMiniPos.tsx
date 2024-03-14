import React, { useContext, useState } from 'react';
import { Box, Text, Link, Amount, Title } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import { useNavigate } from 'react-router-dom';

import AndroidMiniPosImage from 'assets/pos/main-banner/minipos.webp';
import AddToCartButton from 'merchant/views/POS/Cart/AddToCartButton';
import OfferStrip from 'merchant/views/POS/Catalog/OfferStrip';
import { PartnerExclusivePriceContainer } from 'merchant/views/POS/PartnerExclusiveContainer';
import AmountWithStrikeThrough from 'merchant/views/POS/ProductDescription/ProductPriceCards/AmountWithStrikeThrough';
import { PRODUCT_PLANS, ANDROID_MINI_POS } from 'merchant/views/POS/constants';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { getPricingByProduct, getProductFromProductDescriptions } from 'merchant/views/POS/helpers';

import {
  AndroidPosMiniEllipse1,
  AndroidPosMiniEllipse2,
  AndroidPosMiniEllipse3,
  AndroidSmartMiniPosContainer,
  ProductCardLeftImageAnimate,
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

  const { monthly, setupFee, offer } = getPricingByProduct({ productDescription });

  const isValidOffer =
    productDescription?.offer &&
    offer &&
    offer?.prevMonthly !== null &&
    offer?.prevSetupFee !== null &&
    offer?.nextMonthly !== null;

  const isPartnerPricing = productDescription?.isPartnerPricing;
  return (
    <AndroidSmartMiniPosContainer
      isHovered={isHovered}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      data-testid="android-mini-pos-product-card"
    >
      <Box
        width="100%"
        height="100%"
        maxWidth="700px"
        position="relative"
        display="flex"
        alignItems="center"
        zIndex="4"
      >
        <Box
          zIndex="1"
          width="50%"
          height="100%"
          display="flex"
          flexDirection="column"
          justifyContent="space-between"
          paddingX="spacing.5"
        >
          <Box marginTop="spacing.4" padding="spacing.2">
            {productDescription?.offer ? (
              <OfferStrip
                text={
                  isPartnerPricing
                    ? productDescription.offer.partnerOfferText
                    : productDescription.offer.offerText
                }
                type="light"
                isPartnerPricing={isPartnerPricing}
              />
            ) : null}
          </Box>
          <ProductCardLeftImageAnimate
            isHovered={isHovered}
            isOfferExits={!!productDescription?.offer}
          >
            <StyledProductCardImage src={AndroidMiniPosImage} alt="android smart mini image" />
          </ProductCardLeftImageAnimate>
        </Box>
        <Box
          height="100%"
          display="flex"
          flexDirection="column"
          justifyContent="center"
          zIndex={4}
          padding="spacing.5"
        >
          <Title>{productDescription.productTitle}</Title>
          <Text color="surface.text.subtle.lowContrast" weight="bold">
            Feature packed and portable
          </Text>
          <Box marginTop={{ base: 'spacing.4', xl: 'spacing.11' }}>
            <PartnerExclusivePriceContainer
              isPartnerPricing={isPartnerPricing && !isValidOffer}
              type="PRODUCT_CARD"
            >
              {isValidOffer ? (
                <Box>
                  <Text marginBottom="spacing.2" testID="monthy-pricing-text">
                    <Amount
                      value={offer.nextMonthly}
                      suffix="none"
                      size="heading-small-bold"
                      isAffixSubtle={false}
                    />{' '}
                    <AmountWithStrikeThrough value={offer.prevMonthly} size="body-medium-bold" />{' '}
                    /month after 3 months*
                  </Text>
                  <Text marginBottom="spacing.3" testID="setup-pricing-text">
                    <Amount
                      value={setupFee}
                      suffix="none"
                      size="heading-small-bold"
                      isAffixSubtle={false}
                    />{' '}
                    <AmountWithStrikeThrough value={offer.prevSetupFee} size="body-medium-bold" />{' '}
                    setup fee
                  </Text>
                </Box>
              ) : (
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
              )}
              <Text color="surface.text.subtle.lowContrast">*Lifetime Pricing also available.</Text>
            </PartnerExclusivePriceContainer>
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
      <Box>
        <AndroidPosMiniEllipse3 isHovered={isHovered} />
        <AndroidPosMiniEllipse2 isHovered={isHovered} />
        <AndroidPosMiniEllipse1 />
      </Box>
    </AndroidSmartMiniPosContainer>
  );
};

export default AndroidMiniPos;
