import errorService from '@razorpay/universe-cli/errorService';

import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { ModeT } from 'common/services/mode';
import { fetchDuplicate } from '../shared/utils';
import { fetch } from '@federated/apps/shell/rest-fetch';
import { stringifyQueryParams } from 'common/utils/rzp-utils';
import { Program } from 'merchant/views/GCMS/Programs/types';
import {
  InviteResellerParams,
  Reseller,
  ResellerBalance,
  ResellerDetails,
} from 'merchant/views/GCMS/Resellers/types';
import { getGCMSBasePath, RESELLERS_STATUS } from 'merchant/views/GCMS/shared/constants';
import { ListApiResponse } from 'merchant/views/GCMS/shared/types';

export const LIST_FETCH_BATCH_SIZE = 25;

export const fetchProgramsForReseller = async ({
  skip = 0,
  resellerId,
  mode,
  count = LIST_FETCH_BATCH_SIZE,
}: {
  skip?: number;
  resellerId?: string;
  mode?: ModeT;
  count?: number;
}): Promise<ListApiResponse<Program>> => {
  try {
    const response = await fetch<ListApiResponse<Program>>({
      url: `${getGCMSBasePath(mode)}/skus${stringifyQueryParams({
        skip,
        count,
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
  count,
}: {
  mode?: ModeT;
  skip?: number;
  resellerName?: string;
  resellerStatus?: string;
  merchantId?: string;
  count: number;
}): Promise<ListApiResponse<Reseller>> => {
  try {
    const res = await fetchDuplicate<ListApiResponse<Reseller>>({
      url: `${getGCMSBasePath(mode)}/merchants/${merchantId}/resellers${stringifyQueryParams({
        skip,
        count,
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

export const fetchUnmappedResellers = async ({
  mode = 'test',
  skip = 0,
  resellerName,
  merchantId,
  programId,
  count,
}: {
  mode?: ModeT;
  skip?: number;
  resellerName?: string;
  merchantId?: string;
  programId: string;
  count: number;
}): Promise<ListApiResponse<Reseller>> => {
  try {
    const res = await fetchDuplicate<ListApiResponse<Reseller>>({
      url: `${getGCMSBasePath(mode)}/merchants/resellers/unmapped_to_program${stringifyQueryParams({
        skip,
        count,
        merchant_name: resellerName ?? '',
        merchant_id: merchantId,
        program_id: programId,
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

export const fetchResellerDetails = async ({
  resellerId,
  merchantId,
  mode,
}: {
  resellerId?: string;
  merchantId: string;
  mode: ModeT;
}): Promise<ResellerDetails> => {
  try {
    const response = await fetch<ResellerDetails>({
      url: `${getGCMSBasePath(mode)}/merchants/${merchantId}/resellers/${resellerId}`,
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

export const inviteReseller = async ({
  name,
  email,
  phone,
  mode = 'test',
}: InviteResellerParams) => {
  try {
    await fetchDuplicate({
      url: `${getGCMSBasePath(mode)}/merchants/reseller`,
      method: 'post',
      data: {
        name,
        email,
        phone,
      },
    });
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
