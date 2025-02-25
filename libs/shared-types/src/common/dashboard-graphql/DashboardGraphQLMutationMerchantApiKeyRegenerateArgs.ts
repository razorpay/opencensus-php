import { DashboardGraphQLApiKeyRegenerationDelayTypeEnum, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationMerchantApiKeyRegenerateArgs = {
  apiKeyRegenerationDelayType: DashboardGraphQLApiKeyRegenerationDelayTypeEnum;
  oldApiKey: DashboardGraphQLScalars['String'];
};