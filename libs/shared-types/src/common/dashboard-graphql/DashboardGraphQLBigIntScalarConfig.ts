import { DashboardGraphQLResolversTypes, GraphQLScalarTypeConfig } from './index';
export interface DashboardGraphQLBigIntScalarConfig extends GraphQLScalarTypeConfig<DashboardGraphQLResolversTypes['BigInt'], any> {
  name: 'BigInt';
}