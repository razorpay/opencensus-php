import BMerchant from 'merchant/models/BMerchant';
import { USER_LOGOUT } from './session';
import { set } from 'rzp/utils/immutable';
import { makeEntityReducer, updateEntity } from 'rzp/modules/entity';

export const BMERCHANT_FETCH = 'BMERCHANT_FETCH';
export const BMERCHANT_CREATE = 'BMERCHANT_CREATE';
export const BMERCHANT_EDIT = 'BMERCHANT_EDIT';

const reducer = makeEntityReducer(
  BMERCHANT_FETCH,
  {
    [`${BMERCHANT_FETCH}::SUCCESS`]: (state, action) => {
      return set(state, 'merchantData', action.payload);
    },
  },
  {}
);

export const fetchBMerchant = () => {
  let bMerchant = new BMerchant();
  return {
    type: BMERCHANT_FETCH,
    payload: bMerchant.fetch(),
  };
};

export const saveBMerchant = params => {
  let bMerchant = new BMerchant(params);
  return {
    type: bMerchant.isNew ? BMERCHANT_CREATE : BMERCHANT_EDIT,
    payload: bMerchant.save(),
  };
};

export default reducer;
