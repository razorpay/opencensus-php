import React from 'react';
import {
  AlertOnlyIcon,
  Amount,
  CheckIcon,
  ClockIcon,
  CloseIcon,
  SubscriptionsIcon,
  Text,
  UserIcon,
} from '@razorpay/blade/components';

import {
  CommsBannerItemVariants,
  CommsItem,
  CommsStatus,
  CustomCommsContent,
} from 'merchant/views/POS/types';

type StatusAssets = {
  icon: JSX.Element | null;
  variant: CommsBannerItemVariants;
};

const KYC_URL_PROD = 'https://easy.razorpay.com/onboarding';
const CATALOG_URL = '/pos/catalog?focusProduct=true';

export const KYC_STATUS_TYPES = {
  UNDER_REVIEW: 'under_review',
  NEEDS_CLARIFICATION: 'needs_clarification',
  REJECTED: 'rejected',
  ACTIVATED: 'activated',
  KYC_QUALIFIED_STB: 'kyc_qualified_stb',
  KYC_QUALIFIED_UNACTIVATED: 'kyc_qualified_unactivated',
};

export const STATUS_ASSETS_MAPPING: Record<CommsStatus, StatusAssets> = {
  active: {
    icon: <CheckIcon size="medium" color="feedback.icon.neutral.highContrast" />,
    variant: 'positive',
  },
  failed: {
    icon: <CloseIcon size="medium" color="feedback.icon.neutral.highContrast" />,
    variant: 'negative',
  },
  pending: {
    icon: <ClockIcon size="medium" color="surface.action.icon.default.lowContrast" />,
    variant: 'neutral',
  },
  notice: {
    icon: <AlertOnlyIcon size="medium" color="feedback.icon.neutral.highContrast" />,
    variant: 'notice',
  },
  informationRequired: {
    icon: <UserIcon size="medium" color="surface.action.icon.default.lowContrast" />,
    variant: 'neutral',
  },
  processing: {
    icon: <ClockIcon size="medium" color="feedback.icon.neutral.highContrast" />,
    variant: 'information',
  },
  refund_pending: {
    icon: <SubscriptionsIcon size="medium" color="feedback.icon.neutral.highContrast" />,
    variant: 'neutral',
  },
  dispatched: {
    icon: null,
    variant: 'neutral',
  },
};

export const STAGES: Record<string, Omit<CommsItem, 'status'>> = {
  TEST_MODE_ENABLED: {
    title: 'Test Mode Enabled',
    description:
      'Explore POS from our detailed catalog and payments products available in test mode.',
    cta: [
      {
        name: 'Explore POS',
        url: '/pos/catalog?focusProduct=true',
        type: 'button',
      },
    ],
  },
  ACCOUNT_ACTIVATED: {
    title: 'Account Activated',
    description: 'Submit your KYC details to activate your account and to order POS.',
    cta: [
      {
        name: 'Submit KYC',
        url: KYC_URL_PROD,
        type: 'button',
      },
    ],
  },
  ACCESS_POS: {
    title: 'Access POS',
    description: 'Explore out wide range of POS devices.',
    cta: null,
  },
  KYC_POS_DETAILS_SUBMITTED: {
    title: 'KYC & POS Details Submitted',
    description: `Thank you for submitting your KYC & POS details. While we review your details, explore our wide range of POS devices and place your order.`,
    cta: null,
  },
  POS_ORDER_PLACED: {
    title: 'POS Order Placed',
    description: `Thank you for placing your POS order with Razorpay. Sit tight while we review your details and get your device delivered to you.`,
    cta: null,
  },
  UPDATE_KYC: {
    title: 'Update KYC',
    description:
      'Please note that you must update your required details to ensure timely delivery of your device.',
    cta: [
      {
        name: 'Update KYC',
        url: KYC_URL_PROD,
        type: 'button',
      },
    ],
  },
  POS_DISPATCHED: {
    title: 'POS Dispatched',
    description: `We'll dispatch your device once you update your details and we verify it. Once you update your details, it can take upto 10-12 business days.`,
    cta: [
      {
        name: 'Learn More',
        url: ({ order }: CustomCommsContent): string => `/pos/orders/${order?.id}`,
        type: 'link',
      },
    ],
  },
  KYC_QUALIFIED: {
    title: 'KYC Qualified',
    description: `Your POS details look good to us and is under final checks. Your device will be delivered to you in 7-9 business days.`,
    cta: null,
  },
  POS_APPLICATION_REJECTED: {
    title: 'POS Application Rejected',
    description: `Due to some issues with your POS KYC, we are rejecting your application for POS devices. And we will be cancelling your order.`,
    cta: null,
  },
  REFUND_PENDING: {
    title: 'Refund Pending',
    description: ({ order, isMobileOrTablet }: CustomCommsContent): JSX.Element => (
      <Text textAlign={isMobileOrTablet ? 'left' : 'center'}>
        We will initiate the refund of&nbsp;
        <Amount isAffixSubtle={false} value={order?.amount?.total ?? 0} suffix="none" />
        &nbsp;to original payment method. It will reflect in 5-7 business days.
      </Text>
    ),
    cta: null,
  },
  KYC_REJECTED: {
    title: 'KYC Rejected!',
    description: `We regret to inform you that your KYC has not been approved. For any questions, please contact our support team.`,
    cta: null,
  },
  REFUND_INITIATED: {
    title: 'Refund Initiated',
    description: ({ order, isMobileOrTablet }: CustomCommsContent): JSX.Element => (
      <Text textAlign={isMobileOrTablet ? 'left' : 'center'}>
        We have initiated the refund of &nbsp;
        <Amount isAffixSubtle={false} value={order?.refund?.amount ?? 0} suffix="none" />
        &nbsp;to original payment method. It will reflect in 5-7 business days.
      </Text>
    ),
    cta: null,
  },
  AMOUNT_REFUNDED: {
    title: 'Amount Refunded',
    description: ({ order, isMobileOrTablet }: CustomCommsContent): JSX.Element => (
      <Text textAlign={isMobileOrTablet ? 'left' : 'center'}>
        We have refunded the amount of&nbsp;
        <Amount isAffixSubtle={false} value={order?.refund?.amount ?? 0} suffix="none" />
        &nbsp;to original payment method. It will reflect in 5-7 business days.
      </Text>
    ),
    cta: null,
  },
  POS_ACCESS_DENIED: {
    title: 'POS Access Denied!',
    description: `Since your KYC has been rejected, you won’t be able to order POS.`,
    cta: null,
  },
  POS_APPLICATION_UNDER_REVIEW: {
    title: 'POS Application Under Review',
    description: `Your POS application is under review. We won’t be able to process your application until you order a device.`,
    cta: [
      {
        name: 'Order POS',
        url: CATALOG_URL,
        type: 'button',
      },
    ],
  },
  POS_DETAILS_REQUIRED: {
    title: 'POS Details Required',
    description: `Please add few extra details to get activated with Razorpay POS.`,
    cta: [
      {
        name: 'Add Details',
        url: KYC_URL_PROD,
        type: 'button',
      },
    ],
  },

  ONLINE_KYC_UNDER_REVIEW: {
    title: 'Online KYC Under Review',
    description: `Your online application is under review. We will reach out to you in 3-4 business days in case of any queries.`,
    cta: null,
  },

  ONLINE_KYC_QUALIFIED_UNACTIVATED: {
    title: 'Online KYC Under Review',
    description: `Your online application is under review. Your account will be activated once the online onboarding pause is lifted.`,
    cta: null,
  },
};

/**
 * Scenarios as per:
 * figma: https://www.figma.com/file/ANHVUrh5EEwVLL9o09T8JQ/Omnichannel-Onboarding?node-id=4852%3A12848&mode=dev
 * doc: https://docs.google.com/spreadsheets/d/1Twaf_ATehJ6ICeCuVyYt319hUODeYAWqLjnikYNXYKs/edit#gid=1081707366
 */

export const SCENARIOS: CommsItem[][] = [
  [
    { ...STAGES.TEST_MODE_ENABLED, status: 'active' },
    { ...STAGES.ACCOUNT_ACTIVATED, status: 'informationRequired' },
    {
      ...STAGES.ACCESS_POS,
      status: 'pending',
      description: `Explore out wide range of POS devices. Fill the KYC & shop details to place an order.`,
    },
  ],
  [
    { ...STAGES.KYC_POS_DETAILS_SUBMITTED, status: 'active' },
    { ...STAGES.POS_ORDER_PLACED, status: 'active' },
  ],
  [
    { ...STAGES.KYC_POS_DETAILS_SUBMITTED, status: 'active' },
    {
      ...STAGES.ACCESS_POS,
      status: 'pending',
      cta: [
        {
          name: 'Order POS',
          url: CATALOG_URL,
          type: 'button',
        },
      ],
    },
  ],
  [
    { ...STAGES.UPDATE_KYC, status: 'notice' },
    {
      ...STAGES.POS_ORDER_PLACED,
      status: 'notice',
      description:
        'Your POS order is placed. But there is some problem with the KYC details you submitted. Please update your details .',
    },
    {
      ...STAGES.POS_DISPATCHED,
      status: 'dispatched',
    },
  ],
  [
    {
      ...STAGES.UPDATE_KYC,
      status: 'notice',
      description:
        'To continue your POS journey, please ensure that you update the required details and then proceed to order your device.',
    },
    {
      ...STAGES.ACCESS_POS,
      status: 'pending',
      description: `Explore out wide range of POS devices. Update your details to oder POS.`,
      cta: [
        {
          name: 'Explore POS',
          url: CATALOG_URL,
          type: 'link',
        },
      ],
    },
  ],
  [
    {
      ...STAGES.KYC_QUALIFIED,
      status: 'active',
    },
    {
      ...STAGES.POS_ORDER_PLACED,
      status: 'active',
      description: `Thank you for placing your POS order with Razorpay. Sit tight while we review your details and get your device delivered to you.`,
    },
    {
      ...STAGES.POS_DISPATCHED,
      description: `We'll dispatch your device once some final details are reviewed which can take upto 5 days.`,
      status: 'dispatched',
    },
  ],
  [
    {
      ...STAGES.KYC_QUALIFIED,
      status: 'active',
      description: `Good news! Your KYC has successfully cleared the initial checks. To expedite the process, please place your order now.`,
    },
    {
      ...STAGES.ACCESS_POS,
      status: 'pending',
      description: `Explore out wide range of POS devices. Order POS and we’ll bring your device in just 7-9 days.`,
      cta: [{ name: 'Order POS', url: CATALOG_URL, type: 'button' }],
    },
  ],
  [
    {
      ...STAGES.ACCOUNT_ACTIVATED,
      status: 'active',
      description: `Fantastic news! Your POS application is fully approved and finalised. Your order will reach you in just 2-3 business days.`,
      cta: null,
    },
    {
      ...STAGES.POS_ORDER_PLACED,
      status: 'active',
      description: `Thank you for placing your POS order with Razorpay. We are now preparing your order.`,
    },
  ],
  [
    {
      ...STAGES.ACCOUNT_ACTIVATED,
      status: 'active',
      description: `Congratulations! Your KYC is approved. Now you can access online payments.`,
      cta: [{ name: 'Explore Online Payments', url: '/app/dashboard', type: 'link' }],
    },
    {
      ...STAGES.POS_APPLICATION_REJECTED,
      status: 'failed',
    },
    {
      ...STAGES.REFUND_PENDING,
      status: 'refund_pending',
    },
  ],
  [
    {
      ...STAGES.ACCOUNT_ACTIVATED,
      status: 'active',
      description: `Congratulations! Your KYC is approved. Now you can access online payments.`,
      cta: [{ name: 'Explore Online Payments', url: '/app/dashboard', type: 'link' }],
    },
    {
      ...STAGES.POS_APPLICATION_REJECTED,
      status: 'failed',
    },
  ],
  [
    {
      ...STAGES.KYC_REJECTED,
      status: 'failed',
    },
    {
      ...STAGES.POS_ORDER_PLACED,
      status: 'notice',
      description: `Since your KYC has been rejected, we won’t be able to dispatch your order. Your money will be refunded to your account within 5-7 business days.`,
    },
    {
      ...STAGES.REFUND_PENDING,
      status: 'refund_pending',
    },
  ],
  [
    {
      ...STAGES.KYC_REJECTED,
      status: 'failed',
    },
    {
      ...STAGES.POS_ORDER_PLACED,
      status: 'notice',
      description: `Since your KYC has been rejected, we won’t be able to dispatch your order. Your money will be refunded to your account within 5-7 business days.`,
    },
    {
      ...STAGES.REFUND_INITIATED,
      status: 'active',
    },
  ],
  [
    {
      ...STAGES.KYC_REJECTED,
      status: 'failed',
    },
    {
      ...STAGES.POS_ORDER_PLACED,
      status: 'notice',
      description: `Since your KYC has been rejected, we won’t be able to dispatch your order. Your money will be refunded to your account within 5-7 business days.`,
    },
    {
      ...STAGES.AMOUNT_REFUNDED,
      status: 'active',
    },
  ],
  [
    {
      ...STAGES.KYC_REJECTED,
      status: 'failed',
    },
    {
      ...STAGES.POS_ACCESS_DENIED,
      status: 'notice',
    },
  ],
  [
    {
      ...STAGES.POS_APPLICATION_UNDER_REVIEW,
      status: 'processing',
    },
    {
      ...STAGES.ACCESS_POS,
      status: 'pending',
      description:
        'Explore our wide range of POS devices. Order POS and we’ll bring your device in just 2-3 business days post activation.',
    },
  ],
  [
    {
      ...STAGES.POS_DETAILS_REQUIRED,
      status: 'notice',
    },
    {
      ...STAGES.ACCESS_POS,
      status: 'pending',
      description: `Explore out wide range of POS devices. Fill the shop details to place an order.`,
      cta: [
        {
          name: 'Add Details',
          url: KYC_URL_PROD,
          type: 'button',
        },
        { name: 'Explore POS', url: CATALOG_URL, type: 'link' },
      ],
    },
  ],
  [
    {
      ...STAGES.ONLINE_KYC_UNDER_REVIEW,
      status: 'processing',
    },
    {
      ...STAGES.POS_APPLICATION_REJECTED,
      status: 'failed',
    },
    {
      ...STAGES.REFUND_PENDING,
      status: 'refund_pending',
    },
  ],
  [
    {
      ...STAGES.ONLINE_KYC_QUALIFIED_UNACTIVATED,
      status: 'notice',
    },
    {
      ...STAGES.POS_APPLICATION_REJECTED,
      status: 'failed',
    },
    {
      ...STAGES.REFUND_PENDING,
      status: 'refund_pending',
    },
  ],
  [
    {
      ...STAGES.ONLINE_KYC_UNDER_REVIEW,
      status: 'processing',
    },
    {
      ...STAGES.POS_APPLICATION_REJECTED,
      status: 'failed',
    },
  ],
  [
    {
      ...STAGES.ONLINE_KYC_QUALIFIED_UNACTIVATED,
      status: 'notice',
    },
    {
      ...STAGES.POS_APPLICATION_REJECTED,
      status: 'failed',
    },
  ],
];
