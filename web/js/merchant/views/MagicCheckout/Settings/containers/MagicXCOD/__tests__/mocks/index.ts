//Table Mocks

export const mockShippingMethods = [
  {
    shippingZone: 'Profile1',
    fee: 100,
    allow_cod: true,
    allow_prepaid: false,
    cod_fee_rules: {
      amount: {
        lt: 100000,
        gte: 0,
      },
    },
  },
  {
    profileName: 'Profile 2',
    fee: 200,
    allow_cod: false,
    allow_prepaid: false,
    cod_fee_rules: null,
  },
];

// Form Mocks
export const mockFormData = {
  allow_cod: true,
  allow_prepaid: false,
  cod_fee_rules: {
    amount: {
      gte: 100,
      lt: 1000,
    },
  },
  fee: 50,
};

export const shipping_profiles = {
  magicShippingEngine: {
    shipping_profiles: {
      profile1: {
        name: 'Profile1',
        zones: [{ id: 'zone1', name: 'Zone1', shipping_methods: [{ name: 'Method 1' }] }],
      },
    },
    isLoading: { summary: false },
  },
};
