import { DashboardGraphQLSubscriptionSubscribeFn, DashboardGraphQLSubscriptionResolveFn } from './index';
export interface DashboardGraphQLSubscriptionSubscriberObject<
  TResult,
  TKey extends string,
  TParent,
  TContext,
  TArgs,
> {
  subscribe: DashboardGraphQLSubscriptionSubscribeFn<{ [key in TKey]: TResult }, TParent, TContext, TArgs>;
  resolve?: DashboardGraphQLSubscriptionResolveFn<TResult, { [key in TKey]: TResult }, TContext, TArgs>;
}