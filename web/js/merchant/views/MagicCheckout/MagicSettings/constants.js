import lazy from 'merchant/routes/LazyLoader';
import NativeModal from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Native';
import WoocommerceModal from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Woocommerce';

const WoocSettingsForm = lazy(() =>
  import(
    /* webpackChunkName: "MagicSettings" */ 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/SettingsForm'
  ),
);

const WoocSettingsCard = lazy(() =>
  import(
    /* webpackChunkName: "MagicSettings" */ 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingCard'
  ),
);

const WoocShippingTabForm = lazy(() =>
  import(
    /* webpackChunkName: "MagicSettings" */ 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingForm'
  ),
);

const NativeSettingsForm = lazy(() =>
  import(
    /* webpackChunkName: "MagicSettings" */ 'merchant/views/MagicCheckout/MagicSettings/containers/native/SettingsForm'
  ),
);
const NativeSettingsCard = lazy(() =>
  import(
    /* webpackChunkName: "MagicSettings" */ 'merchant/views/MagicCheckout/MagicSettings/containers/native/SettingsCard'
  ),
);
const NativePlatform = lazy(() =>
  import(
    /* webpackChunkName: "MagicSettings" */ 'merchant/views/MagicCheckout/MagicSettings/components/native/Platform'
  ),
);
const ShopifySettingsForm = lazy(() =>
  import(
    /* webpackChunkName: "MagicSettings" */ 'merchant/views/MagicCheckout/MagicSettings/containers/shopify/SettingsForm'
  ),
);
const ShopifySettingsCard = lazy(() =>
  import(
    /* webpackChunkName: "MagicSettings" */ 'merchant/views/MagicCheckout/MagicSettings/containers/shopify/SettingsCard'
  ),
);

const cdnBaseUrl = window.cdnBaseUrl || 'https://cdn.razorpay.com';

export const PLATFORMS = {
  LABELS: {
    WOOCOMMERCE: 'WooCommerce',
    SHOPIFY: 'Shopify',
    NATIVE: 'Custom E-Commerce Platform',
  },
  VALUES: {
    WOOCOMMERCE: 'woocommerce',
    SHOPIFY: 'shopify',
    NATIVE: 'native',
  },
};

export const PLATFORMS_DROPDOWN = [
  { label: 'Select', name: 'select' },
  { label: PLATFORMS.LABELS.WOOCOMMERCE, name: PLATFORMS.VALUES.WOOCOMMERCE },
  { label: PLATFORMS.LABELS.SHOPIFY, name: PLATFORMS.VALUES.SHOPIFY },
  { label: PLATFORMS.LABELS.NATIVE, name: PLATFORMS.VALUES.NATIVE },
];

export const FETCH_STATUS = {
  IDLE: 'idle',
  LOADING: 'loading',
  ERROR: 'error',
};

export const CONTENT = {
  [PLATFORMS.VALUES.WOOCOMMERCE]: {
    LOGO: `${window.cdnBaseUrl}/static/assets/magic-checkout/platforms/wooc.png`,
    VIEW_LABEL: 'Woocommerce',
    EDIT_LABEL: 'WOOCOMMERCE SETTINGS',
  },
  [PLATFORMS.VALUES.SHOPIFY]: {
    LOGO: `${window.cdnBaseUrl}/static/assets/magic-checkout/platforms/shopify.png`,
    VIEW_LABEL: 'Shopify',
    EDIT_LABEL: 'SHOPIFY SETTINGS',
  },
  [PLATFORMS.VALUES.NATIVE]: {
    LOGO: '',
    VIEW_LABEL: 'Custom e-commerce platform',
    EDIT_LABEL: 'CUSTOM E-COMMERCE PLATFORM',
  },
};

export const VIEWS = {
  EDIT: 'edit',
  READ: 'read',
};

export const NATIVE_SHIPPING_VIEWS = {
  PROVIDER_SELECTION: 'provider_selection',
  API: 'Api Service',
  SHIPROCKET: 'Shiprocket',
};

export const SHIPPING_PROVIDERS = [
  { label: 'Select', name: 'select' },
  { label: 'API', name: 'API', view: NATIVE_SHIPPING_VIEWS.API },
  { label: 'Shiprocket', name: 'Shiprocket', view: NATIVE_SHIPPING_VIEWS.SHIPROCKET },
];

export const COMPONENTS = {
  [PLATFORMS.VALUES.NATIVE]: {
    platformComponent: (status) => <NativePlatform status={status} />,
    formComponent: () => <NativeSettingsForm />,
    cardComponent: (onEdit) => <NativeSettingsCard onEdit={onEdit} />,
  },
  [PLATFORMS.VALUES.SHOPIFY]: {
    platformComponent: () => <ShopifySettingsForm />,
    formComponent: () => <ShopifySettingsForm />,
    cardComponent: (onEdit) => <ShopifySettingsCard onEdit={onEdit} />,
  },
  [PLATFORMS.VALUES.WOOCOMMERCE]: {
    platformComponent: () => <WoocSettingsForm />,
    formComponent: (onEdit) => <WoocShippingTabForm onEdit={onEdit} />,
    cardComponent: (onEdit) => <WoocSettingsCard onEdit={onEdit} />,
  },
};

export const NESTED_VIEW_TYPE = {
  PLATFORM_SELECTION: 'platform_selection',
  SETTINGS: 'settings',
};

export const DISABLE_MAGIC_REASONS = [
  {
    value: 'Pricing too high',
    label: 'Pricing too high',
  },
  {
    value: 'Don’t find this useful',
    label: 'Don’t find this useful',
  },
  {
    value: 'Privacy concerns',
    label: 'Privacy concerns',
  },
  {
    value: 'Support issues',
    label: 'Support issues',
  },
  {
    value: 'Others',
    label: 'Others',
  },
];

export const SHOPIFY_CHECKOUT_SETTINGS = [
  {
    label: 'Buy now button',
    value: false,
    key: 'one_cc_buy_now_button',
    description: 'Enable Magic Checkout on Buy Now',
  },
  {
    label: 'Auto fetch coupon',
    value: false,
    key: 'one_cc_auto_fetch_coupons',
    description:
      'Enable auto fetching of coupons to show all available coupons directly on Magic Checkout',
  },
  {
    label: 'International shipping',
    value: false,
    key: 'one_cc_international_shipping',
    description: 'Allow customers to select international pin code for delivery',
  },
  {
    label: 'Capture billing address',
    value: false,
    key: 'one_cc_capture_billing_address',
    description: 'Ask customers to enter billing address separately',
  },
  {
    label: 'Capture GSTIN',
    value: false,
    key: 'one_cc_capture_gstin',
  },
  {
    label: 'Capture order instructions',
    value: false,
    key: 'one_cc_capture_order_instructions',
  },
];

export const SHOPIFY_ANALYTICS_SETTINGS = [
  {
    label: 'Google analytics',
    value: false,
    key: 'one_cc_ga_analytics',
    description: 'Enable Google Analytics tracking for Magic Checkout orders',
  },
  {
    label: 'Facebook pixel',
    value: false,
    key: 'one_cc_fb_analytics',
    description: 'Enable Facebook Pixel tracking for Magic Checkout orders',
  },
];

export const SHIPPING_SETTINGS = [
  {
    label: 'International shipping',
    value: false,
    key: 'one_cc_international_shipping',
    description: 'Allow customers to select international pin code for delivery',
  },
  {
    label: 'Capture billing address',
    value: false,
    key: 'one_cc_capture_billing_address',
    description: 'Ask customers to enter billing address separately',
  },
];

export const COUPON_SETTINGS = {
  label: 'Auto fetch coupon',
  value: false,
  key: 'one_cc_auto_fetch_coupons',
  description:
    'Enable auto fetching of coupons to show all available coupons directly on Magic Checkout',
};

export const SHOPIFY_MAGIC_CHECKOUT = {
  label: 'Magic Checkout',
  value: false,
  description: 'Enable Magic Checkout',
  key: 'one_click_checkout',
};

export const MANUAL_REVIEW_MODAL = {
  woocommerce: {
    component: WoocommerceModal,
    demoUrl: `${cdnBaseUrl}/static/assets/magic-checkout/woocommerce-manual-review-demo.mp4`,
    icon: `${cdnBaseUrl}/static/assets/magic-checkout/platforms/wooc.png`,
  },
  native: {
    component: NativeModal,
    infoHeader: 'Prerequisites for manually review COD orders',
    instructions: [
      {
        customPoints: true,
        points: [
          {
            type: 'text',
            text: 'To manually review COD orders, you will have to create a review order API. Please check ',
          },
          {
            type: 'link',
            text: 'instructions',
            url: 'https://docs.google.com/document/d/1wmUFOful3w_Ga01dIwcAJsAuCDVUtWLtG9-6dWjGi54/edit#heading=h.hfjqmnxzy0c2',
          },
          {
            type: 'text',
            text: ' for the required API contract and response',
          },
        ],
      },
      {
        point:
          'Basic authentication is also mandatory for this API. Please enter the username and password set by you for authentication.',
      },
    ],
  },
};
export const SHOPIFY_RECEIPT_PREFIX = 'shopify_1cc_receipt';

export const ORDER_PENDING = 'Order Pending';

export const SHOPIFY_BUY_NOW_BUTTON = 'one_cc_buy_now_button';

export const GIFT_CARD_FEATURE = {
  label: 'Pay with gift card',
  value: false,
  key: 'one_cc_gift_card',
};

export const GIFT_CARD_SETTINGS = [
  {
    label: 'Pay with multiple gift cards',
    value: false,
    key: 'one_cc_multiple_gift_card',
  },
  {
    label: 'Restrict paying with coupon and gift card together',
    value: false,
    key: 'one_cc_gift_card_restrict_coupon',
  },
  {
    label: 'Restrict buying gift cards with existing gift cards',
    value: false,
    key: 'one_cc_buy_gift_card',
  },
  {
    label: 'Restrict customers from clubbing gift cards with COD',
    value: false,
    key: 'one_cc_gift_card_cod_restrict',
  },
];

export const CHECKOUT_SETTINGS_CONFIG = [
  {
    label: 'Capture GSTIN?',
    value: false,
    key: 'one_cc_capture_gstin',
  },
  {
    label: 'Capture order instructions?',
    value: false,
    key: 'one_cc_capture_order_instructions',
  },
];

export const ADDITIONAL_WOOC_SETTINGS_CONFIG = [
  {
    label: 'Buy now button',
    value: false,
    key: 'one_cc_buy_now_button',
    description: 'Enable Magic Checkout on Buy Now',
  },
  {
    label: 'Mini Cart Button',
    value: false,
    key: 'one_cc_mini_cart_button',
    description: 'Enable Magic Checkout on Mini Cart',
  },
];

export const COUPON = 'coupon';
export const CHECKOUT = 'checkout';
export const ANALYTICS = 'analytics';
export const CARD = 'Card';
export const COUPON_FORM = `${COUPON}Form`;
export const COUPON_CARD = `${COUPON}Card`;
export const CHECKOUT_FORM = `${CHECKOUT}Form`;
export const CHECKOUT_CARD = `${CHECKOUT}Card`;
export const ANALYTICS_FORM = `${ANALYTICS}Form`;
export const ANALYTICS_CARD = `${ANALYTICS}Card`;
export const GIFT_CARD = 'gc';
export const GC_FORM = `${GIFT_CARD}Form`;
export const GC_CARD = `${GIFT_CARD}Card`;
export const CHECKOUT_SETTINGS = 'Checkout Settings';
export const ANALYTICS_SETTINGS = 'Analytics Settings';
export const WOOCOMMERCE_REST_API_URL =
  'https://woocommerce.github.io/woocommerce-rest-api-docs/?shell#authentication';

export const WOOC_MAGIC_SHIPPING = 'magic-shipping-engine';

export const WOOCOMMERCE_SHIPPING_SETTINGS_TYPE = [
  {
    label: 'Magic Shipping',
    value: 'magic-shipping-engine',
  },
  {
    label: 'Woocommerce Shipping',
    value: 'wooc-shipping',
  },
];

export const WOOC_SHIPPING_ENGINE_PLUGIN_UPDATE =
  'Note: To use advance Shipping settings, please update your Razorpay WooCommerce plugin to version 4.5.6 or above.';
