import { fetch } from 'common/services/rest/rest-fetch';

import type { Account, ListApiParams, ListApiResponse } from 'merchant/views/Wallet/types';

export const fetchAccounts = async ({
  skip,
  count,
  mode = 'test',
}: ListApiParams): Promise<ListApiResponse<Account>> => {
  try {
    const res = await fetch<ListApiResponse<Account>>({
      url: `wallet/accounts?skip=${skip}&count=${count}`,
      mode,
    });
    return res;
  } catch (e) {
    throw new Error(e?.response?.errors?.[0]);
  }
};
