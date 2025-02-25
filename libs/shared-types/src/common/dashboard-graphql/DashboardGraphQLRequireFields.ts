import { DashboardGraphQLOmit } from './index';
export type DashboardGraphQLRequireFields<T, K extends keyof T> = DashboardGraphQLOmit<T, K> & { [P in K]-?: NonNullable<T[P]> };