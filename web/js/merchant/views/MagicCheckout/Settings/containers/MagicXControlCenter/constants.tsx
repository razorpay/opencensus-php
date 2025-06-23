import React from 'react';
import {
  Text,
  CreditCardIcon,
  CashIcon,
  CloudLightningIcon,
  AppStoreIcon,
} from '@razorpay/blade/components';

export const C360_ONBOARDING_HREF = 'https://easy.razorpay.com/pg3/onboarding';
export const C360_CONTACT_SALES_HREF = 'mailto:magic-checkout-support@razorpay.com';

export const C360_ONBOARDING_CTA = { START: 'Start Set-Up', RESUME: 'Resume Set-Up' };
export const C360_CONTACT_SALES_CTA = 'Contact Sales';

export const SETUP_MAGICX_ROUTE = '/magic/settings/magicx-store-settings';

export const ROUTES = {
  COD_CONFIG: {
    MAGICX_V1: '/magic/settings/cod-settings',
    MAGICX_V2: '/magic/settings/cod-settings/settings',
  },
  RTO_CONFIG: {
    MAGICX_V1: '/magic/settings/rto-settings',
    MAGICX_V2: '/magic/settings/rto-reduction-setup',
  },
  CHECKOUT_CONFIG: {
    MAGICX_V1: '/magic/settings/magicx-store-settings',
    MAGICX_V2: '/magic/settings/magicx-store-settings',
  },
  SSO_CONFIG: {
    MAGICX_V1: '/magic/settings/sso',
    MAGICX_V2: '/magic/settings/sso',
  },
};

export const DOCS_LINKS = {
  COD: 'https://razorpay.com/docs/payments/cod-magic-checkout/shopify/configure-cod/',
  RTO: 'https://razorpay.com/docs/payments/cod-magic-checkout/shopify/rto-intelligence/ ',
  CHECKOUT: 'https://razorpay.com/docs/payments/cod-magic-checkout/shopify/magic-checkout/',
  SSO: 'https://razorpay.com/docs/payments/cod-magic-checkout/shopify/login-with-razorpay/',
};

export const WELCOME_SECTION_TITLE = 'What does this all-in-one solution give you?';
export const WELCOME_SECTION_FEATURES = [
  {
    Icon: CashIcon,
    Description: () => (
      <Text color="surface.text.gray.normal" size="small" weight="medium">
        Growth in sales with
        <br /> Intelligent COD,{' '}
        <Text as="span" size="small" weight="regular">
          with RTO prediction
        </Text>
      </Text>
    ),
  },
  {
    Icon: CreditCardIcon,
    Description: () => (
      <Text color="surface.text.gray.normal" size="small" weight="regular">
        The Best Payment Gateway
        <br /> with{' '}
        <Text as="span" display="inline" size="small" weight="medium">
          1.5x higher success rates
        </Text>
      </Text>
    ),
  },
  {
    Icon: CloudLightningIcon,
    Description: () => (
      <Text color="surface.text.gray.normal" size="small" weight="regular">
        Superfast checkout with
        <br />
        <Text as="span" size="small" weight="medium">
          100M+ addresses to prefill &amp; smart coupons
        </Text>
      </Text>
    ),
  },
  {
    Icon: AppStoreIcon,
    Description: () => (
      <Text color="surface.text.gray.normal" size="small" weight="regular">
        Higher intent driven by{' '}
        <Text as="span" display="inline" size="small" weight="medium">
          trust <br /> and affordability markers
        </Text>{' '}
        on product page
      </Text>
    ),
  },
];
