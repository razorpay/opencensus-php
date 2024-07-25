import { rest } from 'msw';
import { SWITCH_MERCHANT_MOCKS } from 'apps/pos/src/services/mocks/fixtures/switchMerchant';

export const switchMerchantHandler = (type: string) => {
  if (type === 'success') {
    return rest.get('*/settings/merchants/switch*', (req, res, ctx) => {
      return res(ctx.status(200), ctx.json(SWITCH_MERCHANT_MOCKS.success));
    });
  }
  return rest.get('*/settings/merchants/switch*', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(SWITCH_MERCHANT_MOCKS.failure));
  });
};
