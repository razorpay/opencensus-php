import VirtualAccount from 'merchant/models/VirtualAccount';
import { makeCollectionReducer, fetchAll } from 'rzp/modules/collection';
import makeEntityReducer from 'rzp/modules/entity';

export const VIRTUAL_ACCOUNT_CREATE = 'VIRTUAL_ACCOUNT_CREATE';
export const VIRTUAL_ACCOUNT_EDIT = 'VIRTUAL_ACCOUNT_EDIT';
export const VIRTUAL_ACCOUNT_DELETE = 'VIRTUAL_ACCOUNT_DELETE';
export const VIRTUAL_ACCOUNT_FETCH = 'VIRTUAL_ACCOUNT_FETCH';

export const fetchVirtualAccounts = params =>
  fetchAll(params, VirtualAccount, 'VIRTUAL_ACCOUNTS');

export const fetchItem = id => {
  let virtualAccount = new VirtualAccount();
  return {
    type: VIRTUAL_ACCOUNT_FETCH,
    payload: virtualAccount.fetch(id),
  };
};

export const saveVirtualAccount = params => {
  const virtualAccount = new VirtualAccount(params);
  return {
    type: virtualAccount.isNew ? VIRTUAL_ACCOUNT_CREATE : VIRTUAL_ACCOUNT_EDIT,
    payload: virtualAccount.save(),
  };
};

export const deleteVirtualAccount = params => {
  const virtualAccount = new VirtualAccount(params);

  return {
    type: VIRTUAL_ACCOUNT_DELETE,
    payload: virtualAccount.delete(),
    id: virtualAccount.id,
  };
};

export const virtualAccountsReducer = makeCollectionReducer('VIRTUAL_ACCOUNTS');
export const virtualAccountReducer = makeEntityReducer(VIRTUAL_ACCOUNT_FETCH);
