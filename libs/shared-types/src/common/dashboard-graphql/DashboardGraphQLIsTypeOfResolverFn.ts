import { GraphQLResolveInfo } from "graphql";

export type DashboardGraphQLIsTypeOfResolverFn<T = {}, TContext = {}> = (
  obj: T,
  context: TContext,
  info: GraphQLResolveInfo,
) => boolean | Promise<boolean>;