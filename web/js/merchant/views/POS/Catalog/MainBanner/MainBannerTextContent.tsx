import React from 'react';
import {
  Title,
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

import AddToCartButton from 'merchant/views/POS/Cart/AddToCartButton';
import { PRODUCT_DESCRIPTIONS, PRODUCT_PLANS } from 'merchant/views/POS/constants';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';

import { MainBannerFeaturesContainer, MainBannerFooter } from './styles';

type FEATURE_ITEMS = {
  title: string;
  icon: JSX.Element;
}[];

type MainBannerTextContentProps = {
  setupFee: number;
  monthlyFee: number;
  onLearnMoreClick: () => void;
};

const FEATURE_ITEMS: FEATURE_ITEMS = [
  {
    title: 'Accept card and UPI payments',
    icon: <CreditCardIcon color="feedback.icon.neutral.highContrast" size="large" />,
  },
  {
    title: 'Uninterrupted connectivity over wifi / sim',
    icon: <WifiIcon color="feedback.icon.neutral.highContrast" size="large" />,
  },
  {
    title: 'Instant audio confirmations',
    icon: <VolumeHighIcon color="feedback.icon.neutral.highContrast" size="large" />,
  },
  {
    title: 'In-built printer for printing charges slips',
    icon: <BillIcon color="feedback.icon.neutral.highContrast" size="large" />,
  },
];

const MainBannerTextContent = ({
  setupFee,
  monthlyFee,
  onLearnMoreClick,
}: MainBannerTextContentProps): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();

  return (
    <Box
      display="flex"
      flexDirection="column"
      justifyContent="center"
      paddingX="spacing.5"
      paddingTop="spacing.8"
    >
      <Title
        size="medium"
        color="surface.text.normal.highContrast"
        textAlign={isMobile ? 'center' : 'left'}
      >
        Android Smart POS
      </Title>
      <Heading
        size="medium"
        color="surface.text.muted.highContrast"
        textAlign={isMobile ? 'center' : 'left'}
      >
        All-in-one POS to support all your payment needs
      </Heading>
      {!isMobile ? (
        <MainBannerFeaturesContainer>
          {FEATURE_ITEMS.map(({ title, icon }) => (
            <Box display="flex" alignItems="center" key={title} marginY="spacing.4">
              <Box
                backgroundColor="brand.primary.400"
                display="flex"
                alignItems="center"
                justifyContent="center"
                padding="spacing.3"
                borderRadius="small"
              >
                {icon}
              </Box>
              <Heading
                weight="regular"
                color="surface.text.subdued.highContrast"
                marginLeft="spacing.5"
                testID="main-banner-features"
                marginRight="spacing.5"
              >
                {title}
              </Heading>
            </Box>
          ))}
        </MainBannerFeaturesContainer>
      ) : null}

      <MainBannerFooter>
        <Heading
          color="surface.text.normal.highContrast"
          size={isMobile ? 'medium' : 'small'}
          textAlign={isMobile ? 'center' : 'left'}
        >
          <Amount
            value={monthlyFee}
            suffix="none"
            isAffixSubtle={false}
            size="heading-large-bold"
            testID="monthly-amount"
          />{' '}
          monthly subscription
        </Heading>

        <Box display={{ base: 'block', m: 'flex' }} alignItems="center" marginBottom="spacing.7">
          <Text
            color="surface.text.normal.highContrast"
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
              size={isMobile ? 'body-medium-bold' : 'body-small-bold'}
              testID="setup-amount"
            />{' '}
            one time setup fee.
          </Text>
          <Text
            size="small"
            color="surface.text.muted.highContrast"
            textAlign={isMobile ? 'center' : 'left'}
          >
            *Lifetime Pricing also available.
          </Text>
        </Box>
        <Box
          display="flex"
          position="relative"
          justifyContent={{ base: 'center', m: 'left' }}
          marginBottom={{ base: 'spacing.5', m: 'spacing.8' }}
        >
          <AddToCartButton
            productCode={PRODUCT_DESCRIPTIONS.a50.code}
            plan={PRODUCT_PLANS.MONTHLY}
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
