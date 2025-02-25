import { DashboardGraphQLResolversTypes, GraphQLScalarTypeConfig } from './index';
export interface DashboardGraphQLJsonScalarConfig extends GraphQLScalarTypeConfig<DashboardGraphQLResolversTypes['JSON'], any> {
  name: 'JSON';
}