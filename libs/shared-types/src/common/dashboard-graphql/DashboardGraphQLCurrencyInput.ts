import { DashboardGraphQLCurrencyCodeEnum, DashboardGraphQLInputMaybe, DashboardGraphQLCurrencyNameEnum } from './index';
export type DashboardGraphQLCurrencyInput = {
  code: DashboardGraphQLCurrencyCodeEnum;
  name?: DashboardGraphQLInputMaybe<DashboardGraphQLCurrencyNameEnum>;
};