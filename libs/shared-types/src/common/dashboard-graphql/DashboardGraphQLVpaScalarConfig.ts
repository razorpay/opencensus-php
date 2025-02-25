import { DashboardGraphQLResolversTypes, GraphQLScalarTypeConfig } from './index';
export interface DashboardGraphQLVpaScalarConfig extends GraphQLScalarTypeConfig<DashboardGraphQLResolversTypes['VPA'], any> {
  name: 'VPA';
}