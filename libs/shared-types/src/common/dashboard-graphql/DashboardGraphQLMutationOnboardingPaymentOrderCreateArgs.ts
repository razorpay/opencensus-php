import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMutationOnboardingPaymentOrderCreateArgs = {
  createOrder: DashboardGraphQLScalars['Boolean'];
  paymentType?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};