import { GREYED, ACTIVATED } from 'merchant/views/Settings/PaymentMethods/constants';

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

const SHOW_MORE_METHODS_FEATURE_FLAG_ENABLED = {
  unlockIntlPaymentMethods: {
    showMorePaymentMethodsSection: true,
  },
};

const SHOW_MORE_METHODS_FEATURE_FLAG_DISABLED = {
  unlockIntlPaymentMethods: {
    showMorePaymentMethodsSection: false,
  },
};

const PURPOSE_CODE_PAN_NAME = {
  profile: { fircDetails: { data: { purpose_code: '12121' } } },
  session: { user: { promoter_pan_name: 'user1' } },
};

export const VIRTUAL_ACCOUNTS = [
  [
    'USD',
    [
      {
        input: () => ({
          input1: { leafList: getLeafListData(GREYED, 'USD') },
          input2: SHOW_MORE_METHODS_FEATURE_FLAG_ENABLED,
        }),
        output: {
          disabled: true,
        },
      },
      {
        input: () => ({
          input1: { leafList: getLeafListData(GREYED, 'USD') },
          input2: { ...SHOW_MORE_METHODS_FEATURE_FLAG_DISABLED, ...PURPOSE_CODE_PAN_NAME },
        }),
        output: {
          disabled: false,
        },
      },
      {
        input: () => ({
          input1: { leafList: getLeafListData(ACTIVATED, 'USD') },
          input2: PURPOSE_CODE_PAN_NAME,
        }),
        output: {
          activated: true,
        },
      },
    ],
  ],
  [
    'GBP',
    [
      {
        input: () => ({
          input1: { leafList: getLeafListData(GREYED, 'GBP') },
          input2: SHOW_MORE_METHODS_FEATURE_FLAG_ENABLED,
        }),
        output: {
          disabled: true,
        },
      },
      {
        input: () => ({
          input1: { leafList: getLeafListData(GREYED, 'GBP') },
          input2: { ...SHOW_MORE_METHODS_FEATURE_FLAG_DISABLED, ...PURPOSE_CODE_PAN_NAME },
        }),
        output: {
          disabled: false,
        },
      },
      {
        input: () => ({
          input1: { leafList: getLeafListData(ACTIVATED, 'GBP') },
          input2: PURPOSE_CODE_PAN_NAME,
        }),
        output: {
          activated: true,
        },
      },
    ],
  ],
  [
    'EUR',
    [
      {
        input: () => ({
          input1: { leafList: getLeafListData(GREYED, 'EUR') },
          input2: SHOW_MORE_METHODS_FEATURE_FLAG_ENABLED,
        }),
        output: {
          disabled: true,
        },
      },
      {
        input: () => ({
          input1: { leafList: getLeafListData(GREYED, 'EUR') },
          input2: { ...SHOW_MORE_METHODS_FEATURE_FLAG_DISABLED, ...PURPOSE_CODE_PAN_NAME },
        }),
        output: {
          disabled: false,
        },
      },
      {
        input: () => ({
          input1: { leafList: getLeafListData(ACTIVATED, 'EUR') },
          input2: PURPOSE_CODE_PAN_NAME,
        }),
        output: {
          activated: true,
        },
      },
    ],
  ],
];
