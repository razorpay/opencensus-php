import { DashboardGraphQLCurrencyCodeEnum, DashboardGraphQLMaybe, DashboardGraphQLCurrencyNameEnum } from './index';
export type DashboardGraphQLCurrency = {
  __typename?: 'DashboardGraphQLCurrency';
  code: DashboardGraphQLCurrencyCodeEnum;
  name?: DashboardGraphQLMaybe<DashboardGraphQLCurrencyNameEnum>;
};