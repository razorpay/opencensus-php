import errorService from '@razorpay/universe-utils/errorService';

import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { fetch } from 'common/services/rest/rest-fetch';
import { stringifyQueryParams } from 'common/utils/rzp-utils';
import * as types from 'merchant/views/GCMS/Programs/types';
import { WALLET_BASE_PATH } from 'merchant/views/GCMS/shared/constants';

export const fetchPrograms = async ({
  mode = 'test',
  skip = 0,
  count = 25,
}: types.ListApiParams) => {
  try {
    const res = await fetch<types.ListApiResponse<types.Program>>({
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

export const fetchProgramById = async ({ mode = 'test', programId }: types.ProgramApiParams) => {
  try {
    const res = await fetch<types.ListApiResponse<types.Program>>({
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
