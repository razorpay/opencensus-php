export const DB_ZONE = {
  id: 'McQifBdC8lWapx',
  merchant_id: 'IU65DhHwe8cBOh',
  name: 'North',
  type: 'shipping',
  item_category_id: 'McQgFFRSMa224t',
  created_at: 1694741326,
  updated_at: 1694741326,
  locations: [
    {
      id: 'McQifBdDGe4I05',
      merchant_id: 'IU65DhHwe8cBOh',
      zone_id: 'McQifBdC8lWapx',
      zone_name: 'North',
      type: 'serviceable',
      location_type: 'state',
      zipcode: '',
      state_code: 'AN',
      country_code: 'IN',
      created_at: 1694741326,
      updated_at: 1694741326,
    },
  ],
};

export const DB_METHOD = {
  id: 'McQj7vw8EbBwMb',
  merchant_id: 'IU65DhHwe8cBOh',
  name: 'Standard',
  description: 'Standard',
  etd: '2-3 days',
  delivery_type: 'standard_delivery',
  attribute_rules: {
    customer_tags: {
      Test: 10000,
    },
  },
  fee_rules: {
    amount: {
      lt: 100000,
      gte: 0,
    },
    weight: {
      lt: 100,
      gte: 0,
    },
  },
  fee: 10000,
  allow_cod: true,
  created_at: 0,
  updated_at: 1694741363,
};

export const DB_CATEGORY = {
  id: 'McQn0qLDFPkmhw',
  merchant_id: 'IU65DhHwe8cBOh',
  name: 'Shirts',
  is_default: false,
  type: 'serviceable',
  created_at: 1694741573,
  updated_at: 1694741573,
  items: [
    {
      id: 'McQn0qLEmhlq3E',
      merchant_id: 'IU65DhHwe8cBOh',
      name: 'Product Variant - T Shirt',
      image_url:
        'https://cdn.shopify.com/s/files/1/0601/4173/2007/products/Tshirt-Black.webp?v=1657629306',
      reference_type: 'product',
      reference_id: '7254953459879',
      item_category_id: 'McQn0qLDFPkmhw',
      item_category_name: 'Shirts',
      created_at: 1694741573,
      updated_at: 1694741573,
    },
    {
      id: 'McQn0qLFWaA3ME',
      merchant_id: 'IU65DhHwe8cBOh',
      name: 'full sleeve t-shirt',
      image_url:
        'https://cdn.shopify.com/s/files/1/0601/4173/2007/products/82928123-men-s-shirts-set-folded-on-a-white-background.webp?v=1652191837',
      reference_type: 'product',
      reference_id: '7254953623719',
      item_category_id: 'McQn0qLDFPkmhw',
      item_category_name: 'Shirts',
      created_at: 1694741573,
      updated_at: 1694741573,
    },
  ],
};

export const DB_COUNTRIES = [
  {
    name: 'India',
    code: 'IN',
    states: [
      {
        name: 'Andaman and Nicobar Islands',
        code: 'AN',
      },
      {
        name: 'Andhra Pradesh',
        code: 'AP',
      },
    ],
  },
  {
    name: 'Afghanistan',
    code: 'AF',
    states: [],
  },
  {
    name: 'Aland Islands',
    code: 'AX',
    states: [],
  },
  {
    name: 'Albania',
    code: 'AL',
    states: [],
  },
];

export const DB_PRODUCTS = [
  {
    id: '7254953459879',
    image_url:
      'https://cdn.shopify.com/s/files/1/0601/4173/2007/products/Tshirt-Black.webp?v=1657629306',
    name: 'Product Variant - T Shirt',
    internal_id: null,
    internal_category: null,
  },

  {
    id: '7254953623719',
    image_url:
      'https://cdn.shopify.com/s/files/1/0601/4173/2007/products/82928123-men-s-shirts-set-folded-on-a-white-background.webp?v=1652191837',
    name: 'full sleeve t-shirt',
    internal_id: null,
    internal_category: null,
  },

  {
    id: '7290519650471',
    image_url:
      'https://cdn.shopify.com/s/files/1/0601/4173/2007/products/1628523021canstockphoto22402523-arcos-creator.com_-1024x1024.jpg?v=1678780870',
    name: 'QA product',
    internal_id: null,
    internal_category: null,
  },
];

export const DB_PROFILES = {
  shipping_profiles: [
    {
      id: 'McQgFFRSMa224t',
      name: 'All Other Products',
      is_default: true,
      type: 'shipping',
      item_count: 0,
      zones: [
        {
          id: 'McQifBdC8lWapx',
          merchant_id: '',
          name: 'North',
          type: 'shipping',
          created_at: 1694741326,
          updated_at: 1694741326,
          shipping_methods: [
            {
              id: 'McQj7vw8EbBwMb',
              merchant_id: 'IU65DhHwe8cBOh',
              name: 'Standard',
              description: 'Standard',
              etd: '2-3 days',
              delivery_type: 'standard_delivery',
              attribute_rules: {
                customer_tags: {
                  Test: 10000,
                },
              },
              fee_rules: {
                amount: {
                  lt: 100000,
                  gte: 0,
                },
                weight: {
                  lt: 100,
                  gte: 0,
                },
              },
              fee: 10000,
              allow_cod: true,
              created_at: 1694741353,
              updated_at: 1694741363,
            },
          ],
        },
      ],
    },
    {
      id: 'McQn0qLDFPkmhw',
      name: 'Shirts',
      is_default: false,
      type: 'serviceable',
      item_count: 2,
      zones: [],
    },
  ],
};

export const DEFAULT_PROFILE_NAME = 'All Other Products';

export const INITIAL_STATE = {
  magic_settings: {
    platform: 'shopify',
    cod_engine: true,
    cod_engine_type: 'location',
  },
  magicShippingEngine: {
    isLoading: {
      summary: false,
      fee_rules: false,
      zones: false,
      item_categories: false,
      shipping_methods: false,
    },
    shipping_profiles: {
      'All Other Products': {
        id: 'McQgFFRSMa224t',
        name: 'All Other Products',
        is_default: true,
        type: 'shipping',
        item_count: 0,
        zones: [
          {
            id: 'McQifBdC8lWapx',
            merchant_id: '',
            name: 'North',
            type: 'shipping',
            created_at: 1694741326,
            updated_at: 1694741326,
            shipping_methods: [
              {
                id: 'McQj7vw8EbBwMb',
                merchant_id: 'IU65DhHwe8cBOh',
                name: 'Standard',
                description: 'Standard',
                etd: '2-3 days',
                delivery_type: 'standard_delivery',
                attribute_rules: {
                  customer_tags: {
                    loyal_customers: 10000,
                  },
                },
                fee_rules: {
                  amount: {
                    lt: 100000,
                    gte: 0,
                  },
                  weight: {
                    lt: 100,
                    gte: 0,
                  },
                },
                fee: 10000,
                allow_cod: true,
                created_at: 1694741353,
                updated_at: 1694741363,
              },
            ],
          },
          {
            id: 'BbAB9wKFqJAZmn',
            merchant_id: '',
            name: 'Uploaded file 1',
            type: 'shipping',
            created_at: 1708001824,
            updated_at: 1708001824,
            shipping_methods: [],
            location_count: 4,
          },
        ],
      },
      Shirts: {
        id: 'McQn0qLDFPkmhw',
        name: 'Shirts',
        is_default: false,
        type: 'serviceable',
        item_count: 2,
        zones: [],
      },
    },
    selected_profile: {},
    validations: {
      zones: true,
      item_categories: true,
    },
    default_profile: {
      name: 'All Other Products',
    },
  },
};
