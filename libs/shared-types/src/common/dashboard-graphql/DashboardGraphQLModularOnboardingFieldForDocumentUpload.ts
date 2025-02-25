import { DashboardGraphQLModularOnboardingField, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLModularOnboardingFieldMeta, DashboardGraphQLDocumentUploadFieldValue } from './index';
export type DashboardGraphQLModularOnboardingFieldForDocumentUpload = DashboardGraphQLModularOnboardingField & {
  __typename?: 'DashboardGraphQLModularOnboardingFieldForDocumentUpload';
  failureReason?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  failureReasonType?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  isDisabled: DashboardGraphQLScalars['Boolean'];
  isHidden?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  isRequired: DashboardGraphQLScalars['Boolean'];
  meta?: DashboardGraphQLMaybe<DashboardGraphQLModularOnboardingFieldMeta>;
  name: DashboardGraphQLScalars['String'];
  value: DashboardGraphQLDocumentUploadFieldValue;
};