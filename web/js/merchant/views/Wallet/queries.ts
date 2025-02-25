import errorService from '@razorpay/universe-cli/errorService';
import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { fetch } from '@federated/apps/shell/rest-fetch';
import { stringifyQueryParams, decodeSensitiveFields } from 'common/utils/rzp-utils';

import { WALLET_BASE_PATH } from 'merchant/views/Wallet/constants';

import type * as types from 'merchant/views/Wallet/types';
import { getAppliedFilters } from './utils';

export const fetchAccounts = async ({
  mode = 'test',
  skip = 0,
  count = 25,
  ...filters
}: types.AccountListApiParams): Promise<types.ListApiResponse<types.Account>> => {
  filters = decodeSensitiveFields(filters);

  try {
    const res = await fetch<types.ListApiResponse<types.Account>>({
      url: `${WALLET_BASE_PATH}/accounts${stringifyQueryParams({
        ...filters,
        skip,
        count,
      })}`,
      mode,
    });
    res.items = res.items.map((item) => ({
      ...item,
      balance: parseInt(String(item.balance), 10),
      created_at: parseInt(String(item.created_at), 10),
    }));
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const fetchTransactions = async ({
  mode = 'test',
  skip = 0,
  count = 25,
  ...filters
}: types.TransactionListApiParams): Promise<
  types.DashboardListApiResponse<{ transactions: types.Transaction[] }>
> => {
  try {
    const res = await fetch<types.DashboardListApiResponse<{ transactions: types.Transaction[] }>>({
      url: `${WALLET_BASE_PATH}/dashboard/transactions`,
      mode,
      method: 'POST',
      data: getAppliedFilters({ filters, skip, count }),
    });
    res.entities.transactions = res.entities.transactions.map((item) => ({
      ...item,
      amount: parseInt(String(item.amount), 10),
      credit: parseInt(String(item.credit), 10),
      debit: parseInt(String(item.debit), 10),
      created_at: parseInt(String(item.created_at), 10),
    }));
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const fetchPayments = async ({
  mode = 'test',
  skip = 0,
  count = 25,
  ...filters
}: types.ListApiParams): Promise<
  types.DashboardListApiResponse<{ payments: types.WalletPayment[] }>
> => {
  try {
    const url = `${WALLET_BASE_PATH}/dashboard/payments`;
    const res = await fetch<types.DashboardListApiResponse<{ payments: types.WalletPayment[] }>>({
      url,
      mode,
      method: 'POST',
      data: getAppliedFilters({ filters, skip, count }),
    });
    res.entities.payments = res.entities.payments.map((item) => ({
      ...item,
      amount: parseInt(String(item.amount), 10),
      created_at: parseInt(String(item.created_at), 10),
    }));
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const fetchLoads = async ({
  mode = 'test',
  skip = 0,
  count = 25,
  ...filters
}: types.ListApiParams): Promise<
  types.DashboardListApiResponse<{ recharges: types.WalletLoad[] }>
> => {
  try {
    const url = `${WALLET_BASE_PATH}/dashboard/loads`;
    const res = await fetch<types.DashboardListApiResponse<{ recharges: types.WalletLoad[] }>>({
      url,
      mode,
      method: 'POST',
      data: getAppliedFilters({ filters, skip, count }),
    });
    res.entities.recharges = res.entities.recharges.map((item) => ({
      ...item,
      amount: parseInt(String(item.amount), 10),
      created_at: parseInt(String(item.created_at), 10),
    }));
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const fetchAccountById = async ({
  id,
  mode = 'test',
}: types.DetailApiParams): Promise<types.Account> => {
  try {
    if (!id) {
      throw new Error('No account id');
    }
    const res = await fetch<types.ListApiResponse<types.Account>>({
      url: `${WALLET_BASE_PATH}/accounts?issuing_account_id=${id}`,
      mode,
    });

    const account = res.items?.map((item) => ({
      ...item,
      created_at: parseInt(String(item.created_at), 10),
      balance: parseInt(String(item.balance), 10),
    }));
    return account[0];
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const fetchAccountBalance = async ({
  id,
  mode = 'test',
}: types.DetailApiParams): Promise<types.AccountBalance> => {
  try {
    const res = await fetch<types.AccountBalance>({
      url: `${WALLET_BASE_PATH}/${id}/balance`,
      mode,
    });

    res.available_balance = parseInt(String(res.available_balance), 10);
    res.limits = {
      monthly_load_limit: parseInt(String(res.limits.monthly_load_limit), 10),
      monthly_load_limit_used: parseInt(String(res.limits.monthly_load_limit_used), 10),
      monthly_load_limit_balance: parseInt(String(res.limits.monthly_load_limit_balance), 10),
      yearly_load_limit: parseInt(String(res.limits.yearly_load_limit), 10),
      yearly_load_limit_used: parseInt(String(res.limits.yearly_load_limit_used), 10),
      yearly_load_limit_balance: parseInt(String(res.limits.yearly_load_limit_balance), 10),
    };
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};
