export const FETCH_SUCCESS_RESPONSE = {
  data: {
    locations: [
      {
        country_code: 'IN',
        zipcode: 123456,
        created_at: 1704537453,
      },
      {
        country_code: 'IN',
        zipcode: 234567,
        created_at: 1704451053,
      },
    ],
    count: 2,
    failed: 0,
  },
};

export const INPUT_FIELDS = [
  {
    placeholderText: 'Enter zipcode',
    value: '123456',
  },
  {
    placeholderText: 'Enter count',
    value: '20',
  },
];

export const INIT_STATE = {
  codEngineAllowlistUpload: {
    items: [],
    total_records: 0,
    isLoading: false,
    error: null,
  },
};
