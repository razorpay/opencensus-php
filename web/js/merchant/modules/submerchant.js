import Submerchant from 'merchant/models/Submerchant';

const SUB_MERCHANT_CREATE = 'SUB_MERCHANT_CREATE';

export const create = payload => {
  return {
    type: SUB_MERCHANT_CREATE,
    payload: new Submerchant().create(payload),
  };
};
