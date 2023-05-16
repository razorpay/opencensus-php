export const getInstrumentData = (status, slug = 'ach', vaCurrency = 'USD') => ({
  icon: 'dummy',
  name: 'ACH transfer',
  description: 'US bank transfer',
  slug,
  vaCurrency,
  status,
});

export const getLeafListData = (status, vaCurrency = 'USD') => ({
  listHeader: 'dummy header',
  listDescription: 'dummy description',
  list: [
    {
      name: 'dummy list instrument name 1',
      description: 'dummy list instrument description 1',
      vaCurrency,
      status,
    },
  ],
});

export const getMockedFetchAccountBalance = (status = 'SUCCESS', payload) => {
  return () => (dispatch) => {
    return dispatch({
      type: `B2B_EXPORTS_GET_BALANCE::${status}`,
      payload,
    });
  };
};

export const getAccounts = (va_currency = 'USD') => [
  {
    routing_code: '0000',
    routing_type: '1111',
    account_number: '2222',
    beneficiary_name: 'dummy-beneficiary',
    va_currency,
  },
];
