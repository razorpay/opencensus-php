export const LoadsResponse = {
  status_code: 200,
  success: true,
  data: {
    totals: null,
    total_count: '1',
    entities: {
      recharges: [
        {
          id: 'iload_qwerty87654321',
          entity: 'load',
          account_id: 'iacc_abcdef12345678', // Mandatory
          amount: 5000, //Mandatory
          description: 'Description for this load',
          reference_id: '133453', //Mandatory
          notes: null,
          category: 'cashback',
          status: 'success',
          user_load: true,
          user_load_url: 'http:*.razorpay.com/v1/{qwerty87654321}',
          failure_reason: null,
          created_at: 1234567890,
          contact: '999999999',
        },
      ],
    },
  },
};
