const MOCK_ADDRESS = {
  city: {
    value: 'Bengaluru',
  },
  country: {
    value: 'India',
  },
  district: {
    value: 'district',
  },
  line1: {
    value: '32, 1st ave',
  },
  line2: {
    value: '420',
  },
  state: {
    value: 'Karnataka',
  },
  zipCode: {
    value: '560034',
  },
};

export const MOCK_MERCHANT_DETAILS = {
  id: 'test_merchant',
  activation: {
    status: 'under_review',
    isPgosMerchant: true,
    isFormSubmitted: true,
  },
  contactPerson: {
    name: {
      value: 'Test Merchant',
    },
    phone: {
      value: {
        number: '1234567890',
      },
    },
  },
  business: {
    name: {
      value: 'Test Name',
      verificationStatus: null,
    },
    address: {
      registered: MOCK_ADDRESS,
      operation: MOCK_ADDRESS,
    },
  },
};

export const MERCHANT_ACTIVATION_SUCCESS_RESPONSE = {
  merchantById: {
    ...MOCK_MERCHANT_DETAILS,
  },
};

export const MERCHANT_ACTIVATION_ERROR_RESPONSE = {
  merchantById: {
    code: 200,
    success: false,
    message: 'Some error occured',
  },
};
