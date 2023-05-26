import { fetch } from 'common/services/rest/rest-fetch';

import { WALLET_BASE_PATH } from 'merchant/views/Wallet/constants';

import { FundsSummary, Transaction } from 'merchant/views/Wallet/Funds/types';

import type { ModeT } from 'common/services/mode';
import * as types from 'merchant/views/Wallet/types';

export const fetchFundTransactions = async ({
  skip,
  count,
  mode = 'test',
}: types.ListApiParams): Promise<types.ListApiResponse<Transaction>> => {
  try {
    const res = await fetch<types.ListApiResponse<Transaction>>({
      url: `${WALLET_BASE_PATH}/transactions?type=pool_transaction&skip=${skip}&count=${count}`,
      mode,
    });
    // in runtime, backend will return integers as string. this is due to protobuf conversion log for int64 values.
    // the following is temporary code to eventually convert both integers and strings to numbers.
    res.items = res.items.map((item) => ({
      ...item,
      created_at: parseInt(String(item.created_at), 10),
      credit: parseInt(String(item.credit), 10),
      debit: parseInt(String(item.debit), 10),
    }));
    return res;
  } catch (e) {
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const fetchFundsSummary = async ({
  mode,
  merchantId,
}: {
  mode: ModeT;
  merchantId: string;
}): Promise<FundsSummary> => {
  try {
    const res = await fetch<FundsSummary>({
      url: `${WALLET_BASE_PATH}/ipart_${merchantId}/balance`,
      mode,
    });
    // in runtime, backend will return integers as string. this is due to protobuf conversion log for int64 values.
    // the following is temporary code to eventually convert both integers and strings to numbers.
    res.available_balance = parseInt(String(res.available_balance), 10);
    res.limits = res.limits.map((items) => ({
      ...items,
      monthly_load_limit: parseInt(String(items.monthly_load_limit), 10),
      monthly_load_limit_used: parseInt(String(items.monthly_load_limit_used), 10),
      monthly_load_limit_balance: parseInt(String(items.monthly_load_limit_balance), 10),
      yearly_load_limit: parseInt(String(items.yearly_load_limit), 10),
      yearly_load_limit_used: parseInt(String(items.yearly_load_limit_used), 10),
      yearly_load_limit_balance: parseInt(String(items.yearly_load_limit_balance), 10),
    }));
    return res;
  } catch (e) {
    throw new Error(e?.response?.errors?.[0]);
  }
};
