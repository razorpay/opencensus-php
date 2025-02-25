import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantInstrumentStatusEnum } from './index';
export type DashboardGraphQLMerchantInstrumentsStatus = {
  __typename?: 'DashboardGraphQLMerchantInstrumentsStatus';
  comment?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  createdAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  fadeComment?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  instrument: DashboardGraphQLScalars['String'];
  merchantId: DashboardGraphQLScalars['String'];
  requestId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['ID']>;
  showInitiateButton: DashboardGraphQLScalars['Boolean'];
  showSmartDashboardFlow: DashboardGraphQLScalars['Boolean'];
  status: DashboardGraphQLMerchantInstrumentStatusEnum;
  turnAroundTime?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
  updatedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
};