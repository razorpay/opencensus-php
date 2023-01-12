import { rest } from 'msw';

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

export const fetchBankAccountFailure = () => {
  return rest.get('*/account/bank_account', (req, res, ctx) => {
    return res(ctx.errors(['Some error occurred']), ctx.delay(50));
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

export const fetchBankAccountChangeStatusFailure = () => {
  return rest.get('*/merchants/:merchantId/bank_account_change/status', (req, res, ctx) => {
    return res(ctx.errors(['Some error occurred']), ctx.delay(50));
  });
};

export const fetchSettlementAmountSuccess = () => {
  return rest.get('*/settlements/amount', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: 20000,
      }),
      ctx.delay(50),
    );
  });
};

export const saveBankAccountChangesAutomateSuccessSync = () => {
  return rest.post('*/merchants/bank_account/update', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: {
          sync_flow: true,
          new_bank_account: {
            name: 'bank account name',
            ifsc: 'bank account ifsc',
            account_number: 'bank account number',
          },
        },
      }),
      ctx.delay(50),
    );
  });
};

export const saveBankAccountChangesAutomateSuccessAsync = () => {
  return rest.post('*/merchants/bank_account/update', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: {
          sync_flow: false,
        },
      }),
      ctx.delay(50),
    );
  });
};

export const saveBankAccountChangesAutomateSuccessAsyncTimeout = () => {
  return rest.post('*/merchants/bank_account/update', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: {
          timeout: true,
        },
      }),
      ctx.delay(50),
    );
  });
};

export const saveBankAccountChangesAutomateFailure = () => {
  return rest.post('*/merchants/bank_account/update', (req, res, ctx) => {
    return res(ctx.errors(['Some error occurred']), ctx.delay(50));
  });
};

export const saveBankAccountChangesSuccess = () => {
  return rest.post('*/merchants/bank_account', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: {},
      }),
      ctx.delay(50),
    );
  });
};

export const saveBankAccountChangesFailure = () => {
  return rest.post('*/merchants/bank_account', (req, res, ctx) => {
    return res(ctx.errors(['Some error occurred']), ctx.delay(50));
  });
};
