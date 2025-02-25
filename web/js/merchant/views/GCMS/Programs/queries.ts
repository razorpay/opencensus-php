import errorService from '@razorpay/universe-cli/errorService';

import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { fetch } from '@federated/apps/shell/rest-fetch';
import { stringifyQueryParams } from 'common/utils/rzp-utils';
import { Program, ProgramApiParams, SKU } from 'merchant/views/GCMS/Programs/types';
import { getGCMSBasePath, WALLET_BASE_PATH } from 'merchant/views/GCMS/shared/constants';
import * as types from 'merchant/views/GCMS/shared/types';

export const LIST_FETCH_BATCH_SIZE = 9;

export const fetchPrograms = async ({
  mode = 'test',
  skip = 0,
  count = 25,
}: types.ListApiParams) => {
  try {
    const res = await fetch<types.ListApiResponse<Program>>({
      url: `${WALLET_BASE_PATH}/programs${stringifyQueryParams({
        skip,
        count,
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

export const fetchProgramById = async ({ mode = 'test', programId }: ProgramApiParams) => {
  try {
    const res = await fetch<types.ListApiResponse<Program>>({
      url: `${WALLET_BASE_PATH}/programs${stringifyQueryParams({
        program_id: programId,
      })}`,
      mode,
    });
    return Array.isArray(res.items) && res.items.length > 0 ? res.items[0] : null;
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

export const fetchProgramsByResellerId = async ({
  mode = 'test',
  skip = 0,
  count = 100,
  merchantId,
  resellerId,
}: types.ListApiParams) => {
  try {
    const res = await fetch<types.ListApiResponse<SKU>>({
      url: `${getGCMSBasePath(mode)}/skus${stringifyQueryParams({
        skip,
        count,
        merchant_id: merchantId,
        reseller_id: resellerId,
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
