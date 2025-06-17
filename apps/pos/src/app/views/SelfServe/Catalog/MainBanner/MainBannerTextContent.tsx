import React, { useMemo } from 'react';
import {
  Heading,
  Box,
  CreditCardIcon,
  Text,
  Button,
  ArrowUpRightIcon,
  WifiIcon,
  VolumeHighIcon,
  BillIcon,
  Amount,
} from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import AddToCartButton from 'apps/pos/src/app/views/SelfServe/Cart/AddToCartButton';
import { PartnerExclusivePriceContainer } from 'apps/pos/src/app/views/SelfServe/PartnerExclusiveContainer';
import { PRODUCT_PLANS, ANDROID_SMART_POS } from 'apps/pos/src/app/views/SelfServe/constants';
import { isValidFee, getAvailablePricingPlans } from 'apps/pos/src/app/views/SelfServe/helpers';
import { useBladeBreakpoints } from 'apps/pos/src/app/views/SelfServe/hooks';
import { ProductDescription, Prices } from 'apps/pos/src/app/views/SelfServe/types';
import { MainBannerFeaturesContainer, MainBannerFooter } from './styles';

type FEATURE_ITEMS = {
  title: string;
  icon: JSX.Element;
}[];

type MainBannerTextContentProps = {
  product: ProductDescription;
  productPricing: Prices;
  onLearnMoreClick: () => void;
};

const FEATURE_ITEMS: FEATURE_ITEMS = [
  {
    title: 'Accept card and UPI payments',
    icon: <CreditCardIcon color="surface.icon.staticWhite.normal" size="large" />,
  },
  {
    title: 'Uninterrupted connectivity over wifi / sim',
    icon: <WifiIcon color="surface.icon.staticWhite.normal" size="large" />,
  },
  {
    title: 'Instant audio confirmations',
    icon: <VolumeHighIcon color="surface.icon.staticWhite.normal" size="large" />,
  },
  {
    title: 'In-built printer for printing charges slips',
    icon: <BillIcon color="surface.icon.staticWhite.normal" size="large" />,
  },
];

const MainBannerTextContent = ({
  product,
  productPricing,
  onLearnMoreClick,
}: MainBannerTextContentProps): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();
  const { monthly, setupFee, offer, lifetime } = productPricing;
  const { prevMonthly, prevSetupFee, nextMonthly, prevLifetime } = offer || {};
  const { hasMonthlyPlan } = getAvailablePricingPlans(product);

  const isValidOffer = useMemo(
    () =>
      product.offer && [prevMonthly, prevSetupFee, nextMonthly].every((fee) => !!isValidFee(fee)),
    [product, prevMonthly, prevSetupFee, nextMonthly],
  );
  return (
    <Box
      display="flex"
      flexDirection="column"
      justifyContent="center"
      paddingX="spacing.5"
      paddingTop="spacing.8"
    >
      <Heading
        color="surface.text.staticWhite.normal"
        textAlign={isMobile ? 'center' : 'left'}
        size="xlarge"
      >
        {product.productTitle}
      </Heading>
      <Heading
        color="surface.text.gray.subtle"
        textAlign={isMobile ? 'center' : 'left'}
        size="small"
      >
        All-in-one POS to support all your payment needs
      </Heading>
      {!isMobile ? (
        <MainBannerFeaturesContainer>
          {FEATURE_ITEMS.map(({ title, icon }) => (
            <Box display="flex" alignItems="center" key={title} marginY="spacing.4">
              <Box
                backgroundColor="surface.background.primary.subtle"
                display="flex"
                alignItems="center"
                justifyContent="center"
                padding="spacing.3"
                borderRadius="small"
              >
                {icon}
              </Box>
              <Text
                weight="regular"
                color="surface.text.staticWhite.subtle"
                marginLeft="spacing.5"
                testID="main-banner-features"
                marginRight="spacing.5"
                size="large"
              >
                {title}
              </Text>
            </Box>
          ))}
        </MainBannerFeaturesContainer>
      ) : null}
      <MainBannerFooter>
        <PartnerExclusivePriceContainer
          isPartnerPricing={product?.isPartnerPricing && !isValidOffer}
          type="MAIN_BANNER"
          isMobile={isMobile}
        >
          {isValidOffer ? (
            <Box display={{ base: 'block', l: 'flex' }}>
              <Heading
                color="surface.text.gray.subtle"
                size={isMobile ? 'medium' : 'small'}
                textAlign={isMobile ? 'center' : 'left'}
                marginRight="spacing.6"
                weight="regular"
              >
                <Amount
                  value={hasMonthlyPlan ? (nextMonthly as number) : lifetime}
                  suffix="none"
                  isAffixSubtle={false}
                  testID="monthly-amount"
                  type="heading"
                  size="medium"
                  weight="semibold"
                />
                {'  '}
                {(hasMonthlyPlan && prevMonthly) || (!hasMonthlyPlan && prevLifetime) ? (
                  <Amount
                    value={hasMonthlyPlan ? (prevMonthly as number) : (prevLifetime as number)}
                    testID="prev-monthly"
                    isAffixSubtle={false}
                    suffix="none"
                    marginRight="spacing.2"
                    isStrikethrough={true}
                    color="surface.text.gray.muted"
                    size="large"
                    weight="semibold"
                  />
                ) : null}
                {hasMonthlyPlan ? '/month after 3 months*' : 'lifetime / one time price'}
              </Heading>
              <Heading
                color="surface.text.gray.subtle"
                size={isMobile ? 'medium' : 'small'}
                textAlign={isMobile ? 'center' : 'left'}
                marginRight="spacing.6"
                weight="regular"
              >
                <Amount
                  value={setupFee}
                  suffix="none"
                  isAffixSubtle={false}
                  testID="setup-amount"
                  type="heading"
                  size="medium"
                  weight="semibold"
                />
                {'  '}
                <Amount
                  size="large"
                  value={prevSetupFee as number}
                  testID="prev-setup"
                  isAffixSubtle={false}
                  suffix="none"
                  marginRight="spacing.2"
                  isStrikethrough={true}
                  color="surface.text.gray.muted"
                  weight="semibold"
                />
                setup fee
              </Heading>
            </Box>
          ) : (
            <Heading
              color="surface.text.staticWhite.normal"
              size={isMobile ? 'medium' : 'small'}
              textAlign={isMobile ? 'center' : 'left'}
            >
              <Amount
                value={hasMonthlyPlan ? monthly : lifetime}
                suffix="none"
                isAffixSubtle={false}
                testID="monthly-amount"
                type="heading"
                size="medium"
                weight="semibold"
              />{' '}
              {hasMonthlyPlan ? 'monthly subscription' : 'lifetime / one time price'}
            </Heading>
          )}

          <Box display={{ base: 'block', m: 'flex' }} alignItems="center" marginBottom="spacing.6">
            {!product.offer && !prevSetupFee && hasMonthlyPlan ? (
              <Text
                color="surface.text.staticWhite.normal"
                size={isMobile ? 'medium' : 'small'}
                textAlign={isMobile ? 'center' : 'left'}
                marginBottom="spacing.1"
                marginRight="spacing.2"
              >
                +{' '}
                <Amount
                  value={setupFee}
                  suffix="none"
                  isAffixSubtle={false}
                  size={isMobile ? 'medium' : 'small'}
                  testID="setup-amount"
                />{' '}
                one time setup fee.
              </Text>
            ) : null}
            {hasMonthlyPlan ? (
              <Text
                size="small"
                color="surface.text.gray.subtle"
                textAlign={isMobile ? 'center' : 'left'}
              >
                *Lifetime Pricing also available.
              </Text>
            ) : null}
            <Text
              size="small"
              color="surface.text.gray.subtle"
              textAlign={isMobile ? 'center' : 'left'}
            >
              *Lifetime Pricing also available.
            </Text>
          </Box>
        </PartnerExclusivePriceContainer>
        <Box
          display="flex"
          position="relative"
          justifyContent={{ base: 'center', m: 'left' }}
          marginBottom={{ base: 'spacing.5', m: 'spacing.8' }}
        >
          <AddToCartButton
            productCode={ANDROID_SMART_POS.code}
            plan={hasMonthlyPlan ? PRODUCT_PLANS.MONTHLY : PRODUCT_PLANS.LIFETIME}
            openCartOnUpdate
            onCtaClick={() => {
              analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
                label: 'Add to Cart',
                whatsAppUpdates: 'No',
                l1FunnelStage: 'Device Consideration',
                l2FunnelStage: 'POS Catalog',
                section: 'Devices',
                subSection: 'Android Smart POS',
              });
            }}
            size="large"
          />
          {!isMobile ? (
            <Button
              size="large"
              type="button"
              variant="tertiary"
              marginLeft="spacing.5"
              icon={ArrowUpRightIcon}
              iconPosition="right"
              onClick={onLearnMoreClick}
            >
              Learn More
            </Button>
          ) : null}
        </Box>
      </MainBannerFooter>
    </Box>
  );
};

export default MainBannerTextContent;
