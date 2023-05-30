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
  id: 'Lkdkkp2T5OQfsD',
  merchant_id: 'K1A0SYSrSgTqh5',
  name: 'Zone 1',
  type: 'cod',
  locations: [
    {
      id: 'Lkdkkp2UgGtH0f',
      type: 'serviceable',
      location_type: 'country',
      zipcode: '',
      state_code: '',
      country_code: 'AF',
    },
    {
      id: 'Lkdkkp2VAQXtxk',
      type: 'serviceable',
      location_type: 'country',
      zipcode: '',
      state_code: '',
      country_code: 'AL',
    },
  ],
  rate_type: null,
  fee_rule: null,
  item_categories: null,
  created_at: 1682996861,
  updated_at: 1682996861,
};

export const DB_COUNTRIES = [
  {
    name: 'Afghanistan',
    code: 'AF',
    zone_name: 'Zone 1',
    states: [],
    total_states: 0,
  },
  {
    name: 'Albania',
    code: 'AL',
    zone_name: 'Zone 1',
    states: [],
    total_states: 0,
  },
  {
    name: 'Algeria',
    code: 'DZ',
    zone_name: null,
    states: [],
    total_states: 0,
  },
  {
    name: 'India',
    code: 'IN',
    zone_name: null,
    total_states: 5,
    states: [
      {
        name: 'Andaman and Nicobar Islands',
        code: 'AN',
        zone_name: 'Zone 2',
      },
      {
        name: 'Andhra Pradesh',
        code: 'AP',
        zone_name: null,
      },
      {
        name: 'Arunachal Pradesh',
        code: 'AR',
        zone_name: null,
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

export const INITIAL_STATE = {
  magic_settings: {
    platform: 'shopify',
    cod_engine: true,
    cod_engine_type: 'location',
  },
  magicCODEngine: {
    loading: false,
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
    zones: [],
    validations: {
      fee_rules: true,
      zones: true,
    },
  },
};
