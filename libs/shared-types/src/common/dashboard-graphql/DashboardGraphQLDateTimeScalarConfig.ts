import { DashboardGraphQLResolversTypes, GraphQLScalarTypeConfig } from './index';
export interface DashboardGraphQLDateTimeScalarConfig
  extends GraphQLScalarTypeConfig<DashboardGraphQLResolversTypes['DateTime'], any> {
  name: 'DateTime';
}