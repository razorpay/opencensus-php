import store from 'merchant/store';
import {
  ApiResponse,
  OrderDetailsItem,
  ProductDescription,
  ProductDescriptionPricing,
  ProductFeaturesColumn,
} from 'merchant/views/POS/types';

export const MOCK_PRODUCT: ProductDescription = {
  gallery: [
    {
      main: '',
      mobile: '',
      thumbnail: '',
    },
    {
      main: '',
      mobile: '',
      thumbnail: '',
    },
    {
      main: '',
      mobile: '',
      thumbnail: '',
    },
    {
      main: '',
      mobile: '',
      thumbnail: '',
    },
  ],
  name: 'mock-product',
  code: 'mock-product',
  productTitle: 'Mock Product',
  description: 'Mock Description',
  maxOrder: 0,
  cartImage: '',
  pricing: [
    {
      name: 'Monthly Plan',
      type: 'monthly',
      subText: '*Subscription only starts when device gets delivered. GST charges applicable.',
      breakups: [
        {
          key: 'monthly',
          description: 'Monthly Subscription',
          value: 0,
          suffix: '/mo',
          isExtraFee: false,
          isChargeableAtCheckout: false,
        },
        {
          key: 'setup_fee',
          description: 'Setup Fee',
          value: 0,
          suffix: 'setup fee',
          isExtraFee: true,
          isChargeableAtCheckout: true,
        },
      ],
    },
    {
      name: 'Lifetime Plan',
      type: 'lifetime',
      subText: '*No Setup fees required. GST charges applicable.',
      breakups: [
        {
          key: 'lifetime',
          description: 'Lifetime Plan',
          value: 0,
          suffix: '',
          isExtraFee: false,
          isChargeableAtCheckout: true,
        },
      ],
    },
  ],
  featureGallery: [
    {
      image: '',
      title: 'Test Feature Title',
      description: 'Test Feature Description',
      isImageFirst: false,
    },
    {
      image: '',
      title: 'Multiple Payment options for your cutomers',
      description:
        'Two line attractive copy for this section explaining the image adjacent to this section.',
      isImageFirst: true,
    },
  ],
  infoBanner: {
    image: '',
    mobileImage: '',
    features: [
      {
        icon: '',
        text: 'Info Banner Feature 1',
      },
      {
        icon: '',
        text: 'Info Banner Feature 2',
      },
    ],
  },
  technicalSpecifications: [
    {
      category: 'OS',
      value: 'PayDroid powered by Android 6.0',
    },
    {
      category: 'Processor',
      value:
        'Application CPU: Quad-core Cortex-A7, 1.3GHz Security CPU: 32-bit RISC Core (ARMv7-M), 1.25DMIPS/MHz',
    },
    {
      category: 'Memory',
      value: '1 GB DDR + 8 GB eMMC I 1x Micro SD card slot I Supports upto 128 GBz',
    },
    {
      category: 'Card Reader',
      value: 'Magnetic Card Reader I Smart Card Reader I Contactless Card Reader',
    },
    {
      category: 'Processor',
      value:
        'Application CPU: Quad-core Cortex-A7, 1.3GHz Security CPU: 32-bit RISC Core (ARMv7-M), 1.25DMIPS/MHz',
    },
    {
      category: 'Memory',
      value: '1 GB DDR + 8 GB eMMC I 1x Micro SD card slot I Supports upto 128 GBz',
    },
    {
      category: 'OS',
      value: 'Test Technical Spec',
    },
  ],
};

export const MOCK_FEATURE_SCHEMA: ProductFeaturesColumn[][] = [
  [{ key: 'pricing_plan', name: 'Pricing Plan', icon: '', boxSize: 'medium' }],
  [
    { key: 'upi', name: 'UPI', icon: '', boxSize: 'medium' },
    { key: 'tapAndPay', name: 'Tap & Pay', icon: 'UPI', boxSize: 'medium' },
  ],
  [
    { key: 'barcodeScanner', name: 'Barcode Scanner', icon: '', boxSize: 'medium' },
    { key: 'printer', name: 'printer', icon: 'UPI', boxSize: 'medium' },
  ],
];

export const MOCK_PRODUCT_FEATURE = {
  image: '',
  name: 'mock-product',
  productTitle: 'Mock Product',
  code: 'mock-product',
  features: {
    pricing_plan: {
      isAvailable: true,
      name: 'Pricing Plan',
      isCustomComponent: true,
    },
    upi: {
      isAvailable: true,
      name: 'UPI',
    },
    tapAndPay: {
      isAvailable: false,
      name: 'Card Tap & Pay',
    },
    emi: {
      isAvailable: true,
      name: 'Credit/Debit Card Swipe',
    },
    barcodeScanner: {
      isAvailable: true,
      name: 'Barcode Scanner',
    },
    printer: {
      isAvailable: true,
      name: 'Paper Billing Available',
    },
    screenSize: {
      isAvailable: true,
      name: '5" 720x1280 pixels',
    },
    standalone: {
      isAvailable: true,
      name: 'Standalone',
    },
    intergrationCapabilities: {
      isAvailable: true,
      name: 'Integration to apps and devices.',
    },
    nfc: {
      isAvailable: true,
      name: '4G + WiFi (2.4 GHz)+ Bluetooth 4.0',
    },
    battery: {
      isAvailable: true,
      name: '2600 mAh | 7.2 V',
    },
  },
};

export const MOCK_PRICING_PLAN = [
  {
    productName: 'mock-product',
    monthly_sub: 300,
    setup_fee: 200,
    lifetime: 12000,
  },
  {
    productName: 'mock-product-new',
    monthly_sub: 300,
    setup_fee: 200,
    lifetime: 12000,
  },
];

const globalStore = store.getState();
export const MOCK_USER = {
  ...globalStore.session.user,
  id: 'mock-user-id',
  business_operation_address: 'test operation address',
  business_operation_city: 'test operation city',
  business_operation_state: 'test operation state',
  business_operation_pin: '123456',
  business_registered_address: 'test registered address',
  business_registered_city: 'test registered city',
  business_registered_pin: '123456',
  business_registered_state: 'test registered state',
  contact_mobile: '1234567890',
  contact_name: 'Test Name',
  contact_email: 'testemail@gmail.com',
  merchant: {
    id: 'mock-user-id',
  },
};

export const MOCK_PRICING_WITH_PRICES: ProductDescriptionPricing[] = [
  {
    name: 'Monthly Plan',
    type: 'monthly',
    subText: '*Subscription only starts when device gets delivered. GST charges applicable.',
    breakups: [
      {
        key: 'monthly',
        description: 'Monthly Subscription',
        value: 300,
        suffix: '/mo',
        isExtraFee: false,
        isChargeableAtCheckout: false,
      },
      {
        key: 'setup_fee',
        description: 'Monthly Subscription',
        value: 1200,
        suffix: 'setup fee',
        isExtraFee: true,
        isChargeableAtCheckout: true,
      },
    ],
  },
  {
    name: 'Lifetime Plan',
    type: 'lifetime',
    subText: '*No Setup fees required. GST charges applicable.',
    breakups: [
      {
        key: 'lifetime',
        description: 'Lifetime Plan',
        value: 12000,
        suffix: '',
        isExtraFee: false,
        isChargeableAtCheckout: true,
      },
    ],
  },
];

export const MOCK_ORDER_RESPONSE: ApiResponse<OrderDetailsItem> = {
  status_code: 200,
  success: true,
  data: {
    id: 'mock-order-id',
    order_id: 'order_EKwxwAgItmmXdp',
    merchant_id: 'JKwxwAgItmmXdp',
    status: 'paid',
    created_at: 1654776878,
    arriving_at: 1655193661,
    delivered_at: 0,
    rejected_at: 0,
    rejection_reasons: null,
    amount: {
      base: 10510,
      gst: 1891,
      total: 12401,
    },
    rental_amount: {
      base: 10510,
      gst: 1891,
      total: 12401,
    },
    delivery_address: {
      name: 'Amitabh Baruah',
      address: 'Gram Rajpura Kuti Mhow Indore',
      city: 'Bengaluru',
      country: 'IN',
      state: 'Karnataka',
      pin_code: '560068',
      phone_no: '7578967104',
    },
    items: [
      {
        code: 'mock-order',
        count: 10,
        period: 'monthly',
      },
    ],
    order: {
      id: 'mock-order',
      amount: 100,
      amount_paid: 100,
      amount_due: 0,
      currency: 'INR',
      status: 'created',
      created_at: 1221121,
    },
    payment: {
      amount: 100,
      status: 'captured',
      refund_status: null,
      captured: true,
      created_at: 212121,
    },
    refund: null,
  },
};

export const MOCK_ADDRESSES = [
  {
    id: '0',
    isSelected: true,
    type: 'default',
    name: 'Test Name First',
    phoneNumber: '1234567892',
    pincode: '560034',
    address: 'Test Address First',
    city: 'Test City',
    state: 'KA',
  },
  {
    id: '1',
    isSelected: false,
    type: 'new address',
    name: 'Test Name Second',
    phoneNumber: '1234567892',
    pincode: '560034',
    address: 'Test Address Second',
    city: 'Test City',
    state: 'KA',
  },
];

export const MOCK_GTM = {
  omniChannelGtm: {
    variables: {
      cities: 'Bengaluru',
    },
  },
};

export const MOCK_PRODUCT_PRICING_RESPONSE = [
  {
    name: 'MOCK PRODUCT',
    code: 'mock-product',
    rate_config: {
      monthly: 300,
      lifetime: 12000,
      setup_fee: 200,
    },
  },
  {
    name: 'MOCK PRODUCT NEW',
    code: 'mock-product-new',
    rate_config: {
      monthly: 300,
      lifetime: 12000,
      setup_fee: 200,
    },
  },
];

export const MOCK_PAID_ORDER_ITEM: OrderDetailsItem = {
  id: 'mock-order-id',
  order_id: 'order_mock-order-id',
  merchant_id: 'mock-user-id',
  status: 'paid',
  order: null,
  payment: null,
  refund: null,
  created_at: 1696439810,
  arriving_at: 1696526210,
  delivered_at: 0,
  rejected_at: 0,
  rejection_reasons: null,
  delivery_address: {
    name: 'Test Name',
    address: 'test operation address',
    city: 'test operation city',
    country: 'IN',
    state: 'test operation state',
    pin_code: '781019',
    phone_no: '8486467098',
  },
  items: [
    {
      code: 'mock-product',
      count: 1,
      period: 'monthly',
    },
  ],
  amount: {
    base: 2000,
    gst: 360,
    total: 2360,
  },
  rental_amount: {
    base: 499,
    gst: 89,
    total: 588,
  },
};

export const MOCK_PRE_CHECKOUT_ORDER = {
  id: 'mock-order-id',
  order_id: 'order_mock-order-id',
  merchant_id: 'mock-user-id',
  status: 'created',
  amount: {
    base: 2000,
    gst: 360,
    total: 2360,
  },
  rental_amount: {
    base: 499,
    gst: 89,
    total: 588,
  },
  delivery_address: {
    name: 'Test Name',
    address: 'Test Address',
    city: 'Test City',
    country: 'IN',
    state: 'KA',
    pin_code: '123456',
    phone_no: '+919308490211',
  },
  items: [
    {
      code: 'mock-product',
      count: 1,
      period: 'monthly',
    },
  ],

  order: null,
  payment: null,
  refund: null,
};

export const MOCK_DELIVERED_ORDER_ITEM: OrderDetailsItem = {
  id: 'mock-order-id-second',
  order_id: 'order_mock-order-id-second',
  merchant_id: 'mock-user-id',
  status: 'delivered',
  order: null,
  payment: null,
  refund: null,
  created_at: 1696439810,
  arriving_at: 0,
  delivered_at: 1696526210,
  rejected_at: 0,
  rejection_reasons: null,
  amount: {
    base: 2000,
    gst: 360,
    total: 2360,
  },
  rental_amount: {
    base: 499,
    gst: 89,
    total: 588,
  },
  delivery_address: {
    name: 'Test Name',
    address: 'Test Address',
    city: 'Test City',
    country: 'IN',
    state: 'KA',
    pin_code: '123456',
    phone_no: '+919308490211',
  },
  items: [
    {
      code: 'mock-product',
      count: 1,
      period: 'monthly',
    },
  ],
};

export const MOCK_REJECTED_ORDER_ITEM: OrderDetailsItem = {
  id: 'mock-order-id-third',
  order_id: 'order_mock-order-id-third',
  merchant_id: 'mock-user-id',
  status: 'rejected',
  order: null,
  payment: null,
  refund: null,
  created_at: 1696439810,
  arriving_at: 0,
  delivered_at: 0,
  rejected_at: 1696871810,
  rejection_reasons: {
    error_code: '',
    error_reason: '',
    error_description: 'Failed because of Something',
  },
  amount: {
    base: 2000,
    gst: 360,
    total: 2360,
  },
  rental_amount: {
    base: 499,
    gst: 89,
    total: 588,
  },
  delivery_address: {
    name: 'Test Name',
    address: 'Test Address',
    city: 'Test City',
    country: 'IN',
    state: 'KA',
    pin_code: '123456',
    phone_no: '+919308490211',
  },
  items: [
    {
      code: 'mock-product',
      count: 1,
      period: 'monthly',
    },
  ],
};

export const MOCK_REJECTED_WITH_REFUND_INITIATED: OrderDetailsItem = {
  id: 'mock-order-id-fourth',
  order_id: 'order_mock-order-id-fourth',
  merchant_id: 'mock-user-id',
  status: 'rejected',
  order: null,
  payment: {
    status: 'refunded',
    refund_status: 'full',
    amount: 2360,
    captured: true,
    created_at: 2133131231,
  },
  refund: {
    id: 'mock-refund-id',
    payment_id: 'mock-payment-id',
    acquirer_data: {
      ABQ: 'mock-ref-id',
    },
    amount: 2360,
    created_at: 1697174813,
    status: 'processing',
  },
  created_at: 1696439810,
  arriving_at: 0,
  delivered_at: 0,
  rejected_at: 1696871810,
  rejection_reasons: {
    error_code: '',
    error_reason: '',
    error_description: 'Failed because of Something',
  },
  amount: {
    base: 2000,
    gst: 360,
    total: 2360,
  },
  rental_amount: {
    base: 499,
    gst: 89,
    total: 588,
  },
  delivery_address: {
    name: 'Test Name',
    address: 'Test Address',
    city: 'Test City',
    country: 'IN',
    state: 'KA',
    pin_code: '123456',
    phone_no: '+919308490211',
  },
  items: [
    {
      code: 'mock-product',
      count: 1,
      period: 'monthly',
    },
  ],
};

export const MOCK_REJECTED_WITH_REFUND_COMPLETED: OrderDetailsItem = {
  id: 'mock-order-id-fifth',
  order_id: 'order_mock-order-id-fifth',
  merchant_id: 'mock-user-id',
  status: 'rejected',
  order: null,
  payment: {
    status: 'refunded',
    refund_status: 'full',
    amount: 2360,
    captured: true,
    created_at: 1697174813,
  },
  refund: {
    id: 'mock-refund-id',
    payment_id: 'mock-payment-id',
    acquirer_data: {
      ABQ: 'mock-ref-id',
    },
    amount: 2360,
    created_at: 1697174813,
    status: 'processed',
  },
  created_at: 1696439810,
  arriving_at: 0,
  delivered_at: 0,
  rejected_at: 1696871810,
  rejection_reasons: {
    error_code: '',
    error_reason: '',
    error_description: 'Failed because of Something',
  },
  amount: {
    base: 2000,
    gst: 360,
    total: 2360,
  },
  rental_amount: {
    base: 499,
    gst: 89,
    total: 588,
  },
  delivery_address: {
    name: 'Test Name',
    address: 'Test Address',
    city: 'Test City',
    country: 'IN',
    state: 'KA',
    pin_code: '123456',
    phone_no: '+919308490211',
  },
  items: [
    {
      code: 'mock-product',
      count: 1,
      period: 'monthly',
    },
  ],
};

export const MOCK_ORDER_LIST = [
  MOCK_PAID_ORDER_ITEM,
  MOCK_DELIVERED_ORDER_ITEM,
  MOCK_REJECTED_ORDER_ITEM,
];

export const MOCK_LATEST_ORDER_WITH_DELIVERED_STATUS = {
  ...MOCK_PRE_CHECKOUT_ORDER,
  status: 'delivered',
  order: {
    id: 'order_mock-order-id',
  },
};

export const MOCK_LATEST_ORDER_WITH_PAID_STATUS = {
  ...MOCK_PRE_CHECKOUT_ORDER,
  status: 'paid',
  order: {
    id: 'order_mock-order-id',
  },
};
