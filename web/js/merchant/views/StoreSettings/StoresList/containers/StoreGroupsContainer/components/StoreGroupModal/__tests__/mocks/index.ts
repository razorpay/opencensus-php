export const STATES_AND_CITIES_MOCK_RESPONSE = {
  data: {
    storesStatesAndCitiesByMerchantId: {
      states: ['Karnataka', 'Manipur'],
      cities: ['Bangalore', 'Imphal'],
    },
  },
  isFetching: false,
};

export const STORE_INFO = { id: '123', name: 'Test Store', storeInfo: { storeCode: '123' } };

export const STORE_GROUP_INFO = {
  id: '123',
  name: 'Test Store Group Name',
  description: 'Test Store Group Description',
  stores: [STORE_INFO],
};
