export const INIT_PROVIDER_STATE = {
  Provider_name: '',
  Description: '',
  Gateway: '',
  Gateway_details: {
    'Payment Methods': [],
    'UPI Features': { tpv: 0 },
  },
};

export const INIT_FORM_STATE = {
  isEdit: true,
  provider: {},
  selectedProvider: '',
  steps: {
    1: {
      edit: false,
      show: true,
    },
    2: {
      edit: true,
      show: true,
    },
    3: {
      edit: false,
      show: true,
    },
  },
};
