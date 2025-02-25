import { DashboardGraphQLSubscriptionObject } from './index';
export type DashboardGraphQLSubscriptionResolver<
  TResult,
  TKey extends string,
  TParent = {},
  TContext = {},
  TArgs = {},
> =
  | ((...args: any[]) => DashboardGraphQLSubscriptionObject<TResult, TKey, TParent, TContext, TArgs>)
  | DashboardGraphQLSubscriptionObject<TResult, TKey, TParent, TContext, TArgs>;