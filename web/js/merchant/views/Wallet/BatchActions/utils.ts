import { ACCOUNT_TYPES, BATCH_TYPES } from './constants';

export const getSelectedBatchType = (type) => {
  switch (type) {
    case ACCOUNT_TYPES.ACCOUNT:
      return BATCH_TYPES.CREATE_WALLET_ACCOUNTS;

    case ACCOUNT_TYPES.CONTAINER:
      return BATCH_TYPES.CREATE_WALLET_USERS_CONTAINERS;

    case ACCOUNT_TYPES.GIFT_CARDS:
      return BATCH_TYPES.CREATE_BULK_GIFT_CARDS;

    default:
      return '';
  }
};
