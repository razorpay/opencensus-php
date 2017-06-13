import VirtualAccount from 'merchant/models/VirtualAccount';
import { makeCollectionReducer, fetchAll } from 'rzp/modules/collection';

export const VIRTUAL_ACCOUNT_CREATE = 'VIRTUAL_ACCOUNTS_CREATE';
export const VIRTUAL_ACCOUNT_EDIT = 'VIRTUAL_ACCOUNT_EDIT';

export const fetchVirtualAccounts = params =>
  fetchAll(params, VirtualAccount, 'VIRTUAL_ACCOUNTS');

export const saveVirtualAccount = params => {
  const virtualAccount = new VirtualAccount(params);
  return {
    type: virtualAccount.isNew ? VIRTUAL_ACCOUNT_CREATE : VIRTUAL_ACCOUNT_EDIT,
    payload: virtualAccount.save(),
  };
};
export default makeCollectionReducer('VIRTUAL_ACCOUNTS');
