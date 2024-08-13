export const MERCHANT_OTP_VERIFY_SUCCESS = {
  status_code: 200,
  success: true,
  data: {
    token: 'mock_token_123',
  },
};

export const MERCHANT_OTP_VERIFY_DUPLICATE_USER_ERROR = {
  status_code: 400,
  success: false,
  errors: [
    {
      internal_error_code: 'BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS',
      description: 'User already exists',
    },
  ],
};

export const MERCHANT_OTP_VERIFY_ERROR = {
  status_code: 400,
  success: false,
  errors: [
    {
      internal_error_code: 'RANDOM_ERROR',
      description: 'Some error occured',
    },
  ],
};

export const MERCHANT_ID_REGISTER_SUCCESS = {
  status_code: 200,
  success: true,
  data: {
    id: 'merchant1234',
    name: 'Amitabh',
    email: 'amitabh@razorpay.com',
    merchants: [
      {
        id: 'merchant1234',
        name: 'Amitabh',
      },
    ],
  },
};

export const MERCHANT_ID_REGISTER_ERROR = {
  status_code: 400,
  success: false,
  errors: ['Something went wrong'],
};
