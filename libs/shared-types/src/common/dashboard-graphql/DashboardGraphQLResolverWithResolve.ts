import { DashboardGraphQLResolverFn } from './index';
export type DashboardGraphQLResolverWithResolve<TResult, TParent, TContext, TArgs> = {
  resolve: DashboardGraphQLResolverFn<TResult, TParent, TContext, TArgs>;
};