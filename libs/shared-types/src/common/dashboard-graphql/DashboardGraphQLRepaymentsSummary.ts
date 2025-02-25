import { DashboardGraphQLMaybe, DashboardGraphQLAmount, DashboardGraphQLRepaymentsBreakup, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLRepaymentsSummary = {
  __typename?: 'DashboardGraphQLRepaymentsSummary';
  current_outstanding?: DashboardGraphQLMaybe<DashboardGraphQLAmount>;
  dpd_amount?: DashboardGraphQLMaybe<DashboardGraphQLAmount>;
  repayment_breakups?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLRepaymentsBreakup>>>;
  repayment_date?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  total_outstanding?: DashboardGraphQLMaybe<DashboardGraphQLAmount>;
};