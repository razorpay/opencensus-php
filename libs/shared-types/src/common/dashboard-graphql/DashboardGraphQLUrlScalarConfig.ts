import { DashboardGraphQLResolversTypes, GraphQLScalarTypeConfig } from './index';
export interface DashboardGraphQLUrlScalarConfig extends GraphQLScalarTypeConfig<DashboardGraphQLResolversTypes['URL'], any> {
  name: 'URL';
}