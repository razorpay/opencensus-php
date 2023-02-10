import { rest } from 'msw';

export const saveBankAccountSuccess = () => {
  return rest.post('*/merchants/bank_account/update', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: {
          sync_flow: true,
          new_bank_account: {
            name: 'updated bank account name',
            ifsc: 'updated bank account ifsc',
            account_number: 'updated bank account number',
          },
        },
      }),
      ctx.delay(50),
    );
  });
};

export const saveBankAccountSuccessPennyTestFail = () => {
  return rest.post('*/merchants/bank_account/update', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: {
          create_workflow: true,
          sync_flow: true,
        },
      }),
      ctx.delay(50),
    );
  });
};

export const saveBankAccountSuccessTimeout = () => {
  return rest.post('*/merchants/bank_account/update', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: {
          create_workflow: true,
          sync_flow: true,
          timeout: true,
        },
      }),
      ctx.delay(50),
    );
  });
};

export const saveBankAccountSuccessApiFailure = () => {
  return rest.post('*/merchants/bank_account/update', (req, res, ctx) => {
    return res(ctx.status(400), ctx.json(['Some error occurred']), ctx.delay(50));
  });
};

export const saveBankAccountSuccessBvsInputError = () => {
  return rest.post('*/merchants/bank_account/update', (req, res, ctx) => {
    return res(
      ctx.json({
        status_code: 400,
        success: false,
        errors: ['KC07: Account Closed', 'Status Code: 400'],
      }),
      ctx.delay(50),
    );
  });
};
