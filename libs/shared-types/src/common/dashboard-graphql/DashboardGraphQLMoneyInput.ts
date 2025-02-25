import { DashboardGraphQLCurrencyInput, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMoneyInput = {
  currency: DashboardGraphQLCurrencyInput;
  value: DashboardGraphQLScalars['PositiveInt'];
};