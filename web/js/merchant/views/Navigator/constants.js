export const NETBANKING_FEATURES = 'Netbanking Features';
export const UPI_FEATURES = 'UPI Features';

export const INIT_PROVIDER_STATE = {
  Provider_name: '',
  Description: '',
  Gateway: '',
  Gateway_details: {
    'Payment Methods': [],
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

export const TPV_OPTIONS = [
  { label: 'Non TPV', value: 0 },
  { label: 'TPV Only', value: 1 },
  { label: 'Both (TPV and Non TPV)', value: 2 },
];

export const HAVE_UPI_FEATURES = ['upi_mindgate', 'upi_icici', 'upi_axis'];
export const HAVE_NETBANKING_FEATURES = ['atom'];
