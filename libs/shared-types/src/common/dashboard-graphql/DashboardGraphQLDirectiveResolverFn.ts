import { DashboardGraphQLNextResolverFn, GraphQLResolveInfo } from './index';
export type DashboardGraphQLDirectiveResolverFn<TResult = {}, TParent = {}, TContext = {}, TArgs = {}> = (
  next: DashboardGraphQLNextResolverFn<TResult>,
  parent: TParent,
  args: TArgs,
  context: TContext,
  info: GraphQLResolveInfo,
) => TResult | Promise<TResult>;