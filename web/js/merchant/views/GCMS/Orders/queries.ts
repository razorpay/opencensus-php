import errorService from '@razorpay/universe-utils/errorService';
import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { ModeT } from 'common/services/mode';
import { fetch } from 'common/services/rest/rest-fetch';
import { stringifyQueryParams } from 'common/utils/rzp-utils';
import { ListApiResponse } from 'merchant/views/GCMS/Programs/types';
import { ORDERS_STATUS } from 'merchant/views/GCMS/shared/constants';
import { Order } from './types';

export const LIST_FETCH_BATCH_SIZE = 5;

export const fetchOrders = async ({
  mode = 'test',
  skip = 0,
  resellerName,
  orderStatus,
  fromDate,
  toDate,
}: {
  mode?: ModeT;
  skip?: number;
  resellerName?: string;
  orderStatus?: string;
  fromDate?: number;
  toDate?: number;
}) => {
  try {
    const res = await fetch<ListApiResponse<Order>>({
      url: `gcoms/orders${stringifyQueryParams({
        skip,
        count: LIST_FETCH_BATCH_SIZE,
        reseller_name: resellerName ?? '',
        status: !orderStatus || orderStatus === ORDERS_STATUS.all.value ? '' : orderStatus,
        from: !fromDate ? '' : fromDate,
        to: !toDate ? '' : toDate,
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
