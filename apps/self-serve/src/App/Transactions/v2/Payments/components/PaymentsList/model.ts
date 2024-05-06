import { merchantFetch } from '@dashboard/shared-utils/ajax';
import { createPayloadForSavePreferences } from './utils';

export const saveMerchantColumnPreferences = (selectedColumnsListData): Promise<any> => {
  const payload = { data: createPayloadForSavePreferences(selectedColumnsListData) };
  return merchantFetch({
    method: 'post',
    url: `merchants/payments/saved_columns`,
    data: payload,
  });
};

export const fetchMerchantColumnPreferences = (): Promise<any> => {
  return merchantFetch({
    method: 'get',
    url: `merchants/payments/saved_columns`,
  });
};

export const fetchPaymentNotesKeys = (): Promise<any> => {
  return merchantFetch({
    method: 'get',
    url: `payments/transaction_tab/notes_keys`,
  });
};
