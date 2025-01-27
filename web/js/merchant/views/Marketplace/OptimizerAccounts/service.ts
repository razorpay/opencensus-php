import { merchantFetch } from 'merchant/utils/ajax';

import { OptimizerAccount, CreateOptimizerLinkAccountPayload } from './types';

export const fetchOptimizerAccounts = (
  accountId?: string,
  count?: string,
): Promise<{ success: boolean; data: { data: OptimizerAccount[] } | [] }> => {
  let url = 'optimizer/linked_account';
  if (accountId) {
    url += `?id=${accountId}`;
  } else if (count) {
    url += `${url.includes('?') ? '&' : '?'}skip=0&count=${count}`;
  }
  const params = {
    url,
    method: 'get',
  };
  return merchantFetch(params);
};

export const createLinkAccount = (
  payload: CreateOptimizerLinkAccountPayload,
): Promise<{ success: boolean; data: OptimizerAccount }> => {
  const url = 'optimizer/linked_account';
  return merchantFetch({
    url,
    method: 'post',
    data: payload,
  });
};

export const updateLinkAccount = (
  accountId: string,
  payload: Partial<CreateOptimizerLinkAccountPayload>,
): Promise<{ success: boolean; data: OptimizerAccount }> => {
  const url = `optimizer/linked_account/${accountId}`;
  return merchantFetch({
    url,
    method: 'patch',
    data: payload,
  });
};
