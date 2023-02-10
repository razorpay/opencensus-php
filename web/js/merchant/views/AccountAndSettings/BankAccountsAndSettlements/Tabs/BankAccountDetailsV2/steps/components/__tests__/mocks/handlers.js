import { rest } from 'msw';

export const bankAccountUpdateSuccess = () => {
  return rest.post('*/merchants/bank_account/file/upload', (req, res, ctx) => {
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
    );
  });
};

export const fetchWorkflowStatusSuccess = (response) => {
  return rest.get('*/merchant/bank_detail_update/details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: {
          ...response,
        },
      }),
    );
  });
};

export const uploadDocumentSuccess = () => {
  return rest.post('*/documents', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: {
          id: 'UVBKL83BU',
          display_name: 'Rzp_doc',
        },
      }),
    );
  });
};

export const submitClarificationSuccess = () => {
  return rest.post('*/merchant/submit_clarification/bank_detail_update', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: {
          id: 'UVBKL83BU',
        },
      }),
    );
  });
};

export const submitClarificationError = () => {
  return rest.post('*/merchant/submit_clarification/bank_detail_update', (req, res, ctx) => {
    return res(
      ctx.status(500),
      ctx.json({
        success: false,
        errors: null,
      }),
    );
  });
};
