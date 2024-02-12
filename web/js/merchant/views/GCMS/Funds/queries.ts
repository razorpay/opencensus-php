import errorService from '@razorpay/universe-utils/errorService';

import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { fetch } from 'common/services/rest/rest-fetch';
import { stringifyQueryParams } from 'common/utils/rzp-utils';
import * as types from 'merchant/views/GCMS/Funds/types';
import { getGCMSBasePath, WALLET_BASE_PATH } from 'merchant/views/GCMS/shared/constants';
import { Transaction } from 'merchant/views/Wallet/types';

import { BrandBalance, TransactionListApiParams, ResellersBalance } from './types';

import type { ModeT } from 'common/services/mode';

export const LIST_FETCH_BATCH_SIZE = 25;

export const fetchBrandTransactions = async ({
  mode = 'test',
  skip = 0,
  count = 25,
  issuing_account_id,
  ...filters
}: TransactionListApiParams): Promise<types.ListApiResponse<Transaction>> => {
  try {
    const url = `${WALLET_BASE_PATH}/transactions${stringifyQueryParams({
      ...filters,
      skip,
      count,
      issuing_account_id,
    })}`;
    const res = await fetch<types.ListApiResponse<Transaction>>({
      url,
      mode,
    });
    res.items = res.items.map((item) => ({
      ...item,
      created_at: parseInt(String(item.created_at), 10),
      credit: parseInt(String(item.credit), 10),
      debit: parseInt(String(item.debit), 10),
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

export const fetchBrandBalance = async ({
  mode = 'test',
  merchantId,
}: {
  mode: ModeT;
  merchantId: string;
}): Promise<BrandBalance> => {
  try {
    const res = await fetch<BrandBalance>({
      url: `${getGCMSBasePath(mode)}/merchants/${merchantId}/balances`,
      mode,
    });
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

export const fetchResellersBalances = async ({
  mode = 'test',
  merchantId,
  skip = 0,
  count = LIST_FETCH_BATCH_SIZE,
  ...filters
}: types.ListApiParams): Promise<types.ListApiResponse<ResellersBalance>> => {
  try {
    const url = `${getGCMSBasePath(
      mode,
    )}/merchants/${merchantId}/resellers/balances${stringifyQueryParams({
      skip,
      count,
      ...filters,
    })}`;

    const res = await fetch<types.ListApiResponse<ResellersBalance>>({
      url,
      mode,
    });

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
