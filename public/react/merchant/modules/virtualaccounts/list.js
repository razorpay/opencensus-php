import VirtualAccount from 'merchant/models/VirtualAccount';
import { makeCollectionReducer, fetchAll } from 'rzp/modules/collection';

export const VIRTUAL_ACCOUNTS_CREATE = 'VIRTUAL_ACCOUNTS_CREATE';

export const fetchVirtualAccounts = params =>
  fetchAll(params, VirtualAccount, 'VIRTUAL_ACCOUNTS');

export const saveVirtualAccount = params => {
  const virtualAccount = new VirtualAccount(params);
  return {
    type: VIRTUAL_ACCOUNTS_CREATE,
    payload: virtualAccount.save(),
  };
};
export default makeCollectionReducer('VIRTUAL_ACCOUNTS');
