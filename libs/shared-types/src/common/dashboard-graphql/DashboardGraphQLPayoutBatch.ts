import { DashboardGraphQLMaybe, DashboardGraphQLMerchantBankingAccount, DashboardGraphQLPayoutBatchTypeEnum, DashboardGraphQLUser, DashboardGraphQLPayoutBatchDates, DashboardGraphQLScalars, DashboardGraphQLPayoutBatchPayoutsCount, DashboardGraphQLMoney, DashboardGraphQLPayoutBatchStatusEnum } from './index';
export type DashboardGraphQLPayoutBatch = {
  __typename?: 'DashboardGraphQLPayoutBatch';
  bankingAccount?: DashboardGraphQLMaybe<DashboardGraphQLMerchantBankingAccount>;
  batchType: DashboardGraphQLPayoutBatchTypeEnum;
  createdBy: DashboardGraphQLUser;
  dates: DashboardGraphQLPayoutBatchDates;
  id: DashboardGraphQLScalars['ID'];
  isPendingOnMe?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  name: DashboardGraphQLScalars['String'];
  payoutPurpose?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  payoutsCount?: DashboardGraphQLMaybe<DashboardGraphQLPayoutBatchPayoutsCount>;
  processedAmount: DashboardGraphQLMoney;
  processingBatchId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['ID']>;
  status: DashboardGraphQLPayoutBatchStatusEnum;
  totalAmount: DashboardGraphQLMoney;
  validationBatchId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['ID']>;
};