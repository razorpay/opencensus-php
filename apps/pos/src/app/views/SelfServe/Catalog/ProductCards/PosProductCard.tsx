import React, { useState } from 'react';
import { Box, Text, Link, Heading } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import { useNavigate } from 'react-router-dom';

import AddToCartButton from 'apps/pos/src/app/views/SelfServe/Cart/AddToCartButton';
import OfferStrip from 'apps/pos/src/app/views/SelfServe/Catalog/OfferStrip';
import PricingDescription from 'apps/pos/src/app/views/SelfServe/Catalog/ProductCards/PricingDescription';
import { STANDEEANDSTICKER } from 'apps/pos/src/app/views/SelfServe/constants';
import { getPricingByProduct } from 'apps/pos/src/app/views/SelfServe/helpers';
import { ProductDescription, ProductPlans } from 'apps/pos/src/app/views/SelfServe/types';

import {
  AndroidPosMiniEllipse1,
  AndroidPosMiniEllipse2,
  AndroidPosMiniEllipse3,
  MobilePosCardEllipse1,
  MobilePosCardEllipse2,
  PosProductCardContainer,
  PosProductCardLeftImageAnimate,
  StyledCardFooter,
  StyledProductCardImage,
} from './styles';

export type Variant = 'left' | 'right';
export type PricingType =
  | 'setup-fee'
  | 'monthly-rental'
  | 'free-item'
  | 'lifetime-pricing'
  | 'no-offer-pricing';
export interface PosPricingDescription {
  type: PricingType;
  amount: {
    offer: {
      prevMonthly: number;
      prevSetupFee: number;
      prevLifetime: number;
      nextMonthly: number;
    } | null;
    lifetime?: number;
    monthly?: number;
    setupFee?: number;
  } | null;
}

export interface CtaType {
  primary: {
    title: string;
    onClick: () => void;
  };
  secondary?: {
    title: string;
    onClick: () => void;
  };
}
export interface PosProductCardProps {
  productDescription?: ProductDescription | null;
  title: string;
  description: string;
  pricingDescription: PosPricingDescription[];
  cta: CtaType;
  imageSrc: any;
  footer?: {
    title: string;
    description: string;
  };
  tncText: string;
  plan: ProductPlans;
  variant: Variant;
}

const PosProductCard = ({
  productDescription,
  title,
  description,
  imageSrc,
  tncText,
  plan,
  variant,
  pricingDescription,
  cta,
  footer,
}: PosProductCardProps): JSX.Element | null => {
  const [isHovered, setIsHovered] = useState(false);
  const navigate = useNavigate();

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

  const isPartnerPricing = productDescription?.isPartnerPricing;
  const getTestId = (title) => {
    const productTitle = title.toLowerCase().replaceAll(' ', '-');
    return `${productTitle}-product-card`;
  };
  return (
    <PosProductCardContainer
      isHovered={isHovered}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      data-testid={getTestId(title)}
      hasFooter={!!footer}
    >
      <Box flex={1}>
        <Box
          padding={['spacing.5', 'spacing.0', 'spacing.7', 'spacing.0']}
          position="relative"
          zIndex="4"
        >
          <Box zIndex="4" position="absolute" top="spacing.5" left="spacing.5">
            {productDescription?.offer ? (
              <OfferStrip
                text={
                  isPartnerPricing
                    ? productDescription?.offer.partnerOfferText
                    : productDescription?.offer.offerText
                }
                type="light"
                isPartnerPricing={isPartnerPricing}
              />
            ) : null}
          </Box>
          <Box
            display="flex"
            flexDirection={variant === 'left' ? 'row' : 'row-reverse'}
            justifyContent="center"
            zIndex="4"
          >
            <Box>
              <PosProductCardLeftImageAnimate isHovered={isHovered} variant={variant}>
                <StyledProductCardImage src={imageSrc} alt={`${title} image`} />
              </PosProductCardLeftImageAnimate>
            </Box>
            <Box
              display="flex"
              flexDirection="column"
              justifyContent="center"
              flex={2}
              padding={['88px', 'spacing.5', 'spacing.0', 'spacing.7']}
              zIndex="4"
              minWidth="285px"
            >
              <Box flex={1}>
                <Heading size="medium">{title}</Heading>
                <Text color="surface.text.gray.subtle">{description}</Text>
                <Box marginTop={{ base: 'spacing.5', xl: 'spacing.5' }}>
                  <PricingDescription
                    monthly={monthly}
                    setupFee={setupFee}
                    lifetime={lifetime}
                    isValidOffer={isValidOffer}
                    isPartnerPricing={isPartnerPricing}
                    tncText={tncText}
                    pricingDescription={pricingDescription}
                    hasOnlyLifetimePricing={hasOnlyLifetimePricing}
                    rentalDiscountPeriod={productDescription.rentalDiscountPeriod}
                  />
                </Box>
              </Box>
              <Box>
                <Box display="flex" marginTop="spacing.5" alignItems="center">
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
                  {cta.secondary ? (
                    <Link
                      marginLeft="spacing.5"
                      onClick={() => {
                        cta.secondary?.onClick();
                        analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
                          label: 'Learn More',
                          whatsAppUpdates: 'No',
                          l1FunnelStage: 'Device Exploration',
                          l2FunnelStage: 'POS Catalog',
                          section: 'Devices',
                          subSection: title,
                        });
                        if (productDescription.code !== STANDEEANDSTICKER.code) {
                          navigate(`/pos/catalog/${productDescription.code}`);
                        }
                      }}
                    >
                      {cta.secondary.title}
                    </Link>
                  ) : null}
                </Box>
              </Box>
            </Box>
          </Box>
        </Box>
        {variant === 'left' ? (
          <Box>
            <AndroidPosMiniEllipse3 isHovered={isHovered} />
            <AndroidPosMiniEllipse2 isHovered={isHovered} />
            <AndroidPosMiniEllipse1 />
          </Box>
        ) : (
          <Box>
            <MobilePosCardEllipse2 isHovered={isHovered} />
            <MobilePosCardEllipse1 />
          </Box>
        )}
      </Box>
      {footer ? (
        <StyledCardFooter>
          {footer.title ? (
            <Box minWidth={'fit-content'}>
              <Heading
                testID="pos-catalog-card-footer-title"
                color="interactive.text.staticWhite.normal"
                weight="semibold"
                size="xlarge"
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
    </PosProductCardContainer>
  );
};

export default PosProductCard;
