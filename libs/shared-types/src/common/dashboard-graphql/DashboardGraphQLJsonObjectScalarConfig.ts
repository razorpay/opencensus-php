import { DashboardGraphQLResolversTypes, GraphQLScalarTypeConfig } from './index';
export interface DashboardGraphQLJsonObjectScalarConfig
  extends GraphQLScalarTypeConfig<DashboardGraphQLResolversTypes['JSONObject'], any> {
  name: 'JSONObject';
}