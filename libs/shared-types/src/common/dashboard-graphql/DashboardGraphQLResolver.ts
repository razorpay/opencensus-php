import { DashboardGraphQLResolverFn, DashboardGraphQLResolverWithResolve } from './index';
export type DashboardGraphQLResolver<TResult, TParent = {}, TContext = {}, TArgs = {}> =
  | DashboardGraphQLResolverFn<TResult, TParent, TContext, TArgs>
  | DashboardGraphQLResolverWithResolve<TResult, TParent, TContext, TArgs>;