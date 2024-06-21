import type { TableData } from '@razorpay/blade/components';

export interface RewardsHistoryItem {
  id: string;
  start_date: number;
  end_date: number;
  rewards_type: string;
  rewards_earned: number;
  milestone_details: {
    current_gmv: number;
    milestone: number;
  };
  disbursal_date: number;
  rewards_disbursal_status: string;
}

export type RewardsHistoryAPIResponse = {
  tableData: TableData<RewardsHistoryItem>;
  totalCount: number;
};
