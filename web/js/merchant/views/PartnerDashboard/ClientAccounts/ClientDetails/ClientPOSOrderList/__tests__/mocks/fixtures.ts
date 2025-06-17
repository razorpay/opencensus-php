import { OrderDetailsItem, ProductDescription } from '../../types';

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
export const MOCK_ORDER_LIST = [
  MOCK_PAID_ORDER_ITEM,
  MOCK_DELIVERED_ORDER_ITEM,
  MOCK_REJECTED_ORDER_ITEM,
];

export const MOCK_PRODUCT_PRICING_RESPONSE = [
  {
    name: 'MOCK PRODUCT',
    code: 'mock-product',
    entity_type: 'merchant',
    rate_config: {
      monthly: 300,
      lifetime: 12000,
      setup_fee: 200,
    },
  },
  {
    name: 'MOCK PRODUCT NEW',
    code: 'mock-product-new',
    entity_type: 'merchant',
    rate_config: {
      monthly: 300,
      lifetime: 12000,
      setup_fee: 200,
    },
  },
];

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
          prevValue: null,
          nextValue: null,
        },
        {
          key: 'setup_fee',
          description: 'Setup Fee',
          value: 0,
          suffix: 'setup fee',
          isExtraFee: true,
          isChargeableAtCheckout: true,
          prevValue: null,
          nextValue: null,
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
          prevValue: null,
          nextValue: null,
        },
      ],
    },
  ],
  isPartnerPricing: false,
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
  offer: null,
  rentalDiscountPeriod: 3,
};
