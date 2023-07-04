export const DB_FEE_RULE = {
  id: 'LkdjCi1VH0FLsJ',
  fee_type: 'cod_charge',
  rule: {
    order_amount: {
      lte: 10000,
      gte: 0,
    },
  },
  rule_type: 'slab',
  fee: 2000,
  created_at: 1682996772,
  updated_at: 1682996772,
};
export const DB_ZONE = {
  id: 'M3QL7LS8fHaN4v',
  merchant_id: 'IU65DhHwe8cBOh',
  name: 'Zone 1',
  type: 'cod',
  locations: [
    {
      id: 'M3QL7LSAWmpRqo',
      type: 'serviceable',
      location_type: 'state',
      zipcode: '',
      state_code: 'AN',
      country_code: 'IN',
    },
    {
      id: 'M3QL7LSB4PGnlb',
      type: 'serviceable',
      location_type: 'state',
      zipcode: '',
      state_code: 'AP',
      country_code: 'IN',
    },
    {
      id: 'M3QLK6QOCIHAUY',
      type: 'serviceable',
      location_type: 'state',
      zipcode: '',
      state_code: 'AR',
      country_code: 'IN',
    },
  ],
  rate_type: null,
  fee_rules: null,
  created_at: 1687098085,
  updated_at: 1687098085,
};

export const DB_COUNTRIES = [
  {
    name: 'India',
    code: 'IN',
    zone_name: null,
    total_states: 5,
    states: [
      {
        name: 'Andaman and Nicobar Islands',
        code: 'AN',
        zone_name: 'Zone 1',
      },
      {
        name: 'Andhra Pradesh',
        code: 'AP',
        zone_name: 'Zone 1',
      },
      {
        name: 'Arunachal Pradesh',
        code: 'AR',
        zone_name: 'Zone 1',
      },
      {
        name: 'Assam',
        code: 'AS',
        zone_name: null,
      },
      {
        name: 'Bihar',
        code: 'BR',
        zone_name: null,
      },
    ],
  },
];

export const DB_ZONE_WITH_FEE = {
  id: 'M3QL7LS8fHaN4v',
  merchant_id: 'IU65DhHwe8cBOh',
  name: 'Zone 1',
  type: 'cod',
  locations: [
    {
      id: 'M3QL7LSAWmpRqo',
      type: 'serviceable',
      location_type: 'state',
      zipcode: '',
      state_code: 'AN',
      country_code: 'IN',
    },
    {
      id: 'M3QL7LSB4PGnlb',
      type: 'serviceable',
      location_type: 'state',
      zipcode: '',
      state_code: 'AP',
      country_code: 'IN',
    },
    {
      id: 'M3QLK6QOCIHAUY',
      type: 'serviceable',
      location_type: 'state',
      zipcode: '',
      state_code: 'AR',
      country_code: 'IN',
    },
  ],
  rate_type: null,
  fee_rules: [
    {
      id: 'M3QcUZ6BE7G4oO',
      fee_type: 'cod_fee',
      rule: {
        order_amount: {
          lt: 10000,
          gte: 0,
        },
      },
      rule_type: 'slab',
      fee: 1000,
      created_at: 1687099072,
      updated_at: 1687099072,
    },
  ],
  created_at: 1687098085,
  updated_at: 1687098085,
};

export const DB_CATEGORY = {
  id: 'M3QgGCFCecZmzd',
  name: 'Category 1',
  merchant_id: 'IU65DhHwe8cBOh',
  items: [
    {
      id: 'M3QgGCFENXDMBN',
      product_id: 'gid://shopify/Product/7254953459879',
      product_name: 'Product Variant - T Shirt',
      image_url:
        'https://cdn.shopify.com/s/files/1/0601/4173/2007/products/Tshirt-Black.webp?v=1657629306',
    },
  ],
  category_config: null,
  created_at: 1687099286,
  updated_at: 1687099286,
};

export const DB_CATEGORY_WITH_ZONE = {
  id: 'M3QgGCFCecZmzd',
  name: 'Category 1',
  merchant_id: '',
  item_count: 1,
  zones: [
    {
      id: 'M3QL7LS8fHaN4v',
      merchant_id: 'IU65DhHwe8cBOh',
      name: 'Zone 1',
      type: 'cod',
      fee_rules: [
        {
          id: 'M3QcUZ6BE7G4oO',
          fee_type: 'cod_fee',
          rule: {
            order_amount: {
              lt: 10000,
              gte: 0,
            },
          },
          rule_type: 'slab',
          fee: 1000,
          created_at: 1687099072,
          updated_at: 1687099072,
        },
      ],
    },
  ],
};

export const DB_PRODUCTS = [
  {
    id: 'gid://shopify/Product/7254953459879',
    image_url:
      'https://cdn.shopify.com/s/files/1/0601/4173/2007/products/Tshirt-Black.webp?v=1657629306',
    name: 'Product Variant - T Shirt',
    internal_id: 'M3QgGCFENXDMBN',
    internal_category: 'Category 1',
  },
  {
    id: 'gid://shopify/Product/7254953623719',
    image_url:
      'https://cdn.shopify.com/s/files/1/0601/4173/2007/products/82928123-men-s-shirts-set-folded-on-a-white-background.webp?v=1652191837',
    name: 'full sleeve t-shirt',
    internal_id: null,
    internal_category: null,
  },
  {
    id: 'gid://shopify/Product/7290519650471',
    image_url:
      'https://cdn.shopify.com/s/files/1/0601/4173/2007/products/1628523021canstockphoto22402523-arcos-creator.com_-1024x1024.jpg?v=1678780870',
    name: 'QA product',
    internal_id: null,
    internal_category: null,
  },
];

export const INITIAL_STATE = {
  magic_settings: {
    platform: 'shopify',
    cod_engine: true,
    cod_engine_type: 'location',
  },
  magicCODEngine: {
    loading: {
      summary: false,
      fee_rules: false,
      zones: false,
      item_categories: false,
      mapping: false,
    },
    error: {},
    editMode: true,
    configs: {
      cod_engine: true,
      cod_engine_type: 'slab_charges',
      shop_id: 'magic-checkout-test-store-1',
      engine: 'Basic',
      rate_slabs: true,
    },
    fee_rules: [],
    item_categories: [],
    zones: [],
    validations: {
      fee_rules: true,
      zones: true,
      item_categories: true,
    },
  },
};
