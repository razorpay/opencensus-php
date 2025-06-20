import errorService from '@razorpay/universe-cli/errorService';

import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { fetch } from '@federated/apps/shell/rest-fetch';
import { fetchDuplicate } from '../shared/utils';
import { stringifyQueryParams } from 'common/utils/rzp-utils';
import { Program, ProgramApiParams, SKU } from 'merchant/views/GCMS/Programs/types';
import {
  GCOMS_WALLET_BASE,
  getGCMSBasePath,
  WALLET_BASE_PATH,
} from 'merchant/views/GCMS/shared/constants';
import * as types from 'merchant/views/GCMS/shared/types';
import { ModeT } from 'common/services/mode';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  transformStateToAPIPayload,
  transformStateToPatchAPIPayload,
} from 'merchant/views/GCMS/Programs/helpers';

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

export const fetchBatchActions = async ({
  mode = 'test',
  skip = 0,
  count = 25,
}: types.ListApiParams) => {
  try {
    const res = merchantFetch({
      url: 'batches',
      params: {},
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

export const fetchProgramImage = async ({ fileId, programId, mode }) => {
  try {
    const res = await merchantFetch({
      url: `ufh/file/${fileId}/get-signed-url`,
      mode,
    });

    return [programId, res.data.signed_url];
  } catch (err) {}
};

export const fetchProgramImages = async ({ mode = 'test', programs }) => {
  try {
    const res = await Promise.all(
      programs.items
        .filter(
          ({ policies: { gift_card_file_storage_id } }) =>
            typeof gift_card_file_storage_id === 'string' &&
            gift_card_file_storage_id.startsWith('file_'),
        )
        .map(({ id, policies: { gift_card_file_storage_id } }) =>
          fetchProgramImage({ fileId: gift_card_file_storage_id, programId: id, mode }),
        ),
    );

    return res.reduce(
      (acc, data) => ({
        ...acc,
        [data[0]]: data[1],
      }),
      {},
    );
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

export const fetchResellersByProgram = async ({
  mode = 'test',
  skip = 0,
  merchantId,
  programId,
  count,
}: {
  mode?: ModeT;
  skip?: number;
  merchantId?: string;
  programId: string;
  count: number;
}): Promise<types.ListApiResponse<types.ProgramResellers>> => {
  try {
    const res = await fetchDuplicate<types.ListApiResponse<types.ProgramResellers>>({
      url: `${getGCMSBasePath(mode)}/merchants/resellers/program${stringifyQueryParams({
        skip,
        count,
        program_id: programId.split('iprog_')[1],
        merchant_id: merchantId,
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
    throw e;
  }
};
export async function addGCFile({ file, merchantId, mode, programId = null, prefix = '' }) {
  try {
    const formData = new FormData();
    const requestData = {
      file,
      type: 'gift_card_image',
      name: `${merchantId}-${Date.now()}`,
      merchant_id: merchantId,
      'entity[id]': merchantId,
      'entity[type]': 'merchant_document',
    };
    if (programId) {
      requestData.name = `${merchantId}-${programId}-${prefix}`;
    }

    Object.keys(requestData).forEach((key) => {
      formData.append(key, requestData[key]);
    });

    const data = await merchantFetch({
      url: 'ufh/files/upload',
      method: 'post',
      mode,
      data: formData,
    });
    return data?.data?.file_id;
  } catch (err) {}
}

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

export const linkResellerToProgram = async ({
  resellerIds,
  status = 'active',
  defaultDiscount = 1,
  programId,
  merchantId,
  mode = 'test',
}: types.LinkResellerToProgramParams) => {
  try {
    await fetchDuplicate({
      url: `${getGCMSBasePath(mode)}/skus/create/bulk`,
      method: 'post',
      data: {
        reseller_id: resellerIds,
        merchant_id: merchantId,
        program_id: programId.split('iprog_')[1],
        default_discount: Math.floor(defaultDiscount * 100),
        status,
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

export const createProgram = async ({
  formData,
  mode,
  merchantId,
}: types.AddProgramDetailsParams) => {
  try {
    const programCreationPayload = transformStateToAPIPayload(formData);
    const data = await fetchDuplicate({
      url: `${GCOMS_WALLET_BASE}/programs`,
      method: 'post',
      mode,
      data: {
        ...programCreationPayload,
        merchant_id: merchantId,
      },
    });
    return data.id;
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

export const patchProgram = async ({
  formData,
  mode,
  merchantId,
  urlUpdate,
  programId,
}: types.AddProgramDetailsParams) => {
  try {
    const programUpdatePayload = transformStateToPatchAPIPayload(formData, urlUpdate);
    await fetch({
      url: `${GCOMS_WALLET_BASE}/programs/${programId.split('iprog_')[1]}`,
      method: 'patch',
      data: {
        ...programUpdatePayload,
        merchant_id: merchantId,
        program_id: programId.split('iprog_')[1],
      },
    });
  } catch (e: any) {
    console.error(e);
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};
