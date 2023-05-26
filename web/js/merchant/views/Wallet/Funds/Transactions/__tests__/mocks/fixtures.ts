export const listFundTransactionsResponse = {
  status_code: 200,
  success: true,
  data: {
    count: 10,
    entity: 'pool_transaction',
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

export const fundsSummaryResponse = {
  status_code: 200,
  success: true,
  data: {
    available_balance: String(200 * 100),
    id: 'iacc_I9eCvXfHx7nzZs',
    limits: [
      {
        monthly_load_limit: String(10000 * 100),
        monthly_load_limit_used: String(200 * 100),
        monthly_load_limit_balance: String(9800 * 100),
        yearly_load_limit: String(100000 * 100),
        yearly_load_limit_used: String(200 * 100),
        yearly_load_limit_balance: String(99800 * 100),
      },
    ],
  },
};
