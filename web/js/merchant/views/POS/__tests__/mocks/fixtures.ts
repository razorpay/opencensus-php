import SoundboxImage from 'assets/pos/main-banner/soundbox-kit.webp';

import store from 'merchant/store';
import {
  ApiResponse,
  OrderDetailsItem,
  ProductDescription,
  ProductDescriptionPricing,
  ProductFeaturesColumn,
  PricingTypes,
  PricingBreakupkeys,
  ProductPlans,
  DetailedPricingModel,
} from 'merchant/views/POS/types';

import { CtaType, PosPricingDescription, Variant } from '../../Catalog/ProductCards/PosProductCard';

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
  merchant_business_detail: {
    website_details: {
      physical_store: true,
    },
  },
  pos_activation_status: 'under_review',
  pos_activation_flow: 'whitelist',
  is_pgos_merchant: true,
  business_type: '1',
  isCountryIndia: true,
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
        prevValue: null,
        nextValue: 700,
      },
      {
        key: 'setup_fee',
        description: 'Monthly Subscription',
        value: 1200,
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
        value: 12000,
        suffix: '',
        isExtraFee: false,
        isChargeableAtCheckout: true,
        prevValue: null,
        nextValue: null,
      },
    ],
  },
];

export const MOCK_PRICING_WITH_ONLY_LIFETIME_PLAN: ProductDescriptionPricing[] = [
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
        prevValue: null,
        nextValue: null,
      },
    ],
  },
];

export const MOCK_DETAILED_PRICING: DetailedPricingModel[] = [
  {
    title: null,
    banner: 'info',
    rows: [
      {
        name: 'Particulars',
        value: 'MDR',
      },
    ],
  },
  {
    title: 'Credit Card (Visa/Master/Rupay)',
    rows: [
      {
        name: 'Grocery Stores & Supermarkets',
        value: '1.30%',
        isOfferOnlyField: true,
      },
      {
        name: 'Utility, Govt., Education, Fuel, Insurance',
        value: '1.00%',
        prevValue: '1.10%',
        isOfferOnlyField: true,
      },
      {
        name: 'Other segments',
        value: '1.75%',
        prevValue: '1.85%',
      },
      {
        name: 'International Card/Corp cards/Amex/Diners',
        value: '2.75%',
        prevValue: ' 3.00%',
      },
    ],
  },
  {
    title: 'Debit Card & BQR through Debit Card (Excl Rupay)',
    rows: [
      {
        name: '<2000*',
        value: '0.40%',
      },
      {
        name: '>2000*',
        value: '0.90%',
      },
      {
        name: 'UPI/Rupay Debit Card',
        value: '0.00%',
      },
    ],
  },
];

export const MOCK_PRICING_WITH_PRICES_WITH_OFFER: ProductDescriptionPricing[] = [
  {
    name: 'Monthly Plan',
    type: 'monthly',
    subText: '*Subscription only starts when device gets delivered. GST charges applicable.',
    breakups: [
      {
        key: 'monthly',
        description: 'Monthly Subscription',
        value: 100,
        suffix: '/mo',
        isExtraFee: false,
        isChargeableAtCheckout: false,
        prevValue: 400,
        nextValue: 700,
      },
      {
        key: 'setup_fee',
        description: 'Monthly Subscription',
        value: 1000,
        suffix: 'setup fee',
        isExtraFee: true,
        isChargeableAtCheckout: true,
        prevValue: 1200,
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
        value: 12000,
        suffix: '',
        isExtraFee: false,
        isChargeableAtCheckout: true,
        prevValue: 30000,
        nextValue: null,
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

export const MOCK_PARTNER_PRODUCT_PRICING_RESPONSE = [
  {
    name: 'MOCK PRODUCT',
    code: 'mock-product',
    entity_type: 'partner',
    rate_config: {
      monthly: 300,
      lifetime: 12000,
      setup_fee: 200,
    },
  },
  {
    name: 'MOCK PRODUCT NEW',
    code: 'mock-product-new',
    entity_type: 'partner',
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

export const MOCK_CMMA_CASE_CREATE_CALL = {
  pos_activation_status: 'under_review',
  is_pgos_merchant: true,
  pos_activation_flow: 'whitelist',
  is_pos_details_submitted: true,
};

export const MOCK_PRODUCT_PRICING_WITH_OFFER_RESPONSE = [
  {
    name: 'MOCK PRODUCT',
    code: 'mock-product',
    offer: {
      valid_till: 1707983687,
      offer_text: 'Mock offer text',
      prev_rate_config: {
        monthly: 400,
        lifetime: 20000,
        setup_fee: 300,
      },
    },
    rate_config: {
      monthly: 300,
      lifetime: 12000,
      setup_fee: 200,
    },
  },
  {
    name: 'MOCK PRODUCT NEW',
    code: 'mock-product-new',
    offer: {
      valid_till: 1707983687,
      offer_text: 'Mock offer text',
      prev_rate_config: {
        monthly: 400,
        lifetime: 20000,
        setup_fee: 300,
      },
    },
    rate_config: {
      monthly: 300,
      lifetime: 12000,
      setup_fee: 200,
    },
  },
];

//The pricing keys like [monthly, setup_fee] should match the keys with devcie config api
export const MOCK_PRODUCT_OFFER_CONFIG = {
  'mock-product': {
    offerText: 'Mock offer text',
    pdpOfferText: 'Mock offer text',
    partnerOfferText: 'Mock partner offer text',
    partnerPdpOfferText: 'Mock partner pdp offer text',
    preRateConfig: {
      monthly: 400,
      lifetime: 20000,
      setup_fee: 300,
    },
    nextRateConfig: {
      monthly: 299,
      lifetime: null,
      setup_fee: null,
    },
  },
};

export const MOCK_PRODUCT_OFFERS = {
  a910: {
    offerText: 'Limited Time Offer till 31st May',
    pdpOfferText: 'Offer valid on orders placed before 31st May',
    partnerOfferText: 'Partner Exclusive Time Offer till 31st May',
    partnerPdpOfferText: 'Partner offer valid on orders placed before 31st May',
    preRateConfig: {
      monthly: 549,
      lifetime: 12000,
      setup_fee: 3000,
    },
    nextRateConfig: {
      monthly: 299,
      lifetime: null,
      setup_fee: null,
    },
  },
  a50: {
    offerText: 'Limited Time Offer till 31st May',
    pdpOfferText: 'Offer valid on orders placed before 31st May',
    partnerOfferText: 'Partner Exclusive Time Offer till 31st May',
    partnerPdpOfferText: 'Partner offer valid on orders placed before 31st May',
    preRateConfig: {
      monthly: 499,
      lifetime: 10500,
      setup_fee: 2000,
    },
    nextRateConfig: {
      monthly: 249,
      lifetime: null,
      setup_fee: null,
    },
  },
};

export const getMockModularResponse = ({
  consented = '',
  isCustomRateEnabled = true,
  isAgreementRequired = true,
}) => ({
  workflow_data: {
    id: 'ORjaqbNGKAqMgg',
    milestones: [
      {
        can_submit: false,
        meta: {
          template: 'grid',
          title: 'Merchant Onboarding',
        },
        name: 'sales_milestone',
        progress: 25,
        status: 'processing',
        steps: [
          {
            components: [
              {
                fields: [
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: true,
                    is_required: false,
                    meta: {
                      data_type: 'string',
                    },
                    name: 'device_item_id_field',
                    user_comments: '',
                    value: '',
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: false,
                    meta: {
                      data_type: 'string',
                    },
                    name: 'device_item_name_field',
                    user_comments: '',
                    value: '',
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: false,
                    meta: {
                      data_type: 'string',
                    },
                    name: 'device_item_plan_field',
                    user_comments: '',
                    value: '',
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'int',
                    },
                    name: 'device_item_quantity_field',
                    user_comments: '',
                    value: 0,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'radio',
                      options: [
                        {
                          label: 'Standard',
                          value: 'standard',
                        },
                        {
                          label: 'Custom',
                          value: 'custom',
                        },
                      ],
                      title: 'Setup Fee',
                    },
                    name: 'device_item_setup_fee_type_field',
                    user_comments: '',
                    value: '',
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: false,
                    meta: {
                      data_type: 'float',
                    },
                    name: 'device_item_custom_setup_fee_field',
                    user_comments: '',
                    value: 0,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'radio',
                      options: [
                        {
                          label: 'Standard',
                          value: 'standard',
                        },
                        {
                          label: 'Custom',
                          value: 'custom',
                        },
                      ],
                      title: 'Setup Fee',
                    },
                    name: 'device_item_monthly_rental_charges_type_field',
                    user_comments: '',
                    value: '',
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: true,
                    is_internal: false,
                    is_required: false,
                    meta: {
                      data_type: 'float',
                    },
                    name: 'device_item_custom_monthly_rental_charges_field',
                    user_comments: '',
                    value: 0,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'checkbox',
                      title: 'Collecting Rental Charges in Advance',
                    },
                    name: 'device_item_advanced_rental_field',
                    user_comments: '',
                    value: false,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: false,
                    meta: {
                      data_type: 'int',
                    },
                    name: 'device_item_advanced_rental_months_field',
                    user_comments: '',
                    value: 0,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {},
                    name: 'device_add_to_cart_field',
                    user_comments: '',
                    value: false,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: true,
                    is_required: false,
                    meta: {
                      data_type: 'deviceOrderItemSummaryList',
                    },
                    name: 'device_order_items_summary_field',
                    user_comments: '',
                    value: null,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: true,
                    is_required: false,
                    meta: {
                      data_type: 'string',
                    },
                    name: 'device_order_summary_field',
                    user_comments: '',
                    value: {
                      advance_rental_charge: 0,
                      device_charge: 0,
                      gst: 0,
                      order_id: '',
                      paper_roll_charge: 0,
                      rental_charge: null,
                      shipping_charge: 0,
                      total_order_charge: 0,
                      total_rental_charge: 0,
                    },
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {},
                    name: 'device_selection_completion_field',
                    user_comments: '',
                    value: false,
                  },
                ],
                is_required: true,
                meta: {
                  description: 'Choose Suitable Devices for your merchant',
                  device_config: [
                    {
                      default_values: {
                        device_item_advanced_rental_field: false,
                        device_item_monthly_rental_charges_type_field: 'standard',
                        device_item_plan_field: 'monthly',
                        device_item_quantity_field: 1,
                        device_item_setup_fee_type_field: 'standard',
                      },
                      display_name: 'Andriod Smart Pos',
                      icon: 'andriod_smart_pos_device.img',
                      rate_config: [
                        {
                          active: false,
                          advanced_rental_months: 0,
                          name: 'Standard',
                          paper_roll_charges: 15,
                          plans: [
                            {
                              plan_name: 'Monthly',
                              rental_charges: 100,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Quarterly',
                              rental_charges: 200,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Half Yearly',
                              rental_charges: 400,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Yearly',
                              rental_charges: 600,
                              setup_fee: 2000,
                            },
                            {
                              one_time_charge: 4999,
                              plan_name: 'Lifetime',
                            },
                          ],
                          rental_discount_months: 0,
                        },
                        {
                          active: true,
                          advanced_rental_months: 0,
                          name: 'POS Sale',
                          paper_roll_charges: 15,
                          plans: [
                            {
                              plan_name: 'Monthly',
                              rental_charges: 100,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Quarterly',
                              rental_charges: 200,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Half Yearly',
                              rental_charges: 400,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Yearly',
                              rental_charges: 600,
                              setup_fee: 2000,
                            },
                            {
                              one_time_charge: 4999,
                              plan_name: 'Lifetime',
                            },
                          ],
                          rental_discount_months: 0,
                        },
                      ],
                    },
                    {
                      default_values: {
                        device_item_advanced_rental_field: false,
                        device_item_monthly_rental_charges_type_field: 'standard',
                        device_item_plan_field: 'monthly',
                        device_item_quantity_field: 1,
                        device_item_setup_fee_type_field: 'standard',
                      },
                      display_name: 'Andriod Smart mini Pos',
                      icon: 'andriod_smart_pos_device.img',
                      rate_config: [
                        {
                          active: false,
                          advanced_rental_months: 0,
                          name: 'Standard',
                          paper_roll_charges: 15,
                          plans: [
                            {
                              plan_name: 'Monthly',
                              rental_charges: 100,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Quarterly',
                              rental_charges: 200,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Half Yearly',
                              rental_charges: 400,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Yearly',
                              rental_charges: 600,
                              setup_fee: 2000,
                            },
                            {
                              one_time_charge: 4999,
                              plan_name: 'Lifetime',
                            },
                          ],
                          rental_discount_months: 0,
                        },
                        {
                          active: true,
                          advanced_rental_months: 0,
                          name: 'POS Sale',
                          paper_roll_charges: 15,
                          plans: [
                            {
                              plan_name: 'Monthly',
                              rental_charges: 100,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Quarterly',
                              rental_charges: 200,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Half Yearly',
                              rental_charges: 400,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Yearly',
                              rental_charges: 600,
                              setup_fee: 2000,
                            },
                            {
                              one_time_charge: 4999,
                              plan_name: 'Lifetime',
                            },
                          ],
                          rental_discount_months: 0,
                        },
                      ],
                    },
                    {
                      default_values: {
                        device_item_advanced_rental_field: false,
                        device_item_monthly_rental_charges_type_field: 'standard',
                        device_item_plan_field: 'monthly',
                        device_item_quantity_field: 1,
                        device_item_setup_fee_type_field: 'standard',
                      },
                      display_name: 'mPOS (Mobile POS)',
                      icon: 'andriod_smart_pos_device.img',
                      rate_config: [
                        {
                          active: false,
                          advanced_rental_months: 0,
                          name: 'Standard',
                          paper_roll_charges: 15,
                          plans: [
                            {
                              plan_name: 'Monthly',
                              rental_charges: 100,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Quarterly',
                              rental_charges: 200,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Half Yearly',
                              rental_charges: 400,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Yearly',
                              rental_charges: 600,
                              setup_fee: 2000,
                            },
                            {
                              one_time_charge: 4999,
                              plan_name: 'Lifetime',
                            },
                          ],
                          rental_discount_months: 0,
                        },
                        {
                          active: true,
                          advanced_rental_months: 0,
                          name: 'POS Sale',
                          paper_roll_charges: 15,
                          plans: [
                            {
                              plan_name: 'Monthly',
                              rental_charges: 100,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Quarterly',
                              rental_charges: 200,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Half Yearly',
                              rental_charges: 400,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Yearly',
                              rental_charges: 600,
                              setup_fee: 2000,
                            },
                            {
                              one_time_charge: 4999,
                              plan_name: 'Lifetime',
                            },
                          ],
                          rental_discount_months: 0,
                        },
                      ],
                    },
                    {
                      default_values: {
                        device_item_advanced_rental_field: false,
                        device_item_monthly_rental_charges_type_field: 'standard',
                        device_item_plan_field: 'monthly',
                        device_item_quantity_field: 1,
                        device_item_setup_fee_type_field: 'standard',
                      },
                      display_name: 'Soundbox',
                      icon: 'andriod_smart_pos_device.img',
                      rate_config: [
                        {
                          active: false,
                          advanced_rental_months: 0,
                          name: 'Standard',
                          paper_roll_charges: 15,
                          plans: [
                            {
                              plan_name: 'Monthly',
                              rental_charges: 100,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Quarterly',
                              rental_charges: 200,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Half Yearly',
                              rental_charges: 400,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Yearly',
                              rental_charges: 600,
                              setup_fee: 2000,
                            },
                            {
                              one_time_charge: 4999,
                              plan_name: 'Lifetime',
                            },
                          ],
                          rental_discount_months: 0,
                        },
                        {
                          active: true,
                          advanced_rental_months: 0,
                          name: 'POS Sale',
                          paper_roll_charges: 15,
                          plans: [
                            {
                              plan_name: 'Monthly',
                              rental_charges: 100,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Quarterly',
                              rental_charges: 200,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Half Yearly',
                              rental_charges: 400,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Yearly',
                              rental_charges: 600,
                              setup_fee: 2000,
                            },
                            {
                              one_time_charge: 4999,
                              plan_name: 'Lifetime',
                            },
                          ],
                          rental_discount_months: 0,
                        },
                      ],
                    },
                    {
                      default_values: {
                        device_item_advanced_rental_field: false,
                        device_item_monthly_rental_charges_type_field: 'standard',
                        device_item_plan_field: 'monthly',
                        device_item_quantity_field: 1,
                        device_item_setup_fee_type_field: 'standard',
                      },
                      display_name: 'Dynamic HQ',
                      icon: 'andriod_smart_pos_device.img',
                      rate_config: [
                        {
                          active: false,
                          advanced_rental_months: 0,
                          name: 'Standard',
                          paper_roll_charges: 15,
                          plans: [
                            {
                              plan_name: 'Monthly',
                              rental_charges: 100,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Quarterly',
                              rental_charges: 200,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Half Yearly',
                              rental_charges: 400,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Yearly',
                              rental_charges: 600,
                              setup_fee: 2000,
                            },
                            {
                              one_time_charge: 4999,
                              plan_name: 'Lifetime',
                            },
                          ],
                          rental_discount_months: 0,
                        },
                        {
                          active: true,
                          advanced_rental_months: 0,
                          name: 'POS Sale',
                          paper_roll_charges: 15,
                          plans: [
                            {
                              plan_name: 'Monthly',
                              rental_charges: 100,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Quarterly',
                              rental_charges: 200,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Half Yearly',
                              rental_charges: 400,
                              setup_fee: 2000,
                            },
                            {
                              plan_name: 'Yearly',
                              rental_charges: 600,
                              setup_fee: 2000,
                            },
                            {
                              one_time_charge: 4999,
                              plan_name: 'Lifetime',
                            },
                          ],
                          rental_discount_months: 0,
                        },
                      ],
                    },
                  ],
                  is_custom_pricing_applicable: true,
                  template: 'grid',
                  title: '2. Device Selection & Ordering',
                },
                name: 'device_catalogue_component',
                progress: 0,
                status: 'pending',
                verification: null,
              },
            ],
            meta: {
              description:
                'Help your merchants optimise their transactions with the perfect POS devices',
              icon: 'Device Image',
              template: 'linear',
              title: '2. Device Selection & Ordering',
            },
            name: 'device_selection_step',
            progress: 0,
            status: 'pending',
          },
          {
            components: [
              {
                fields: [
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'radio',
                      options: [
                        {
                          label: 'Aggregator Model',
                          value: 'aggregator',
                        },
                        {
                          label: 'Direct Model',
                          value: 'direct',
                        },
                      ],
                      selection_type: 'single',
                    },
                    name: 'acquisition_model_field',
                    user_comments: '',
                    value: 'aggregator',
                  },
                ],
                is_required: true,
                meta: {
                  description: 'Choose payment methods & review MDR rates',
                  is_custom_pricing_applicable: true,
                  template: 'grid',
                  title: '3. Payment Method & Service Selection',
                },
                name: 'acquisition_model_component',
                progress: 100,
                status: 'executed',
                verification: null,
              },
              {
                fields: [
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: false,
                    meta: {},
                    name: 'custom_rates_enabled_field',
                    user_comments: '',
                    value: false,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: false,
                    meta: {
                      data_type: 'file_upload',
                      upload_type: 'multiple',
                      validations: [
                        {
                          condition: '5 MB',
                          errorMessage: 'File size greater than 5 MB',
                          type: 'maxFileSize',
                        },
                        {
                          condition: '5',
                          errorMessage: 'No of files greater than 5',
                          type: 'maxFiles',
                        },
                      ],
                    },
                    name: 'custom_rates_documents_field',
                    user_comments: '',
                    value: [],
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'float',
                    },
                    name: 'debit_card_rupay_mdr_rate_field',
                    user_comments: '',
                    value: 1,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'float',
                    },
                    name: 'debit_card_visa_mastercard_maestro_greater_than_2k_mdr_rate_field',
                    user_comments: '',
                    value: 1.1,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'float',
                    },
                    name: 'debit_card_visa_mastercard_maestro_less_than_2k_mdr_rate_field',
                    user_comments: '',
                    value: 1.2,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'true',
                    },
                    name: 'credit_card_mdr_rate_field',
                    user_comments: '',
                    value: 1,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'true',
                    },
                    name: 'prepaid_b2b_corporate_channel_international_card_mdr_rate_field',
                    user_comments: '',
                    value: 1.3,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'true',
                    },
                    name: 'upi_mdr_rate_field',
                    user_comments: '',
                    value: 0.5,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'true',
                    },
                    name: 'vas_cc_emi_rate_field',
                    user_comments: '',
                    value: 1.4,
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'float',
                    },
                    name: 'vas_dc_emi_rate_field',
                    user_comments: '',
                    value: 1.3,
                  },
                ],
                is_required: true,
                meta: {
                  description: 'Choose payment methods & review MDR rates',
                  is_custom_pricing_applicable: true,
                  template: 'grid',
                  title: '3. Payment Method & Service Selection',
                },
                name: 'mdr_vas_rates_component',
                progress: 100,
                status: 'executed',
                verification: null,
              },
              {
                fields: [
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: false,
                    meta: {
                      data_type: 'file_upload',
                      upload_type: 'single',
                      validations: [
                        {
                          condition: '5 MB',
                          errorMessage: 'File size greater than 5 MB',
                          type: 'maxFileSize',
                        },
                      ],
                    },
                    name: 'nach_form_document_field',
                    user_comments: '',
                    value: {
                      file_id: '',
                      file_store_id: '',
                      name: '',
                      size: 0,
                    },
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: false,
                    meta: {
                      data_type: 'string',
                      title: 'Sales Comments',
                    },
                    name: 'nach_form_comments_field',
                    user_comments: '',
                    value: '',
                  },
                ],
                is_required: true,
                meta: {
                  description: 'Upload NACH Form',
                  template: 'grid',
                  title: '5. Additional Details',
                },
                name: 'nach_form_component',
                progress: 100,
                status: 'executed',
                verification: null,
              },
            ],
            meta: {
              description: "Provide merchant's business information to start the POS journey",
              icon: 'Pricing Image',
              template: 'linear',
              title: '3. Payment Method & Service Selection',
            },
            name: 'pricing_step',
            progress: 100,
            status: 'executed',
          },
          {
            components: [
              {
                fields: [
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: false,
                    meta: {
                      data_type: 'file_upload',
                      upload_type: 'multiple',
                      validations: [
                        {
                          condition: '5 MB',
                          errorMessage: 'File size greater than 5 MB',
                          type: 'maxFileSize',
                        },
                        {
                          condition: '5',
                          errorMessage: 'No of files greater than 5',
                          type: 'maxFiles',
                        },
                      ],
                    },
                    name: 'agreement_documents_field',
                    user_comments: '',
                    value: [],
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: false,
                    meta: {
                      data_type: 'file_upload',
                      upload_type: 'single',
                      validations: [
                        {
                          condition: '5 MB',
                          errorMessage: 'File size greater than 5 MB',
                          type: 'maxFileSize',
                        },
                      ],
                    },
                    name: 'agreement_document_field',
                    user_comments: '',
                    value: {
                      file_id: '',
                      file_store_id: '',
                      name: '',
                      size: 0,
                    },
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'radio',
                      options: [
                        {
                          label: 'Online',
                          value: 'online',
                        },
                        {
                          label: 'Offline',
                          value: 'offline',
                        },
                      ],
                      title: 'Choose Mode of Agreement Signing',
                    },
                    name: 'agreement_type_field',
                    user_comments: '',
                    value: '',
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: true,
                    is_required: isAgreementRequired,
                    meta: {},
                    name: 'agreement_status_field',
                    user_comments: '',
                    value: consented,
                  },
                ],
                is_required: true,
                meta: {
                  description: 'Upload NACH Form',
                  template: 'grid',
                  title: '5. Additional Details',
                },
                name: 'agreement_component',
                progress: 0,
                status: 'pending',
                verification: null,
              },
            ],
            meta: {
              description: "Merchant's T&C and Pricing Agreement with Razorpay",
              icon: 'Agreement Image',
              template: 'linear',
              title: '6. Agreement Signing',
            },
            name: 'agreement_step',
            progress: 0,
            status: 'pending',
          },
          {
            components: [
              {
                fields: [
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {
                      data_type: 'string',
                    },
                    name: 'terms_and_conditions_consent_field',
                    user_comments: '',
                    value: {
                      Event: '',
                      RecreateId: '',
                      TemplateId: '',
                      Type: '',
                    },
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: true,
                    meta: {},
                    name: 'privacy_consent_field',
                    user_comments: '',
                    value: {
                      Event: '',
                      RecreateId: '',
                      TemplateId: '',
                      Type: '',
                    },
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: false,
                    is_required: isCustomRateEnabled,
                    meta: {},
                    name: 'pricing_consent_field',
                    user_comments: '',
                    value: {
                      Event: '',
                      RecreateId: '',
                      TemplateId: '',
                      Type: '',
                    },
                  },
                  {
                    failure_reason: '',
                    failure_reason_type: '',
                    is_editable: true,
                    is_hidden: false,
                    is_internal: true,
                    is_required: false,
                    meta: {},
                    name: 'agreement_status_field',
                    user_comments: '',
                    value: false,
                  },
                ],
                is_required: false,
                meta: {
                  templates: {
                    pricing_consent: '',
                    privacy_consent: 'CbmmvThVpCoLhK',
                    terms_and_conditions_consent: 'k73mkwp7p8jqGx',
                  },
                },
                name: 'consent_component',
                progress: 0,
                status: 'pending',
                verification: null,
              },
            ],
            meta: {},
            name: 'consent_step',
            progress: 0,
            status: 'pending',
          },
        ],
      },
    ],
    progress: 25,
    status: 'processing',
  },
  onboarding_state: {
    components: ['device_catalogue_component', 'agreement_component', 'consent_component'],
    milestones: ['sales_milestone'],
    steps: ['device_selection_step', 'agreement_step', 'consent_step'],
  },
  onboarding_status: '',
  onboarding_progress: 25,
  country_code: 'IN',
  onboarding_type: 'DEFAULT_ONBOARDING',
  merchant_type: 'Curlec Payments',
});

interface GetMockPropsForPosProductCardComponent {
  variant?: string;
  pricingDescription?: PosPricingDescription[];
  cta?: CtaType;
  footer?: {
    title: string;
    description: string;
  };
  isPartnerPricing?: boolean;
  hasOffer?: boolean;
}
export const getMockPropsForPosProductCardComponent = ({
  variant = 'left',
  pricingDescription,
  cta,
  footer,
  isPartnerPricing,
  hasOffer = true,
}: GetMockPropsForPosProductCardComponent) => ({
  productDescription: {
    gallery: [
      {
        main: 'https://localhost:8080/public/dist/images/main-5.600ceea792570089.webp',
        mobile: 'https://localhost:8080/public/dist/images/thumbnail-5.4e36abc249a68137.webp',
        thumbnail: 'https://localhost:8080/public/dist/images/thumbnail-5.4e36abc249a68137.webp',
      },
      {
        main: 'https://localhost:8080/public/dist/images/main-2.bd0fbe5dcdc230fd.webp',
        mobile: 'https://localhost:8080/public/dist/images/thumbnail-2.787af7c200cc3aae.webp',
        thumbnail: 'https://localhost:8080/public/dist/images/thumbnail-2.787af7c200cc3aae.webp',
      },
      {
        main: 'https://localhost:8080/public/dist/images/main-3.821901066a03b60d.webp',
        mobile: 'https://localhost:8080/public/dist/images/thumbnail-3.5f7bd97fefffad91.webp',
        thumbnail: 'https://localhost:8080/public/dist/images/thumbnail-3.5f7bd97fefffad91.webp',
      },
      {
        main: 'https://localhost:8080/public/dist/images/main-4.f436f16d456f8b04.webp',
        mobile: 'https://localhost:8080/public/dist/images/thumbnail-4.1234fe01013c3d4a.webp',
        thumbnail: 'https://localhost:8080/public/dist/images/thumbnail-4.1234fe01013c3d4a.webp',
      },
    ],
    code: 'wd10',
    name: 'android-mini-pos',
    productTitle: 'Soundbox kit',
    description:
      '1x Speaker, Volume up to 100 dB | 32-bit ARM CPU | 2000mAh Li-ion Battery. 1x Standee, 2x QR Stickers.',
    maxOrder: 0,
    cartImage: 'https://localhost:8080/public/dist/images/cart-img.9430b8ab5a5fe199.webp',
    pricing: [
      {
        name: 'Monthly Plan',
        type: 'monthly' as PricingTypes,
        subText: '*Subscription only starts when device gets delivered. GST charges applicable.',
        breakups: [
          {
            key: 'monthly' as PricingBreakupkeys,
            description: 'Monthly Subscription',
            value: 217,
            suffix: '/mo',
            isExtraFee: false,
            isChargeableAtCheckout: false,
            prevValue: 499,
            nextValue: 100,
          },
          {
            key: 'setup_fee' as PricingBreakupkeys,
            description: 'One Time Setup Fee',
            value: 0,
            suffix: 'setup fee',
            isExtraFee: true,
            isChargeableAtCheckout: true,
            prevValue: 2000,
            nextValue: null,
          },
        ],
      },
      {
        name: 'Lifetime Plan',
        type: 'lifetime' as PricingTypes,
        subText: '*No Setup fees required. GST charges applicable.',
        breakups: [
          {
            key: 'lifetime' as PricingBreakupkeys,
            description: 'Lifetime Plan',
            value: 999,
            suffix: '',
            isExtraFee: false,
            isChargeableAtCheckout: true,
            prevValue: 12000,
            nextValue: 999,
          },
        ],
      },
    ],
    isPartnerPricing: isPartnerPricing ?? false,
    featureGallery: [
      {
        image: 'https://localhost:8080/public/dist/images/thumbnail-2.787af7c200cc3aae.webp',
        title: 'Voice alerts',
        description: 'Instant audio confirmation on successful UPI payments',
        isImageFirst: false,
      },
      {
        image: 'https://localhost:8080/public/dist/images/thumbnail-3.5f7bd97fefffad91.webp',
        title: 'Always Connected',
        description: 'Connect seamlessly using a SIM card',
        isImageFirst: true,
      },
      {
        image: 'https://localhost:8080/public/dist/images/thumbnail-4.1234fe01013c3d4a.webp',
        title: 'Long-lasting battery life',
        description: 'Powerful battery that charges via micro USB',
        isImageFirst: false,
      },
    ],
    infoBanner: {
      image: 'https://localhost:8080/public/dist/images/info-banner.6b899853c88b2950.webp',
      mobileImage:
        'https://localhost:8080/public/dist/images/info-banner-mobile.adfb23e267205636.webp',
      features: [
        {
          icon: 'https://localhost:8080/public/dist/images/brightness.bbb7bf4376eef61d.svg',
          text: 'Clear QR code display',
        },
        {
          icon: 'https://localhost:8080/public/dist/images/connectivity.a388f587d8b775ca.svg',
          text: 'LED indicators to confirm connectivity',
        },
        {
          icon: 'https://localhost:8080/public/dist/images/alarm.468ac42463ee8fcd.svg',
          text: 'Sound notifications on updates and charging',
        },
        {
          icon: 'https://localhost:8080/public/dist/images/transaction-history.eb2b73a748f06372.svg',
          text: 'Transaction history available on the mPOS app',
        },
      ],
    },
    technicalSpecifications: [
      {
        category: 'Model',
        value: 'WD10 (With optional dynamic QR display)',
      },
      {
        category: 'Processor',
        value: '32-bit ARM based',
      },
      {
        category: 'Memory',
        value: 'RAM: 16MB ROM:16MB',
      },
      {
        category: 'Speaker',
        value: '403W, 1105dB >( 1M)',
      },
      {
        category: 'Charging',
        value: 'DC 5V/1A, USB Type-C connector',
      },
      {
        category: 'SIM',
        value: 'Single nano SIM slot',
      },
      {
        category: 'QR code size',
        value: 'Maximum 50mm',
      },
      {
        category: 'Ideal runtime',
        value: '200broadcastsadayfor 3days',
      },
      {
        category: 'Application',
        value: 'Supermarket, Convenience Store, Restaurant, Parking lot, Beauty Salon, Hotel',
      },
      {
        category: 'Language Support',
        value: 'Hindi, English (Other languages are customizable)',
      },
      {
        category: 'Operating Voltage',
        value: '3.7V - 4.2V',
      },
      {
        category: 'Standby current',
        value: '4G: 10mA; WIFI: 40mA',
      },
      {
        category: 'Data encryption mode',
        value: 'TLS',
      },
      {
        category: 'Communication Network',
        value: '2G,4G CAT1 / GPRS; WIFI (Optional)',
      },
      {
        category: 'Communication protocol',
        value: 'MQT',
      },
      {
        category: 'Frequency band',
        value: 'TDD-LTE: B34/B38/B39/B40/B41; GSM:900MHz/1800MHz',
      },
      {
        category: 'Environment',
        value: 'Operating temperature: -10°C ~ +60°C; Storage temperature: -20°C ~+70°C',
      },
      {
        category: 'Button',
        value: '1*Power Key 1*Function Key 2*Volume Up/Down Keys',
      },
      {
        category: 'Indicator Lights',
        value: '3color LED indicator light (blue, green and red)',
      },
      {
        category: 'Weight',
        value: '330g',
      },
      {
        category: 'Dimension',
        value:
          'Sound box size: 114mm*56mm*59mm | Panel size: Length xbreadth 114mm*155mm (Thickness:3.5mm)',
      },
      {
        category: 'Battery',
        value: '3.7V 2000mAh lithium manganate battery; Standby: ≥120H',
      },
    ],
    offer: hasOffer
      ? {
          offerText: 'Limited Time Offer till 31st March',
          pdpOfferText: 'Offer valid on orders placed before 31st March',
          partnerOfferText: 'Partner Exclusive Time Offer till 31st March',
          partnerPdpOfferText: 'Partner offer valid on orders placed before 31st March',
        }
      : null,
    shouldShowProductVarietyTable: false,
    linkedItems: [
      {
        image: 'https://localhost:8080/public/dist/images/thumbnail-1.c1321625c23544d7.webp',
        title: 'QR Sticker',
        offerLabel: 'Free with Combo Offer',
        quantity: 2,
      },
      {
        image: 'https://localhost:8080/public/dist/images/thumbnail-1.48b0d0fefc766d56.webp',
        title: 'Standee',
        offerLabel: 'Free with Combo Offer',
        quantity: 1,
      },
    ],
    rentalDiscountPeriod: 3,
  },
  title: 'Soundbox kit',
  description: 'Sample description',
  imageSrc: SoundboxImage,
  tncText: 'Lifetime free',
  plan: 'monthly' as ProductPlans,
  variant: variant as Variant,
  pricingDescription: pricingDescription ?? [
    {
      amount: {
        lifetime: 4500,
        monthly: 0,
        setupFee: 99,
        offer: {
          nextMonthly: 100,
          prevMonthly: 499,
          prevLifetime: 12000,
          prevSetupFee: 1000,
        },
      },
      type: 'monthly-rental',
    },
  ],
  cta: cta ?? {
    primary: {
      title: 'Add to cart',
      onClick: jest.fn(),
    },
    secondary: {
      title: 'Learn more',
      onClick: jest.fn(),
    },
  },
  footer,
});
