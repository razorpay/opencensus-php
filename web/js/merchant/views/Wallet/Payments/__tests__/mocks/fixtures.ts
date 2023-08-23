export const PaymentsResponse = {
  status_code: 200,
  success: true,
  data: {
    totals: null,
    total_count: '1',
    entities: {
      payments: [
        {
          id: 'ipayment_qwerty87654321',
          entity: 'load',
          account_id: 'iacc_abcdef12345678', // Mandatory
          amount: 5000, //Mandatory
          description: 'Description for this payment',
          reference_id: '133453', //Mandatory
          notes: null,
          status: 'success',
          failure_reason: null,
          created_at: 1234567890,
          merchantId: 'Lco1P8KKNy60GZ',
          contact: '9999999999',
        },
      ],
    },
  },
};
