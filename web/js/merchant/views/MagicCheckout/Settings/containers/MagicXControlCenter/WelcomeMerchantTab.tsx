import React from 'react';
import { connect } from 'react-redux';
import { Box, Heading, Button, Text } from '@razorpay/blade/components';

import {
  C360BannerImage,
  ShopifyBadge,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter/styled';

import c360Banner from 'assets/c360/banner.png';
import c360RzpLogo from 'assets/c360/c360-rzp-logo.svg';
import shopifyLogo from 'assets/c360/shopify.svg';

import {
  C360_ONBOARDING_HREF,
  C360_CONTACT_SALES_HREF,
  C360_ONBOARDING_CTA,
  C360_CONTACT_SALES_CTA,
  WELCOME_SECTION_FEATURES,
  WELCOME_SECTION_TITLE,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter/constants';

export const WelcomeMerchantTab = ({ user }) => {
  return (
    <Box
      borderWidth="thin"
      borderColor="surface.border.gray.muted"
      borderRadius="large"
      backgroundColor="surface.background.cloud.subtle"
      overflow="hidden"
      marginY="spacing.3"
      minHeight="500px"
      display="grid"
      gridTemplateColumns="340px 1fr"
    >
      <Box
        width="auto"
        minHeight="535px"
        maxHeight="550px"
        borderRadius="large"
        borderTopRightRadius="none"
        borderBottomRightRadius="none"
        position="relative"
        overflowX="hidden"
      >
        <C360BannerImage src={c360Banner} alt="" role="presentation" />
        <Box position="absolute" top="36px" left="36px">
          <img src={c360RzpLogo} alt="" role="presentation" style={{ marginBottom: '16px' }} />
          <Text size="small" weight="regular" color="surface.text.staticWhite.normal">
            Boost buyer intent, reduce fake orders, &amp;
            <br /> increase pre-paid order share on Checkout360
          </Text>
        </Box>
        <ShopifyBadge>
          <Text size="small" marginRight="spacing.1">
            built for
          </Text>
          <img src={shopifyLogo} alt="Shopify stores" />
        </ShopifyBadge>
      </Box>
      <Box width="100%" display="flex" alignItems="start" justifyContent="center">
        <Box height="100%" maxWidth="420px" paddingY="65px">
          <Heading size="medium" weight="semibold" marginBottom="spacing.5">
            {WELCOME_SECTION_TITLE}
          </Heading>

          <Box
            marginBottom="60px"
            display="grid"
            gridTemplateColumns="repeat(2, 1fr)"
            gridTemplateRows="repeat(2, auto)"
            gap="spacing.3"
          >
            {WELCOME_SECTION_FEATURES.map((item, index) => (
              <Box
                key={index}
                display="flex"
                flexDirection="column"
                gap="spacing.5"
                paddingX="spacing.6"
                paddingY="spacing.5"
                backgroundColor="surface.background.gray.intense"
                borderWidth="thinner"
                borderColor="surface.border.gray.subtle"
                borderRadius="large"
              >
                <item.Icon size="large" color="surface.icon.onCloud.onSubtle" />
                <item.Description />
              </Box>
            ))}
          </Box>
          <Box display="flex" alignItems="center" gap="spacing.3">
            <Button color="primary" size="large" href={C360_ONBOARDING_HREF}>
              {user.isC360OnboardingToBeResumed
                ? C360_ONBOARDING_CTA.RESUME
                : C360_ONBOARDING_CTA.START}
            </Button>
            <Button color="primary" variant="tertiary" size="large" href={C360_CONTACT_SALES_HREF}>
              {C360_CONTACT_SALES_CTA}
            </Button>
          </Box>
        </Box>
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(WelcomeMerchantTab);
