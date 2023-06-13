export const accountDetailResponse = {
  status_code: 200,
  success: true,
  data: {
    entity: 'collections',
    count: 1,
    items: [
      {
        account_id: 'iacc_Ly9Ey7bZEttXUL',
        contact: '9999999999',
        account_holder_name: 'Container User',
        merchant: 'Container Merchant',
        program: 'Container_Program',
        balance: '0',
        status: 'active',
        email: 'container@razorpay.com',
        created_at: '1685946168',
        type: 'container',
        user_id: 'iuser_Ly9Ey7bZEttXUl',
        partner_customer_id: 'container_cust_001',
      },
    ],
  },
};

export const walletAccountDetailResponse = {
  status_code: 200,
  success: true,
  data: {
    entity: 'collections',
    count: 1,
    items: [
      {
        account_id: 'iacc_Ly9Ey7bZEttXUL',
        contact: '9999999999',
        account_holder_name: 'Razor',
        merchant: 'Razorpay Wallet Merchant',
        program: 'Razorpay_Employee_Wallet_Program',
        balance: '0',
        status: 'active',
        email: 'acme.corp@email.com',
        created_at: '1685946168',
        type: 'account',
        user_id: 'iuser_Ly9Ey7bZEttXUl',
        partner_customer_id: 'swiggy_001',
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
