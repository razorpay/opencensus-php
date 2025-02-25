import { DashboardGraphQLSubscriptionSubscriberObject, DashboardGraphQLSubscriptionResolverObject } from './index';
export type DashboardGraphQLSubscriptionObject<TResult, TKey extends string, TParent, TContext, TArgs> =
  | DashboardGraphQLSubscriptionSubscriberObject<TResult, TKey, TParent, TContext, TArgs>
  | DashboardGraphQLSubscriptionResolverObject<TResult, TParent, TContext, TArgs>;