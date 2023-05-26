export const accountDetailResponse = {
  status_code: 200,
  success: true,
  data: {
    items: [
      {
        account_id: 'iacc_I9eCvXfHx7nzZs',
        contact: '+91 9353231953',
        account_holder_name: 'Goutam B Seervi',
        merchant: 'I9eCvXfHx7nzZF',
        program: 'Wallet_PPI',
        balance: String(200 * 100),
        status: 'active',
        created_at: '1032373800',
        email: 'goutambseervi@gmail.com',
        full_kyc: true,
      },
    ],
  },
};

export const accountBalanceResponse = {
  status_code: 200,
  success: true,
  data: {
    available_balance: String(200 * 100),
    id: 'iacc_I9eCvXfHx7nzZs',
    limits: {
      monthly_load_limit: String(10000 * 100),
      monthly_load_limit_used: String(200 * 100),
      monthly_load_limit_balance: String(9800 * 100),
      yearly_load_limit: String(100000 * 100),
      yearly_load_limit_used: String(200 * 100),
      yearly_load_limit_balance: String(99800 * 100),
    },
  },
};
