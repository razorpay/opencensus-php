export const STORES_MOCK_RESPONSE = {
  stores: {
    limit: 5,
    offset: 0,
    total: 10,
    stores: [
      {
        id: '1',
        name: 'Test Store 1',
        storeInfo: {
          storeCode: '123',
        },
        dates: {
          deletedAt: null,
        },
      },
      {
        id: '2',
        name: 'Test Store 2',
        storeInfo: {
          storeCode: '234',
        },
        dates: {
          deletedAt: null,
        },
      },
    ],
  },
};

export const STATES_AND_CITIES_MOCK_RESPONSE = {
  storesStatesAndCitiesByMerchantId: {
    states: ['Karnataka', 'Manipur'],
    cities: ['Bangalore', 'Imphal'],
  },
};
