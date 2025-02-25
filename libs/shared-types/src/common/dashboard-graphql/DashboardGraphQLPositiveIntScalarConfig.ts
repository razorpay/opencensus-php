import { DashboardGraphQLResolversTypes, GraphQLScalarTypeConfig } from './index';
export interface DashboardGraphQLPositiveIntScalarConfig
  extends GraphQLScalarTypeConfig<DashboardGraphQLResolversTypes['PositiveInt'], any> {
  name: 'PositiveInt';
}