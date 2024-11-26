import { rest } from 'msw';

export const mockUser = {
  isAdminOrOwner: true,
  id: 'LEHESQGCPP3TsA',
  tags: [],
  activation_status: 'activated',
  activation_form_milestone: 'L2',
  merchant: {
    hold_funds: false,
  },
};

export const fetchBankAccountChangeStatusFailure = () => {
  return rest.get('*/merchants/:merchantId/bank_account_change/status', (req, res, ctx) => {
    return res(ctx.status(500), ctx.json(['Some error occurred']), ctx.delay(50));
  });
};

export const fetchBankAccountChangeStatusSuccess = () => {
  return rest.get('*/merchants/:merchantId/bank_account_change/status', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: false,
      }),
      ctx.delay(50),
    );
  });
};

export const fetchBankAccountSuccess = () => {
  return rest.get('*/account/bank_account', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: {
          name: 'bank account name',
          ifsc: 'bank account ifsc',
          account_number: 'bank account number',
        },
      }),
      ctx.delay(50),
    );
  });
};

export const mockFetchSettlementConfig = (status = true) => {
  return rest.post('*/settlements/dashboard/merchant_config/get', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          config: {
            features: {
              hold: {
                status,
              },
            },
            schedules: {
              payment: {
                'domestic:default': 'T+2 Working days',
                'international:default': 'T+7 Working days',
              },
            },
          },
        },
      }),
    );
  });
};

export const defaultState = {
  session: {
    user: mockUser,
    profile: {
      bankAccount: {
        id: 'ba_Kzh9hAp7KziEFU',
        entity: 'bank_account',
        ifsc: 'PYTM0123456',
        bank_name: 'Paytm Payments Bank',
        name: 'hgjkdsaf',
        notes: [],
        account_number: '1234567890',
        updated_at: 1672746857,
      },
    },
    org: {
      features: ['block_account_update'],
    },
  },
};

export const HOLD_CASES_FIXTURES = {
  ACTIVATED: {
    ...defaultState,
    session: {
      ...defaultState.session,
      user: {
        ...mockUser,
        activation_status: 'activated',
        activation_form_milestone: 'L2',
      },
    },
  },
  NON_ACTIVATED_WITH_L2: {
    ...defaultState,
    session: {
      ...defaultState.session,
      user: {
        ...mockUser,
        activation_status: 'mcc_pending',
        activation_form_milestone: 'L2',
      },
    },
  },
  NON_ACTIVATED_WITH_L1: {
    ...defaultState,
    session: {
      ...defaultState.session,
      user: {
        ...mockUser,
        activation_status: 'mcc_pending',
        activation_form_milestone: 'L1',
      },
      profile: {},
    },
  },
  FOH: {
    ...defaultState,
    session: {
      user: {
        ...mockUser,
        isAdminOrOwner: false,
        activation_status: 'activated',
        merchant: { hold_funds: true },
      },
      profile: {},
      org: {
        features: ['block_account_update'],
      },
    },
  },
  SOH: {
    ...defaultState,

    session: {
      user: {
        ...mockUser,
        isAdminOrOwner: false,
        activation_status: 'activated',
      },
      profile: {},
      org: {
        features: ['block_account_update'],
      },
    },
  },
};
