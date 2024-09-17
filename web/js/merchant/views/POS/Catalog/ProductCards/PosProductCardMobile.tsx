import React from 'react';
import { Box, Heading, Text } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import MainBannerBackdropImage from 'assets/pos/main-banner/mainbannerbackground.webp';
import { useNavigate } from 'react-router-dom';

import { useSplitzService } from 'common/splitz';
import AddToCartButton from 'merchant/views/POS/Cart/AddToCartButton';
import OfferStrip from 'merchant/views/POS/Catalog/OfferStrip';
import { PosPricingDescription } from 'merchant/views/POS/Catalog/ProductCards/PosProductCard';
import { PartnerExclusivePriceContainer } from 'merchant/views/POS/PartnerExclusiveContainer';
import { STANDEEANDSTICKER } from 'merchant/views/POS/constants';
import { getPricingByProduct, fetchProductOffers } from 'merchant/views/POS/helpers';
import { ProductDescription, ProductPlans } from 'merchant/views/POS/types';

import PricingDescription from './PricingDescription';
import { MobileEllpise, StyledCardFooter, StyledProductCardImage } from './styles';

export interface PosProductCardProps {
  productDescription?: ProductDescription | null;
  title: string;
  description: string;
  pricingDescription: PosPricingDescription[];
  cta: {
    primary: {
      title: string;
      onClick: () => void;
    };
    secondary?: {
      title: string;
      onClick: () => void;
    };
  };
  imageSrc: any;
  footer?: {
    title: string;
    description: string;
  };
  tncText: string;
  plan: ProductPlans;
}

const PosProductCardMobile = ({
  productDescription,
  title,
  description,
  imageSrc,
  tncText,
  plan,
  pricingDescription,
  cta,
  footer,
}: PosProductCardProps): JSX.Element | null => {
  const navigate = useNavigate();

  const { abExperiments } = useSplitzService();
  const { isEnabled: isOfferEnabled } = fetchProductOffers({ abExperiments });
  if (!productDescription || !productDescription.pricing) return null;

  const { setupFee, offer, lifetime, monthly } = getPricingByProduct({ productDescription });
  const hasOnlyLifetimePricing =
    productDescription.pricing?.length === 1 && productDescription.pricing[0].type === 'lifetime';
  const isValidOffer =
    productDescription?.offer &&
    offer &&
    offer?.prevMonthly !== null &&
    offer?.prevSetupFee !== null &&
    offer?.nextMonthly !== null;

  const handleOnCardClick = () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
      label: 'Learn More',
      whatsAppUpdates: 'No',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Catalog',
      section: 'Devices',
      subSection: title,
    });
    cta.secondary?.onClick();
    if (productDescription.code !== STANDEEANDSTICKER.code) {
      navigate(`/pos/catalog/${productDescription.code}`);
    }
  };

  const isPartnerPricing = productDescription?.isPartnerPricing;
  return (
    <div onClick={handleOnCardClick}>
      <Box
        backgroundColor="surface.background.gray.subtle"
        paddingTop="spacing.8"
        borderRadius="large"
        position="relative"
        overflow="hidden"
        marginBottom="spacing.5"
      >
        {productDescription?.offer ? (
          <Box marginBottom="spacing.5">
            <OfferStrip
              text={
                isPartnerPricing
                  ? productDescription.offer.partnerOfferText
                  : productDescription.offer.offerText
              }
              type="light"
              isPartnerPricing={isPartnerPricing}
            />
          </Box>
        ) : null}
        <Heading textAlign="center" size="xlarge">
          {title}
        </Heading>
        <Heading
          textAlign="center"
          marginBottom="spacing.8"
          size="small"
          color="surface.text.gray.muted"
        >
          {description}
        </Heading>
        <PartnerExclusivePriceContainer
          isPartnerPricing={isPartnerPricing && !isOfferEnabled}
          type="PRODUCT_CARD"
          isMobileCard
        >
          <Box textAlign="center" marginTop={{ base: 'spacing.5', xl: 'spacing.5' }}>
            <PricingDescription
              monthly={monthly}
              setupFee={setupFee}
              isValidOffer={isValidOffer}
              lifetime={lifetime}
              isPartnerPricing={isPartnerPricing}
              tncText={tncText}
              pricingDescription={pricingDescription}
              hasOnlyLifetimePricing={hasOnlyLifetimePricing}
              rentalDiscountPeriod={productDescription.rentalDiscountPeriod}
            />
          </Box>
        </PartnerExclusivePriceContainer>
        <Box
          display="flex"
          justifyContent="center"
          zIndex={1}
          position="relative"
          marginBottom="spacing.10"
        >
          <AddToCartButton
            productCode={productDescription.code}
            plan={plan}
            openCartOnUpdate
            btnText={cta.primary.title}
            onCtaClick={() => {
              cta.primary.onClick();
              analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
                label: 'Add to Cart',
                whatsAppUpdates: 'No',
                l1FunnelStage: 'Device Consideration',
                l2FunnelStage: 'POS Catalog',
                section: 'Devices',
                subSection: title,
              });
            }}
          />
        </Box>
        <Box
          width="100%"
          display="flex"
          alignItems="center"
          flexDirection="column"
          zIndex={1}
          position="relative"
        >
          <Box>
            <StyledProductCardImage src={imageSrc} alt={`${title} image`} />
          </Box>
        </Box>
        {footer ? (
          <StyledCardFooter isMobile>
            {footer.title ? (
              <Box>
                <Heading
                  testID="pos-catalog-card-footer-title"
                  color="interactive.text.staticWhite.normal"
                  weight="semibold"
                  size="large"
                >
                  {footer.title}
                </Heading>
              </Box>
            ) : null}
            {footer.description ? (
              <Box>
                <Text
                  testID="pos-catalog-card-footer-description"
                  color="interactive.text.staticWhite.normal"
                >
                  {footer.description}
                </Text>
              </Box>
            ) : null}
          </StyledCardFooter>
        ) : null}
        <Box
          position="absolute"
          maxWidth="800px"
          width="100%"
          height="100%"
          top="0px"
          left="0px"
          backgroundPosition="65% center"
          backgroundSize="cover"
          backgroundRepeat="no-repeat"
          backgroundImage={`url("${MainBannerBackdropImage}")`}
        />
        <MobileEllpise />
      </Box>
    </div>
  );
};

export default PosProductCardMobile;
