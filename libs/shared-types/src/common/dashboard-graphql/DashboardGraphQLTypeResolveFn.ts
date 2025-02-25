import { DashboardGraphQLMaybe, GraphQLResolveInfo } from './index';
export type DashboardGraphQLTypeResolveFn<TTypes, TParent = {}, TContext = {}> = (
  parent: TParent,
  context: TContext,
  info: GraphQLResolveInfo,
) => DashboardGraphQLMaybe<TTypes> | Promise<DashboardGraphQLMaybe<TTypes>>;