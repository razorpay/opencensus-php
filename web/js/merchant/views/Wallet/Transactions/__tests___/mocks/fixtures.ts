export const listTransactionsResponse = {
  status_code: 200,
  success: true,
  data: {
    count: 10,
    entity: 'transaction',
    items: [
      {
        account_holder_name: 'QA',
        account_id: 'iacc_I9eCvXfHx7nzZf',
        amount: 200 * 100,
        debit: 200 * 100,
        credit: 0 * 100,
        instrument_id: 'instrument_I9eCvXfHx7nzZs',
        source: 'merchant',
        contact: '+91 9353231953',
        created_at: Date.now() / 1000,
        currency: 'INR',
        description: 'User load #1',
        email: 'qa@razorpay.com',
        id: 'I9eCvXfHx7nzZf',
        program_name: 'PPI Wallet',
        status: 'success',
        type: 'user',
        entity: 'payment',
      },
    ],
  },
};
