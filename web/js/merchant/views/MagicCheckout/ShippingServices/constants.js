import ShipRocketIcon from 'merchant/views/MagicCheckout/ShippingServices/assets/shiprocket.svg';
import DelhiveryIcon from 'merchant/views/MagicCheckout/ShippingServices/assets/delhivery.svg';
import IThinkLogisticsIcon from 'assets/ithink-logistics-logo.png';
import lazy from 'merchant/routes/LazyLoader';

const ShipRocketModal = lazy(() =>
  import(
    /* webpackChunkName: "MagicShippingService" */ 'merchant/views/MagicCheckout/ShippingServices/ShipRocketAccountModal'
  ),
);

const DelhiveryModal = lazy(() =>
  import(
    /* webpackChunkName: "MagicShippingService" */ 'merchant/views/MagicCheckout/ShippingServices/DelhiveryAccountModal'
  ),
);

const IThinkModal = lazy(() =>
  import(
    /* webpackChunkName: "MagicShippingService" */ 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal'
  ),
);

export const RULE_TYPES_RADIO_INPUT = [
  {
    value: 'free',
    label: 'Free',
  },
  {
    value: 'flat',
    label: 'Flat Charge',
  },
  {
    value: 'slabs',
    label: 'Slabs',
  },
];

export const PAYMENT_PAGE_RULE_TYPES = [
  {
    value: 'free',
    label: 'No extra charge',
  },
  {
    value: 'flat',
    label: 'Yes, a flat amount',
  },
  {
    value: 'slabs',
    label: 'Depends on the order amount',
  },
];

//eslint-disable-next-line
export const EMAIL_REGEX =
  /^(([^<>()[\].,;:\s@"]+(\.[^<>()[\].,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

export const STEPS_TEXT = [
  {
    instructions: {
      heading: 'Go to the settings section',
      points: [
        'Go to API Section - on the bottom right (as shown in adjacent pane)',
        { customPointer: true },
      ],
    },
    cta: {
      primary: 'Next',
      secondary: 'Skip Instructions',
      secondaryStep: 2,
    },
  },
  {
    instructions: {
      heading: 'Create an API User',
      subheading: 'In the API section',
      points: [
        'Click on “Create an API User” button ',
        'In the form that opens, enter a new Email ID & choose a password',
        'Click “Generate API credential” button',
      ],
      alternateInstrustion: true,
    },
    cta: {
      secondary: 'Back',
      primary: 'Enter credentials',
      secondaryStep: 0,
    },
  },
];

export const BENEFITS_SHIPROCKET_HIGHLIGHTS = [
  {
    startingText: 'To get started with Magic Checkout, you will no longer have to worry about',
    functionality: ' pincode serviceability APIs. ',
    subText: 'Magic checkout will directly fetch serviceability from your Shiprocket account.',
    image: true,
  },
  {
    startingText: 'You will be able to configure flat or slab based ',
    functionality: ' Shipping and COD fee ',
    subText: 'from the Magic Checkout dashboard.',
  },
  {
    startingText: 'Magic Checkout will fetch ',
    functionality: 'order status updates ',
    subText: 'from your Shiprocket account to provide better RTO protection on your COD orders.',
  },
];

export const BENEFITS_SHIPROCKET_HIGHLIGHTS_INTELLIGENCE = [
  {
    startingText: 'Automatic delivery status updates',
    subText: 'Share order data from your logistic partner account directly.',
  },
  {
    startingText: 'RTO protection on COD orders',
    subText: 'You will be eligible for RTO protection on these orders.',
    image: true,
  },
];

export const DELHIVERY_STEPS = [
  {
    instructions: {
      heading: 'Production Authentication Token',
      points: [{ custompoint: true }],
    },
  },
];

export const SHIPPING_PARTNERS = {
  shiprocket: {
    provider_type: 'Shiprocket',
    image: ShipRocketIcon,
    component: <ShipRocketModal />,
  },
  delhivery: {
    provider_type: 'Delhivery',
    image: DelhiveryIcon,
    component: <DelhiveryModal />,
  },
  ithink_logistics: {
    provider_type: 'iThink Logistics',
    image: IThinkLogisticsIcon,
    component: <IThinkModal />,
  },
};

export const DISCONNECT_TEXTS = {
  serviceability: {
    header: 'Disable serviceability using shiprocket',
    subText: 'Are you sure you want to disable ?',
    desc: 'Razorpay will stop receiving Pincode serviceablity updates from your Shiprocket account. This will remove your shipping & COD settings.',
    secondaryCtaLabel: "No, don't disable",
    primaryCtaLabel: 'Yes, disable',
  },
  shiprocket: {
    header: 'Disconnect Shiprocket',
    subText: 'Are you sure you want to disconnect ?',
    desc: 'Razorpay will stop receiving order status, Pincode serviceablity updates from your Shiprocket account. This will remove your shipping & COD settings.',
    secondaryCtaLabel: 'No, don’t disconnect',
    primaryCtaLabel: 'Yes, disconnect',
  },
  delhivery: {
    header: 'Disconnect Delhivery',
    subText: 'Are you sure you want to disconnect ?',
    desc: 'Razorpay will stop receiving delivery status updates from your Delhivery account. Orders shipped via Delhivery will no longer be eligible for RTO insurance.',
    secondaryCtaLabel: 'No, don’t disconnect',
    primaryCtaLabel: 'Yes, disconnect',
  },
  ithink_logistics: {
    header: 'Disconnect iThink Logistics',
    subText: 'Are you sure you want to disconnect ?',
    desc: 'Razorpay will stop receiving delivery status updates from your iThink Logistics account. Orders shipped via iThink Logistics will no longer be eligible for RTO insurance.',
    secondaryCtaLabel: 'No, don’t disconnect',
    primaryCtaLabel: 'Yes, disconnect',
  },
};

export const ITHINK_LOGISTICS_STEP_TEXT = [
  {
    instructions: {
      heading: 'Follow the below steps',
      points: [
        'Go to the settings section',
        'Go to API Key section',
        'Copy API Key and Secret Key and paste in the section ahead',
      ],
    },
    cta: {
      primary: 'Enter credentials',
      secondary: 'Skip instructions',
      secondaryStep: 1,
    },
  },
];
