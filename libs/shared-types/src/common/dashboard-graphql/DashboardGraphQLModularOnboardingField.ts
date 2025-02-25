import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLModularOnboardingFieldMeta } from './index';
export type DashboardGraphQLModularOnboardingField = {
  failureReason?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  failureReasonType?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  isDisabled: DashboardGraphQLScalars['Boolean'];
  isHidden?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  isRequired: DashboardGraphQLScalars['Boolean'];
  meta?: DashboardGraphQLMaybe<DashboardGraphQLModularOnboardingFieldMeta>;
  name: DashboardGraphQLScalars['String'];
};