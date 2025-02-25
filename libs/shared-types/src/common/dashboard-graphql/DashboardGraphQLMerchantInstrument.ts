import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantInstrumentStatusEnum } from './index';
export type DashboardGraphQLMerchantInstrument = {
  __typename?: 'DashboardGraphQLMerchantInstrument';
  comment?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  createdAt: DashboardGraphQLScalars['DateTime'];
  instrument: DashboardGraphQLScalars['String'];
  merchantId: DashboardGraphQLScalars['ID'];
  requestId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['ID']>;
  status: DashboardGraphQLMerchantInstrumentStatusEnum;
  updatedAt: DashboardGraphQLScalars['DateTime'];
};