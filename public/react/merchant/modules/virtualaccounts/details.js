import VirtualAccount from 'merchant/models/VirtualAccount';
import makeEntityReducer from 'rzp/modules/entity';

export const VIRTUAL_ACCOUNT_FETCH = 'VIRTUAL_ACCOUNT_FETCH';

export const fetchItem = id => {
  let virtualAccount = new VirtualAccount();
  return {
    type: VIRTUAL_ACCOUNT_FETCH,
    payload: virtualAccount.fetch(id),
  };
};

export default makeEntityReducer(VIRTUAL_ACCOUNT_FETCH);
