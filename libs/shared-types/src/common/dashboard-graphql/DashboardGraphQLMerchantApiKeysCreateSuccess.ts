import { DashboardGraphQLMerchantApiKeyInterface, DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantApiKeysCreateSuccess = DashboardGraphQLMerchantApiKeyInterface &
  DashboardGraphQLMutationResponseInterface & {
    __typename?: 'DashboardGraphQLMerchantApiKeysCreateSuccess';
    code: DashboardGraphQLScalars['PositiveInt'];
    createdAt: DashboardGraphQLScalars['DateTime'];
    expiredAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
    id: DashboardGraphQLScalars['String'];
    message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
    secret: DashboardGraphQLScalars['String'];
    success: DashboardGraphQLScalars['Boolean'];
    updatedAt: DashboardGraphQLScalars['DateTime'];
  };