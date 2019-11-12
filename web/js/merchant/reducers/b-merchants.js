import BMerchant from 'merchant/models/BMerchant';
import { set } from 'common/utils/immutable';
import { makeEntityReducer } from 'merchant_common/reducers/entity';

export const BMERCHANT_FETCH = 'BMERCHANT_FETCH';
export const BMERCHANT_CREATE = 'BMERCHANT_CREATE';
export const BMERCHANT_EDIT = 'BMERCHANT_EDIT';

const setEntity = (state, action) => {
  return set(state, 'merchantData', action.payload);
};

const reducer = makeEntityReducer(
  BMERCHANT_FETCH,
  {
    [`${BMERCHANT_CREATE}::SUCCESS`]: setEntity,
    [`${BMERCHANT_EDIT}::SUCCESS`]: setEntity,
    [`${BMERCHANT_FETCH}::SUCCESS`]: setEntity,
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
