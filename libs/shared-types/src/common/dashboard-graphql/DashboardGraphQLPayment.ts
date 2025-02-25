import { DashboardGraphQLPaymentAmount, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLCustomer, DashboardGraphQLPaymentError, DashboardGraphQLFeeBearerEnum, DashboardGraphQLPaymentMethod, DashboardGraphQLPaymentRefund, DashboardGraphQLPaymentStatusEnum } from './index';
export type DashboardGraphQLPayment = {
  __typename?: 'DashboardGraphQLPayment';
  amount: DashboardGraphQLPaymentAmount;
  createdAt: DashboardGraphQLScalars['DateTime'];
  customer?: DashboardGraphQLMaybe<DashboardGraphQLCustomer>;
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  error?: DashboardGraphQLMaybe<DashboardGraphQLPaymentError>;
  feeBearer?: DashboardGraphQLMaybe<DashboardGraphQLFeeBearerEnum>;
  id: DashboardGraphQLScalars['ID'];
  isCaptured: DashboardGraphQLScalars['Boolean'];
  isInternational: DashboardGraphQLScalars['Boolean'];
  method?: DashboardGraphQLMaybe<DashboardGraphQLPaymentMethod>;
  notes?: DashboardGraphQLMaybe<DashboardGraphQLScalars['JSONObject']>;
  refunds?: DashboardGraphQLMaybe<Array<DashboardGraphQLPaymentRefund>>;
  status: DashboardGraphQLPaymentStatusEnum;
};