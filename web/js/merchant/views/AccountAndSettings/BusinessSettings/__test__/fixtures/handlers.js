import { rest } from 'msw';

export const adminAsMerchantHandler = () => {
  rest.get('*/merchant/is_admin_as_merchant', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          data: {
            is_admin_as_merchant: true,
          },
        },
      }),
      ctx.delay(50),
    );
  });
};

export const updateMerchantConfigHandler = () => {
  return rest.put('*/merchant/api/*/account/config', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          display_name: 'John updated display name',
        },
      }),
      ctx.delay(50),
    );
  });
};

export const updateMerchantConfigErrorHandler = () => {
  return rest.put('*/merchant/api/*/account/config', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: false,
        errors: ['error in updating display name'],
      }),
      ctx.delay(50),
    );
  });
};

export const acceptInvitationHandler = ({ isErrorCase = false, errors = [] } = {}) => {
  return rest.post('*/settings/invitations/:inviteId/accept', (req, res, ctx) => {
    return res(
      isErrorCase
        ? ctx.json({
            status_code: 400,
            success: false,
            errors,
          })
        : ctx.json({
            status_code: 200,
            success: true,
          }),
      ctx.delay(50),
    );
  });
};

export const rejectInvitationHandler = ({ isErrorCase = false, errors = [] } = {}) => {
  return rest.post('*/invitations/:inviteId/reject', (req, res, ctx) => {
    return res(
      isErrorCase
        ? ctx.json({
            status_code: 400,
            success: false,
            errors,
          })
        : ctx.json({
            status_code: 200,
            success: true,
          }),
      ctx.delay(50),
    );
  });
};

export const fetchSupportDetail = () => {
  return rest.get('*/merchants/supportdetails', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          phone: '9677868778',
          email: 'support@example.com',
          url: '',
        },
      }),
    );
  });
};
