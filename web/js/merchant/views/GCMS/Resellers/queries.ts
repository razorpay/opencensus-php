import errorService from '@razorpay/universe-utils/errorService';

import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { ModeT } from 'common/services/mode';
import { fetch } from 'common/services/rest/rest-fetch';
import { stringifyQueryParams } from 'common/utils/rzp-utils';
import { Program } from 'merchant/views/GCMS/Programs/types';
import { Reseller, ResellerBalance } from 'merchant/views/GCMS/Resellers/types';
import { getGCMSBasePath, RESELLERS_STATUS } from 'merchant/views/GCMS/shared/constants';
import { ListApiResponse } from 'merchant/views/GCMS/shared/types';

export const LIST_FETCH_BATCH_SIZE = 25;

export const fetchProgramsForReseller = async ({
  skip = 0,
  resellerId,
  mode,
}: {
  skip: number;
  resellerId?: string;
  mode?: ModeT;
}): Promise<ListApiResponse<Program>> => {
  try {
    const response = await fetch<ListApiResponse<Program>>({
      url: `${getGCMSBasePath(mode)}/skus${stringifyQueryParams({
        skip,
        count: LIST_FETCH_BATCH_SIZE,
        reseller_id: resellerId,
      })}`,
      mode,
    });
    return response;
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

export const fetchResellers = async ({
  mode = 'test',
  skip = 0,
  resellerName,
  resellerStatus,
  merchantId,
}: {
  mode?: ModeT;
  skip?: number;
  resellerName?: string;
  resellerStatus?: string;
  merchantId?: string;
}): Promise<ListApiResponse<Reseller>> => {
  try {
    const res = await fetch<ListApiResponse<Reseller>>({
      url: `${getGCMSBasePath(mode)}/merchants/${merchantId}/resellers${stringifyQueryParams({
        skip,
        count: LIST_FETCH_BATCH_SIZE,
        merchant_name: resellerName ?? '',
        status:
          !resellerStatus || resellerStatus === RESELLERS_STATUS.all.value ? '' : resellerStatus,
      })}`,
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

export const fetchResellerBalance = async ({
  mode,
  resellerId,
  merchantId,
}: {
  mode: ModeT;
  resellerId?: string;
  merchantId: string;
}): Promise<ResellerBalance> => {
  try {
    const response = await fetch<ResellerBalance>({
      url: `${getGCMSBasePath(mode)}/merchants/${merchantId}/resellers/${resellerId}/balances`,
    });
    return response;
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
