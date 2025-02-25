import { DashboardGraphQLMaybe, DashboardGraphQLAcquirerData, DashboardGraphQLMoney, DashboardGraphQLScalars, DashboardGraphQLPaymentRefundSpeed, DashboardGraphQLPaymentRefundStatusEnum } from './index';
export type DashboardGraphQLPaymentRefund = {
  __typename?: 'DashboardGraphQLPaymentRefund';
  acquirerData?: DashboardGraphQLMaybe<DashboardGraphQLAcquirerData>;
  amount: DashboardGraphQLMoney;
  batchId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  createdAt: DashboardGraphQLScalars['DateTime'];
  entity: DashboardGraphQLScalars['String'];
  id: DashboardGraphQLScalars['ID'];
  notes?: DashboardGraphQLMaybe<DashboardGraphQLScalars['JSONObject']>;
  paymentId: DashboardGraphQLScalars['ID'];
  processedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  speed?: DashboardGraphQLMaybe<DashboardGraphQLPaymentRefundSpeed>;
  status: DashboardGraphQLPaymentRefundStatusEnum;
};