import { DashboardGraphQLResolversTypes, GraphQLScalarTypeConfig } from './index';
export interface DashboardGraphQLNonNegativeIntScalarConfig
  extends GraphQLScalarTypeConfig<DashboardGraphQLResolversTypes['NonNegativeInt'], any> {
  name: 'NonNegativeInt';
}