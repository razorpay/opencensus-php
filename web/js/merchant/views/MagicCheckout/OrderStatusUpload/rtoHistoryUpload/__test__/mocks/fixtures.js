export const MockFetchSuccessPayload = {
  status_code: 200,
  success: true,
  data: {
    merchant_file_upload_audits: [
      {
        merchant_id: 'JCVHSjHRi9QHto',
        shipping_provider: 'Shiprocket',
        file_id: 'file_KFkSSPUmovBszP',
        created_at: '1662714846',
      },
    ],
    create_allowed: true,
  },
};

export const MockTimeErrorPayload = {
  status_code: 400,
  success: false,
  errors: ['merchant_upload_time_period_expired_error'],
};

export const MockFetchResponse = {
  merchant_file_upload_audits: [
    {
      merchant_id: 'JCVHSjHRi9QHto',
      shipping_provider: 'Shiprocket',
      file_id: 'file_KFkSSPUmovBszP',
      created_at: '1662714846',
    },
  ],
  create_allowed: true,
};

export const MockCreateErrorResponse = {
  status_code: 400,
  success: false,
  errors: [
    'BAD_REQUEST_ERROR: shipping_provider: cannot be blank. code: invalid_request',
    'Status Code: 400',
  ],
};
