import { DashboardGraphQLSubscriptionSubscribeFn, DashboardGraphQLSubscriptionResolveFn } from './index';
export interface DashboardGraphQLSubscriptionResolverObject<TResult, TParent, TContext, TArgs> {
  subscribe: DashboardGraphQLSubscriptionSubscribeFn<any, TParent, TContext, TArgs>;
  resolve: DashboardGraphQLSubscriptionResolveFn<TResult, any, TContext, TArgs>;
}