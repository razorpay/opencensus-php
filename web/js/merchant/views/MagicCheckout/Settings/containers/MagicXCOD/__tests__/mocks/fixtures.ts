export const mockServerData = {
  'Free shipping': {
    id: 'OqFGprTbo4rtoF',
    name: 'Free shipping',
    is_default: false,
    item_count: 1,
    zones: [
      {
        id: 'OqFHIdQJsPNaJc',
        name: 'Pan india',
        shipping_methods: [
          {
            id: 'OqFHjIJ8sV2nnV',
            name: 'Pan india',
            description: 'Free shipping',
            fee_rules: {
              amount: {
                lt: 100000,
                gte: 0,
              },
            },
            allow_cod: true,
            cod_fee_rules: {
              amount: {
                lt: 100000,
                gte: 0,
              },
            },
            created_at: '2022-01-01',
            updated_at: '2022-01-02',
          },
        ],
        created_at: 1729449836998,
      },
    ],
  },
};

export const mockTableData = [
  {
    id: 'OqFHjIJ8sV2nnV',
    name: 'Pan india',
    description: 'Free shipping',
    allow_cod: true,
    fee_rules: {
      amount: {
        lt: 100000,
        gte: 0,
      },
    },
    cod_fee_rules: {
      amount: {
        lt: 100000,
        gte: 0,
      },
    },
    zone_id: 'OqFHIdQJsPNaJc',
    item_category_id: 'OqFGprTbo4rtoF',
    shippingZone: 'Free shipping-Pan india',
    profileName: 'Free shipping',
    created_at: '2022-01-01',
    updated_at: '2022-01-02',
  },
];

export const mockFormData = {
  id: 'OqFHjIJ8sV2nnV',
  name: 'Pan india',
  description: 'Free shipping',
  allow_cod: true,
  fee_rules: {
    amount: {
      lt: 100000,
      gte: 0,
    },
  },
  cod_fee_rules: {
    amount: {
      lt: 1000,
      gte: 0,
    },
  },
  zone_id: 'OqFHIdQJsPNaJc',
  item_category_id: 'OqFGprTbo4rtoF',
  shippingZone: 'Free shipping-Pan india',
  profileName: 'Free shipping',
  created_at: '2022-01-01',
  updated_at: '2022-01-02',
};

export const mockPayload = {
  id: 'OqFHjIJ8sV2nnV',
  name: 'Pan india',
  description: 'Free shipping',
  item_category_id: 'OqFGprTbo4rtoF',
  allow_cod: true,
  cod_fee_rules: {
    amount: {
      lt: 100000,
      gte: 0,
    },
  },
  fee_rules: {
    amount: {
      lt: 100000,
      gte: 0,
    },
  },
  zone_id: 'OqFHIdQJsPNaJc',
  app_type: 'sopc',
};

export const mockShippingProfiles = {
  'Free Shipping 1': mockServerData['Free shipping'],
  'Free Shipping 2': {
    id: 'OqFGprTbo4rtoZ',
    name: 'Free shipping 2',
    is_default: false,
    item_count: 1,
    zones: [
      {
        id: 'OqFHIdQJsPNaJD',
        name: 'Pan india 2',
        shipping_methods: [
          {
            id: 'OqFHjIJ8sV2nnV',
            name: 'Pan india',
            description: 'Free shipping',
            fee_rules: {
              amount: {
                lt: 100000,
                gte: 0,
              },
            },
            allow_cod: true,
            cod_fee_rules: {
              amount: {
                lt: 100000,
                gte: 0,
              },
            },
            created_at: '2022-01-01',
            updated_at: '2022-01-02',
          },
        ],
        created_at: 1729449836988,
      },
    ],
  },
};
