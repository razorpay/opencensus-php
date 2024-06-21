import { merchantFetch } from 'merchant/utils/ajax';

import { TABLE_PAGE_SIZE } from './constants';
import { RewardsHistoryAPIResponse } from './types';

export const fetchRewardsHistory = async (page: number) => {
  try {
    const response = await merchantFetch({
      method: 'GET',
      url: `payments_cross_border_live/v1/reward-history?page=${page}&limit=${TABLE_PAGE_SIZE}`,
      mode: 'live',
    });

    if (response?.success) {
      const { data } = response;

      return {
        tableData: { nodes: data || [] },
        totalCount: data?.totalCount || 0,
      } as RewardsHistoryAPIResponse;
    }
    throw new Error('Rewards history fetch was unsuccessful. Please try again.');
  } catch {
    throw new Error('Rewards history fetch failed! Please try again');
  }
};
